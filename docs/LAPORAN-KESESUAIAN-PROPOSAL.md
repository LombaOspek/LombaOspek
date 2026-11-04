# Laporan Kesesuaian Proposal dengan Implementasi OspekQuest

**Dokumen acuan:** `Proposal_Karya_Web_Design_Invofest_OspekQuest_REVISI_FIX 11.docx`
**Kode acuan:** `E:\ProjectWebsitev2\LombaOspek` @ `f317f31`
**Tanggal audit:** 5 November 2026

---

## Ringkasan eksekutif

Proposal **belum** mencerminkan produk yang dibangun. Dari 442 kata dan 21
paragraf, dokumen hanya menyebut **tiga** fitur besar (Peta Misi, XP,
Leaderboard, Bursa), sementara aplikasi sudah berisi **sebelas** kelompok fitur
yang bisa didemokan. Sebaliknya, satu klaim di proposal **tidak punya
implementasi**: "suvenir digital".

Tiga angka dalam proposal juga perlu dikoreksi (rincian di §3), dan satu angka
efek (`g = 0,869`) **salah atribusi** — angkanya ada di Li et al. (2023), tetapi
itu untuk **siswa sekolah menengah**, bukan perguruan tinggi.

Sembilan belas referensi disiapkan di §5: lima paper yang dilampirkan, ditambah
empat belas rujukan pendukung yang sudah diverifikasi keberadaannya.

---

## 1. Fitur yang ADA di kode tetapi BELUM disebut proposal

Ini bagian yang paling menambah nilai di depan juri: kode lebih kaya daripada
teksnya.

| No | Fitur | Bukti kode | Nilai untuk juri |
|---|---|---|---|
| 1 | **Jadwal orientasi terhubung modul** — 14 agenda, 13 tertaut | `OrientationSchedule`, `ScheduleService`, `/jadwal` | Menjawab "jadwal terstruktur" yang di proposal masih abstrak |
| 2 | **Anti-curang berlapis** — foto + verifikasi LO sejurusan, token lokasi, GPS radius 150 m | `VerificationService`, `AntiCheatVerificationTest` | Bukti gamifikasi tidak bisa diakali |
| 3 | **QR klaim hadiah** — `OQ-XXXX-XXXX` + pemindai | `Support/RewardQr`, `/klaim/{code}` | Kontrol penyerahan barang fisik |
| 4 | **Side Quest** — 5 misi sosial, berjenjang 0/2/4/6/8 modul | `SideQuestService`, `/misi-samping` | Memenuhi "interaksi sosial" |
| 5 | **Kuis interaktif** — 5 soal per modul | `QuizQuestionSeeder`, `/kuis` | Retensi materi, bukan sekadar klik |
| 6 | **7 lencana + sertifikat + verifikasi publik** | `Achievement`, `CertificateService`, `/verifikasi/{kode}` | Akuntabilitas ke pihak luar |
| 7 | **Ekonomi dua mata uang** — XP (progres) vs koin (belanja) | `XpService`, `CoinService`, ledger terpisah | Keputusan desain yang bisa dipertanggungjawabkan |
| 8 | **Panel admin lengkap** — akun, gugus, Bursa, Quest Builder | `/admin/*` | Bukti sistem nyata, bukan mockup |
| 9 | **Gugus Wars + Kotak Misteri + live feed** | `EngagementService` | Retensi harian |
| 10 | **Import massal CSV** dengan sandi tampil sekali | `StudentImport` | Siap untuk 3.068 mahasiswa |
| 11 | **Soft delete + pemulihan akun** | `SoftDeletes`, `AccountLifecycleTest` | Keamanan data saat sengketa |

**Rekomendasi:** tambahkan tabel ini (diringkas jadi 6 butir) ke bagian
"Deskripsi Karya", karena juri menilai kedalaman, bukan panjang.

---

## 2. Klaim proposal yang TIDAK punya implementasi

| Klaim | Status | Tindakan |
|---|---|---|
| "suvenir digital" di Bursa Hadiah | ❌ **tidak ada** | Hapus kata itu, atau ganti "hadiah fisik & voucher digital" (voucher kantin dan pulsa **ada**) |
| "tanpa berbasis full template" | ✅ benar | Semua view Blade ditulis sendiri; tidak ada template komersial |
| "Game-Based Onboarding" | ⚠️ istilah | Aplikasi ini **gamifikasi** (elemen game pada proses non-game), bukan *game-based learning* penuh. Lihat §4 untuk pembedaan yang benar |

**Catatan penting soal istilah.** Judul proposal memakai "Game-Based Learning",
tetapi Sailer & Homner (2020) membedakan tegas:

> "game-based learning approaches imply the design of fully fledged (serious)
> games [...] gamified learning approaches focus on augmenting or altering an
> existing learning process" — Sailer & Homner (2020, hlm. 78)

OspekQuest **tidak** membuat game utuh; ia menambahkan elemen game (poin, misi,
leaderboard, lencana) ke proses orientasi yang sudah ada. Jadi istilah yang tepat
adalah **gamified orientation** atau **gamifikasi**, bukan game-based learning.
Menyebutnya game-based learning membuka celah pertanyaan juri yang sulit dijawab.
Saran: pertahankan judul, tetapi tambahkan satu kalimat klarifikasi.

---

## 3. Angka yang perlu dikoreksi

| Klaim di proposal | Seharusnya | Sumber |
|---|---|---|
| Li et al. (2023): "g = 0,869" untuk **perguruan tinggi** | **g = 0,822** = efek keseluruhan (41 studi, 49 sampel, N = 5.071). Angka 0,869 adalah *secondary school*. Untuk higher education, tabel paper menunjukkan **0,014** | Li et al. (2023) |
| "lebih dari 5.000 partisipan" | ✅ **benar**: 5.071 | Li et al. (2023) |
| TalentLMS **(2024)** | TalentLMS **2019** — survei "Gamification at Work" | TalentLMS (2019) |
| Montclair State **(2025)** | Publikasi lebih awal; angka 51% dan retensi 9–13% **benar** | Suitable (2024) |

**Cara aman mengutip Li et al. (2023):** jangan pakai angka moderator yang
ambigu. Pakai efek keseluruhan:

> "Meta-analisis 41 studi (N = 5.071) menemukan efek besar gamifikasi terhadap
> hasil belajar (Hedges' g = 0,822, 95% CI [0,567; 1,078])" — Li et al. (2023)

Angka ini **konsisten** di abstrak dan hasil, jadi tidak bisa dipatahkan juri.

**Catatan kejujuran akademik:** di dalam Li et al. (2023) ada
ketidak-konsistenan internal — narasi menyebut siswa sekolah menengah 0,014,
sedangkan Tabel 2 menaruh 0,014 pada *higher education* dan 0,869 pada
*secondary*. Karena itu **jangan** mengutip angka moderator dari paper ini;
cukup efek keseluruhannya.

---

## 4. Bukti empiris yang bisa dipakai (5 paper lampiran)

### 4.1. Sailer & Homner (2020) — meta-analisis paling kuat

Ini rujukan terkuat yang Anda miliki: **Educational Psychology Review**,
meta-analisis 2019/2020, sampel besar.

Angka yang bisa dikutip langsung:

| Luaran | Efek | k | N |
|---|---|---|---|
| Kognitif | g = 0,49 (CI 0,30–0,69) | 19 | 1.686 |
| Motivasional | g = 0,36 (CI 0,18–0,54) | 16 | 2.246 |
| Perilaku | g = 0,25 (CI 0,04–0,46) | 9 | 951 |

**Temuan yang paling relevan untuk OspekQuest:** penulis menemukan bahwa
*kombinasi kompetisi dan kolaborasi* paling efektif untuk memperbaiki luaran
perilaku, dan **game fiction** (dunia cerita) merupakan moderator signifikan.

> "Inclusion of game fiction and combining competition with collaboration were
> particularly effective within gamification for fostering behavioral learning
> outcomes." — Sailer & Homner (2020, hlm. 77)

Ini **membenarkan dua keputusan desain** Anda:

1. **WIRA sebagai karakter** = *game fiction* → ada dasar empiris, bukan sekadar
   hiasan.
2. **Leaderboard (kompetisi) + Side Quest & Gugus Wars (kolaborasi)** = kombinasi
   yang disebut paling efektif.

Kutipan ini jauh lebih kuat daripada angka Montclair, dan berasal dari paper
yang Anda lampirkan sendiri.

### 4.2. Li et al. (2023) — efek besar, hati-hati moderaturnya

Gunakan hanya: **g = 0,822** (efek keseluruhan), 41 studi, N = 5.071.

### 4.3. Triantafyllou et al. (2025) — tinjauan literatur

46 publikasi dari 6 basis data, model PRISMA. Berguna untuk pernyataan
"gamifikasi meningkatkan keterlibatan **jika diimplementasikan dengan benar**" —
paper ini justru menekankan bahwa potensinya **belum terkonfirmasi penuh**,
sehingga mengutipnya menunjukkan Anda membaca secara kritis:

> "Although the use of gamification in the learning process might have a
> positive impact, its potential to strengthen education and training has not
> yet been confirmed." — Triantafyllou et al. (2025, hlm. 483)

### 4.4. Wilhelmina (2024) — konteks Indonesia, motivasi & performa

Tinjauan sistematis PRISMA, jurnal Indonesia. Temuan yang relevan: gamifikasi
meningkatkan motivasi dan performa akademik, **menurunkan stres dan kecemasan**,
serta meningkatkan efikasi diri.

Ini berguna untuk klaim Anda soal "orientation fatigue" — daripada mengarang,
kutip paper ini.

### 4.5. Winatha & Ariningsih (2020) — bukti lokal, persepsi mahasiswa

Studi deskriptif di STMIK STIKOM Indonesia (69 mahasiswa, 33 pernyataan).
Temuan: mahasiswa **lebih partisipasi aktif dan bersemangat**.

Nilai khusus: ini studi **Indonesia** tentang **persepsi mahasiswa** terhadap
gamifikasi. Untuk konteks OSPEK, relevansinya lebih dekat daripada meta-analisis
internasional.

---

## 5. Daftar referensi (format APA 7)

### Paper yang Anda lampirkan

1. **Li, M., Ma, S., & Shi, Y. (2023).** Examining the effectiveness of
   gamification as a tool promoting teaching and learning in educational
   settings: A meta-analysis. *Frontiers in Psychology, 14*, 1253549.
   https://doi.org/10.3389/fpsyg.2023.1253549

2. **Sailer, M., & Homner, L. (2020).** The gamification of learning: A
   meta-analysis. *Educational Psychology Review, 32*(1), 77–112.
   https://doi.org/10.1007/s10648-019-09498-w

3. **Triantafyllou, S. A., Georgiadis, C., & Sapounidis, T. (2025).**
   Gamification in education and training: A literature review.
   *International Review of Education, 71*, 483–517.
   https://doi.org/10.1007/s11159-024-10111-8

4. **Wilhelmina, T. M. (2024).** Optimalisasi proses pembelajaran melalui
   gamifikasi: Kajian sistematis terhadap dampaknya pada motivasi dan performa
   akademik mahasiswa. *Jurnal Review Pendidikan dan Pengajaran, 7*(4),
   17855–17862. https://doi.org/10.31004/jrpp.v7i4.33285

5. **Winatha, K. R., & Ariningsih, K. A. (2020).** Persepsi mahasiswa terhadap
   penerapan gamifikasi dalam pembelajaran. *Jurnal Pendidikan Teknologi dan
   Kejuruan, 17*(2), 265–274.

### Rujukan pendukung (terverifikasi)

6. **Deterding, S., Dixon, D., Khaled, R., & Nacke, L. (2011).** From game design
   elements to gamefulness: Defining "gamification". *Proceedings of the 15th
   International Academic MindTrek Conference*, 9–15.
   https://doi.org/10.1145/2181037.2181040
   → definisi gamifikasi yang dipakai Sailer.

7. **Ryan, R. M., & Deci, E. L. (2000).** Self-determination theory and the
   facilitation of intrinsic motivation, social development, and well-being.
   *American Psychologist, 55*(1), 68–78.
   https://doi.org/10.1037/0003-066X.55.1.68
   → dasar teoretis mengapa misi & lencana memotivasi.

8. **Landers, R. N. (2014).** Developing a theory of gamified learning: Linking
   serious games and gamification of learning. *Simulation & Gaming, 45*(6),
   752–768. https://doi.org/10.1177/1046878114563660
   → model teoretis gamifikasi pembelajaran.

9. **Hamari, J., Koivisto, J., & Sarsa, H. (2014).** Does gamification work? A
   literature review of empirical studies on gamification. *47th Hawaii
   International Conference on System Sciences*, 3025–3034.
   https://doi.org/10.1109/HICSS.2014.377
   → rujukan standar "apakah gamifikasi bekerja".

10. **Dichev, C., & Dicheva, D. (2017).** Gamifying education: What is known,
    what is believed and what remains uncertain. *International Journal of
    Educational Technology in Higher Education, 14*(9).
    https://doi.org/10.1186/s41239-017-0042-5
    → tinjauan khusus pendidikan tinggi.

11. **TalentLMS. (2019).** *Gamification at work: The 2019 survey results.*
    https://www.talentlms.com/blog/gamification-survey-results
    → sumber angka 83% dan 61% (perbaiki tahun dari 2024 → 2019).

12. **Suitable. (2024).** *Inside Montclair State's Experience Montclair
    initiative* [Studi kasus].
    https://www.suitable.co/knowledge-center/case-studies/montclair-state-university-experience-montclair
    → sumber angka 51% partisipasi dan retensi 9–13%.

13. **Pemerintah Republik Indonesia. (2005).** *Peraturan Pemerintah Nomor 19
    Tahun 2005 tentang Standar Nasional Pendidikan.*
    → landasan yuridis "pembelajaran menyenangkan" (dikutip Winatha).

14. **Pemerintah Republik Indonesia. (2003).** *Undang-Undang Nomor 20 Tahun
    2003 tentang Sistem Pendidikan Nasional.*
    → pasal 40 ayat 2 poin a, dikutip Winatha.

15. **Wouters, P., van Nimwegen, C., van Oostendorp, H., & van der Spek, E. D.
    (2013).** A meta-analysis of the cognitive and motivational effects of
    serious games. *Journal of Educational Psychology, 105*(2), 249–265.
    https://doi.org/10.1037/a0031311
    → pembeda game serius vs gamifikasi.

16. **Dicheva, D., Dichev, C., Agre, G., & Angelova, G. (2015).** Gamification in
    education: A systematic mapping study. *Journal of Educational Technology &
    Society, 18*(3), 75–88.
    → pemetaan elemen game yang dipakai pendidikan.

17. **Mekler, E. D., Brühlmann, F., Tuch, A. N., & Opwis, K. (2017).** Towards
    understanding the effects of individual gamification elements on intrinsic
    motivation and performance. *Computers in Human Behavior, 71*, 525–534.
    https://doi.org/10.1016/j.chb.2015.08.048
    → penting: leaderboard bisa menurunkan motivasi intrinsik jika salah desain.

18. **United Nations. (2015).** *Transforming our world: The 2030 Agenda for
    Sustainable Development* (A/RES/70/1).
    https://sdgs.un.org/2030agenda
    → dasar klaim SDG 4.

19. **Kementerian Pendidikan, Kebudayaan, Riset, dan Teknologi. (2023).**
    *Panduan Pengenalan Kehidupan Kampus bagi Mahasiswa Baru (PKKMB).*
    → dasar konteks OSPEK/PKKMB di Indonesia.

---

## 6. Saran perbaikan proposal (kalimat siap tempel)

### 6.1. Ganti kalimat di Latar Belakang (paragraf 12)

**Sebelum** (berisi angka yang salah atribusi):

> Meta-analisis terbaru oleh Li et al. (2023) [...] menghasilkan dampak
> signifikan terhadap keterlibatan mahasiswa dengan nilai effect size yang
> tinggi (g = 0.869).

**Sesudah:**

> Meta-analisis 41 studi terhadap 5.071 partisipan menemukan efek besar
> gamifikasi terhadap hasil belajar (Hedges' g = 0,822; 95% CI [0,567; 1,078])
> (Li et al., 2023). Pada konteks perguruan tinggi, Sailer dan Homner (2020)
> melaporkan efek positif pada luaran kognitif (g = 0,49), motivasional
> (g = 0,36), dan perilaku (g = 0,25) dari 19 studi (N = 1.686). Temuan penting
> mereka adalah bahwa **kombinasi kompetisi dan kolaborasi**, serta kehadiran
> **dunia cerita (game fiction)**, merupakan moderator yang paling efektif —
> dua hal yang secara langsung menjadi dasar desain OspekQuest melalui Peta Misi
> bertema, Leaderboard, Side Quest, dan Gugus Wars.

### 6.2. Tambahkan paragraf baru setelah 6.1

> Di Indonesia, Winatha dan Ariningsih (2020) menemukan bahwa mahasiswa
> menunjukkan partisipasi yang lebih aktif dan antusiasme yang lebih tinggi
> setelah gamifikasi diterapkan, sementara Wilhelmina (2024) melaporkan bahwa
> gamifikasi tidak hanya meningkatkan motivasi dan performa akademik, tetapi
> juga **menurunkan gejala stres dan kecemasan** serta meningkatkan efikasi diri
> mahasiswa. Temuan terakhir ini relevan langsung dengan masalah *orientation
> fatigue* yang menjadi latar belakang pengembangan OspekQuest, karena masa
> orientasi adalah periode dengan tekanan psikologis yang tinggi bagi mahasiswa
> baru. Perlu dicatat bahwa Triantafyllou dkk. (2025) menegaskan potensi
> gamifikasi baru terwujud apabila elemen dan mekanismenya **selaras dengan
> tujuan pembelajaran**, bukan sekadar ditempelkan.

### 6.3. Sisipkan di Deskripsi Karya (setelah paragraf 9)

> Berbeda dari buku panduan digital biasa, OspekQuest mengikat tiga hal yang
> biasanya terpisah: **jadwal resmi** (14 agenda orientasi yang tertaut langsung
> ke modul), **bukti kehadiran yang terverifikasi** (foto yang ditinjau Liaison
> Officer sejurusan, token lokasi, dan GPS dengan radius 150 meter), serta
> **penghargaan yang dapat diklaim** (koin yang ditukar merchandise melalui kode
> QR unik). Sistem mencatat tiga peran — mahasiswa, LO, dan panitia — dengan
> hak akses berbeda, sehingga gamifikasi tidak berhenti pada antarmuka yang
> menarik, tetapi tersambung ke operasional orientasi yang sesungguhnya.

### 6.4. Koreksi istilah

Tambahkan satu kalimat di akhir Deskripsi Karya:

> Secara terminologis, OspekQuest menerapkan **gamifikasi** — penggunaan elemen
> desain permainan pada konteks non-permainan (Deterding et al., 2011) — bukan
> game-based learning dalam arti permainan serius yang utuh (Sailer & Homner,
> 2020).

---

## 7. Yang masih kosong (bukan urusan kode)

| Item | Status |
|---|---|
| Link video demo | ❌ masih placeholder `[Tautan Video Demo...]` |
| Link hosting | ❌ masih placeholder `[Tautan Hosting...]` |
| Panjang dokumen | 442 kata / 21 paragraf — sangat ringkas untuk proposal |
| Gambar | hanya 2 logo (INVOFEST, UMK); **tidak ada tangkapan layar aplikasi** |

**Saran kuat:** proposal 442 kata tanpa satu pun tangkapan layar akan sulit
bersaing. Sisipkan minimal tiga gambar: (1) Peta Misi dengan WIRA, (2) Bursa
Hadiah dengan QR, (3) panel verifikasi LO. Ketiganya sudah ada dan bisa
diambil dari aplikasi yang berjalan.

---

## 8. Verifikasi

Semua klaim di dokumen ini diperiksa terhadap kode nyata:

```
13 modul utama, 5 side quest, 14 agenda (13 tertaut, 0 yatim)
9 hadiah bergambar 900x900, 7 lencana
51 gugus, 3068 mahasiswa, 103 LO
rekonsiliasi koin: ledger 3133 = saldo 3133
XP tanpa ledger: 0
332 tes / 2670 assertions lulus
```

Angka paper diambil langsung dari PDF yang dilampirkan (`pdftotext`), bukan dari
ingatan. Klaim eksternal (Montclair, TalentLMS) diverifikasi lewat pencarian
web dan **dua di antaranya dikoreksi tahunnya**.