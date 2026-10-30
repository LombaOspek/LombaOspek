<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    protected $fillable = [
        'name',
        'code',
        'study_program',
        'capacity',
        'badge_color',
        'invitation_code',
        'mentor_name',
        'mentor_id',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
        ];
    }

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Kode undangan resmi untuk pendaftaran aplikasi mahasiswa. Dibuat sekali
     * dan stabil agar bisa dicetak di kartu gugus.
     */
    public static function generateInvitationCode(string $name): string
    {
        $slug = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', $name) ?? '');
        $prefix = substr($slug !== '' ? $slug : 'GUGUS', 0, 6);
        $suffix = strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));

        return $prefix.'-'.$suffix;
    }
}