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
    {{--
        data-wars-endpoint + data-wars-ratio: engagement.js mem-polling endpoint
        ini untuk memperbarui tarikan tanpa memuat ulang halaman. Badge LIVE
        hanya dinyalakan setelah polling pertama berhasil - klaim "live" harus
        dibuktikan, bukan sekadar ditempel.
    --}}
    data-gugus-wars
    data-wars-endpoint="{{ route('engagement.summary') }}"
    data-wars-ratio="{{ $wars['available'] ? $wars['ratio'] : '' }}"
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
        {{-- Badge LIVE redup sampai polling pertama berhasil; tanpa itu,
             "LIVE" hanyalah hiasan yang tidak dibuktikan apa pun. --}}
        <span
            class="stamp shrink-0 bg-vermilion text-white opacity-40 transition-opacity duration-300"
            data-wars-live
            title="Menghubungkan…"
        >LIVE</span>
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
                        <span data-war-{{ $slot['side'] }}-xp>{{ number_format($slot['team']['xp'], 0, ',', '.') }}</span> XP
                        <span class="font-semibold text-ink-faint">· {{ $slot['team']['members'] }} org</span>
                    </p>
                </div>
            @endforeach
        </div>

        {{-- Lintasan tali: pembatas warna dan penanda VS digeser oleh rasio yang SAMA. --}}
        <div class="relative mt-3" role="img" data-war-aria aria-label="Tali tarik tambang: {{ $left['name'] }} {{ $leftPct }} persen, {{ $right['name'] }} {{ 100 - $leftPct }} persen">
            {{-- overflow-hidden wajib: penanda digeser sampai +/-100% lebarnya, jadi
                 tanpa klip ia menonjol keluar kartu (terukur 3 elemen keluar
                 batas di 360/393/430px walau body tidak ikut melebar). --}}
            <div
                class="war-arena relative h-8 overflow-hidden rounded-sm border-2 border-ink bg-paper-dim"
                style="--war-ratio: {{ $ratio }}"
                data-war-arena
            >
                {{--
                    Batang biru/kuning mengikuti --war-ratio, BUKAN 50/50 tetap.

                    Sebelumnya batang dibagi rata di tengah sementara VS bergeser
                    mengikuti rasio: pada 73% vs 27%, VS ada di 73% tetapi batas
                    warna tetap di 50% - dua penanda posisi yang saling
                    bertentangan. Sekarang keduanya memakai variabel yang sama.
                --}}
                <div
                    class="absolute inset-y-0 left-0 bg-brand"
                    style="width: calc(var(--war-ratio) * 100%)"
                    data-war-bar-left
                ></div>
                <div
                    class="absolute inset-y-0 right-0 bg-gold"
                    style="width: calc(100% - var(--war-ratio) * 100%)"
                    data-war-bar-right
                ></div>

                {{-- Garis tengah putus-putus: penanda "imbang" yang bermakna,
                     menggantikan garis hitam selebar wadah yang dulu tidak
                     menandai apa pun. --}}
                <div class="pointer-events-none absolute inset-y-0 left-1/2 w-0 -translate-x-1/2 border-l-2 border-dashed border-ink/40" aria-hidden="true"></div>

                {{-- Lingkaran VS: penanda posisi tarikan saat ini. --}}
                <div class="war-flag absolute inset-y-0 left-0 flex w-full items-center justify-center" data-war-flag>
                    <span class="grid size-6 place-items-center rounded-full border-2 border-ink bg-paper text-[0.5rem] font-extrabold">VS</span>
                </div>
            </div>

            {{-- Skala 0-100 di bawah tali. --}}
            <div class="mt-1 flex justify-between font-mono text-[0.55rem] font-bold text-ink-faint">
                <span data-war-left-pct>{{ $leftPct }}%</span>
                <span>imbang</span>
                <span data-war-right-pct>{{ 100 - $leftPct }}%</span>
            </div>
        </div>

        <p class="mt-2 rounded-sm border-2 border-ink bg-gold/25 px-2 py-1.5 text-[0.68rem] font-semibold leading-snug" data-war-message>
            {{ $wars['message'] }}
        </p>
    @endif
</section>
