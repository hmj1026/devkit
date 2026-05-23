<?php

namespace Devkit\Laravel\Http\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

if (!function_exists(__NAMESPACE__ . '\\getClientTruthIp')) {
    function getClientTruthIp(Request $request = null)
    {
        $request = $request ?: request();
        $forwarded = $request->headers->get('x-forwarded-for');

        if (is_string($forwarded) && $forwarded !== '') {
            $parts = array_map('trim', explode(',', $forwarded));
            if (isset($parts[0]) && $parts[0] !== '') {
                return $parts[0];
            }
        }

        return $request->headers->get('x-real-ip') ?: $request->ip();
    }
}

if (!function_exists(__NAMESPACE__ . '\\getUserClientIdCookie')) {
    function getUserClientIdCookie(Request $request = null, $name = 'user_client_id')
    {
        $request = $request ?: request();
        $value = $request->cookies->get($name);

        return $value ?: (string) Str::uuid();
    }
}
