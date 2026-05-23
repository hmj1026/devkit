<?php

namespace Devkit\Laravel\Http\Asset;

use Devkit\Http\Asset\Contract\HostResolverInterface;
use Devkit\Http\Asset\HttpUri;
use Illuminate\Cache\Repository;

class HttpUriCacheAdapter extends HttpUri
{
    public function __construct(
        Repository $cache,
        HostResolverInterface $hostResolver = null,
        $cacheKey = 'devkit.asset_version',
        $ttl = 3600
    ) {
        parent::__construct($cache, $hostResolver, $cacheKey, $ttl);
    }
}
