<?php

namespace Devkit\Laravel\Command\Generators;

class MakeHttpClientCommand extends AbstractMakeCommand
{
    protected $signature = 'devkit:make:http-client {name}';
    protected $description = 'Generate a Gateway subclass.';
    protected function basePath() { return 'Http/Clients'; }
    protected function stubName() { return 'http-client.stub'; }
}
