<?php

namespace Devkit\Tests\Logging\GoogleChat\Fixture;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * PSR-18 client that records every dispatched request and returns a
 * canned 200 response. Used by GoogleChatLogHandlerTest to assert
 * webhook URL, body shape, color codes, and mention rendering without
 * touching the network.
 */
class RecordingHttpClient implements ClientInterface
{
    /**
     * @var RequestInterface[]
     */
    public $requests = array();

    /**
     * @var int
     */
    public $responseStatus = 200;

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;
        return new Response($this->responseStatus);
    }
}
