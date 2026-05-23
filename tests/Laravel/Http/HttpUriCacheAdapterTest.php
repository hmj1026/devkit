<?php

namespace Devkit\Tests\Laravel\Http;

use Devkit\Laravel\Http\Asset\HttpUriCacheAdapter;
use Devkit\Tests\Laravel\TestCase;
use Illuminate\Support\Facades\Cache;

class HttpUriCacheAdapterTest extends TestCase
{
    public function testLaravelCacheRepositoryBacksCoreHttpUri()
    {
        $httpUri = new HttpUriCacheAdapter(Cache::store(), null, 'devkit.test.asset_version', 3600);

        $first = $httpUri->url('/logo.png');
        $second = $httpUri->url('/logo.png');

        $this->assertSame($first, $second);
        $this->assertStringStartsWith('/logo.png?v=', $first);
    }
}
