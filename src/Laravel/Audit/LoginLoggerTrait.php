<?php

namespace Devkit\Laravel\Audit;

use Devkit\Laravel\Audit\Contract\LoginLogTargetContract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

trait LoginLoggerTrait
{
    public function recordLogin($guard, Request $request, LoginLogTargetContract $target = null)
    {
        $agent = new AgentSupport();
        $target = $target ?: $this->resolveLoginLogTarget();

        if (!$target) {
            return;
        }

        $target->saveLogin(array(
            'user_id' => method_exists($this, 'getKey') ? $this->getKey() : null,
            'guard' => $guard,
            'device' => $agent->device(),
            'browser' => $agent->browser(),
            'platform' => $agent->platform(),
            'ip' => $request->ip(),
            'headers' => AgentSupport::sanitizeHeaders($request->headers->all()),
            'created_at' => date('Y-m-d H:i:s'),
        ));
    }

    protected function resolveLoginLogTarget()
    {
        $target = Config::get('devkit.audit.login_target');

        if ($target instanceof LoginLogTargetContract) {
            return $target;
        }

        if (is_string($target) && class_exists($target)) {
            return app($target);
        }

        return null;
    }
}
