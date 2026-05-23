<?php

namespace Devkit\Tests\Core\Validation;

use PHPUnit\Framework\TestCase;

use function Devkit\Core\Validation\isTaiwanCellPhone;
use function Devkit\Core\Validation\withoutSpecialCharacters;

class ValidationFunctionsTest extends TestCase
{
    public function testTaiwanCellPhoneValidation()
    {
        $this->assertTrue(isTaiwanCellPhone('0912345678'));
        $this->assertTrue(isTaiwanCellPhone('+886912345678'));
        $this->assertFalse(isTaiwanCellPhone('0812345678'));
    }

    public function testWithoutSpecialCharactersAllowsLettersNumbersAndCjk()
    {
        $this->assertTrue(withoutSpecialCharacters('abc123測試'));
        $this->assertFalse(withoutSpecialCharacters('abc<script>'));
    }
}
