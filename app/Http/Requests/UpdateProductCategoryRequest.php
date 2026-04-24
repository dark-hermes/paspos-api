<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductCategoryRequest extends FormRequest
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
        $category = $this->route('product_category');
        $categoryId = is_object($category) ? $category->getKey() : $category;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:32', Rule::unique('product_categories', 'name')->ignore($categoryId)],
        ];
    }
}
