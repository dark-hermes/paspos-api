<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAddressRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:16'],
            'address' => ['required', 'string'],
            'notes' => ['nullable', 'string', 'max:32'],
            'receiver_name' => ['required', 'string', 'max:32'],
            'receiver_phone' => ['required', 'string', 'max:16'],
            'is_default' => ['boolean'],
        ];
    }
}
