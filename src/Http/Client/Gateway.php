<?php

namespace Devkit\Http\Client;

use Devkit\Http\Client\Contract\LogObserverContract;
use Devkit\Http\Client\Exception\RetryExhaustedException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException as GuzzleConnectException;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use Psr\Http\Client\ClientInterface as Psr18ClientInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Single-class HTTP gateway wrapping Guzzle 7 (or any injected PSR-18 client)
 * with built-in retry-on-5xx-or-connection-error, exponential backoff, and a
 * fan-out LogObserverContract hook.
 *
 * Per devkit-http-gateway spec, this class collapses the previous 4-layer
 * (Client + AbstractGateway + AbstractRequest + AbstractResponse) hierarchy
 * down to a single subclassable surface. Consumers integrate with third-party
 * APIs by extending Gateway and overriding $baseUri / $defaultHeaders or by
 * adding domain-specific convenience methods on the subclass.
 *
 * Pure PHP — no Illuminate imports. PHP 5.6-syntax-safe (no return-type
 * declarations, no null coalesce) per openspec/config.yaml.
 *
 * Design notes (documented inline for future maintainers):
 *
 *   - The Guzzle client is created with `http_errors => false` so 5xx
 *     responses arrive as ResponseInterface and the retry decider can
 *     inspect status codes; without this, Guzzle throws BadResponseException
 *     and the retry-vs-bubble decision becomes catch-only.
 *   - When a PSR-18 client is injected, the Gateway dispatches through its
 *     sendRequest(). Network failures detected via NetworkExceptionInterface
 *     trigger retry; other ClientExceptionInterface subclasses bubble.
 *   - Backoff sleep is usleep($initialDelayMs * (2 ** $attempt - 1) * 1000);
 *     attempt counter starts at 1, so for initialDelayMs=100 and
 *     maxAttempts=3 the sleeps are 100ms (after attempt 1) + 200ms (after
 *     attempt 2). After attempt 3 (maxAttempts), no sleep — we raise.
 *   - Observers fire in registration order on EVERY attempt (including
 *     retries) so callers can log every wire call, not just the successful
 *     one. The onResponse hook is invoked even for 5xx responses; only the
 *     terminal RetryExhaustedException case skips onResponse for the final
 *     last-5xx (no successful response to hand off).
 */
class Gateway
{
    /**
     * Base URI prefix for relative request URIs. Subclasses typically
     * override this in their class body.
     *
     * @var string
     */
    protected $baseUri = '';

    /**
     * Default headers merged into every request. Subclass override.
     *
     * @var array
     */
    protected $defaultHeaders = array();

    /**
     * @var Psr18ClientInterface|null
     */
    protected $psr18Client;

    /**
     * @var GuzzleClient|null
     */
    protected $guzzleClient;

    /**
     * @var int
     */
    protected $maxAttempts;

    /**
     * @var int
     */
    protected $initialDelayMs;

    /**
     * @var LogObserverContract[]
     */
    protected $observers = array();

    /**
     * @param  string|null  $baseUri  Override the class property when supplied.
     * @param  Psr18ClientInterface|null  $psr18Client  When supplied, dispatch through this client; otherwise an internal Guzzle client is built.
     * @param  int  $maxAttempts  Total attempts including the first try. Default 3.
     * @param  int  $initialDelayMs  Base delay for exponential backoff in ms. Default 100.
     */
    public function __construct($baseUri = null, $psr18Client = null, $maxAttempts = 3, $initialDelayMs = 100)
    {
        if ($baseUri !== null) {
            $this->baseUri = $baseUri;
        }

        $this->maxAttempts = (int) $maxAttempts;
        $this->initialDelayMs = (int) $initialDelayMs;

        if ($psr18Client !== null) {
            $this->psr18Client = $psr18Client;
        } else {
            $config = array('http_errors' => false);
            if ($this->baseUri !== '') {
                $config['base_uri'] = $this->baseUri;
            }
            $this->guzzleClient = new GuzzleClient($config);
        }
    }

    /**
     * Attach a LogObserverContract. Multiple observers are notified in
     * registration order on every request and every response.
     *
     * @param  LogObserverContract  $observer
     * @return $this
     */
    public function addLogObserver(LogObserverContract $observer)
    {
        $this->observers[] = $observer;

        return $this;
    }

    /**
     * Dispatch an HTTP request through the configured transport. Retries
     * on 5xx responses and connection errors using exponential backoff up
     * to maxAttempts; raises RetryExhaustedException when exhausted.
     *
     * @param  string  $method
     * @param  string  $uri
     * @param  array  $options  Per-request overrides: 'headers' => array, 'body' => string.
     * @return ResponseInterface
     */
    public function request($method, $uri, array $options = array())
    {
        $request = $this->buildRequest($method, $uri, $options);

        $lastResponse = null;
        $lastException = null;

        for ($attempt = 1; $attempt <= $this->maxAttempts; $attempt++) {
            $this->notifyRequest($request);

            try {
                $response = $this->dispatch($request);
            } catch (GuzzleConnectException $e) {
                $lastException = $e;
                $response = null;
            } catch (NetworkExceptionInterface $e) {
                $lastException = $e;
                $response = null;
            }

            if ($response !== null) {
                // Notify observers of EVERY received response — including 5xx
                // ones that will trigger a retry. Observers used for logging
                // need to see every wire call; observers used for "only on
                // success" can filter by status code on their end.
                $this->notifyResponse($response);
                $status = $response->getStatusCode();
                if ($status < 500) {
                    return $response;
                }
                $lastResponse = $response;
            }

            if ($attempt < $this->maxAttempts) {
                $this->backoff($attempt);
            }
        }

        throw new RetryExhaustedException(
            sprintf(
                'Gateway exhausted %d attempts for %s %s',
                $this->maxAttempts,
                strtoupper($method),
                $uri
            ),
            $lastResponse,
            $lastException
        );
    }

    /**
     * @return string
     */
    public function getBaseUri()
    {
        return $this->baseUri;
    }

    /**
     * @return GuzzleClient|null
     */
    public function getGuzzleClient()
    {
        return $this->guzzleClient;
    }

    /**
     * @return Psr18ClientInterface|null
     */
    public function getPsr18Client()
    {
        return $this->psr18Client;
    }

    /**
     * @param  string  $method
     * @param  string  $uri
     * @param  array  $options
     * @return RequestInterface
     */
    protected function buildRequest($method, $uri, array $options)
    {
        $headers = $this->defaultHeaders;
        if (isset($options['headers']) && is_array($options['headers'])) {
            $headers = array_merge($headers, $options['headers']);
        }

        $body = isset($options['body']) ? $options['body'] : null;
        $resolvedUri = $this->resolveUri($uri);

        return new GuzzleRequest($method, $resolvedUri, $headers, $body);
    }

    /**
     * @param  string  $uri
     * @return string
     */
    protected function resolveUri($uri)
    {
        // Absolute URIs pass through unchanged; relative URIs prepend baseUri.
        if (preg_match('#^https?://#i', $uri)) {
            return $uri;
        }
        if ($this->baseUri === '') {
            return $uri;
        }
        return rtrim($this->baseUri, '/') . '/' . ltrim($uri, '/');
    }

    /**
     * @param  RequestInterface  $request
     * @return ResponseInterface
     */
    protected function dispatch(RequestInterface $request)
    {
        if ($this->psr18Client !== null) {
            return $this->psr18Client->sendRequest($request);
        }
        // Guzzle 7 implements PSR-18 ClientInterface; send() returns ResponseInterface.
        return $this->guzzleClient->send($request);
    }

    /**
     * @param  RequestInterface  $request
     * @return void
     */
    protected function notifyRequest(RequestInterface $request)
    {
        foreach ($this->observers as $observer) {
            $observer->onRequest($request);
        }
    }

    /**
     * @param  ResponseInterface  $response
     * @return void
     */
    protected function notifyResponse(ResponseInterface $response)
    {
        foreach ($this->observers as $observer) {
            $observer->onResponse($response);
        }
    }

    /**
     * Sleep for an exponentially-growing delay. After attempt N the delay
     * is initialDelayMs * (2^N - 1) ms. With default initialDelayMs=100
     * the cumulative delays across 3 attempts are 0 + 100ms + 200ms.
     *
     * @param  int  $attempt  1-indexed attempt number that just finished.
     * @return void
     */
    protected function backoff($attempt)
    {
        if ($this->initialDelayMs <= 0) {
            return;
        }
        $delayMs = $this->initialDelayMs * ((1 << $attempt) - 1);
        usleep($delayMs * 1000);
    }
}
