<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu agenda pada satu hari orientasi.
 *
 * Menghubungkan tiga hal yang sebelumnya terpisah: hari (angka), acara
 * (seminar, apel, stan), dan modul peta misi yang harus dikerjakan setelahnya.
 * Tanpa tabel ini panitia hanya punya `quests.unlock_day` — angka tanpa
 * keterangan acara apa yang mendahuluinya.
 */
class OrientationSchedule extends Model
{
    protected $table = 'orientation_schedules';

    protected $fillable = [
        'day',
        'title',
        'description',
        'starts_at',
        'ends_at',
        'location',
        'speaker',
        'kind',
        'quest_id',
        'is_mandatory',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'day' => 'integer',
            'is_mandatory' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function quest(): BelongsTo
    {
        return $this->belongsTo(Quest::class);
    }

    /**
     * Agenda pada hari tertentu, urut jam.
     *
     * Diurutkan `starts_at` lalu `sort_order` karena dua agenda bisa mulai
     * pada jam yang sama (mis. stan paralel); `sort_order` yang memutuskan.
     */
    public function scopeForDay(Builder $query, int $day): Builder
    {
        return $query->where('day', $day)
            ->orderBy('starts_at')
            ->orderBy('sort_order');
    }

    /** Rentang jam yang siap ditampilkan, mis. "08.00–10.00". */
    public function getTimeRangeAttribute(): ?string
    {
        if (! $this->starts_at) {
            return null;
        }

        $start = substr((string) $this->starts_at, 0, 5);

        if (! $this->ends_at) {
            return $start;
        }

        return $start.'–'.substr((string) $this->ends_at, 0, 5);
    }

    /**
     * Label jenis agenda untuk UI.
     */
    public function getKindLabelAttribute(): string
    {
        return match ($this->kind) {
            'opening' => 'Pembukaan',
            'seminar' => 'Seminar',
            'workshop' => 'Workshop',
            'tour' => 'Tur Kampus',
            'fair' => 'Stan & Pameran',
            'ceremony' => 'Upacara',
            'closing' => 'Penutupan',
            default => 'Sesi',
        };
    }

    /** Ikon dari App\Support\Icon yang dipakai kartu jadwal. */
    public function getKindIconAttribute(): string
    {
        return match ($this->kind) {
            'opening', 'closing', 'ceremony' => 'flag',
            'seminar' => 'podium',
            'workshop' => 'bolt',
            'tour' => 'compass',
            'fair' => 'users',
            default => 'calendar',
        };
    }
}