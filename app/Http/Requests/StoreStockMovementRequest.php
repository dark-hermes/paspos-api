<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockMovementRequest extends FormRequest
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
            'src_store_id' => ['required', 'integer', Rule::exists('stores', 'id')],
            'dest_store_id' => ['required', 'integer', Rule::exists('stores', 'id')],
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'type' => ['required', 'string', Rule::in(['in', 'out'])],
            'title' => ['required', 'string', 'max:32'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
