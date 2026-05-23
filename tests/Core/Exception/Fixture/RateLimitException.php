<?php

namespace Devkit\Tests\Core\Exception\Fixture;

use Devkit\Core\Exception\AbstractHttpException;

/**
 * Header-bearing subclass: exercises the headers passthrough scenario.
 */
class RateLimitException extends AbstractHttpException
{
    protected $statusCode = 429;

    protected $headers = array(
        'Retry-After' => '60',
        'X-Custom' => 'value',
    );
}
