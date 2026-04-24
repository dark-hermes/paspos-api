<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
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
        $product = $this->route('product');
        $productId = is_object($product) ? $product->getKey() : $product;

        return [
            'category_id' => ['sometimes', 'required', 'integer', Rule::exists('product_categories', 'id')],
            'brand_id' => ['sometimes', 'required', 'integer', Rule::exists('brands', 'id')],
            'name' => ['sometimes', 'required', 'string', 'max:64'],
            'barcode' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('products', 'barcode')->ignore($productId)],
            'sku' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('products', 'sku')->ignore($productId)],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'unit' => ['sometimes', 'required', 'string', 'max:8'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];
    }
}
