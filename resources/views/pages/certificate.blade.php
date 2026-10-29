<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sijil Tamat Orientasi - {{ $user->name }}</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Plus+Jakarta+Sans:wght@500;600;700;800;900&family=Fredoka:wght@600;700&display=swap" rel="stylesheet">

    <style>
        .font-cinzel {
            font-family: 'Cinzel', serif;
        }
        .brutal-border-4 {
            border: 4px solid #1E1E1E;
        }
        .brutal-border-gold {
            border: 6px double #C78C35;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: white !important;
                padding: 0 !important;
            }
            .certificate-container {
                box-shadow: none !important;
                border: 4px solid black !important;
                max-width: 100% !important;
            }
        }
    </style>
</head>
<body class="bg-[#DCDAD5] min-h-screen p-4 sm:p-8 flex flex-col items-center justify-center font-sans">

    <!-- Top Action Toolbar (Hidden when printing) -->
    <div class="no-print max-w-4xl w-full mb-6 flex items-center justify-between gap-4">
        <a href="{{ route('achievements') }}" class="px-4 py-2 bg-white hover:bg-neutral-100 border-2 border-black rounded-xl font-bold text-xs uppercase shadow-[3px_3px_0px_0px_#000] flex items-center gap-2">
            <span>←</span>
            <span>Kembali ke Pencapaian</span>
        </a>

        <div class="flex items-center gap-3">
            <button type="button" onclick="window.print()" class="px-6 py-2.5 bg-[#F9D976] hover:bg-amber-300 border-2 border-black rounded-xl font-black text-xs uppercase shadow-[3px_3px_0px_0px_#000] flex items-center gap-2 cursor-pointer">
                <span>🖨️</span>
                <span>Cetak / Muat Turun Sijil PDF</span>
            </button>
        </div>
    </div>

    <!-- Master Certificate Container -->
    <div class="certificate-container w-full max-w-4xl bg-[#FFFDF9] brutal-border-4 rounded-3xl p-8 sm:p-14 shadow-[8px_8px_0px_0px_#1E1E1E] relative overflow-hidden text-center">

        <!-- Outer Double Border Ornament -->
        <div class="absolute inset-4 brutal-border-gold rounded-2xl pointer-events-none"></div>

        <!-- Corner Ornaments -->
        <div class="absolute top-6 left-6 text-2xl text-amber-600">⚜️</div>
        <div class="absolute top-6 right-6 text-2xl text-amber-600">⚜️</div>
        <div class="absolute bottom-6 left-6 text-2xl text-amber-600">⚜️</div>
        <div class="absolute bottom-6 right-6 text-2xl text-amber-600">⚜️</div>

        <!-- Certificate Header -->
        <div class="flex flex-col items-center mb-6 relative z-10">
            <div class="w-16 h-16 rounded-2xl bg-[#F9D976] border-2 border-black flex items-center justify-center text-3xl shadow-[3px_3px_0px_0px_#000] mb-3">
                🎓
            </div>
            <span class="font-cinzel text-xs uppercase tracking-widest text-amber-900 font-bold block mb-1">
                Universiti Kebangsaan • Pejabat Hal Ehwal Pelajar
            </span>
            <h1 class="font-cinzel text-3xl sm:text-4xl font-black text-neutral-900 tracking-tight leading-tight uppercase">
                Sijil Tamat Orientasi
            </h1>
            <span class="text-xs font-black uppercase tracking-wider text-amber-700 bg-amber-100 border border-black/20 px-3 py-0.5 rounded-full mt-2">
                Program Orientasi OspekQuest Sesi 2025/2026
            </span>
        </div>

        <!-- Certificate Body -->
        <div class="max-w-2xl mx-auto space-y-4 my-8 relative z-10">
            <p class="text-xs uppercase font-extrabold tracking-widest text-neutral-500">
                Dengan ini diperakui dengan bangganya bahawa
            </p>

            <!-- Student Name -->
            <div class="py-2 border-b-2 border-dashed border-neutral-300">
                <h2 class="text-3xl sm:text-4xl font-display font-black text-neutral-950 tracking-wide">
                    {{ $user->name }}
                </h2>
                <span class="font-mono text-sm font-bold text-neutral-600 block mt-1">
                    No. Matrik: {{ $user->nim }}
                </span>
            </div>

            <p class="text-xs sm:text-sm font-semibold text-neutral-700 leading-relaxed pt-2">
                Daripada <strong>{{ $user->faculty }}</strong>, Program <strong>{{ $user->major }}</strong>, telah berjaya menamatkan kesemua modul kembara orientasi dengan menunjukkan sahsiah diri yang tinggi, semangat berpasukan kumpulan <strong>{{ $user->group_name }}</strong>, serta pencapaian gred cemerlang dalam modul etika, kepimpinan, dan Sistem SKS.
            </p>

            <div class="inline-flex items-center gap-3 px-4 py-2 bg-[#FFF3D6] border-2 border-black rounded-xl text-xs font-black text-amber-950 shadow-[2px_2px_0px_0px_#000]">
                <span>⭐ Status: Graduan Harapan Orientasi</span>
                <span>•</span>
                <span>Mata XP Terkumpul: {{ number_format($user->xp) }} XP</span>
            </div>
        </div>

        <!-- Verification & Signatures Section -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 items-end pt-8 mt-6 border-t-2 border-neutral-200 relative z-10">

            <!-- Dean Signature -->
            <div class="text-center sm:text-left">
                <div class="font-display font-bold text-lg text-neutral-800 italic mb-1">
                    Prof. Dr. Ahmad Zulkifli
                </div>
                <div class="w-36 h-0.5 bg-black mb-1 mx-auto sm:mx-0"></div>
                <span class="text-[10px] font-black uppercase text-neutral-600 block">Dekan Hal Ehwal Pelajar</span>
                <span class="text-[9px] text-neutral-400">Universiti Kebangsaan</span>
            </div>

            <!-- Official Gold Seal -->
            <div class="flex flex-col items-center justify-center">
                <div class="w-20 h-20 rounded-full bg-gradient-to-tr from-amber-500 via-yellow-300 to-amber-600 border-3 border-black flex flex-col items-center justify-center shadow-[4px_4px_0px_0px_#000] rotate-12">
                    <span class="text-2xl">🎖️</span>
                    <span class="text-[7px] font-black uppercase text-black tracking-widest leading-none mt-0.5">METERAI RASMI</span>
                </div>
                <span class="text-[9px] font-mono font-bold text-neutral-500 mt-2">Tarikh: {{ now()->translatedFormat('d F Y') }}</span>
            </div>

            <!-- Vice Chancellor Signature -->
            <div class="text-center sm:text-right">
                <div class="font-display font-bold text-lg text-neutral-800 italic mb-1">
                    Tan Sri Dato' Seri Halim
                </div>
                <div class="w-36 h-0.5 bg-black mb-1 mx-auto sm:ml-auto sm:mr-0"></div>
                <span class="text-[10px] font-black uppercase text-neutral-600 block">Naib Canselor Universiti</span>
                <span class="text-[9px] text-neutral-400">Penaung Majlis Orientasi</span>
            </div>

        </div>

        <!-- Digital Verification Barcode / Footer -->
        <div class="mt-8 pt-4 border-t border-dashed border-neutral-300 flex flex-col sm:flex-row items-center justify-between text-[10px] font-mono font-bold text-neutral-500 gap-2 relative z-10">
            <span>KOD VERIFIKASI: OSPEK-2025-{{ strtoupper(substr(md5($user->id . 'ospek'), 0, 8)) }}</span>
            <span>DIJANAKAN SECARA AUTOMASI OLEH SISTEM OSPEKQUEST</span>
        </div>

    </div>

</body>
</html>
