<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInventoryRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'store_id' => ['sometimes', 'required', 'integer', Rule::exists('stores', 'id')],
            'product_id' => ['sometimes', 'required', 'integer', Rule::exists('products', 'id')],
            'stock' => ['sometimes', 'numeric', 'min:0'],
            'purchase_price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'selling_price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'discount_percentage' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'min_stock' => ['sometimes', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
