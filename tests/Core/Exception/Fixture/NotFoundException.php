<?php

namespace Devkit\Tests\Core\Exception\Fixture;

use Devkit\Core\Exception\AbstractHttpException;

/**
 * Status-only subclass: exercises the default headers + default shouldReport.
 */
class NotFoundException extends AbstractHttpException
{
    protected $statusCode = 404;
}
