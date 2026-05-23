<?php

namespace Devkit\Tests\Laravel\Integration;

use Devkit\Laravel\DevkitServiceProvider;
use Devkit\Tests\Laravel\TestCase;
use Illuminate\Support\ServiceProvider;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class DevkitGeneratorCommandTest extends TestCase
{
    protected static $basePath;

    protected function getPackageProviders($app)
    {
        return array(DevkitServiceProvider::class);
    }

    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        foreach (array('logging', 'http', 'storage', 'search', 'database', 'messaging', 'ui', 'queue') as $module) {
            $app['config']->set('devkit.modules.' . $module . '.enabled', false);
        }

        $app['config']->set('devkit.commands.generators.enabled', true);
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (is_dir(self::basePath())) {
            self::removeDirectory(self::basePath());
        }

        mkdir(self::basePath() . '/app', 0777, true);
        mkdir(self::basePath() . '/config', 0777, true);
        mkdir(self::basePath() . '/stubs/vendor/devkit', 0777, true);

        $this->app->setBasePath(self::basePath());
        $this->app->useAppPath(self::basePath() . '/app');
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$basePath !== null && is_dir(self::$basePath)) {
            self::removeDirectory(self::$basePath);
        }

        parent::tearDownAfterClass();
    }

    public function testServiceGeneratorCreatesClassAtExpectedPath()
    {
        $this->artisan('devkit:make:service', array('name' => 'Account/Register/RegisterAccountService'))
            ->assertExitCode(0);

        $path = self::basePath() . '/app/Services/Account/Register/RegisterAccountService.php';

        $this->assertFileExists($path);
        $this->assertStringContainsString('namespace App\Services\Account\Register;', file_get_contents($path));
        $this->assertStringContainsString('class RegisterAccountService', file_get_contents($path));
    }

    public function testPublishedStubOverridesDefaultGeneratorStub()
    {
        file_put_contents(
            self::basePath() . '/stubs/vendor/devkit/service.stub',
            "<?php\n\nnamespace {{ namespace }};\n\nclass {{ class }}\n{\n    public const SOURCE = 'published';\n}\n"
        );

        $this->artisan('devkit:make:service', array('name' => 'Billing/BillingService'))
            ->assertExitCode(0);

        $contents = file_get_contents(self::basePath() . '/app/Services/Billing/BillingService.php');

        $this->assertStringContainsString("public const SOURCE = 'published';", $contents);
    }

    public function testInstallCommandPublishesConfigAndStubs()
    {
        $configPaths = ServiceProvider::pathsToPublish(DevkitServiceProvider::class, 'devkit-config');
        $stubPaths = ServiceProvider::pathsToPublish(DevkitServiceProvider::class, 'devkit-stubs');

        $this->assertContains(realpath(__DIR__ . '/../../../config/devkit.php'), array_map('realpath', array_keys($configPaths)));
        $this->assertContains(realpath(__DIR__ . '/../../../stubs/devkit'), array_map('realpath', array_keys($stubPaths)));
        $this->assertStringEndsWith('config/devkit.php', reset($configPaths));
        $this->assertStringEndsWith('stubs/vendor/devkit', reset($stubPaths));
    }

    protected static function basePath()
    {
        if (self::$basePath === null) {
            self::$basePath = sys_get_temp_dir() . '/devkit-generator-test-' . getmypid();
        }

        return self::$basePath;
    }

    protected static function removeDirectory($path)
    {
        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($path);
    }
}
