<?php

namespace Devkit\Core\Support;

if (!function_exists('Devkit\\Core\\Support\\isJson')) {
    /**
     * Determine whether the given string decodes as valid JSON.
     *
     * Empty strings and non-string scalars return false. Decoding is performed
     * with PHP's native json_decode; the input is not modified.
     *
     * @param  mixed  $value
     * @return bool
     */
    function isJson($value)
    {
        if (!is_string($value) || $value === '') {
            return false;
        }

        json_decode($value);

        return json_last_error() === JSON_ERROR_NONE;
    }
}
