<?php

namespace Illuminate\Contracts\Database\Eloquent {
    if (! interface_exists(CastsAttributes::class, true)) {
        interface CastsAttributes
        {
            public function get($model, string $key, $value, array $attributes);

            public function set($model, string $key, $value, array $attributes);
        }

        if (! defined('DEVKIT_CASTS_ATTRIBUTES_POLYFILLED')) {
            define('DEVKIT_CASTS_ATTRIBUTES_POLYFILLED', true);
        }
    }
}
