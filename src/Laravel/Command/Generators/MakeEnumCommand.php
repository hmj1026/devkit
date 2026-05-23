<?php

namespace Devkit\Laravel\Command\Generators;

class MakeEnumCommand extends AbstractMakeCommand
{
    protected $signature = 'devkit:make:enum {name}';
    protected $description = 'Generate a Devkit AbstractEnum subclass.';
    protected function basePath() { return 'Enums'; }
    protected function stubName() { return 'enum.stub'; }
}
