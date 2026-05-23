<?php

namespace Devkit\Laravel\Command\Generators;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

abstract class AbstractMakeCommand extends Command
{
    protected $signatureSuffix = '{name}';

    protected $description = 'Generate a Devkit class.';

    protected $rootNamespace = 'App';

    abstract protected function basePath();

    abstract protected function stubName();

    public function handle()
    {
        $name = trim($this->argument('name'), '\\/');
        $class = class_basename(str_replace('/', '\\', $name));
        $relative = str_replace('\\', '/', $name) . '.php';
        $path = app_path($this->basePath() . '/' . $relative);
        $namespace = $this->rootNamespace . '\\' . str_replace('/', '\\', trim($this->basePath() . '/' . dirname($relative), '/.'));
        $contents = str_replace(
            array('{{ namespace }}', '{{ class }}'),
            array($namespace, $class),
            file_get_contents($this->stubPath())
        );

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $contents);

        $this->info('Created: ' . $path);

        return 0;
    }

    protected function stubPath()
    {
        $published = base_path('stubs/vendor/devkit/' . $this->stubName());

        return file_exists($published)
            ? $published
            : __DIR__ . '/../../../../stubs/devkit/' . $this->stubName();
    }
}
