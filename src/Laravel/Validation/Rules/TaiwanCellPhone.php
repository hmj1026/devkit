<?php

namespace Devkit\Laravel\Validation\Rules;

use Illuminate\Contracts\Validation\Rule;

use function Devkit\Core\Validation\isTaiwanCellPhone;

class TaiwanCellPhone implements Rule
{
    public function passes($attribute, $value)
    {
        return isTaiwanCellPhone($value);
    }

    public function message()
    {
        return 'The :attribute must be a valid Taiwan cell phone number.';
    }
}
