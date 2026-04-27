<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderShippingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['main_admin', 'branch_admin'], true);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'shipping_fee' => ['required', 'numeric', 'min:0'],
            'courier_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
