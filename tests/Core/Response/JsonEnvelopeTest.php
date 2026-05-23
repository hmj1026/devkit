<?php

namespace Devkit\Tests\Core\Response;

use Devkit\Core\Response\JsonEnvelope;
use GuzzleHttp\Psr7\HttpFactory;
use PHPUnit\Framework\TestCase;

class JsonEnvelopeTest extends TestCase
{
    /**
     * @return JsonEnvelope
     */
    private function makeEnvelope()
    {
        // GuzzleHttp\Psr7\HttpFactory implements both ResponseFactoryInterface
        // and StreamFactoryInterface — already pulled in transitively via guzzle.
        $factory = new HttpFactory();
        return new JsonEnvelope($factory, $factory);
    }

    public function testSuccessEnvelopeShape()
    {
        $response = $this->makeEnvelope()->success(array('id' => 1));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(
            'application/json; charset=utf-8',
            $response->getHeaderLine('Content-Type')
        );

        $decoded = json_decode((string) $response->getBody(), true);
        $this->assertSame(0, $decoded['code']);
        $this->assertSame('OK', $decoded['message']);
        $this->assertSame(array('id' => 1), $decoded['data']);
    }

    public function testSuccessWithNullDataIsExplicitNull()
    {
        $response = $this->makeEnvelope()->success();

        $decoded = json_decode((string) $response->getBody(), true);
        $this->assertSame(0, $decoded['code']);
        $this->assertSame('OK', $decoded['message']);
        $this->assertNull($decoded['data']);
    }

    public function testSuccessAllowsCustomMessage()
    {
        $response = $this->makeEnvelope()->success(array('x' => 'y'), 'created');

        $decoded = json_decode((string) $response->getBody(), true);
        $this->assertSame('created', $decoded['message']);
    }

    public function testFailureEnvelopeShape()
    {
        $response = $this->makeEnvelope()->failure(
            'invalid input',
            422,
            array('field' => 'email')
        );

        $this->assertSame(422, $response->getStatusCode());

        $decoded = json_decode((string) $response->getBody(), true);
        $this->assertSame(422, $decoded['code']);
        $this->assertSame('invalid input', $decoded['message']);
        $this->assertSame(array('field' => 'email'), $decoded['data']);
    }

    public function testFailureWithoutDataIsExplicitNull()
    {
        $response = $this->makeEnvelope()->failure('server error', 500);

        $decoded = json_decode((string) $response->getBody(), true);
        $this->assertSame(500, $decoded['code']);
        $this->assertSame('server error', $decoded['message']);
        $this->assertNull($decoded['data']);
    }

    public function testUnicodeBodyIsNotEscaped()
    {
        $response = $this->makeEnvelope()->success(array('name' => '台北'));

        $body = (string) $response->getBody();
        $this->assertStringContainsString('台北', $body);
        $this->assertStringNotContainsString('\\u53f0\\u5317', $body);
    }
}
