<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStockMovementRequest extends FormRequest
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
            'src_store_id' => ['sometimes', 'required', 'integer', Rule::exists('stores', 'id')],
            'dest_store_id' => ['sometimes', 'required', 'integer', Rule::exists('stores', 'id')],
            'product_id' => ['sometimes', 'required', 'integer', Rule::exists('products', 'id')],
            'quantity' => ['sometimes', 'required', 'numeric', 'gt:0'],
            'type' => ['sometimes', 'required', 'string', Rule::in(['in', 'out'])],
            'title' => ['sometimes', 'required', 'string', 'max:32'],
            'note' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
