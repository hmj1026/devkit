<?php

namespace Devkit\Tests\Http\Asset\Fixture;

use DateInterval;
use Psr\SimpleCache\CacheInterface;

/**
 * Minimal PSR-16 in-memory cache for tests. Stores values in an array;
 * supports per-key TTL via a parallel expiry timestamp map.
 *
 * Public `$writes` counter exposed so tests can assert cache-population
 * vs cache-hit behaviour without mocking.
 */
class InMemoryCache implements CacheInterface
{
    /**
     * @var array
     */
    public $store = array();

    /**
     * Unix timestamps keyed identically to $store; null = no expiry.
     *
     * @var array
     */
    public $expiry = array();

    /**
     * @var int
     */
    public $writes = 0;

    public function get($key, $default = null)
    {
        if (!array_key_exists($key, $this->store)) {
            return $default;
        }
        if (isset($this->expiry[$key]) && $this->expiry[$key] !== null && time() >= $this->expiry[$key]) {
            unset($this->store[$key], $this->expiry[$key]);
            return $default;
        }
        return $this->store[$key];
    }

    public function set($key, $value, $ttl = null)
    {
        $this->store[$key] = $value;
        $this->expiry[$key] = $this->normaliseTtl($ttl);
        $this->writes++;
        return true;
    }

    public function delete($key)
    {
        unset($this->store[$key], $this->expiry[$key]);
        return true;
    }

    public function clear()
    {
        $this->store = array();
        $this->expiry = array();
        return true;
    }

    public function getMultiple($keys, $default = null)
    {
        $out = array();
        foreach ($keys as $key) {
            $out[$key] = $this->get($key, $default);
        }
        return $out;
    }

    public function setMultiple($values, $ttl = null)
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
        return true;
    }

    public function deleteMultiple($keys)
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
        return true;
    }

    public function has($key)
    {
        return $this->get($key, $this) !== $this;
    }

    /**
     * @param  int|DateInterval|null  $ttl
     * @return int|null  Unix expiry timestamp, or null for no expiry.
     */
    protected function normaliseTtl($ttl)
    {
        if ($ttl === null) {
            return null;
        }
        if ($ttl instanceof DateInterval) {
            $reference = new \DateTimeImmutable();
            return $reference->add($ttl)->getTimestamp();
        }
        return time() + (int) $ttl;
    }
}
