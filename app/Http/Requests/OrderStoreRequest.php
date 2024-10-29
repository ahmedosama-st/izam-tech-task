<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class OrderStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'products' => 'required|array',
            // NOTE: could allow repeating product ids, but will have to aggregate the quantities (keeping it simple for now)
            'products.*.id' => 'required|exists:products,id|distinct',
            'products.*.quantity' => 'required|integer|min:1',
        ];
    }
}
