<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class FilterFormat implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Ensure 'filter' is an array if present
        if (! is_array($value)) {
            $fail('The :attribute must be an array.');

            return;
        }

        // Check if 'category' key exists and validate it
        if (isset($value['category'])) {
            if (! is_string($value['category']) || empty($value['category'])) {
                $fail('The "filter[category]" must be a non-empty string.');

                return;
            }
        }

        // Check if 'price' key exists and validate it
        if (isset($value['price'])) {
            $price = $value['price'];

            // Ensure the 'price' format matches "0,100"
            if (! preg_match('/^\d+,\s?\d+$/', $price)) {
                $fail('The "filter[price]" must be in the format "min,max".');

                return;
            }

            // Extract the numbers and ensure the first is less than the second
            $values = array_map('intval', explode(',', $price));

            if ($values[0] >= $values[1]) {
                $fail('The first value in "filter[price]" must be less than the second.');
            }
        }
    }
}
