<?php

namespace Devkit\Tests\Core\Response;

use Devkit\Core\Response\WebEnvelope;
use GuzzleHttp\Psr7\HttpFactory;
use PHPUnit\Framework\TestCase;

class WebEnvelopeTest extends TestCase
{
    /**
     * @return WebEnvelope
     */
    private function makeEnvelope()
    {
        return new WebEnvelope(new HttpFactory());
    }

    public function testRedirectDefaultsTo302()
    {
        $response = $this->makeEnvelope()->redirect('/home');

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/home', $response->getHeaderLine('Location'));
    }

    public function testRedirectAcceptsCustomStatus()
    {
        $response = $this->makeEnvelope()->redirect('/permanent-home', 301);
        $this->assertSame(301, $response->getStatusCode());

        $response = $this->makeEnvelope()->redirect('/temp-home', 307);
        $this->assertSame(307, $response->getStatusCode());
    }

    public function testRedirectToAbsoluteUrlPreservesScheme()
    {
        $response = $this->makeEnvelope()->redirect('https://example.com/dashboard');

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(
            'https://example.com/dashboard',
            $response->getHeaderLine('Location')
        );
    }
}
