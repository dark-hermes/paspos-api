<?php

namespace App\Http\Requests;

use App\Support\PhoneNumberNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $phone = $this->input('phone');

        if (is_string($phone)) {
            $this->merge([
                'phone' => PhoneNumberNormalizer::normalize($phone),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->route('user');
        $userId = is_object($user) ? $user->getKey() : $user;

        return [
            'full_name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['sometimes', 'nullable', 'string', 'min:10', 'max:20', Rule::unique('users', 'phone')->ignore($userId)],
            'password' => ['sometimes', 'required', 'string', 'min:8', 'confirmed'],
            'role' => ['sometimes', 'required', 'string', Rule::in(['main_admin', 'branch_admin', 'cashier', 'member'])],
            'store_id' => ['sometimes', 'nullable', 'integer', Rule::exists('stores', 'id')],
        ];
    }
}
