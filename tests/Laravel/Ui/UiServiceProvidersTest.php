<?php

namespace Devkit\Tests\Laravel\Ui;

use Devkit\Laravel\Ui\Facades\MetaTags;
use Devkit\Laravel\Ui\Facades\Trail;
use Devkit\Laravel\Ui\MetaTag\MetaTagServiceProvider;
use Devkit\Laravel\Ui\Trail\TrailServiceProvider;
use Devkit\Tests\Laravel\TestCase;
use Illuminate\Support\Facades\Blade;

class UiServiceProvidersTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return array(
            TrailServiceProvider::class,
            MetaTagServiceProvider::class,
        );
    }

    public function testTrailFacadeAndHelperResolveSameTrail()
    {
        Trail::register('admin')->clear()->appendItem('Home', '/');

        $this->assertSame('Home', trail('admin')->title());
    }

    public function testMetaTagsFacadeResolvesManager()
    {
        MetaTags::reset();
        MetaTags::addScript('polyfill', '/p.js', array(), 'head', 10);

        $this->assertSame('polyfill', MetaTags::scriptsAt('head')[0]['name']);
    }

    public function testMetaTagsBladeDirectiveIsRegistered()
    {
        $compiled = Blade::compileString("@meta_tags('head')");

        $this->assertStringContainsString('Devkit\\Laravel\\Ui\\MetaTag\\MetaRenderer::render', $compiled);
    }
}
