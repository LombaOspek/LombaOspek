<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom verifikasi pada quest + tabel token sekali-pakai.
     *
     * Anti-curang bertumpu pada empat hal:
     * 1. `verification_method` menentukan bukti apa yang wajib.
     * 2. Token disimpan sebagai HASH dan hanya boleh dipakai sekali per akun.
     * 3. GPS divalidasi jaraknya di server, bukan di browser.
     * 4. Foto wajib ditinjau LO sebelum XP keluar.
     */
    public function up(): void
    {
        Schema::table('quests', function (Blueprint $table) {
            $table->string('verification_method', 20)->default('none')->after('action_type');
            $table->string('status', 20)->default('published')->after('verification_method');
            $table->unsignedTinyInteger('difficulty')->default(2)->after('status');
            $table->string('target_scope', 20)->default('all')->after('difficulty');
            $table->json('target_study_programs')->nullable()->after('target_scope');
            $table->string('sponsor_name')->nullable()->after('target_study_programs');
            $table->string('bonus_reward')->nullable()->after('sponsor_name');

            // Konfigurasi token
            $table->string('token_hash', 64)->nullable()->after('bonus_reward');
            $table->string('token_hint', 40)->nullable()->after('token_hash');
            $table->unsignedSmallInteger('token_quota')->nullable()->after('token_hint');
            $table->timestamp('token_expires_at')->nullable()->after('token_quota');

            // Konfigurasi GPS
            $table->decimal('latitude', 10, 7)->nullable()->after('token_expires_at');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->unsignedSmallInteger('radius_meters')->default(100)->after('longitude');

            $table->index(['status', 'step_number']);
        });

        Schema::create('quest_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quest_id')->constrained()->cascadeOnDelete();

            $table->string('method', 20);
            $table->string('status', 20)->default('pending');

            // Bukti
            $table->string('proof_path')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('distance_meters')->nullable();
            $table->string('token_used_hash', 64)->nullable();
            $table->text('note')->nullable();

            // Keputusan peninjau
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('rejection_reason', 300)->nullable();

            $table->timestamps();

            // Satu mahasiswa hanya boleh punya satu pengajuan per modul.
            $table->unique(['user_id', 'quest_id']);
            $table->index(['status', 'created_at']);
            $table->index(['quest_id', 'status']);
        });

        Schema::create('token_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quest_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash', 64);
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            // Inti anti-curang: satu token = satu klaim per akun.
            $table->unique(['quest_id', 'user_id']);
            $table->index('token_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('token_redemptions');
        Schema::dropIfExists('quest_submissions');

        Schema::table('quests', function (Blueprint $table) {
            $table->dropIndex(['status', 'step_number']);
            $table->dropColumn([
                'verification_method', 'status', 'difficulty', 'target_scope',
                'target_study_programs', 'sponsor_name', 'bonus_reward',
                'token_hash', 'token_hint', 'token_quota', 'token_expires_at',
                'latitude', 'longitude', 'radius_meters',
            ]);
        });
    }
};