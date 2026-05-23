<?php

namespace Devkit\Tests\Core\Exception\Fixture;

use Devkit\Core\Exception\AbstractHttpException;

/**
 * Quiet subclass: overrides shouldReport() to suppress logging, mirroring the
 * spec scenario "Subclass opts out of reporting".
 */
class ValidationException extends AbstractHttpException
{
    protected $statusCode = 422;

    public function shouldReport()
    {
        return false;
    }
}
