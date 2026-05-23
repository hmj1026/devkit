<?php

namespace Devkit\Tests\Http\Client\Fixture;

use Devkit\Http\Client\Contract\LogObserverContract;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Observer that captures every onRequest / onResponse call in two
 * append-only lists. Used by GatewayTest to assert observer firing and
 * ordering without pulling in Mockery for one-line spies.
 */
class RecordingObserver implements LogObserverContract
{
    /**
     * @var RequestInterface[]
     */
    public $requests = array();

    /**
     * @var ResponseInterface[]
     */
    public $responses = array();

    public function onRequest(RequestInterface $request)
    {
        $this->requests[] = $request;
    }

    public function onResponse(ResponseInterface $response)
    {
        $this->responses[] = $response;
    }
}
