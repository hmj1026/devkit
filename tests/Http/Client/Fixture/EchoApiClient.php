<?php

namespace Devkit\Tests\Http\Client\Fixture;

use Devkit\Http\Client\Gateway;

/**
 * Subclass fixture that pins a baseUri for the "subclass binds upstream"
 * spec scenario. Kept in its own file so the tested class remains explicit.
 */
class EchoApiClient extends Gateway
{
    protected $baseUri = 'https://api.example.com';
}
