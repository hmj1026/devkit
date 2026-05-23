<?php

namespace Devkit\Laravel\Audit;

use Devkit\Logging\Contract\LogTargetContract;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

trait AbstractEntityChangeLogger
{
    /**
     * @var LogTargetContract|null
     */
    protected static $auditLogTarget;

    public static function bootAbstractEntityChangeLogger()
    {
        static::created(function ($model) {
            $model->writeAuditLog('created', $model->auditCreatedChanges());
        });

        static::updated(function ($model) {
            $changes = $model->auditUpdatedChanges();

            if ($changes !== array()) {
                $model->writeAuditLog('updated', $changes);
            }
        });

        static::deleting(function ($model) {
            $model->writeAuditLog('deleting', $model->auditDeletedChanges());
        });
    }

    public static function setAuditLogTarget(LogTargetContract $target = null)
    {
        static::$auditLogTarget = $target;
    }

    protected function auditCreatedChanges()
    {
        return $this->attributesToArray();
    }

    protected function auditUpdatedChanges()
    {
        $changes = array();

        foreach ($this->getChanges() as $key => $to) {
            if (in_array($key, $this->auditIgnoredColumns(), true)) {
                continue;
            }

            $changes[$key] = array(
                'from' => method_exists($this, 'getRawOriginal') ? $this->getRawOriginal($key) : $this->getOriginal($key),
                'to' => $to,
            );
        }

        return $changes;
    }

    protected function auditDeletedChanges()
    {
        return $this->getOriginal();
    }

    protected function auditIgnoredColumns()
    {
        return array('created_at', 'updated_at');
    }

    protected function writeAuditLog($action, array $changes)
    {
        $target = static::$auditLogTarget ?: $this->resolveAuditLogTarget();

        if (!$target) {
            return;
        }

        $target->save(array(
            'entity_type' => get_class($this),
            'entity_table' => method_exists($this, 'getTable') ? $this->getTable() : null,
            'entity_id' => $this->getKey(),
            'action' => $action,
            'changes' => $changes,
            'user_id' => $this->resolveAuditUserId(),
            'created_at' => date('Y-m-d H:i:s'),
        ));
    }

    protected function resolveAuditLogTarget()
    {
        $target = Config::get('devkit.audit.target');

        if ($target instanceof LogTargetContract) {
            return $target;
        }

        if (is_string($target) && class_exists($target)) {
            return app($target);
        }

        return null;
    }

    protected function resolveAuditUserId()
    {
        if (class_exists(Auth::class) && Auth::check()) {
            return Auth::id();
        }

        return null;
    }
}
