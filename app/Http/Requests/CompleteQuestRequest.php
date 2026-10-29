<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteQuestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'reflection' => ['nullable', 'string', 'min:80', 'max:1200'],
            'confirm' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reflection.min' => 'Refleksi minimal 80 karakter agar benar-benar bermakna.',
            'confirm.accepted' => 'Centang konfirmasi kehadiran terlebih dahulu.',
        ];
    }
}
