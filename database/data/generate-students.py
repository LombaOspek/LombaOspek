"""
Bangkitkan dataset mahasiswa OspekQuest untuk SELURUH fakultas UMK.

Bukan hanya Fakultas Teknik: OspekQuest dipakai satu universitas, jadi datanya
harus mencerminkan 6 fakultas dan 16 program studi S1 seperti yang tercantum
di PMB UMK.

Prinsip yang dipegang:
1. Nama diambil dari dataset lama lalu dikombinasikan ulang (tidak mengarang
   orang baru), deterministik lewat seed tetap.
2. Daftar fakultas & prodi berasal dari PMB UMK, bukan karangan.
3. NIM tersegmentasi per prodi agar tidak bertabrakan dengan akun demo seeder.
4. Jumlah mahasiswa per prodi TIDAK rata: prodi populer (Manajemen, PGSD)
   memang lebih besar, seperti kampus sungguhan.
5. Sebagian email dikosongkan untuk menguji fallback <NIM>@students.umk.ac.id.
6. Berkas cacat dibuat terpisah agar dataset bersih tetap bersih.
"""

import csv
import hashlib
import io
import random
from pathlib import Path

SRC = Path(r"C:\Users\Aldi\AppData\Local\hermes\attachments\mahasiswa-ospekquest-1000.csv")
OUT_DIR = Path(r"E:\ProjectWebsitev2\LombaOspek\database\data")

# 6 fakultas, 16 program studi S1 (sumber: pmb.umk.ac.id/program-studi).
# (fakultas, prodi, prefix NIM, jumlah mahasiswa)
PRODI = [
    # Fakultas Ekonomi dan Bisnis
    ("Fakultas Ekonomi dan Bisnis", "Manajemen", "202611", 130),
    ("Fakultas Ekonomi dan Bisnis", "Akuntansi", "202612", 90),

    # Fakultas Hukum
    ("Fakultas Hukum", "Ilmu Hukum", "202621", 70),

    # Fakultas Keguruan dan Ilmu Pendidikan
    ("Fakultas Keguruan dan Ilmu Pendidikan", "Pendidikan Guru Sekolah Dasar", "202631", 120),
    ("Fakultas Keguruan dan Ilmu Pendidikan", "Bimbingan dan Konseling", "202632", 85),
    ("Fakultas Keguruan dan Ilmu Pendidikan", "Pendidikan Bahasa Inggris", "202633", 75),
    ("Fakultas Keguruan dan Ilmu Pendidikan", "Pendidikan Bahasa dan Sastra Indonesia", "202634", 55),
    ("Fakultas Keguruan dan Ilmu Pendidikan", "Pendidikan Matematika", "202635", 50),

    # Fakultas Pertanian
    ("Fakultas Pertanian", "Agroteknologi", "202641", 60),
    ("Fakultas Pertanian", "Agribisnis", "202642", 55),

    # Fakultas Teknik
    ("Fakultas Teknik", "Teknik Informatika", "202651", 100),
    ("Fakultas Teknik", "Sistem Informasi", "202652", 90),
    ("Fakultas Teknik", "Teknik Mesin", "202653", 60),
    ("Fakultas Teknik", "Teknik Elektro", "202654", 50),
    ("Fakultas Teknik", "Teknik Industri", "202655", 55),

    # Fakultas Psikologi
    ("Fakultas Psikologi", "Psikologi", "202661", 65),
]

GUGUS = ["G-01", "G-02", "G-03", "G-04"]
NIM_START = 201   # 001-200 dipakai akun demo seeder
SEED = 20261


def main() -> None:
    rng = random.Random(SEED)

    rows = list(csv.reader(io.StringIO(SRC.read_text(encoding="utf-8-sig"))))
    names = [r[1].strip() for r in rows[1:] if len(r) > 1 and r[1].strip()]

    depan = sorted({n.split()[0] for n in names})
    belakang = sorted({n.split()[-1] for n in names if len(n.split()) > 1})

    taken: set[str] = set()

    def make_name() -> str:
        for _ in range(500):
            candidate = f"{rng.choice(depan)} {rng.choice(belakang)}"
            if candidate not in taken:
                taken.add(candidate)
                return candidate
        raise RuntimeError("kombinasi nama habis")

    records: list[dict] = []
    global_index = 0

    for _faculty, _major, prefix, jumlah in PRODI:
        for i in range(NIM_START, NIM_START + jumlah):
            nim = f"{prefix}{i:03d}"

            # ~18% email kosong untuk menguji fallback kolom email.
            email = "" if rng.random() < 0.18 else f"{nim}@students.umk.ac.id"

            records.append({
                "NIM": nim,
                "Nama Penuh": make_name(),
                "Jurusan": _major,
                "Email Kampus": email,
                "Kode Gugus": GUGUS[global_index % len(GUGUS)],
            })

            global_index += 1

    total = len(records)
    rng.shuffle(records)

    fields = ["NIM", "Nama Penuh", "Jurusan", "Email Kampus", "Kode Gugus"]

    def write(path: Path, data: list[dict]) -> None:
        with path.open("w", encoding="utf-8-sig", newline="") as fh:
            w = csv.DictWriter(fh, fieldnames=fields)
            w.writeheader()
            w.writerows(data)

    bersih = OUT_DIR / "mahasiswa-umk-1000.csv"
    write(bersih, records)

    # Contoh 10 baris: satu per fakultas dulu (6), lalu 4 sisanya, supaya
    # variasi fakultas langsung terlihat saat uji cepat.
    faculty_of = {m: f for f, m, _p, _j in PRODI}
    picked: list[dict] = []
    seen_faculty: set[str] = set()

    for rec in records:
        f = faculty_of[rec["Jurusan"]]
        if f not in seen_faculty:
            seen_faculty.add(f)
            picked.append(rec)
        if len(picked) == 6:
            break

    for rec in records:
        if len(picked) >= 10:
            break
        if all(rec["NIM"] != p["NIM"] for p in picked):
            picked.append(rec)

    write(OUT_DIR / "mahasiswa-umk-contoh-10.csv", picked[:10])

    cacat = OUT_DIR / "mahasiswa-umk-contoh-cacat.csv"
    write(cacat, [
        records[0],                                       # valid
        {**records[1], "NIM": records[0]["NIM"]},         # NIM duplikat
        {**records[2], "Nama Penuh": ""},                 # nama kosong
        {**records[3], "Kode Gugus": "G-99"},             # gugus tak dikenal
        {**records[4], "NIM": "12345"},                   # NIM bukan 9 digit
        {**records[5], "Jurusan": ""},                    # jurusan kosong
        {**records[6], "Email Kampus": ""},               # email kosong
    ])

    print(f"bersih : {bersih.name} ({total} baris)")
    print("contoh : mahasiswa-umk-contoh-10.csv (10 baris, 6 fakultas)")
    print(f"cacat  : {cacat.name} (7 baris, 6 cacat disengaja)")
    print()
    print(f"{'Fakultas':42} {'Prodi':40} {'Jml':>4}  NIM")
    print("-" * 96)
    for fakultas, major, prefix, jumlah in PRODI:
        print(f"{fakultas:42} {major:40} {jumlah:4}  {prefix}{NIM_START:03d}..")

    per_fak: dict[str, int] = {}
    for rec in records:
        f = faculty_of[rec["Jurusan"]]
        per_fak[f] = per_fak.get(f, 0) + 1

    print()
    for fakultas, jumlah in sorted(per_fak.items()):
        print(f"  {fakultas:44} {jumlah:4}")

    kosong = sum(1 for r in records if not r["Email Kampus"])
    print(f"\nemail kosong (uji fallback): {kosong}")
    print("gugus:", dict(sorted({g: sum(1 for r in records if r['Kode Gugus'] == g) for g in GUGUS}.items())))
    print("prodi terpakai:", len({r['Jurusan'] for r in records}))
    print(f"sha256(bersih) = {hashlib.sha256(bersih.read_bytes()).hexdigest()[:16]}")


if __name__ == "__main__":
    main()
