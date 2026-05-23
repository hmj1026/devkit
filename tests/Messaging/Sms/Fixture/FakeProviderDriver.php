<?php

namespace Devkit\Tests\Messaging\Sms\Fixture;

use Devkit\Messaging\Sms\Contract\SmsMessageContract;
use Devkit\Messaging\Sms\Driver\AbstractHttpSmsDriver;
use Devkit\Messaging\Sms\SmsResult;
use Psr\Http\Message\ResponseInterface;

/**
 * Test-only subclass exercising the three AbstractHttpSmsDriver hooks
 * against a fictional provider expecting POST /v1/send with a JSON body
 * `{phone, text, opts}` and returning `{ok: bool, id: string}`.
 */
class FakeProviderDriver extends AbstractHttpSmsDriver
{
    protected $baseUri = 'https://fake-provider.test';

    protected function endpointFor(SmsMessageContract $message)
    {
        return '/v1/send';
    }

    protected function payloadFor(SmsMessageContract $message)
    {
        return array(
            'phone' => $message->cellPhone(),
            'text' => $message->body(),
            'opts' => $message->options(),
        );
    }

    protected function parseResponse(ResponseInterface $response)
    {
        $body = json_decode((string) $response->getBody(), true);
        $body = is_array($body) ? $body : array();

        return new SmsResult(
            isset($body['ok']) ? (bool) $body['ok'] : false,
            isset($body['id']) ? $body['id'] : null,
            (string) $response->getStatusCode(),
            $body
        );
    }
}
