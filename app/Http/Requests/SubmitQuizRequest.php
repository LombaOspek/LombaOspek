<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitQuizRequest extends FormRequest
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
            'module_code' => ['required', 'string', 'max:20'],
            'answers' => ['required', 'array', 'min:1'],
            'answers.*' => ['nullable', 'string', 'in:A,B,C,D,a,b,c,d'],
            'started_at' => ['required', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'answers.required' => 'Belum ada jawaban yang dipilih.',
            'answers.*.in' => 'Pilihan jawaban tidak valid.',
            'started_at.required' => 'Sesi kuis tidak dikenali. Muat ulang halaman.',
        ];
    }
}
