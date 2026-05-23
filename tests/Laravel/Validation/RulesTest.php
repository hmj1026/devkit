<?php

namespace Devkit\Tests\Laravel\Validation;

use Devkit\Laravel\Validation\Rules\TaiwanCellPhone;
use Devkit\Laravel\Validation\Rules\WithoutSpecialCharacters;
use Devkit\Tests\Laravel\TestCase;

class RulesTest extends TestCase
{
    public function testTaiwanCellPhoneRule()
    {
        $rule = new TaiwanCellPhone();

        $this->assertTrue($rule->passes('phone', '+886912345678'));
        $this->assertFalse($rule->passes('phone', '123'));
        $this->assertNotSame('', $rule->message());
    }

    public function testWithoutSpecialCharactersRule()
    {
        $rule = new WithoutSpecialCharacters();

        $this->assertTrue($rule->passes('name', 'Devkit測試123'));
        $this->assertFalse($rule->passes('name', 'Devkit!'));
        $this->assertNotSame('', $rule->message());
    }
}
