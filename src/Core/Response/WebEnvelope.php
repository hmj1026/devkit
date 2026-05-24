<?php

namespace Devkit\Core\Response;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Non-JSON response envelope. Provides redirect helpers returning PSR-7
 * responses distinct from JsonEnvelope's semantics. View-rendering helpers
 * (which require a template engine) are deferred to framework adapters;
 * this class stays framework-agnostic.
 *
 * Pure PHP — depends on PSR-7 + PSR-17 only. No Illuminate imports.
 */
class WebEnvelope
{
    /**
     * @var ResponseFactoryInterface
     */
    protected $responseFactory;

    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    /**
     * Build a redirect response with a Location header.
     *
     * @param  string  $url
     * @param  int  $statusCode  Defaults to 302; 301 / 303 / 307 / 308 are all valid.
     * @return ResponseInterface
     */
    public function redirect($url, $statusCode = 302)
    {
        return $this->responseFactory
            ->createResponse((int) $statusCode)
            ->withHeader('Location', (string) $url);
    }
}
