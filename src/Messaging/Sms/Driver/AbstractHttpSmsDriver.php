<?php

namespace Devkit\Messaging\Sms\Driver;

use Devkit\Http\Client\Gateway;
use Devkit\Messaging\Sms\Contract\SmsDriverContract;
use Devkit\Messaging\Sms\Contract\SmsMessageContract;
use Devkit\Messaging\Sms\Contract\SmsResultContract;
use Psr\Http\Message\ResponseInterface;

/**
 * Subclassable base for HTTP-backed SMS provider drivers. Inherits the
 * Gateway machinery (retry decider, exponential backoff, log observers,
 * PSR-18 client injection) so concrete drivers only declare the three
 * provider-specific hooks: endpointFor / payloadFor / parseResponse.
 *
 * Per devkit-sms-dispatch spec, the package ships only this abstract
 * base + NullSmsDriver; consumers author their own concrete driver
 * subclasses (Twilio / AWS SNS / in-house APIs) — none ship in src/.
 *
 * Pure PHP — no Illuminate imports. Production code stays within the package's
 * PHP 7.3 compatibility floor.
 */
abstract class AbstractHttpSmsDriver extends Gateway implements SmsDriverContract
{
    /**
     * Dispatch a single SMS. Assembles a PSR-7 request from the subclass-
     * provided endpoint + payload, sends it through the inherited Gateway
     * (which applies retry / backoff / observers), then hands the response
     * to the subclass for parsing into an SmsResultContract.
     *
     * Retry exhaustion bubbles as RetryExhaustedException — subclasses
     * MAY catch it inside payloadFor / parseResponse if they want to
     * surface a failure SmsResult instead of an exception.
     *
     * @param  SmsMessageContract  $message
     * @return SmsResultContract
     */
    public function sendSms(SmsMessageContract $message)
    {
        $endpoint = $this->endpointFor($message);
        $payload = $this->payloadFor($message);
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $response = $this->request('POST', $endpoint, array(
            'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
            'body' => $body === false ? '{}' : $body,
        ));

        return $this->parseResponse($response);
    }

    /**
     * Provider-specific endpoint URI (relative to $baseUri or absolute).
     *
     * @param  SmsMessageContract  $message
     * @return string
     */
    abstract protected function endpointFor(SmsMessageContract $message);

    /**
     * Provider-specific request body. Returned as an array; the base
     * JSON-encodes it and writes the encoded string into the request.
     *
     * @param  SmsMessageContract  $message
     * @return array
     */
    abstract protected function payloadFor(SmsMessageContract $message);

    /**
     * Translate a provider response into an SmsResultContract. Called
     * once per successful dispatch (non-5xx after retries).
     *
     * @param  ResponseInterface  $response
     * @return SmsResultContract
     */
    abstract protected function parseResponse(ResponseInterface $response);
}
