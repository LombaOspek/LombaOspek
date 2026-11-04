{{--
    Fragmen untuk paginasi tanpa reload.

    Hanya berisi dua bagian yang berubah saat berpindah halaman: daftar gugus
    dan tabel akun. Header, sidebar, form, dan modal tidak dikirim ulang -
    dengan 3.000+ akun, mengirim seluruh halaman setiap klik halaman adalah
    pemborosan yang terasa di jaringan kampus.

    Dipakai oleh account-table.js; tanpa JavaScript, tautan paginasi tetap
    berupa <a href> biasa dan halaman termuat penuh seperti sebelumnya.
--}}
<div data-fragment-group-list>
    <x-group-list :groups="$groups" />
</div>

<div data-fragment-accounts>
    @include('pages.admin.partials.accounts-table', [
        'users' => $users,
        'filters' => $filters,
        'roles' => $roles,
        'groups' => $allGroups,
    ])
</div>