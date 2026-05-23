<?php

namespace Devkit\Tests\Laravel\Http;

use Devkit\Laravel\Http\Jobs\AccessLogJob;
use Devkit\Laravel\Http\Jobs\ResponseLogJob;
use Devkit\Laravel\Http\Middleware\AccessLogMiddleware;
use Devkit\Laravel\Http\Support\UserClientIdCookie;
use Devkit\Tests\Laravel\TestCase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use function Devkit\Laravel\Http\Support\getClientTruthIp;
use function Devkit\Laravel\Http\Support\getUserClientIdCookie;

class HttpSupportTest extends TestCase
{
    public function testGetClientTruthIpPrefersForwardedHeader()
    {
        $request = Request::create('/x', 'GET', array(), array(), array(), array(
            'HTTP_X_FORWARDED_FOR' => '203.0.113.10, 10.0.0.1',
            'REMOTE_ADDR' => '127.0.0.1',
        ));

        $this->assertSame('203.0.113.10', getClientTruthIp($request));
    }

    public function testUserClientIdCookieReadsExistingOrGeneratesNewId()
    {
        $request = Request::create('/x', 'GET', array(), array('user_client_id' => 'known-id'));

        $this->assertSame('known-id', getUserClientIdCookie($request));

        $fresh = Request::create('/x');
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/i', getUserClientIdCookie($fresh));
    }

    public function testCookieCanQueueValueOnResponse()
    {
        $cookie = new UserClientIdCookie('abc');
        $response = new Response('ok');

        $cookie->attachTo($response);

        $this->assertCount(1, $response->headers->getCookies());
        $this->assertSame('user_client_id', $response->headers->getCookies()[0]->getName());
    }

    public function testAccessLogMiddlewarePassesRequestThroughAndAddsClientCookie()
    {
        $middleware = new AccessLogMiddleware();
        $request = Request::create('/x');

        $response = $middleware->handle($request, function () {
            return new Response('ok');
        });

        $this->assertSame('ok', $response->getContent());
        $this->assertSame('user_client_id', $response->headers->getCookies()[0]->getName());
    }

    public function testLogJobsExposePayloadToHandler()
    {
        $access = new AccessLogJob(array('path' => '/x'));
        $response = new ResponseLogJob(array('status' => 200));

        $this->assertSame(array('path' => '/x'), $access->payload());
        $this->assertSame(array('status' => 200), $response->payload());
    }
}
