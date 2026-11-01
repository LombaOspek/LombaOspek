@props(['wars'])

{{--
    Gugus Wars — adu tarik tambang XP antargugus.

    Satu-satunya angka yang masuk ke klien adalah rasio 0..1 yang sudah
    dihitung server; JS tidak menghitung ulang kekuatan gugus. Tali digeser
    lewat custom property `--war-ratio` sehingga geraknya murni CSS.
--}}
<section
    {{ $attributes->merge(['class' => 'rounded-lg border-2 border-ink bg-paper p-4 shadow-[var(--shadow-flat)]']) }}
    aria-labelledby="gugus-wars-title"
>
    <div class="flex items-start justify-between gap-2">
        <div class="min-w-0">
            <h2 id="gugus-wars-title" class="flex items-center gap-1.5 font-display text-[0.95rem] font-extrabold leading-tight">
                <x-icon name="users" class="size-4" :stroke="2.4" />
                Gugus Wars
            </h2>
            <p class="mt-0.5 text-[0.68rem] font-medium leading-snug text-ink-soft">
                Tarik tambang XP antargugus. Setiap modul yang Anda tuntaskan menambah tarikan.
            </p>
        </div>
        <span class="stamp shrink-0 bg-vermilion text-white">LIVE</span>
    </div>

    @if (! $wars['available'])
        <div class="mt-3 rounded-sm border-2 border-dashed border-ink/30 bg-paper-dim px-3 py-4 text-center">
            <p class="text-[0.72rem] font-semibold leading-snug text-ink-soft">{{ $wars['message'] }}</p>
        </div>
    @else
        @php
            $left = $wars['left'];
            $right = $wars['right'];
            $ratio = $wars['ratio'];
            $leftPct = (int) round($ratio * 100);
        @endphp

        <div class="mt-3 grid grid-cols-2 gap-2">
            @foreach ([
                ['side' => 'left', 'team' => $left, 'align' => 'text-left', 'badge' => 'bg-brand text-white'],
                ['side' => 'right', 'team' => $right, 'align' => 'text-right', 'badge' => 'bg-gold text-ink'],
            ] as $slot)
                @php
                    $isMine = $wars['my_side'] === $slot['side'];
                @endphp
                <div class="min-w-0 rounded-sm border-2 border-ink bg-paper-dim px-2 py-1.5 {{ $slot['align'] }}">
                    <p class="flex items-center gap-1 text-[0.6rem] font-extrabold uppercase tracking-[0.08em] text-ink-faint {{ $slot['side'] === 'right' ? 'justify-end' : '' }}">
                        {{ $slot['side'] === 'left' ? 'Tim Kiri' : 'Tim Kanan' }}
                        @if ($isMine)
                            <span class="stamp bg-ink text-paper text-[0.45rem]">Gugus Anda</span>
                        @endif
                    </p>
                    <p class="mt-0.5 truncate font-display text-[0.78rem] font-extrabold leading-tight">{{ $slot['team']['name'] }}</p>
                    <p class="numeric text-[0.68rem] font-bold text-ink-soft">
                        {{ number_format($slot['team']['xp'], 0, ',', '.') }} XP
                        <span class="font-semibold text-ink-faint">· {{ $slot['team']['members'] }} org</span>
                    </p>
                </div>
            @endforeach
        </div>

        {{-- Lintasan tali: garis tengah bergerigi, penanda di posisi rasio. --}}
        <div class="relative mt-3" role="img" aria-label="Tali tarik tambang: {{ $left['name'] }} {{ $leftPct }} persen, {{ $right['name'] }} {{ 100 - $leftPct }} persen">
            {{-- overflow-hidden wajib: tali digeser sampai +/-100% lebarnya, jadi
                 tanpa klip ia menonjol keluar kartu (terukur 3 elemen keluar
                 batas di 360/393/430px walau body tidak ikut melebar). --}}
            <div class="relative h-8 overflow-hidden rounded-sm border-2 border-ink bg-paper-dim">
                {{-- Kiri (biru) vs kanan (kuning): lebar tetap 50/50 supaya tali
                     terlihat seperti tali, bukan bar progres biasa. --}}
                <div class="absolute inset-y-0 left-0 w-1/2 bg-brand"></div>
                <div class="absolute inset-y-0 right-0 w-1/2 bg-gold"></div>

                {{-- Tali: digeser sesuai rasio. --}}
                <div
                    class="war-rope absolute inset-y-0 left-0 flex w-full items-center justify-center"
                    style="--war-ratio: {{ $ratio }}"
                    data-war-rope
                >
                    <span class="h-1.5 w-full bg-ink"></span>
                </div>

                {{-- Bendera tengah: menunjukkan siapa yang sedang menarik. --}}
                <div
                    class="war-flag absolute inset-y-0 left-0 flex w-full items-center justify-center"
                    style="--war-ratio: {{ $ratio }}"
                    data-war-flag
                >
                    <span class="grid size-6 place-items-center rounded-full border-2 border-ink bg-paper text-[0.5rem] font-extrabold">VS</span>
                </div>
            </div>

            {{-- Skala 0-100 di bawah tali. --}}
            <div class="mt-1 flex justify-between font-mono text-[0.55rem] font-bold text-ink-faint">
                <span>{{ $leftPct }}%</span>
                <span>imbang</span>
                <span>{{ 100 - $leftPct }}%</span>
            </div>
        </div>

        <p class="mt-2 rounded-sm border-2 border-ink bg-gold/25 px-2 py-1.5 text-[0.68rem] font-semibold leading-snug">
            {{ $wars['message'] }}
        </p>
    @endif
</section>
