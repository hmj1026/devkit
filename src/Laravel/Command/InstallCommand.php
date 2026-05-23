<?php

namespace Devkit\Laravel\Command;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'devkit:install';

    protected $description = 'Publish Devkit config and stubs.';

    public function handle()
    {
        $this->call('vendor:publish', array('--tag' => 'devkit-config', '--force' => false));
        $this->call('vendor:publish', array('--tag' => 'devkit-stubs', '--force' => false));

        return 0;
    }
}
