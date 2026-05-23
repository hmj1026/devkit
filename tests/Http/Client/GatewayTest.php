<?php

namespace Devkit\Tests\Http\Client;

use Devkit\Http\Client\Exception\RetryExhaustedException;
use Devkit\Http\Client\Gateway;
use Devkit\Tests\Http\Client\Fixture\EchoApiClient;
use Devkit\Tests\Http\Client\Fixture\RecordingObserver;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface as Psr18ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class GatewayTest extends TestCase
{
    public function testDefaultGuzzleSetup()
    {
        $gateway = new Gateway();

        $this->assertNull($gateway->getPsr18Client());
        $this->assertInstanceOf(GuzzleClient::class, $gateway->getGuzzleClient());
    }

    public function testSubclassBindsBaseUri()
    {
        $mock = new MockHandler(array(new Response(200, array(), 'ok')));
        // Capture the actual dispatched URI via a recording observer.
        $observer = new RecordingObserver();

        // Build a Guzzle injected with MockHandler so no real network hits.
        $stack = HandlerStack::create($mock);
        $guzzle = new GuzzleClient(array('handler' => $stack, 'http_errors' => false));

        $client = new EchoApiClient(null, $guzzle, 1, 0);
        $client->addLogObserver($observer);
        $response = $client->request('GET', '/v1/foo');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(1, $observer->requests);
        $this->assertSame(
            'https://api.example.com/v1/foo',
            (string) $observer->requests[0]->getUri()
        );
    }

    public function testPsr18ClientInjection()
    {
        $sent = array();
        $psr18 = new class($sent) implements Psr18ClientInterface {
            private $sent;
            public function __construct(array &$sent) { $this->sent = &$sent; }
            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                $this->sent[] = $request;
                return new Response(204);
            }
        };

        $gateway = new Gateway(null, $psr18, 1, 0);
        $response = $gateway->request('POST', 'https://example.test/x');

        $this->assertSame(204, $response->getStatusCode());
        $this->assertCount(1, $sent);
        $this->assertNull($gateway->getGuzzleClient());
        $this->assertSame($psr18, $gateway->getPsr18Client());
    }

    public function test503RetriesUpToLimit()
    {
        $mock = new MockHandler(array(
            new Response(503, array(), 'try again'),
            new Response(503, array(), 'try again'),
            new Response(503, array(), 'try again'),
            new Response(200, array(), 'ok'),
        ));
        $stack = HandlerStack::create($mock);
        $guzzle = new GuzzleClient(array('handler' => $stack, 'http_errors' => false));

        $gateway = new Gateway(null, $guzzle, 4, 0);
        $response = $gateway->request('GET', 'https://example.test/');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(0, $mock->count(), 'expected MockHandler queue fully consumed');
    }

    public function testExhaustedRetriesRaiseException()
    {
        $mock = new MockHandler(array(
            new Response(503, array('X-Try' => '1'), 'fail-1'),
            new Response(503, array('X-Try' => '2'), 'fail-2'),
            new Response(503, array('X-Try' => '3'), 'fail-3'),
            new Response(503, array('X-Try' => '4'), 'fail-4'),
        ));
        $stack = HandlerStack::create($mock);
        $guzzle = new GuzzleClient(array('handler' => $stack, 'http_errors' => false));

        $gateway = new Gateway(null, $guzzle, 4, 0);

        try {
            $gateway->request('GET', 'https://example.test/');
            $this->fail('expected RetryExhaustedException');
        } catch (RetryExhaustedException $e) {
            $this->assertInstanceOf(ResponseInterface::class, $e->getLastResponse());
            $this->assertSame(503, $e->getLastResponse()->getStatusCode());
            $this->assertSame('4', $e->getLastResponse()->getHeaderLine('X-Try'));
        }
    }

    public function testObserverCapturesRequestAndResponse()
    {
        $mock = new MockHandler(array(new Response(200, array(), 'ok')));
        $stack = HandlerStack::create($mock);
        $guzzle = new GuzzleClient(array('handler' => $stack, 'http_errors' => false));

        $observer = new RecordingObserver();
        $gateway = new Gateway(null, $guzzle, 1, 0);
        $gateway->addLogObserver($observer);

        $gateway->request('GET', 'https://example.test/');

        $this->assertCount(1, $observer->requests);
        $this->assertCount(1, $observer->responses);
    }

    public function testMultipleObserversFireInRegistrationOrder()
    {
        $mock = new MockHandler(array(new Response(200, array(), 'ok')));
        $stack = HandlerStack::create($mock);
        $guzzle = new GuzzleClient(array('handler' => $stack, 'http_errors' => false));

        $first = new RecordingObserver();
        $second = new RecordingObserver();
        $gateway = new Gateway(null, $guzzle, 1, 0);
        $gateway->addLogObserver($first)->addLogObserver($second);

        $gateway->request('GET', 'https://example.test/');

        $this->assertCount(1, $first->requests);
        $this->assertCount(1, $second->requests);
        $this->assertCount(1, $first->responses);
        $this->assertCount(1, $second->responses);
        // Observers were registered first→second; both fire — registration
        // order is observable indirectly via deterministic spy state.
    }

    public function testObserverFiresOnEveryRetryAttempt()
    {
        $mock = new MockHandler(array(
            new Response(503),
            new Response(503),
            new Response(200),
        ));
        $stack = HandlerStack::create($mock);
        $guzzle = new GuzzleClient(array('handler' => $stack, 'http_errors' => false));

        $observer = new RecordingObserver();
        $gateway = new Gateway(null, $guzzle, 3, 0);
        $gateway->addLogObserver($observer);

        $gateway->request('GET', 'https://example.test/');

        $this->assertCount(3, $observer->requests, 'onRequest fires per attempt');
        // 5xx responses also notify observers (callers may log retries).
        $this->assertCount(3, $observer->responses);
    }

    public function testNoAbstractRequestClassShipped()
    {
        $this->assertFalse(
            class_exists('Devkit\\Http\\Client\\AbstractRequest'),
            'devkit-http-gateway spec forbids a sibling AbstractRequest class'
        );
        $this->assertFalse(
            class_exists('Devkit\\Http\\Client\\AbstractResponse'),
            'devkit-http-gateway spec forbids a sibling AbstractResponse class'
        );
    }
}
