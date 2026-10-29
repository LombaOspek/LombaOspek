<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
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
            'sound_effects' => ['nullable', 'boolean'],
            'email_notifications' => ['nullable', 'boolean'],
            'whatsapp_notifications' => ['nullable', 'boolean'],
            'reduced_motion' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Checkbox yang tidak dicentang tidak dikirim browser. `$request->boolean()`
     * tanpa default true akan salah menganggapnya "tidak berubah" — di sini
     * semua toggle selalu ditulis eksplisit sebagai true/false.
     *
     * @return array<string, bool>
     */
    public function toggles(): array
    {
        return [
            'sound_effects' => $this->boolean('sound_effects'),
            'email_notifications' => $this->boolean('email_notifications'),
            'whatsapp_notifications' => $this->boolean('whatsapp_notifications'),
            'dark_mode' => $this->boolean('reduced_motion'),
        ];
    }
}
