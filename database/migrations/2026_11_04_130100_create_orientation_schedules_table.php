<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jadwal orientasi sebagai entitas nyata.
 *
 * Sebelum ini "jadwal" hanya berupa angka `quests.unlock_day` dan teks
 * `deadline` seperti "Hari ke-3". Akibatnya panitia tidak punya tempat untuk
 * mencatat APA yang terjadi pada hari itu (seminar, apel, stan organisasi),
 * dan mahasiswa hanya melihat modul yang terbuka tanpa tahu acara apa yang
 * mendahuluinya.
 *
 * Tabel ini mengikat ketiganya: satu baris = satu agenda pada satu hari, punya
 * jam, lokasi, pembicara, dan boleh menunjuk modul yang harus dikerjakan
 * SETELAH agenda itu selesai.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orientation_schedules', function (Blueprint $table): void {
            $table->id();

            // Hari ke-N orientasi. Bukan tanggal, supaya jadwal tidak perlu
            // disusun ulang ketika tanggal mulai bergeser.
            $table->unsignedTinyInteger('day');

            $table->string('title', 150);
            $table->text('description')->nullable();

            $table->time('starts_at')->nullable();
            $table->time('ends_at')->nullable();

            $table->string('location', 150)->nullable();
            $table->string('speaker', 150)->nullable();

            /*
             * Jenis agenda menentukan ikon dan warna di UI, sekaligus menjadi
             * penanda kasar bagi panitia saat menyusun ulang.
             */
            $table->string('kind', 30)->default('session');

            /*
             * Modul yang WAJIB dikerjakan setelah agenda ini.
             *
             * Inilah penghubung yang diminta: "setelah seminar hari 1, kasih
             * kuis lewat OspekQuest". Satu agenda boleh menunjuk satu modul;
             * satu modul hanya boleh ditunjuk satu agenda supaya mahasiswa
             * tidak melihat tombol yang sama di dua tempat.
             */
            $table->foreignId('quest_id')->nullable()->unique()
                ->constrained('quests')->nullOnDelete();

            $table->boolean('is_mandatory')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['day', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orientation_schedules');
    }
};