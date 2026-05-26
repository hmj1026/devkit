<?php

namespace Devkit\Tests\Laravel\Database\Fixture;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class CountingCast implements CastsAttributes
{
    public static $getCount = 0;

    public static function reset(): void
    {
        self::$getCount = 0;
    }

    public function get($model, string $key, $value, array $attributes)
    {
        ++self::$getCount;

        return $value;
    }

    public function set($model, string $key, $value, array $attributes)
    {
        return $value;
    }
}
