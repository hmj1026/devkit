<?php

namespace Devkit\Tests\Http\Asset;

use Devkit\Http\Asset\HttpUri;
use Devkit\Tests\Http\Asset\Fixture\InMemoryCache;
use Devkit\Tests\Http\Asset\Fixture\StaticHostResolver;
use PHPUnit\Framework\TestCase;

class HttpUriTest extends TestCase
{
    public function testRelativePathAppendsVersion()
    {
        $cache = new InMemoryCache();
        $httpUri = new HttpUri($cache);

        $result = $httpUri->url('/images/logo.png');

        $this->assertMatchesRegularExpression('#^/images/logo\.png\?v=\d+$#', $result);
    }

    public function testAbsoluteUrlPreservesHostAndAppendsVersion()
    {
        $cache = new InMemoryCache();
        $httpUri = new HttpUri($cache);

        $result = $httpUri->url('https://cdn.example.com/logo.png');

        $this->assertMatchesRegularExpression('#^https://cdn\.example\.com/logo\.png\?v=\d+$#', $result);
    }

    public function testFirstCallWritesCacheSecondCallReads()
    {
        $cache = new InMemoryCache();
        $httpUri = new HttpUri($cache);

        $first = $httpUri->url('/x');
        $writesAfterFirst = $cache->writes;
        $second = $httpUri->url('/x');

        $this->assertSame($first, $second, 'cached timestamp must be stable across calls');
        $this->assertSame(1, $writesAfterFirst);
        $this->assertSame(1, $cache->writes, 'second call must not re-write the cache');
    }

    public function testClearForcesFreshTimestampOnNextCall()
    {
        $cache = new InMemoryCache();
        $httpUri = new HttpUri($cache);

        $httpUri->url('/x');
        $this->assertSame(1, $cache->writes);
        $httpUri->clear();
        $this->assertArrayNotHasKey('devkit.asset_version', $cache->store);

        // Force a different timestamp by advancing time via cache state.
        $cache->store['devkit.asset_version'] = null;
        unset($cache->store['devkit.asset_version']);

        $httpUri->url('/x');
        $this->assertSame(2, $cache->writes, 'clear() then url() must re-populate the cache');
    }

    public function testHostResolverPrependsForRelativePath()
    {
        $cache = new InMemoryCache();
        $resolver = new StaticHostResolver('https://cdn.example.com');
        $httpUri = new HttpUri($cache, $resolver);

        $result = $httpUri->url('/x.png');

        $this->assertMatchesRegularExpression('#^https://cdn\.example\.com/x\.png\?v=\d+$#', $result);
    }

    public function testHostResolverDoesNotOverrideAbsoluteUrl()
    {
        $cache = new InMemoryCache();
        $resolver = new StaticHostResolver('https://cdn.example.com');
        $httpUri = new HttpUri($cache, $resolver);

        $result = $httpUri->url('https://other.example.org/logo.png');

        $this->assertStringStartsWith('https://other.example.org/logo.png', $result);
        $this->assertStringNotContainsString('cdn.example.com', $result);
    }

    public function testEmptyHostResolverOutputLeavesRelativePath()
    {
        $cache = new InMemoryCache();
        $resolver = new StaticHostResolver('');
        $httpUri = new HttpUri($cache, $resolver);

        $result = $httpUri->url('/x.png');

        $this->assertStringStartsWith('/x.png', $result);
    }

    public function testCustomCacheKeyIsUsed()
    {
        $cache = new InMemoryCache();
        $httpUri = new HttpUri($cache, null, 'custom.asset.v', 60);

        $httpUri->url('/x');

        $this->assertArrayHasKey('custom.asset.v', $cache->store);
        $this->assertArrayNotHasKey('devkit.asset_version', $cache->store);
    }

    public function testExistingQueryStringIsPreserved()
    {
        $cache = new InMemoryCache();
        $httpUri = new HttpUri($cache);

        $result = $httpUri->url('/x.png?a=1');

        $this->assertMatchesRegularExpression('#^/x\.png\?a=1&v=\d+$#', $result);
    }

    public function testCacheHitWithIntegerStringStillReturnsInt()
    {
        $cache = new InMemoryCache();
        // Some PSR-16 backends serialise integers as strings; HttpUri must tolerate this.
        $cache->store['devkit.asset_version'] = '1234567890';
        $cache->expiry['devkit.asset_version'] = time() + 3600;
        $httpUri = new HttpUri($cache);

        $this->assertSame('/x?v=1234567890', $httpUri->url('/x'));
    }
}
