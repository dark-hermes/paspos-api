<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePosOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['main_admin', 'branch_admin', 'cashier']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'store_id' => ['required', 'exists:stores,id'],
            'customer_id' => ['nullable', 'exists:users,id', 'required_if:payment_method,pay_later'],
            'payment_method' => ['required', 'in:cash,transfer,qris,cod,pay_later'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            
            // Shipping fields allowed if they are set (though mostly for online orders, we validate them just in case)
            'type' => ['nullable', 'in:pos,online'],
            'shipping_name' => ['nullable', 'string', 'max:255', 'required_if:type,online'],
            'shipping_receiver_name' => ['nullable', 'string', 'max:255', 'required_if:type,online'],
            'shipping_receiver_phone' => ['nullable', 'string', 'max:50', 'required_if:type,online'],
            'shipping_address' => ['nullable', 'string', 'required_if:type,online'],
            'shipping_notes' => ['nullable', 'string'],
        ];
    }
}
