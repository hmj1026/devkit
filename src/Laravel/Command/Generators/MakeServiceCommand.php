<?php

namespace Devkit\Laravel\Command\Generators;

class MakeServiceCommand extends AbstractMakeCommand
{
    protected $signature = 'devkit:make:service {name}';
    protected $description = 'Generate a service class.';
    protected function basePath() { return 'Services'; }
    protected function stubName() { return 'service.stub'; }
}
