<?php

namespace Devkit\Tests\Search\Foundation;

use Devkit\Search\Foundation\AwsSignedHandler;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class AwsSignedHandlerTest extends TestCase
{
    public function testConstructionAndAccessors()
    {
        $credentials = new \stdClass();
        $handler = new AwsSignedHandler('ap-northeast-1', $credentials);

        $this->assertSame('ap-northeast-1', $handler->getRegion());
        $this->assertSame($credentials, $handler->getCredentials());
        $this->assertSame($handler, $handler->setService('aoss'));
    }

    public function testInvokeThrowsWhenAwsSdkAbsent()
    {
        $handler = new AwsSignedHandlerWithoutSdk('ap-northeast-1', new \stdClass());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('aws/aws-sdk-php');

        $handler(array(
            'http_method' => 'GET',
            'uri' => '/_search',
            'host' => 'example.es.amazonaws.com',
        ));
    }
}

class AwsSignedHandlerWithoutSdk extends AwsSignedHandler
{
    protected function sdkAvailable()
    {
        return false;
    }
}
