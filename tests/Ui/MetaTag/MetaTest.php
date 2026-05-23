<?php

namespace Devkit\Tests\Ui\MetaTag;

use Butschster\Head\Packages\Entities\OpenGraphPackage;
use Devkit\Ui\MetaTag\Meta;
use Devkit\Ui\MetaTag\Title;
use PHPUnit\Framework\TestCase;

class MetaTest extends TestCase
{
    public function testAddScriptOrdersByAscendingWeight()
    {
        $meta = new Meta();
        $meta->addScript('analytics', 'https://x/a.js', array(), 'head', 100);
        $meta->addScript('polyfill', 'https://x/p.js', array(), 'head', 10);

        $names = array_map(function ($entry) {
            return $entry['name'];
        }, $meta->scriptsAt('head'));

        $this->assertSame(array('polyfill', 'analytics'), $names);
    }

    public function testAddScriptEqualWeightPreservesInsertionOrder()
    {
        $meta = new Meta();
        $meta->addScript('A', 'https://x/a.js', array(), 'head', 50);
        $meta->addScript('B', 'https://x/b.js', array(), 'head', 50);

        $names = array_map(function ($entry) {
            return $entry['name'];
        }, $meta->scriptsAt('head'));

        $this->assertSame(array('A', 'B'), $names);
    }

    public function testAddStyleOrdersByAscendingWeightInHead()
    {
        $meta = new Meta();
        $meta->addStyle('print', '/css/print.css', array(), 100);
        $meta->addStyle('base', '/css/base.css', array(), 10);

        $names = array_map(function ($entry) {
            return $entry['name'];
        }, $meta->stylesAt('head'));

        $this->assertSame(array('base', 'print'), $names);
    }

    public function testAddTagOrdersByAscendingWeight()
    {
        $meta = new Meta();
        $meta->addTag('canonical', array('href' => '/'), 'head', 100);
        $meta->addTag('charset', array('charset' => 'utf-8'), 'head', 5);

        $names = array_map(function ($entry) {
            return $entry['name'];
        }, $meta->tagsAt('head'));

        $this->assertSame(array('charset', 'canonical'), $names);
    }

    public function testScriptsRespectPlacementBoundary()
    {
        $meta = new Meta();
        $meta->addScript('head-script', 'https://x/h.js', array(), 'head', 0);
        $meta->addScript('foot-script', 'https://x/f.js', array(), 'footer', 0);

        $this->assertCount(1, $meta->scriptsAt('head'));
        $this->assertCount(1, $meta->scriptsAt('footer'));
        $this->assertSame('head-script', $meta->scriptsAt('head')[0]['name']);
        $this->assertSame('foot-script', $meta->scriptsAt('footer')[0]['name']);
    }

    public function testAddScriptDefaultsToFooterPlacement()
    {
        $meta = new Meta();
        $meta->addScript('bundle', 'https://x/bundle.js');

        $this->assertCount(1, $meta->scriptsAt('footer'));
        $this->assertSame(array(), $meta->scriptsAt('head'));
    }

    public function testScriptsAtUnknownPlacementReturnsEmptyArray()
    {
        $this->assertSame(array(), (new Meta())->scriptsAt('nowhere'));
    }

    public function testTitleAppendsWithConfiguredSeparator()
    {
        $meta = new Meta();
        $meta->getTitle()->setSeparator(' - ');
        $meta->appendTitle('Home')->appendTitle('Site');

        $this->assertSame('Home - Site', $meta->makeTitle());
    }

    public function testAppendTitleNullIsNoOp()
    {
        $meta = new Meta();
        $meta->getTitle()->setSeparator(' | ');
        $meta->appendTitle('Home');
        $meta->appendTitle(null);
        $meta->appendTitle('Site');

        $this->assertSame('Home | Site', $meta->makeTitle());
    }

    public function testMakeTitleOfEmptyMetaReturnsEmptyString()
    {
        $this->assertSame('', (new Meta())->makeTitle());
    }

    public function testGetOpenGraphPackageLazilyCreates()
    {
        $meta = new Meta();
        $package = $meta->getOpenGraphPackage('og:product');

        $this->assertInstanceOf(OpenGraphPackage::class, $package);
    }

    public function testGetOpenGraphPackageReturnsSameInstanceOnRepeatedAccess()
    {
        $meta = new Meta();
        $first = $meta->getOpenGraphPackage('og:product');
        $second = $meta->getOpenGraphPackage('og:product');

        $this->assertSame($first, $second);
    }

    public function testGetOpenGraphPackageReturnsDistinctInstancesPerName()
    {
        $meta = new Meta();
        $a = $meta->getOpenGraphPackage('og:product');
        $b = $meta->getOpenGraphPackage('og:article');

        $this->assertNotSame($a, $b);
    }

    public function testResetClearsAllRegisteredState()
    {
        $meta = new Meta();
        $meta->addScript('s', 'x', array(), 'head', 0);
        $meta->addStyle('c', 'x', array(), 0);
        $meta->addTag('t', array(), 'head', 0);
        $meta->getOpenGraphPackage('og:p');
        $meta->appendTitle('Hi');
        $meta->reset();

        $this->assertSame(array(), $meta->scriptsAt('head'));
        $this->assertSame(array(), $meta->stylesAt('head'));
        $this->assertSame(array(), $meta->tagsAt('head'));
        $this->assertSame('', $meta->makeTitle());
        // A fresh OG package is created post-reset since the previous one was dropped.
        $this->assertInstanceOf(OpenGraphPackage::class, $meta->getOpenGraphPackage('og:p'));
    }

    public function testConstructorAcceptsCustomTitleInstance()
    {
        $title = new Title();
        $title->setSeparator(' :: ');
        $meta = new Meta($title);
        $meta->appendTitle('A')->appendTitle('B');

        $this->assertSame('A :: B', $meta->makeTitle());
        $this->assertSame($title, $meta->getTitle());
    }
}
