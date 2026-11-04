@props(['groups'])

{{--
    Daftar gugus terdaftar + paginasi.

    Dipisah menjadi komponen karena dipakai dua jalur: render penuh saat
    halaman dibuka, dan render fragmen saat paginasi tanpa reload (AJAX).
    Kalau markupnya diduplikasi, keduanya akan menyimpang seiring waktu.

    Paginasi memakai parameter `gugus_page` sendiri agar tidak bertabrakan
    dengan paginasi tabel akun (`page`).
--}}
@if ($groups->isNotEmpty())
    <div class="mt-3 border-t-2 border-dashed border-ink/25 pt-2.5">
        <div class="flex items-baseline justify-between gap-2">
            <p class="text-[0.58rem] font-extrabold uppercase tracking-[0.12em] text-ink-faint">
                Gugus terdaftar
            </p>
            <p class="font-mono text-[0.56rem] font-bold text-ink-faint">
                {{ number_format($groups->total(), 0, ',', '.') }} gugus
            </p>
        </div>

        <ul class="mt-1.5 flex flex-col gap-1">
            @foreach ($groups as $group)
                <li class="flex flex-wrap items-center justify-between gap-1.5 rounded-xs bg-paper-dim px-2 py-1">
                    <a href="{{ route('admin.groups.show', $group) }}" class="min-w-0 underline-offset-2 hover:underline" title="Kelola anggota gugus ini">
                        <span class="block truncate text-[0.68rem] font-bold">{{ $group->name }}</span>
                        <span class="block font-mono text-[0.56rem] font-medium text-ink-faint">{{ $group->study_program }}</span>
                    </a>
                    <span class="flex shrink-0 items-center gap-1.5">
                        <span class="font-mono text-[0.6rem] font-bold text-ink-faint">{{ $group->invitation_code }}</span>
                        <span class="stamp bg-paper text-[0.54rem]">{{ $group->members_count }} anggota</span>
                    </span>
                </li>
            @endforeach
        </ul>

        @if ($groups->hasPages())
            <div class="mt-2 border-t border-ink/10 pt-2">
                <x-pagination :paginator="$groups" label="gugus" data-gugus-pagination />
            </div>
        @endif
    </div>
@endif