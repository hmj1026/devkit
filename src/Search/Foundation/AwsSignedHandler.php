<?php

namespace Devkit\Search\Foundation;

use RuntimeException;

/**
 * Callable handler for elasticsearch-php 7.x that signs each outgoing
 * request with AWS SigV4 (intended for AWS-managed Elasticsearch /
 * OpenSearch domains).
 *
 * Implemented as an optional component — the AWS SDK is a `suggest`,
 * not a `require`, so the class is safe to ship but throws clearly
 * when invoked without the SDK installed.
 *
 * Wiring is done by {@see \Devkit\Search\Client\ConnectionFactory},
 * which only constructs this handler when AWS SDK classes are present.
 *
 * Pure PHP — no Illuminate imports.
 */
class AwsSignedHandler
{
    /** @var string */
    protected $region;

    /** @var object  \Aws\Credentials\CredentialsInterface */
    protected $credentials;

    /** @var string  AWS service code; "es" covers Elasticsearch + OpenSearch. */
    protected $service = 'es';

    /**
     * @param  string  $region
     * @param  object  $credentials  Anything implementing \Aws\Credentials\CredentialsInterface.
     */
    public function __construct($region, $credentials)
    {
        $this->region = (string) $region;
        $this->credentials = $credentials;
    }

    /**
     * @return string
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * @return object
     */
    public function getCredentials()
    {
        return $this->credentials;
    }

    /**
     * @param  string  $service  e.g. 'es' (default) or 'aoss' for serverless.
     * @return $this
     */
    public function setService($service)
    {
        $this->service = (string) $service;

        return $this;
    }

    /**
     * Invoke shape mirrors elasticsearch-php's RingPHP handler
     * convention: receives a request array, returns a future array
     * the transport can resolve.
     *
     * @param  array  $request
     * @return mixed
     */
    public function __invoke(array $request)
    {
        if (!$this->sdkAvailable()) {
            throw new RuntimeException(
                'AwsSignedHandler requires aws/aws-sdk-php. Install it via `composer require aws/aws-sdk-php`.'
            );
        }
        if (!class_exists('\\GuzzleHttp\\Psr7\\Request')) {
            throw new RuntimeException('AwsSignedHandler requires guzzlehttp/psr7 (transitive of guzzlehttp/guzzle).');
        }

        $psr7Request = $this->buildPsr7Request($request);

        $signerClass = '\\Aws\\Signature\\SignatureV4';
        $signer = new $signerClass($this->service, $this->region);
        $signed = $signer->signRequest($psr7Request, $this->credentials);

        $handlerFactory = '\\Aws\\default_http_handler';
        if (!function_exists($handlerFactory)) {
            throw new RuntimeException('AwsSignedHandler requires \\Aws\\default_http_handler() — install/upgrade aws/aws-sdk-php.');
        }
        $handler = call_user_func($handlerFactory);

        // Guzzle's HTTP handler signature is `($psr7Request, $options)`;
        // the second argument is Guzzle request options, NOT the raw
        // elasticsearch-php request array. Only forward known timeout
        // keys so we don't accidentally feed Guzzle options that
        // collide with ES ring-request keys (e.g. `body`, `headers`).
        $options = array();
        if (isset($request['client']['timeout'])) {
            $options['timeout'] = $request['client']['timeout'];
        }
        if (isset($request['client']['connect_timeout'])) {
            $options['connect_timeout'] = $request['client']['connect_timeout'];
        }

        $promise = $handler($signed, $options);
        $response = $promise->wait();

        return $this->wrapResponse($response);
    }

    /**
     * @return bool
     */
    protected function sdkAvailable()
    {
        return class_exists('\\Aws\\Signature\\SignatureV4');
    }

    /**
     * Build a PSR-7 Request from elasticsearch-php's request array.
     *
     * @param  array  $request
     * @return \GuzzleHttp\Psr7\Request
     */
    protected function buildPsr7Request(array $request)
    {
        $method = isset($request['http_method']) ? strtoupper($request['http_method']) : 'GET';
        $scheme = isset($request['scheme']) ? $request['scheme'] : 'https';
        $host = isset($request['headers']['Host'][0])
            ? $request['headers']['Host'][0]
            : (isset($request['host']) ? $request['host'] : '');
        $uri = isset($request['uri']) ? $request['uri'] : '/';
        $body = isset($request['body']) ? $request['body'] : null;
        $headers = isset($request['headers']) ? $request['headers'] : array();

        $url = $scheme . '://' . $host . $uri;
        if (isset($request['query_string']) && $request['query_string'] !== '') {
            $url .= '?' . $request['query_string'];
        }

        $requestClass = '\\GuzzleHttp\\Psr7\\Request';

        return new $requestClass($method, $url, $headers, $body);
    }

    /**
     * Wrap the PSR-7 response into the future-array shape RingPHP
     * handlers return. Uses CompletedFutureArray when available;
     * otherwise falls back to a plain array.
     *
     * @param  \Psr\Http\Message\ResponseInterface  $response
     * @return mixed
     */
    protected function wrapResponse($response)
    {
        $body = (string) $response->getBody();
        $headers = $response->getHeaders();
        $status = $response->getStatusCode();

        $result = array(
            'status' => $status,
            'reason' => $response->getReasonPhrase(),
            'headers' => $headers,
            'body' => $body,
            'effective_url' => '',
            'transfer_stats' => array(),
        );

        $futureClass = '\\GuzzleHttp\\Ring\\Future\\CompletedFutureArray';
        if (class_exists($futureClass)) {
            return new $futureClass($result);
        }

        return $result;
    }
}
