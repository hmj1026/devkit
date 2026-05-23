<?php

namespace Devkit\Tests\Core\Exception;

use Devkit\Core\Exception\AbstractHttpException;
use Devkit\Core\Exception\Contract\ReportExceptionContract;
use Devkit\Tests\Core\Exception\Fixture\NotFoundException;
use Devkit\Tests\Core\Exception\Fixture\RateLimitException;
use Devkit\Tests\Core\Exception\Fixture\ValidationException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class AbstractHttpExceptionTest extends TestCase
{
    public function testImplementsHttpExceptionInterface()
    {
        $exception = new NotFoundException('not here');

        $this->assertInstanceOf(HttpExceptionInterface::class, $exception);
    }

    public function testImplementsReportExceptionContract()
    {
        $exception = new NotFoundException();

        $this->assertInstanceOf(ReportExceptionContract::class, $exception);
    }

    public function testSubclassExposesItsStatusCode()
    {
        $this->assertSame(404, (new NotFoundException())->getStatusCode());
        $this->assertSame(422, (new ValidationException())->getStatusCode());
        $this->assertSame(429, (new RateLimitException())->getStatusCode());
    }

    public function testDefaultHeadersIsEmptyArray()
    {
        $this->assertSame(array(), (new NotFoundException())->getHeaders());
    }

    public function testSubclassHeadersArePassedThrough()
    {
        $exception = new RateLimitException();

        $this->assertSame(
            array('Retry-After' => '60', 'X-Custom' => 'value'),
            $exception->getHeaders()
        );
    }

    public function testShouldReportDefaultsToTrue()
    {
        $this->assertTrue((new NotFoundException())->shouldReport());
        $this->assertTrue((new RateLimitException())->shouldReport());
    }

    public function testSubclassMayOptOutOfReporting()
    {
        $this->assertFalse((new ValidationException())->shouldReport());
    }

    public function testIsThrowable()
    {
        $this->expectException(AbstractHttpException::class);
        $this->expectExceptionMessage('boom');

        throw new NotFoundException('boom');
    }

    public function testPreservesMessageAndPreviousChain()
    {
        $previous = new \RuntimeException('upstream');
        $exception = new NotFoundException('downstream', 0, $previous);

        $this->assertSame('downstream', $exception->getMessage());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
