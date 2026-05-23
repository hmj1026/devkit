<?php

namespace Devkit\Laravel\Command\Generators;

class MakeActionCommand extends AbstractMakeCommand
{
    protected $signature = 'devkit:make:action {name}';
    protected $description = 'Generate an invokable action class.';
    protected function basePath() { return 'Actions'; }
    protected function stubName() { return 'action.stub'; }
}
