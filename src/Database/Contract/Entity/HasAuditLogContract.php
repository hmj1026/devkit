<?php

namespace Devkit\Database\Contract\Entity;

/**
 * Marker interface. Models implementing it trigger the audit-logging trait
 * (Devkit\Laravel\Audit\AbstractEntityChangeLogger, Wave 5) to capture
 * created / updated / deleting events and persist diffs via the configured
 * LogTargetContract.
 *
 * No methods are declared in v1: the contract exists only so application
 * code can type-hint "any audit-enabled entity" and so framework glue can
 * `instanceof`-detect. v2 may add `getAuditLogTarget()` if/when per-model
 * target overrides become a use case.
 *
 * Pure PHP — no Illuminate imports.
 */
interface HasAuditLogContract
{
}
