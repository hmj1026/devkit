<?php

namespace Devkit\Core\Exception;

use Devkit\Core\Exception\Contract\ReportExceptionContract;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Base for HTTP-aware exceptions thrown from devkit modules and consumer code.
 *
 * Implements Symfony's HttpExceptionInterface so framework integrations
 * (Laravel, Symfony, Slim) recognise the status code and headers without
 * extra glue; implements ReportExceptionContract so subclasses can opt out
 * of being logged / notified.
 *
 * Subclasses declare the HTTP semantics via property overrides:
 *   protected $statusCode = 404;
 *   protected $headers = ['X-Custom' => 'value'];
 * and override shouldReport() when the exception type is expected (e.g.
 * validation failures) and should not generate noise in the log channel.
 *
 * Return-type declarations on getStatusCode() / getHeaders() are required
 * by HttpExceptionInterface as of symfony/http-kernel ^6.0; declaring them
 * here keeps the LSP contract satisfied across the entire ^4.4 || ^5.0 ||
 * ^6.0 || ^7.0 range we support.
 */
abstract class AbstractHttpException extends RuntimeException implements HttpExceptionInterface, ReportExceptionContract
{
    /**
     * HTTP status code returned by getStatusCode(). Subclasses SHOULD override.
     *
     * @var int
     */
    protected $statusCode = 500;

    /**
     * Response headers returned by getHeaders(). Subclasses MAY override.
     *
     * @var array
     */
    protected $headers = array();

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @return bool
     */
    public function shouldReport()
    {
        return true;
    }
}
