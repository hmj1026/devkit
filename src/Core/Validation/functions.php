<?php

namespace Devkit\Core\Validation;

if (!function_exists(__NAMESPACE__ . '\\isTaiwanCellPhone')) {
    function isTaiwanCellPhone($value)
    {
        $value = preg_replace('/[\s-]+/', '', (string) $value);

        return (bool) preg_match('/^(09\d{8}|\+?8869\d{8})$/', $value);
    }
}

if (!function_exists(__NAMESPACE__ . '\\withoutSpecialCharacters')) {
    function withoutSpecialCharacters($value)
    {
        return (bool) preg_match('/^[\p{L}\p{N}\s_-]+$/u', (string) $value);
    }
}
