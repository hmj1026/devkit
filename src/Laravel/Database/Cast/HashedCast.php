<?php

namespace Devkit\Laravel\Database\Cast;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Facades\Hash;

class HashedCast implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        return $value;
    }

    public function set($model, string $key, $value, array $attributes)
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $value = (string) $value;

        return Hash::needsRehash($value) ? Hash::make($value) : $value;
    }
}
