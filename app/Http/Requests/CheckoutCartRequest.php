<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutCartRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === 'member';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'payment_method' => ['nullable', 'in:cod'],
            'shipping_name' => ['required', 'string', 'max:255'],
            'shipping_receiver_name' => ['required', 'string', 'max:255'],
            'shipping_receiver_phone' => ['required', 'string', 'max:50'],
            'shipping_address' => ['required', 'string'],
            'shipping_notes' => ['nullable', 'string'],
        ];
    }
}
