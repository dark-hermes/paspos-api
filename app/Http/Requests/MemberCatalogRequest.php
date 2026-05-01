<?php

namespace App\Http\Requests;

use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;

class MemberCatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'search.string' => 'Kata kunci pencarian harus berupa teks.',
            'search.max' => 'Kata kunci pencarian terlalu panjang.',
        ];
    }

    public function validateBranch(int $branch): void
    {
        if (! Store::query()->whereKey($branch)->exists()) {
            abort(404, 'Branch not found.');
        }
    }
}
