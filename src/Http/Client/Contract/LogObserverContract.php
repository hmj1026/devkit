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
     * Called just after the Gateway receives the response (or after a
     * retry chain completes successfully). Not invoked when the request
     * exhausts retries without success — observers needing failure
     * notification SHOULD subscribe to the surrounding try/catch.
     *
     * @param  ResponseInterface  $response
     * @return void
     */
    public function onResponse(ResponseInterface $response);
}
