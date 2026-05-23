<?php

namespace Devkit\Laravel\Http\Middleware;

use Devkit\Laravel\Http\Support\UserClientIdCookie;
use Illuminate\Http\Response;

use function Devkit\Laravel\Http\Support\getUserClientIdCookie;

class AccessLogMiddleware
{
    public function handle($request, $next)
    {
        $response = $next($request);

        if (!$response instanceof Response) {
            return $response;
        }

        $clientId = getUserClientIdCookie($request);
        (new UserClientIdCookie($clientId))->attachTo($response);

        return $response;
    }
}
