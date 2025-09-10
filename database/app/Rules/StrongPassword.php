<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\Rule;

class StrongPassword implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        // Password must be at least 8 characters long
        if (strlen($value) < 8) {
            return false;
        }

        // Password must contain at least one uppercase letter
        if (!preg_match('/[A-Z]/', $value)) {
            return false;
        }

        // Password must contain at least one lowercase letter
        if (!preg_match('/[a-z]/', $value)) {
            return false;
        }

        // Password must contain at least one number
        if (!preg_match('/[0-9]/', $value)) {
            return false;
        }

        // Password must contain at least one special character
        if (!preg_match('/[!@#$%^&*()\-_=+{};:,<.>]/', $value)) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, one number, and one special character.';
    }
}
