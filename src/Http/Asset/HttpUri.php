<?php

namespace Devkit\Http\Asset;

use Devkit\Http\Asset\Contract\HostResolverInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Cache-busted asset URL generator. Appends a `?v=<timestamp>` query
 * parameter to every URL it emits; the timestamp is persisted in a
 * PSR-16 cache so successive calls return the same value until the
 * cache TTL expires or `clear()` is called explicitly.
 *
 * Behaviour matrix:
 *   - Absolute URL input  → host preserved, `?v=<ts>` appended.
 *   - Relative path input + no HostResolverInterface → relative path
 *     preserved, `?v=<ts>` appended.
 *   - Relative path input + HostResolverInterface → resolver's origin
 *     prepended, then `?v=<ts>` appended.
 *
 * Pure PHP — depends only on PSR-16 + an optional in-house resolver.
 * No Illuminate imports.
 */
class HttpUri
{
    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var HostResolverInterface|null
     */
    protected $hostResolver;

    /**
     * @var string
     */
    protected $cacheKey;

    /**
     * @var int
     */
    protected $ttl;

    /**
     * @param  CacheInterface  $cache
     * @param  HostResolverInterface|null  $hostResolver
     * @param  string  $cacheKey
     * @param  int  $ttl  Seconds; default 1 hour.
     */
    public function __construct(
        CacheInterface $cache,
        HostResolverInterface $hostResolver = null,
        $cacheKey = 'devkit.asset_version',
        $ttl = 3600
    ) {
        $this->cache = $cache;
        $this->hostResolver = $hostResolver;
        $this->cacheKey = $cacheKey;
        $this->ttl = (int) $ttl;
    }

    /**
     * Return the input path with a cache-busting timestamp query appended.
     *
     * @param  string  $path
     * @return string
     */
    public function url($path)
    {
        $timestamp = $this->resolveTimestamp();
        $base = $this->expandPath($path);

        $separator = (strpos($base, '?') === false) ? '?' : '&';

        return $base . $separator . 'v=' . $timestamp;
    }

    /**
     * Drop the cached timestamp; the next `url()` call will generate and
     * persist a fresh one.
     *
     * @return $this
     */
    public function clear()
    {
        $this->cache->delete($this->cacheKey);

        return $this;
    }

    /**
     * @return int  Unix timestamp.
     */
    protected function resolveTimestamp()
    {
        $cached = $this->cache->get($this->cacheKey);
        if (is_int($cached) && $cached > 0) {
            return $cached;
        }
        if (is_string($cached) && ctype_digit($cached)) {
            return (int) $cached;
        }

        $now = time();
        $this->cache->set($this->cacheKey, $now, $this->ttl);

        return $now;
    }

    /**
     * Expand the input into a fully-qualified-or-relative URL ready for
     * the version-query append step. Absolute URLs pass through; relative
     * paths optionally pick up the HostResolverInterface origin.
     *
     * @param  string  $path
     * @return string
     */
    protected function expandPath($path)
    {
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        if ($this->hostResolver !== null) {
            $origin = $this->hostResolver->resolve();
            if (is_string($origin) && $origin !== '') {
                return rtrim($origin, '/') . '/' . ltrim($path, '/');
            }
        }

        return $path;
    }
}
