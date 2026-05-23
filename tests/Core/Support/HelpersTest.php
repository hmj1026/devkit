<?php

namespace Devkit\Tests\Core\Support;

use PHPUnit\Framework\TestCase;
use function Devkit\Core\Support\isJson;

class HelpersTest extends TestCase
{
    public function testIsJsonReturnsTrueForJsonObject()
    {
        $this->assertTrue(isJson('{"a":1}'));
    }

    public function testIsJsonReturnsTrueForJsonArray()
    {
        $this->assertTrue(isJson('[1,2,3]'));
    }

    public function testIsJsonReturnsTrueForJsonScalar()
    {
        $this->assertTrue(isJson('123'));
        $this->assertTrue(isJson('"hello"'));
        $this->assertTrue(isJson('true'));
        $this->assertTrue(isJson('null'));
    }

    public function testIsJsonReturnsFalseForInvalidJson()
    {
        $this->assertFalse(isJson('{invalid}'));
        $this->assertFalse(isJson('{"a":}'));
        $this->assertFalse(isJson('not json'));
    }

    public function testIsJsonReturnsFalseForEmptyString()
    {
        $this->assertFalse(isJson(''));
    }

    public function testIsJsonReturnsFalseForNonString()
    {
        $this->assertFalse(isJson(null));
        $this->assertFalse(isJson(123));
        $this->assertFalse(isJson(true));
        $this->assertFalse(isJson(array('a' => 1)));
    }
}
