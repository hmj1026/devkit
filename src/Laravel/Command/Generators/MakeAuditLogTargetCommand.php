<?php

namespace Devkit\Laravel\Command\Generators;

class MakeAuditLogTargetCommand extends AbstractMakeCommand
{
    protected $signature = 'devkit:make:audit-log-target {name}';
    protected $description = 'Generate a LogTargetContract implementation.';
    protected function basePath() { return 'Audit'; }
    protected function stubName() { return 'audit-log-target.stub'; }
}
