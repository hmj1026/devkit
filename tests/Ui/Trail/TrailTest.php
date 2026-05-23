<?php

namespace Devkit\Tests\Ui\Trail;

use Devkit\Ui\Trail\Trail;
use Devkit\Ui\Trail\TrailManager;
use Devkit\Ui\Trail\TrailTag;
use PHPUnit\Framework\TestCase;

class TrailTest extends TestCase
{
    protected function setUp(): void
    {
        TrailManager::forget();
    }

    public function testAppendItemReturnsSameTrailForChaining()
    {
        $trail = new Trail();

        $this->assertSame(
            $trail,
            $trail->appendItem('Home', '/')
        );
    }

    public function testAppendedItemsPreserveOrder()
    {
        $trail = new Trail();
        $trail->appendItem('Home', '/')->appendItem('Products', '/products');

        $items = $trail->breadcrumb()->all();

        $this->assertCount(2, $items);
        $this->assertInstanceOf(TrailTag::class, $items[0]);
        $this->assertSame('Home', $items[0]->getText());
        $this->assertSame('/', $items[0]->getHref());
        $this->assertSame('Products', $items[1]->getText());
        $this->assertSame('/products', $items[1]->getHref());
    }

    public function testAppendItemWithoutHrefStoresNull()
    {
        $trail = new Trail();
        $trail->appendItem('Home');

        $items = $trail->all();

        $this->assertSame('Home', $items[0]->getText());
        $this->assertNull($items[0]->getHref());
        $this->assertNull($items[0]['href']);
    }

    public function testPrependItemInsertsAtHead()
    {
        $trail = new Trail();
        $trail->appendItem('Products', '/products')->prependItem('Home', '/');

        $items = $trail->all();

        $this->assertSame('Home', $items[0]->getText());
        $this->assertSame('Products', $items[1]->getText());
    }

    public function testTitleJoinsTextsWithDefaultSeparator()
    {
        $trail = new Trail();
        $trail->appendItem('Home', '/')->appendItem('Products', '/products');

        // default separator is ' - '
        $this->assertSame('Home - Products', $trail->title());
    }

    public function testTitleUsesCustomSeparator()
    {
        $trail = new Trail();
        $trail->setSeparator(' > ')
            ->appendItem('Home', '/')
            ->appendItem('Products', '/products');

        $this->assertSame('Home > Products', $trail->title());
    }

    public function testTitleOfEmptyTrailIsEmpty()
    {
        $this->assertSame('', (new Trail())->title());
    }

    public function testClearRemovesAllItems()
    {
        $trail = new Trail();
        $trail->appendItem('Home', '/')->appendItem('Products', '/products');
        $trail->clear();

        $this->assertSame(array(), $trail->all());
        $this->assertSame('', $trail->title());
    }

    public function testTrailTagArrayAccessRead()
    {
        $tag = new TrailTag(array('text' => 'Home', 'href' => '/'));

        $this->assertSame('Home', $tag['text']);
        $this->assertSame('/', $tag['href']);
        $this->assertTrue(isset($tag['text']));
        $this->assertFalse(isset($tag['missing']));
        $this->assertNull($tag['missing']);
    }

    public function testTrailTagArrayAccessWrite()
    {
        $tag = new TrailTag();
        $tag['text'] = 'Updated';

        $this->assertSame('Updated', $tag->getText());
        $this->assertSame(array('text' => 'Updated'), $tag->toArray());
    }

    public function testTrailTagArrayAccessUnset()
    {
        $tag = new TrailTag(array('text' => 'Home', 'href' => '/'));
        unset($tag['href']);

        $this->assertSame('Home', $tag->getText());
        $this->assertNull($tag->getHref());
    }

    public function testTrailManagerReturnsSameInstanceForSameNamespace()
    {
        $first = TrailManager::register('main');
        $second = TrailManager::register('main');

        $this->assertSame($first, $second);
    }

    public function testTrailManagerReturnsDistinctInstancesPerNamespace()
    {
        $main = TrailManager::register('main');
        $admin = TrailManager::register('admin');

        $this->assertNotSame($main, $admin);
        $this->assertInstanceOf(Trail::class, $main);
        $this->assertInstanceOf(Trail::class, $admin);
    }

    public function testTrailManagerDefaultsToDefaultNamespace()
    {
        $explicit = TrailManager::register('default');
        $implicit = TrailManager::register();

        $this->assertSame($explicit, $implicit);
    }

    public function testTrailManagerForgetClearsNamedInstance()
    {
        $first = TrailManager::register('scratch');
        TrailManager::forget('scratch');
        $second = TrailManager::register('scratch');

        $this->assertNotSame($first, $second);
    }
}
