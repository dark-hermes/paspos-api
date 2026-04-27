<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
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
            'category_id' => ['required', 'integer', Rule::exists('product_categories', 'id')],
            'brand_id' => ['required', 'integer', Rule::exists('brands', 'id')],
            'name' => ['required', 'string', 'max:64'],
            'barcode' => ['nullable', 'string', 'max:255', Rule::unique('products', 'barcode')],
            'sku' => ['required', 'string', 'max:255', Rule::unique('products', 'sku')],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'unit' => ['required', 'string', 'max:8'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
