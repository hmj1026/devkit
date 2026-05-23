<?php

namespace Devkit\Http\Client\Contract;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Observer attached to Devkit\Http\Client\Gateway (Wave 3) and notified
 * for every PSR-7 request/response pair the Gateway processes. Decouples
 * logging from the request lifecycle so callers can register zero, one,
 * or many observers without touching Gateway internals.
 *
 * Multiple observers attached to the same Gateway are notified in
 * registration order.
 *
 * Pure PHP — depends only on PSR-7 contracts.
 */
interface LogObserverContract
{
    /**
     * Called just before the Gateway dispatches the request.
     *
     * @param  RequestInterface  $request
     * @return void
     */
    public function onRequest(RequestInterface $request);

    /**
     * Called after the Gateway receives any response from the wire,
     * including 5xx responses that will trigger a retry. NOT invoked when
     * a request exhausts retries due to network-level exceptions only (no
     * response was ever received). Implementations wanting "success-only"
     * semantics SHOULD filter on `$response->getStatusCode()`.
     *
     * @param  ResponseInterface  $response
     * @return void
     */
    public function onResponse(ResponseInterface $response);
}
