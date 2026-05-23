<?php

namespace Illuminate\Contracts\Database\Eloquent {
    if (! interface_exists(CastsAttributes::class, true)) {
        interface CastsAttributes
        {
            public function get($model, string $key, $value, array $attributes);

            public function set($model, string $key, $value, array $attributes);
        }
    }
}
