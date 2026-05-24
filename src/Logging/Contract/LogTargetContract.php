<?php

namespace Devkit\Logging\Contract;

/**
 * Strategy contract for audit-logging targets. The audit-logging trait
 * (Devkit\Laravel\Audit\AbstractEntityChangeLogger) and any other
 * code that produces structured log entries SHALL accept a
 * LogTargetContract so backends (Eloquent table, Elasticsearch index,
 * stdout JSON, etc.) are swappable without changing the producer.
 *
 * Pure PHP — no Illuminate imports.
 */
interface LogTargetContract
{
    /**
     * Persist a single log entry. The entry's shape is producer-defined
     * (a common shape from devkit-audit-logging is
     * `{entity_id, action, changes, user_id, created_at}`).
     *
     * Implementations SHOULD swallow transient backend errors and surface
     * permanent ones; the producer cannot retry without losing context.
     *
     * @param  array  $entry
     * @return void
     */
    public function save(array $entry);
}
