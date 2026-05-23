<?php

namespace Devkit\Tests\Http\Client\Fixture;

use Devkit\Http\Client\Gateway;

/**
 * Subclass fixture that pins a baseUri for the "subclass binds upstream"
 * spec scenario. Kept in its own file (not an anonymous class) so the
 * test suite stays PHP 5.6-syntax-safe.
 */
class EchoApiClient extends Gateway
{
    protected $baseUri = 'https://api.example.com';
}
