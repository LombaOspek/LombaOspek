@props([
    'groups' => collect(),
    'majorsByFaculty' => [],
    'officersByMajor' => [],
])

@php
    use App\Enums\UserRole;

    $majorOptions = collect($majorsByFaculty)
        ->map(fn (array $programs) => collect($programs)->mapWithKeys(fn ($p) => [$p => $p])->all())
        ->all();
@endphp

{{--
    Modal CRUD akun: tambah, edit, reset sandi, pulihkan.

    Satu modal dipakai untuk semua aksi; isinya diisi ulang oleh
    account-actions.js dari atribut data tombol pemicu. Alasannya: tabel bisa
    berisi 10 baris x beberapa aksi, dan merender satu form tersembunyi per aksi
    membuat DOM membengkak serta bikin validasi sulit dilacak.

    Semua keputusan tetap di server. Modal ini hanya mengumpulkan input.
--}}
<div
    id="account-modal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-ink/60 p-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="account-modal-title"
    tabindex="-1"
    data-account-modal
    {{--
        Modal dibuka kembali otomatis bila ada error validasi dari server.
        Tanpa ini, halaman termuat ulang dan pesan error tersembunyi di dalam
        modal yang tertutup - pengguna tidak tahu apa yang salah.
    --}}
    @if ($errors->any())
        data-reopen="{{ old('_modal_mode', 'create') }}"
        data-reopen-id="{{ old('_modal_id') }}"
    @endif
>
    <div class="w-full max-w-lg overflow-hidden rounded-xl border-2 border-ink bg-paper shadow-[var(--shadow-flat-xl)]">

        {{-- Kepala --}}
        <div class="flex items-start justify-between gap-2 border-b-2 border-ink bg-gold/25 px-4 py-3">
            <div class="flex items-start gap-2.5">
                <span class="grid size-9 shrink-0 place-items-center rounded-sm border-2 border-ink bg-paper">
                    <x-icon name="user" class="size-5" :stroke="2" />
                </span>
                <div class="min-w-0">
                    <h2 id="account-modal-title" class="font-display text-[0.92rem] font-extrabold leading-tight" data-modal-title>
                        Tambah Akun
                    </h2>
                    <p class="mt-0.5 text-[0.64rem] font-semibold text-ink-soft" data-modal-subtitle>
                        Akun baru mendapat sandi sementara yang ditampilkan sekali.
                    </p>
                </div>
            </div>

            <button
                type="button"
                class="grid size-7 shrink-0 place-items-center rounded-xs border-2 border-ink bg-paper transition hover:bg-vermilion hover:text-white"
                data-modal-close
                aria-label="Tutup dialog"
            >
                <x-icon name="x" class="size-3.5" :stroke="2.6" />
            </button>
        </div>

        {{-- Isi: form tunggal, aksinya diganti lewat JS --}}
        <form method="POST" action="{{ route('admin.accounts.store') }}" class="max-h-[70vh] overflow-y-auto p-4" data-modal-form>
            @csrf
            <input type="hidden" name="_method" value="POST" data-modal-method>
            {{-- Mode & id akun dicatat agar modal bisa dibuka lagi setelah error validasi. --}}
            <input type="hidden" name="_modal_mode" value="{{ old('_modal_mode', 'create') }}">
            <input type="hidden" name="_modal_id" value="{{ old('_modal_id') }}">

            <div class="flex flex-col gap-2.5">
                <div data-field-name>
                    <label for="am-name" class="block font-display text-[0.66rem] font-bold uppercase tracking-[0.08em]">
                        Nama Lengkap <span class="text-vermilion">*</span>
                    </label>
                    <input
                        type="text" id="am-name" name="name" maxlength="120"
                        class="mt-1 w-full rounded-sm border-2 border-ink bg-white px-2 py-1.5 text-[0.74rem] font-semibold focus:outline-none"
                        placeholder="Contoh: Rani Puspita"
                    >
                    @error('name')<p class="mt-1 text-[0.64rem] font-bold text-vermilion-deep">{{ $message }}</p>@enderror
                </div>

                <div data-field-nim>
                    <label for="am-nim" class="block font-display text-[0.66rem] font-bold uppercase tracking-[0.08em]">
                        NIM / ID <span class="text-vermilion">*</span>
                    </label>
                    <input
                        type="text" id="am-nim" name="nim" maxlength="30"
                        class="mt-1 w-full rounded-sm border-2 border-ink bg-white px-2 py-1.5 font-mono text-[0.74rem] font-bold focus:outline-none"
                        placeholder="202653033"
                    >
                    <p class="mt-1 text-[0.6rem] font-medium text-ink-faint" data-nim-hint>
                        NIM dipakai untuk login dan tidak bisa diubah setelah dibuat.
                    </p>
                    @error('nim')<p class="mt-1 text-[0.64rem] font-bold text-vermilion-deep">{{ $message }}</p>@enderror
                </div>

                <div data-field-email>
                    <label for="am-email" class="block font-display text-[0.66rem] font-bold uppercase tracking-[0.08em]">
                        Email
                    </label>
                    <input
                        type="email" id="am-email" name="email" maxlength="150"
                        class="mt-1 w-full rounded-sm border-2 border-ink bg-white px-2 py-1.5 text-[0.74rem] font-semibold focus:outline-none"
                        placeholder="kosongkan untuk <NIM>@students.umk.ac.id"
                    >
                    @error('email')<p class="mt-1 text-[0.64rem] font-bold text-vermilion-deep">{{ $message }}</p>@enderror
                </div>

                <div class="grid gap-2.5 sm:grid-cols-2">
                    <div data-field-role>
                        <x-dropdown
                            name="role"
                            label="Peran"
                            :options="[
                                UserRole::Student->value => 'Mahasiswa',
                                UserRole::LiaisonOfficer->value => 'Liaison Officer',
                            ]"
                            :selected="UserRole::Student->value"
                            placeholder="— Pilih peran —"
                            :required="true"
                        />
                        @error('role')<p class="mt-1 text-[0.64rem] font-bold text-vermilion-deep">{{ $message }}</p>@enderror
                    </div>

                    <div data-field-major>
                        <x-dropdown
                            name="major"
                            label="Jurusan"
                            :options="$majorOptions"
                            :grouped="true"
                            :searchable="true"
                            placeholder="— Pilih jurusan —"
                            :required="true"
                            id="am-major"
                            hint="Menentukan fakultas dan gugus yang boleh dipilih."
                        />
                        @error('major')<p class="mt-1 text-[0.64rem] font-bold text-vermilion-deep">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div data-field-group>
                    <x-dropdown
                        name="group_id"
                        label="Gugus"
                        :options="collect($groups)->mapWithKeys(fn ($g) => [$g->id => $g->name.' · '.$g->study_program])->all()"
                        placeholder="— Tanpa gugus —"
                        hint="Hanya gugus dengan jurusan yang sama yang akan diterima."
                        id="am-group"
                    />
                    @error('group_id')<p class="mt-1 text-[0.64rem] font-bold text-vermilion-deep">{{ $message }}</p>@enderror
                </div>

                {{-- Peringatan hapus: konfirmasi ketik NIM/nama. --}}
                <div data-field-confirm class="hidden">
                    <div class="rounded-sm border-2 border-vermilion-deep bg-vermilion/10 p-2.5">
                        <p class="text-[0.66rem] font-bold text-vermilion-deep">
                            Akun akan dipindahkan ke arsip.
                        </p>
                        <p class="mt-1 text-[0.62rem] font-medium leading-snug text-ink-soft">
                            Riwayat XP, bukti modul, dan klaim hadiah tetap tersimpan dan bisa
                            dipulihkan nanti. Ketik <span class="font-mono font-bold" data-confirm-target></span>
                            untuk melanjutkan.
                        </p>
                        <input
                            type="text" name="confirm_identity"
                            class="mt-1.5 w-full rounded-sm border-2 border-ink bg-white px-2 py-1.5 font-mono text-[0.72rem] font-bold focus:outline-none"
                            placeholder="Ketik NIM atau nama persis"
                            autocomplete="off"
                        >
                    </div>
                    @error('confirm_identity')<p class="mt-1 text-[0.64rem] font-bold text-vermilion-deep">{{ $message }}</p>@enderror
                </div>

                {{-- Peringatan reset sandi. --}}
                <div data-field-password class="hidden">
                    <div class="rounded-sm border-2 border-ink/40 bg-paper-dim p-2.5">
                        <p class="text-[0.66rem] font-bold">Sandi baru akan dibuat acak.</p>
                        <p class="mt-1 text-[0.62rem] font-medium leading-snug text-ink-soft">
                            Sandi ditampilkan <strong>sekali</strong> setelah disimpan. Aplikasi ini
                            tidak punya fitur "lupa sandi" maupun email reset, jadi catat atau unduh
                            saat itu juga — kalau tertutup, sandi harus disetel ulang lagi.
                        </p>
                    </div>
                </div>

                <p class="text-[0.58rem] font-medium text-ink-faint" data-modal-note></p>
            </div>

            <div class="mt-3 flex flex-col-reverse gap-1.5 sm:flex-row sm:justify-end">
                <button type="button" class="btn btn-ghost btn-sm" data-modal-close>Batal</button>
                <button type="submit" class="btn btn-primary btn-sm" data-modal-submit>
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>