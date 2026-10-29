<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHelpTicketRequest extends FormRequest
{
    /**
     * Kategori yang tersedia; dipakai juga oleh view agar tidak ada nilai liar.
     *
     * @var array<int, string>
     */
    public const CATEGORIES = [
        'Akademik & SKS',
        'Kartu Mahasiswa',
        'Fasilitas Kampus',
        'Organisasi & UKM',
        'Kesehatan',
        'Umum',
    ];

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'min:5', 'max:120'],
            'category' => ['required', 'string', Rule::in(self::CATEGORIES)],
            'message' => ['required', 'string', 'min:20', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'subject.min' => 'Subjek minimal 5 karakter.',
            'message.min' => 'Jelaskan pertanyaan Anda minimal 20 karakter agar mentor bisa menjawab tepat.',
            'category.in' => 'Kategori tidak dikenali.',
        ];
    }
}
