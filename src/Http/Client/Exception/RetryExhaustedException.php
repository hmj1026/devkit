<?php

namespace Devkit\Http\Client\Exception;

use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Throwable;

/**
 * Raised by Devkit\Http\Client\Gateway when its retry loop exhausts the
 * configured maxAttempts without producing a non-5xx response. The last
 * response observed is attached so callers can inspect status / headers /
 * body for diagnostics or for surfacing a useful error to the end user.
 *
 * Pure PHP — extends RuntimeException; no Illuminate imports.
 */
class RetryExhaustedException extends RuntimeException
{
    /**
     * @var ResponseInterface|null
     */
    protected $lastResponse;

    /**
     * @param  string  $message
     * @param  ResponseInterface|null  $lastResponse  The final response received before giving up (null when only network errors were seen).
     * @param  Throwable|null  $previous
     */
    public function __construct($message = '', ResponseInterface $lastResponse = null, Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->lastResponse = $lastResponse;
    }

    /**
     * @return ResponseInterface|null
     */
    public function getLastResponse()
    {
        return $this->lastResponse;
    }
}
