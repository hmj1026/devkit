<?php

namespace Devkit\Http\Asset\Contract;

/**
 * Resolver for the scheme+host prefix prepended to relative paths by
 * Devkit\Http\Asset\HttpUri. Implementations decide where assets live
 * (CDN, app host, env-specific bucket) and return the bare origin
 * string (no trailing slash, no path component).
 *
 * Pure PHP — no Illuminate imports. Laravel adapter (Wave 5) supplies
 * a concrete resolver wired to Illuminate\Http\Request / config.
 */
interface HostResolverInterface
{
    /**
     * Return the origin (scheme + host[:port]) to prepend to relative
     * paths, e.g. `https://cdn.example.com` or `https://example.test:8443`.
     * Implementations SHOULD return an empty string when no host should
     * be prepended (HttpUri then leaves the relative path untouched).
     *
     * @return string
     */
    public function resolve();
}
