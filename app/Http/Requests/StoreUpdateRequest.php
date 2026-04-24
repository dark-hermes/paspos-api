<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUpdateRequest extends FormRequest
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
        $store = $this->route('store');
        $storeId = is_object($store) ? $store->getKey() : $store;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('stores', 'name')->ignore($storeId)],
            'address' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'type' => ['sometimes', 'required', 'string', Rule::in(['main', 'branch'])],
        ];
    }
}
