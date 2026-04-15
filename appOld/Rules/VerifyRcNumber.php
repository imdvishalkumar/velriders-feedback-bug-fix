<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class VerifyRcNumber implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function passes($attribute, $value)
    {
        $rcNumber = $value != '' ? $value : ''; //Testing Purpose - HJ01ME5678 (valid) HJ01ME5679, HJ01ME5279 (invalid)
        $response = '';
        if($rcNumber != ''){
            $response = validateRc($rcNumber);
        }
        if($response){
            if($response != '' && isset($response['status']) && $response['status'] != '' && strtolower($response['status']) == 'valid'){
                return true;
            }           
        }
        
        return false;
    }

    public function message()
    {
        // Custom error message
        return 'RC Number is Invalid';
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->passes($attribute, $value)) {
            $fail($this->message());
        }
    }
}
