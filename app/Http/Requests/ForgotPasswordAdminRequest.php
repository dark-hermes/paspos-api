<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordAdminRequest extends FormRequest
{
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
            'email' => [
                'required',
                'email',
                'exists:users,email',
                function ($attribute, $value, $fail) {
                    $user = \App\Models\User::query()->where('email', $value)->first();
                    if ($user && ! in_array($user->role, ['main_admin', 'branch_admin'])) {
                        $fail('Email tidak terdaftar sebagai admin.');
                    }
                },
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        // No preparation needed for email
    }
}
