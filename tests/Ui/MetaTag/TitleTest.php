<?php

namespace Devkit\Tests\Ui\MetaTag;

use Devkit\Ui\MetaTag\Title;
use PHPUnit\Framework\TestCase;

class TitleTest extends TestCase
{
    public function testDefaultSeparatorIsHyphen()
    {
        $this->assertSame(' - ', (new Title())->getSeparator());
    }

    public function testAppendOrderingPreserved()
    {
        $title = new Title();
        $title->append('A')->append('B')->append('C');

        $this->assertSame(array('A', 'B', 'C'), $title->segments());
        $this->assertSame('A - B - C', $title->render());
    }

    public function testPrependInsertsAtHead()
    {
        $title = new Title();
        $title->append('B')->append('C')->prepend('A');

        $this->assertSame(array('A', 'B', 'C'), $title->segments());
    }

    public function testSetSeparatorAffectsRender()
    {
        $title = new Title();
        $title->setSeparator(' | ')->append('X')->append('Y');

        $this->assertSame('X | Y', $title->render());
    }

    public function testRenderEmpty()
    {
        $this->assertSame('', (new Title())->render());
    }
}
