# Catatan Operasional Gugus

Dokumen ini mencatat aturan penempatan gugus yang **ditegakkan kode**, bukan
sekadar kesepakatan panitia. Dibuat setelah ditemukan data awal yang melanggar
aturan tersebut secara massal.

## Aturan yang berlaku

1. **Satu gugus = satu program studi.** Mahasiswa hanya boleh berada di gugus
   yang jurusannya sama dengan jurusannya.
2. **Satu LO = satu jurusan.** LO tidak boleh membimbing gugus dari jurusan
   lain. LO **boleh** membimbing beberapa gugus selama semuanya sejurusan,
   karena jumlah LO selalu jauh lebih sedikit daripada jumlah gugus.
3. **Gugus tanpa LO diperbolehkan**, tetapi tidak bisa memverifikasi bukti
   modul. Ini sengaja: menugaskan LO lintas jurusan lebih buruk daripada
   membiarkan gugus menunggu LO yang tepat.

Aturan 1 dan 2 ditegakkan di server (`AccountController::storeGroup()`,
`store()`, `update()`), sehingga tidak bisa dilewati lewat UI.

## Kenapa perlu disusun ulang

Data awal hanya punya **5 gugus untuk 17 program studi**. Akibatnya:

| Temuan | Jumlah |
|---|---|
| Mahasiswa di gugus jurusan lain | **2.241 dari 3.070** |
| Prodi dalam satu gugus (Kelompok 04) | 15 prodi |
| LO membimbing gugus beda jurusan | 1 (Kak Dimas, Sistem Informasi, membimbing gugus Akuntansi) |

Gugus Wars jadi tidak bermakna (membandingkan 789 orang campur 15 prodi), dan
aturan "LO hanya memverifikasi mahasiswa gugusnya" tidak bisa ditegakkan.

## Cara menyusun ulang

```bash
# Lihat rencananya dulu
php artisan groups:rebuild --dry-run

# Jalankan (default maksimal 70 anggota per gugus)
php artisan groups:rebuild

# Ubah batas anggota
php artisan groups:rebuild --size=50
```

Perintah ini **aman diulang**: mahasiswa yang sudah berada di gugus sejurusan
dengan ukuran wajar tidak dipindah, dan gugus lama yang tidak dipakai rencana
dihapus.

Hasil pada data nyata (3.067 mahasiswa):

```
3067 mahasiswa -> 17 prodi -> 51 gugus (maks 70 anggota)
anggota beda jurusan           : 0
kode gugus duplikat            : 0
gugus dengan LO lintas jurusan : 0
gugus tanpa LO                 : 31
```

## Membuat LO agar setiap gugus punya pembimbing

Setelah gugus disusun per prodi, awalnya hanya ada **4 LO** (semuanya Fakultas
Teknik) sehingga **31 dari 51 gugus** tidak punya pembimbing. Melonggarkan
aturan jurusan bukan jalan keluar — itu merusak validasi dan membuat Gugus Wars
tidak adil.

```bash
# Lihat rencananya
php artisan lo:generate --dry-run

# Buat 2 LO per gugus
php artisan lo:generate --per-group=2
```

Perintah ini:
- Membuat akun LO **sejurusan** dengan gugusnya, NIM berpola `LO-<prodi>-<urut>`
- Idempoten: gugus yang sudah punya cukup LO dilewati
- Menampilkan kredensial **sekali** dan menyimpannya ke
  `storage/app/lo-credentials-<tanggal>.csv`
- Menugaskan LO pertama sebagai `groups.mentor_id` untuk setiap gugus

Hasil pada data nyata: **102 LO**, 51 gugus semuanya ≥2 LO sejurusan, NIM unik.

### PERINGATAN UNTUK PRODUKSI

Akun LO hasil perintah ini adalah **akun uji untuk demo**. Pada orientasi
nyata, ganti dengan LO sungguhan sebelum hari-H karena merekalah yang menilai
bukti mahasiswa. Akun uji mudah dikenali: nama berawalan `LO ` dan email
berdomain `@lo.umk.ac.id`.

## Catatan teknis

Kode gugus memakai **inisial kata + hash 3 huruf** dari nama prodi
(`TI-B85-01`, `TM-A10-01`). Ini bukan hiasan: slug 6 huruf pernah membuat
"Pendidikan Bahasa Inggris" dan "Pendidikan Bahasa dan Sastra Indonesia"
sama-sama menjadi `PENDID`, sehingga anggota dua prodi tercampur ke satu gugus.
Ada tes regresi untuk ini (`RebuildGroupsTest`).