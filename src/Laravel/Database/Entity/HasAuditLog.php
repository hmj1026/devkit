<?php

namespace Devkit\Laravel\Database\Entity;

use Devkit\Laravel\Audit\AbstractEntityChangeLogger;

trait HasAuditLog
{
    use AbstractEntityChangeLogger;
}
