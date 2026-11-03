@props([
    'name',
    'label' => null,
    'options' => [],
    'selected' => null,
    'placeholder' => '— Pilih —',
    'required' => false,
    'searchable' => false,
    'grouped' => false,
    'hint' => null,
    'error' => null,
    'id' => null,
    'disabled' => false,
])

@php
    /*
     * Dropdown custom, pengganti <select> bawaan.
     *
     * Kenapa tidak <select> biasa: tampilan select berbeda-beda per sistem
     * operasi (native picker di iOS, popup abu-abu di Windows) dan tidak bisa
     * diberi border 2px bertumpuk bayangan solid ala neo-brutalism. Yang ini
     * memakai <button> + listbox ARIA, jadi tetap bisa dioperasikan keyboard
     * dan pembaca layar.
     *
     * $options bentuknya dua macam:
     *  - datar   : [value => label]
     *  - bergrup  : [grup => [value => label]]   (dipakai untuk fakultas → prodi)
     */
    $id = $id ?? 'dd-'.str_replace(['[', ']', '.'], '-', $name);
    $selected = old($name, $selected);
    $selectedLabel = null;

    // Variabel loop TIDAK boleh bernama $label/$value: keduanya prop milik
    // komponen, dan PHP mempertahankan variabel loop setelah perulangan
    // selesai. Akibatnya label field tampil sebagai nama opsi terakhir
    // ("Psikologi" bukan "Program Studi").
    if ($grouped) {
        foreach ($options as $groupLabel => $items) {
            foreach ($items as $optValue => $optLabel) {
                if ((string) $optValue === (string) $selected) {
                    $selectedLabel = $optLabel;
                }
            }
        }
    } else {
        foreach ($options as $optValue => $optLabel) {
            if ((string) $optValue === (string) $selected) {
                $selectedLabel = $optLabel;
            }
        }
    }
@endphp

<div
    class="dd"
    data-dropdown
    data-searchable="{{ $searchable ? '1' : '0' }}"
    {{ $attributes->merge(['class' => '']) }}
>
    @if ($label)
        <label for="{{ $id }}" class="block font-display text-[0.66rem] font-bold uppercase tracking-[0.08em]">
            {{ $label }}
            @if ($required)<span class="text-vermilion">*</span>@endif
        </label>
    @endif

    {{-- Nilai yang benar-benar dikirim ke server. --}}
    <input type="hidden" name="{{ $name }}" value="{{ $selected }}" data-dd-value @if($required) data-dd-required @endif>

    <button
        type="button"
        id="{{ $id }}"
        class="dd-trigger mt-1"
        data-dd-trigger
        aria-haspopup="listbox"
        aria-expanded="false"
        aria-controls="{{ $id }}-list"
        @if($disabled) disabled @endif
    >
        <span class="dd-trigger-label {{ $selectedLabel ? '' : 'is-placeholder' }}" data-dd-label>
            {{ $selectedLabel ?? $placeholder }}
        </span>
        <x-icon name="chevron-down" class="dd-caret size-3.5 shrink-0" :stroke="2.6" />
    </button>

    <div class="dd-panel" id="{{ $id }}-list" data-dd-panel role="listbox" hidden>
        @if ($searchable)
            <div class="dd-search">
                <x-icon name="search" class="size-3.5 shrink-0 text-ink-faint" :stroke="2.2" />
                <input
                    type="text"
                    class="dd-search-input"
                    data-dd-search
                    placeholder="Cari…"
                    autocomplete="off"
                    aria-label="Cari pilihan"
                >
            </div>
        @endif

        <div class="dd-options" data-dd-options>
            @if ($grouped)
                @foreach ($options as $groupLabel => $items)
                    <p class="dd-group">{{ $groupLabel }}</p>
                    @foreach ($items as $optValue => $optLabel)
                        <button
                            type="button"
                            class="dd-option {{ (string) $optValue === (string) $selected ? 'is-selected' : '' }}"
                            data-dd-option
                            data-value="{{ $optValue }}"
                            data-label="{{ $optLabel }}"
                            role="option"
                            aria-selected="{{ (string) $optValue === (string) $selected ? 'true' : 'false' }}"
                        >
                            <span class="dd-option-text">{{ $optLabel }}</span>
                            <x-icon name="check" class="dd-check size-3.5 shrink-0" :stroke="3" />
                        </button>
                    @endforeach
                @endforeach
            @else
                @foreach ($options as $optValue => $optLabel)
                    <button
                        type="button"
                        class="dd-option {{ (string) $optValue === (string) $selected ? 'is-selected' : '' }}"
                        data-dd-option
                        data-value="{{ $optValue }}"
                        data-label="{{ $optLabel }}"
                        role="option"
                        aria-selected="{{ (string) $optValue === (string) $selected ? 'true' : 'false' }}"
                    >
                        <span class="dd-option-text">{{ $optLabel }}</span>
                        <x-icon name="check" class="dd-check size-3.5 shrink-0" :stroke="3" />
                    </button>
                @endforeach
            @endif

            <p class="dd-empty" data-dd-empty hidden>Tidak ada yang cocok.</p>
        </div>
    </div>

    @if ($hint)
        <p class="mt-1 text-[0.6rem] font-medium leading-snug text-ink-faint">{{ $hint }}</p>
    @endif

    @if ($error)
        <p class="mt-1 text-[0.64rem] font-bold text-vermilion-deep">{{ $error }}</p>
    @endif
</div>