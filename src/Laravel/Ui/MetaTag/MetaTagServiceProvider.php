<?php

namespace Devkit\Laravel\Ui\MetaTag;

use Devkit\Ui\MetaTag\Meta;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class MetaTagServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('devkit.meta', function () {
            return new Meta();
        });
    }

    public function boot()
    {
        Blade::directive('meta_tags', function ($expression) {
            return "<?php echo \\Devkit\\Laravel\\Ui\\MetaTag\\MetaRenderer::render(app('devkit.meta'), {$expression}); ?>";
        });
    }
}
