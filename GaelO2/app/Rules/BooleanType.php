<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\InvokableRule;

class BooleanType implements InvokableRule
{
    /**
     * Run the validation rule.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     * @return void
     */
    public function __invoke($attribute, $value, $fail)
    {
        if ( !($value === true || $value ===false) ) {
            $fail('The :attribute must be a boolean.');
        }
    }
}
