{{--
    Tabel akun + paginasi.

    Dipisah ke partial karena dipakai dua jalur: render penuh saat halaman
    dibuka, dan render fragmen saat paginasi tanpa reload. Markup yang
    diduplikasi akan menyimpang seiring waktu.

    `data-accounts-region` adalah penanda bagi account-table.js untuk
    mengganti isi bagian ini saja.
--}}
<div data-accounts-region>
        <div class="overflow-x-auto border-t-2 border-ink/10">
            <table class="w-full min-w-[46rem] border-collapse text-left">
                <thead>
                    <tr class="bg-paper-dim">
                        <th class="px-3 py-2 text-[0.56rem] font-extrabold uppercase tracking-[0.1em] text-ink-faint">Nama Pengguna</th>
                        <th class="px-3 py-2 text-[0.56rem] font-extrabold uppercase tracking-[0.1em] text-ink-faint">NIM</th>
                        <th class="px-3 py-2 text-[0.56rem] font-extrabold uppercase tracking-[0.1em] text-ink-faint">Peran</th>
                        <th class="px-3 py-2 text-[0.56rem] font-extrabold uppercase tracking-[0.1em] text-ink-faint">Jurusan</th>
                        <th class="px-3 py-2 text-[0.56rem] font-extrabold uppercase tracking-[0.1em] text-ink-faint">Gugus</th>
                        <th class="px-3 py-2 text-right text-[0.56rem] font-extrabold uppercase tracking-[0.1em] text-ink-faint">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y-2 divide-ink/10">
                    @forelse ($users as $user)
                        <tr
                            class="transition hover:bg-paper-dim/60 {{ $user->trashed() ? 'bg-vermilion/10 opacity-70' : '' }}"
                            data-account-row
                            data-account-id="{{ $user->id }}"
                            data-name="{{ $user->name }}"
                            data-nim="{{ $user->nim }}"
                            data-email="{{ $user->email }}"
                            data-role="{{ $user->role->value }}"
                            data-role-label="{{ $user->role->label() }}"
                            data-major="{{ $user->major }}"
                            data-group-id="{{ $user->group_id }}"
                            data-group-label="{{ $user->group?->name }}"
                            data-url-create="{{ route('admin.accounts.store') }}"
                            data-url-update="{{ route('admin.accounts.update', $user) }}"
                            data-url-delete="{{ route('admin.accounts.destroy', $user) }}"
                            data-url-password="{{ route('admin.accounts.password', $user) }}"
                        >
                            <td class="px-3 py-2">
                                <div class="flex items-center gap-2">
                                    <span class="grid size-7 shrink-0 place-items-center rounded-full border-[1.5px] border-ink bg-brand-soft text-[0.6rem] font-extrabold">
                                        {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block truncate text-[0.74rem] font-bold leading-tight">{{ $user->name }}</span>
                                        <span class="block truncate text-[0.62rem] font-medium text-ink-faint">{{ $user->email }}</span>
                                    </span>
                                </div>
                            </td>
                            <td class="px-3 py-2 font-mono text-[0.68rem] font-bold">{{ $user->nim }}</td>
                            <td class="px-3 py-2">
                                <span class="stamp {{ $user->role->badgeClasses() }} text-[0.54rem]">{{ $user->role->label() }}</span>
                                @if ($user->trashed())
                                    <span class="stamp ml-1 bg-vermilion text-white text-[0.54rem]">Arsip</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-[0.7rem] font-semibold text-ink-soft">{{ $user->major ?: '—' }}</td>
                            <td class="px-3 py-2">
                                @if ($user->group)
                                    <span class="stamp bg-paper-dim text-[0.54rem]">{{ $user->group->name }}</span>
                                @else
                                    <span class="text-[0.66rem] font-semibold text-ink-faint">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                <div class="flex items-center justify-end gap-1">
                                    <button
                                        type="button"
                                        class="grid size-6 place-items-center rounded-xs border-[1.5px] border-ink bg-paper transition hover:bg-brand-soft"
                                        data-role-toggle="role-{{ $user->id }}"
                                        aria-label="Ubah peran {{ $user->name }}"
                                        title="Ubah peran & gugus"
                                    >
                                        <x-icon name="settings" class="size-3" :stroke="2" />
                                    </button>

                                    {{-- Ubah data dasar: nama, email, jurusan, gugus. --}}
                                    <button
                                        type="button"
                                        class="grid size-6 place-items-center rounded-xs border-[1.5px] border-ink bg-paper transition hover:bg-gold"
                                        data-account-action="edit"
                                        aria-label="Ubah akun {{ $user->name }}"
                                        title="Ubah akun"
                                    >
                                        <x-icon name="write" class="size-3" :stroke="2" />
                                    </button>

                                    {{-- Setel ulang sandi: tidak ada fitur lupa sandi di aplikasi ini. --}}
                                    <button
                                        type="button"
                                        class="grid size-6 place-items-center rounded-xs border-[1.5px] border-ink bg-paper transition hover:bg-gold"
                                        data-account-action="password"
                                        aria-label="Setel ulang sandi {{ $user->name }}"
                                        title="Setel ulang sandi"
                                    >
                                        <x-icon name="key" class="size-3" :stroke="2" />
                                    </button>

                                    @unless ($user->is(auth()->user()))
                                        @if ($user->trashed())
                                            {{-- Akun terarsip: satu-satunya aksi yang masuk akal adalah pulihkan. --}}
                                            <form method="POST" action="{{ route('admin.accounts.restore', $user) }}">
                                                @csrf
                                                <button
                                                    type="submit"
                                                    class="grid size-6 place-items-center rounded-xs border-[1.5px] border-ink bg-gold transition hover:bg-brand-soft"
                                                    aria-label="Pulihkan {{ $user->name }}"
                                                    title="Pulihkan akun"
                                                >
                                                    <x-icon name="unlock" class="size-3" :stroke="2.2" />
                                                </button>
                                            </form>
                                        @else
                                            <button
                                                type="button"
                                                class="grid size-6 place-items-center rounded-xs border-[1.5px] border-ink bg-paper transition hover:bg-vermilion hover:text-white"
                                                data-account-action="delete"
                                                aria-label="Hapus {{ $user->name }}"
                                                title="Hapus akun (bisa dipulihkan)"
                                            >
                                                <x-icon name="x" class="size-3" :stroke="2.4" />
                                            </button>
                                        @endif
                                    @endunless
                                </div>

                                {{-- Form ubah peran --}}
                                <form
                                    method="POST"
                                    action="{{ route('admin.accounts.role', $user) }}"
                                    id="role-{{ $user->id }}"
                                    class="mt-1.5 hidden flex-col gap-1 rounded-xs border-[1.5px] border-ink/50 bg-paper-dim p-1.5"
                                    data-submit-lock
                                >
                                    @csrf
                                    <select name="role" class="rounded-xs border-[1.5px] border-ink bg-white px-1.5 py-1 text-[0.66rem] font-semibold focus:outline-none">
                                        @foreach ($roles as $role)
                                            <option value="{{ $role->value }}" @selected($user->role === $role)>{{ $role->label() }}</option>
                                        @endforeach
                                    </select>
                                    <select name="group_id" class="rounded-xs border-[1.5px] border-ink bg-white px-1.5 py-1 text-[0.66rem] font-semibold focus:outline-none">
                                        <option value="">— Tanpa gugus —</option>
                                        @foreach ($groups as $group)
                                            <option value="{{ $group->id }}" @selected($user->group_id === $group->id)>{{ $group->name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-6 text-center text-[0.74rem] font-semibold text-ink-faint">
                                Tidak ada akun yang cocok dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="border-t-2 border-ink/10 p-3">
                <x-pagination :paginator="$users" label="akun" />
            </div>
        @endif
</div>
