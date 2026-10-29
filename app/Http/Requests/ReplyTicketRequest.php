<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReplyTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isMentor() === true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'reply' => ['required', 'string', 'min:10', 'max:2000'],
            'status' => ['nullable', 'in:answered,resolved'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reply.min' => 'Jawaban minimal 10 karakter agar jelas bagi mahasiswa.',
        ];
    }
}
