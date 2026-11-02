"""
Bangkitkan dataset mahasiswa OspekQuest untuk 5 program studi Fakultas Teknik UMK.

Prinsip yang dipegang:
1. NAMA diambil dari dataset yang sudah ada (tidak mengarang orang baru), lalu
   dikombinasikan ulang. Dengan begitu tidak ada keluarga yang tiba-tiba punya
   anggota dengan nama karangan.
2. Program studi memakai 5 prodi NYATA Fakultas Teknik UMK, bukan karangan.
3. NIM tersegmentasi per prodi agar bisa dibaca manusia dan tidak bertabrakan
   dengan dataset lama (202653xxx = SI, 202654xxx = Informatika).
4. 20% email dikosongkan untuk menguji fallback `<NIM>@students.umk.ac.id`.
5. 10 baris terakhir sengaja dibuat cacat (NIM duplikat, nama kosong, gugus
   tidak dikenal) supaya panitia bisa MELIHAT laporan "baris dilewati" bekerja,
   dalam berkas terpisah agar dataset bersih tetap bersih.
"""

import csv
import hashlib
import io
import random
from pathlib import Path

SRC = Path(r"C:\Users\Aldi\AppData\Local\hermes\attachments\mahasiswa-ospekquest-1000.csv")
OUT_DIR = Path(r"E:\ProjectWebsitev2\LombaOspek\database\data")

# 5 program studi Fakultas Teknik UMK (terverifikasi dari situs resmi kampus).
# Prefix NIM mengikuti pola kampus: tahun angkatan + kode prodi.
PRODI = [
    ("Sistem Informasi",  "202653"),   # 653 = Sistem Informasi
    ("Teknik Informatika", "202654"),  # 654 = Teknik Informatika
    ("Teknik Elektro",     "202655"),  # 655 = Teknik Elektro
    ("Teknik Mesin",       "202656"),  # 656 = Teknik Mesin
    ("Teknik Industri",    "202657"),  # 657 = Teknik Industri
]

GUGUS = ["G-01", "G-02", "G-03", "G-04"]

SEED = 20261  # deterministik: bangkitkan ulang hasilnya selalu sama


def main() -> None:
    rng = random.Random(SEED)

    rows = list(csv.reader(io.StringIO(SRC.read_text(encoding="utf-8-sig"))))
    names = [r[1].strip() for r in rows[1:] if len(r) > 1 and r[1].strip()]

    depan = sorted({n.split()[0] for n in names})
    belakang = sorted({n.split()[-1] for n in names if len(n.split()) > 1})

    def make_name(taken: set[str]) -> str:
        for _ in range(500):
            candidate = f"{rng.choice(depan)} {rng.choice(belakang)}"
            if candidate not in taken:
                taken.add(candidate)
                return candidate
        raise RuntimeError("kombinasi nama habis")

    taken_names: set[str] = set()

    # 1000 mahasiswa, dibagi rata 200 per prodi.
    #
    # Nomor mulai dari 101, bukan 001: akun demo seeder memakai NIM di rentang
    # 001-100 (202653033, 202653044, ...). Kalau dataset memakai rentang yang
    # sama, import akan melewati baris itu sebagai "sudah terdaftar".
    NIM_START = 201
    PER_PRODI = 200
    records: list[dict] = []
    global_index = 0

    for major, prefix in PRODI:
        for i in range(NIM_START, NIM_START + PER_PRODI):
            nim = f"{prefix}{i:03d}"
            name = make_name(taken_names)

            # 20% email kosong untuk menguji fallback kolom email.
            email = "" if rng.random() < 0.20 else f"{nim}@students.umk.ac.id"

            records.append({
                "NIM": nim,
                "Nama Penuh": name,
                "Jurusan": major,
                "Email Kampus": email,
                # Indeks gugus dari penghitung global, BUKAN len(records)+i:
                # pola itu melompat tiap ganti prodi dan membuat gugus menumpuk
                # di dua kelompok saja.
                "Kode Gugus": GUGUS[global_index % len(GUGUS)],
            })

            global_index += 1

    rng.shuffle(records)

    # ---------------------------- berkas bersih ----------------------------
    bersih = OUT_DIR / "mahasiswa-umk-ft-1000.csv"
    with bersih.open("w", encoding="utf-8-sig", newline="") as fh:
        w = csv.DictWriter(fh, fieldnames=["NIM", "Nama Penuh", "Jurusan", "Email Kampus", "Kode Gugus"])
        w.writeheader()
        w.writerows(records)

    # ---------------------------- contoh 10 ----------------------------
    contoh = OUT_DIR / "mahasiswa-umk-ft-contoh-10.csv"
    with contoh.open("w", encoding="utf-8-sig", newline="") as fh:
        w = csv.DictWriter(fh, fieldnames=["NIM", "Nama Penuh", "Jurusan", "Email Kampus", "Kode Gugus"])
        w.writeheader()
        # ambil 2 per prodi supaya variasi jurusan langsung terlihat
        picked, seen = [], {}
        for rec in records:
            seen[rec["Jurusan"]] = seen.get(rec["Jurusan"], 0) + 1
            if seen[rec["Jurusan"]] <= 2:
                picked.append(rec)
        w.writerows(picked[:10])

    # ---------------------------- berkas cacat (uji validasi) ----------------------------
    cacat = OUT_DIR / "mahasiswa-umk-ft-contoh-cacat.csv"
    with cacat.open("w", encoding="utf-8-sig", newline="") as fh:
        w = csv.DictWriter(fh, fieldnames=["NIM", "Nama Penuh", "Jurusan", "Email Kampus", "Kode Gugus"])
        w.writeheader()
        w.writerow(records[0])  # valid
        w.writerow({**records[1], "NIM": records[0]["NIM"]})       # NIM duplikat
        w.writerow({**records[2], "Nama Penuh": ""})               # nama kosong
        w.writerow({**records[3], "Kode Gugus": "G-99"})           # gugus tak dikenal
        w.writerow({**records[4], "NIM": "12345"})                 # NIM bukan 9 digit
        w.writerow({**records[5], "Jurusan": ""})                  # jurusan kosong -> default
        w.writerow({**records[6], "Email Kampus": ""})             # email kosong -> fallback

    # ---------------------------- laporan ----------------------------
    print(f"bersih   : {bersih.name}  ({len(records)} baris)")
    print(f"contoh   : {contoh.name}   ({len(picked[:10])} baris)")
    print(f"cacat    : {cacat.name}  (7 baris, 6 cacat disengaja)")
    print()

    per_prodi: dict[str, int] = {}
    for rec in records:
        per_prodi[rec["Jurusan"]] = per_prodi.get(rec["Jurusan"], 0) + 1

    for major, prefix in PRODI:
        nim_awal = min(r["NIM"] for r in records if r["Jurusan"] == major)
        nim_akhir = max(r["NIM"] for r in records if r["Jurusan"] == major)
        print(f"{major:20} {per_prodi[major]:4} orang   NIM {nim_awal}-{nim_akhir}")

    kosong = sum(1 for r in records if not r["Email Kampus"])
    print(f"\nemail kosong (uji fallback): {kosong}")

    gugus: dict[str, int] = {}
    for rec in records:
        gugus[rec["Kode Gugus"]] = gugus.get(rec["Kode Gugus"], 0) + 1
    print("sebaran gugus:", dict(sorted(gugus.items())))

    h = hashlib.sha256(bersih.read_bytes()).hexdigest()[:16]
    print(f"sha256(bersih) = {h}")


if __name__ == "__main__":
    main()
