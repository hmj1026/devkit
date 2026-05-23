<?php

namespace Devkit\Laravel\Validation\Rules;

use Illuminate\Contracts\Validation\Rule;

use function Devkit\Core\Validation\withoutSpecialCharacters;

class WithoutSpecialCharacters implements Rule
{
    public function passes($attribute, $value)
    {
        return withoutSpecialCharacters($value);
    }

    public function message()
    {
        return 'The :attribute may only contain letters, numbers, spaces, underscores, and hyphens.';
    }
}
