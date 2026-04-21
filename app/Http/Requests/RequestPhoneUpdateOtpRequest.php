<?php

namespace App\Http\Requests;

use App\Support\PhoneNumberNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RequestPhoneUpdateOtpRequest extends FormRequest
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
        $newPhone = $this->input('new_phone');

        if (is_string($newPhone)) {
            $this->merge([
                'new_phone' => PhoneNumberNormalizer::normalize($newPhone),
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
        return [
            'new_phone' => [
                'required',
                'string',
                'min:10',
                'max:20',
                Rule::unique('users', 'phone')->ignore($this->user()?->id),
            ],
        ];
    }
}
