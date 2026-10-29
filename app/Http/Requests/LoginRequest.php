<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'identity' => ['required', 'string', 'max:120'],
            'password' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'identity.required' => 'NIM atau email wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'identity' => is_string($this->identity) ? trim($this->identity) : $this->identity,
        ]);
    }
}
