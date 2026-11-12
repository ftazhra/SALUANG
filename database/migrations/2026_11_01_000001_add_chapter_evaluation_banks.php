<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Identitas bank Evaluasi berdasarkan Bab dan kelas.
        Schema::create('evaluation_banks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('chapter_id')
                ->constrained('chapters')
                ->restrictOnDelete();

            $table->foreignId('teacher_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('class_name', 20);
            $table->string('title', 150);
            $table->boolean('is_published')->default(false);

            // NULL = tidak aktif, 1 = aktif.
            // Unique index menghindari dua bank aktif
            // untuk Bab dan kelas yang sama di MySQL.
            $table->unsignedTinyInteger('active_slot')->nullable();

            $table->timestamps();

            $table->unique(
                ['chapter_id', 'class_name', 'active_slot'],
                'eval_banks_one_active_per_chapter_class'
            );
        });

        // Soal pilihan ganda pada bank Evaluasi baru.
        Schema::create('evaluation_bank_questions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('evaluation_bank_id')
                ->constrained('evaluation_banks')
                ->restrictOnDelete();

            $table->unsignedTinyInteger('nomor_soal');
            $table->text('pertanyaan');
            $table->json('konten_soal');
            $table->json('kunci_jawaban');
            $table->text('pembahasan')->nullable();

            $table->timestamps();

            $table->unique(
                ['evaluation_bank_id', 'nomor_soal'],
                'eval_bank_questions_number_unique'
            );
        });

        // Menambahkan identitas Bab dan bank pada attempt.
        // Nullable supaya attempt Evaluasi lama tetap valid.
        Schema::table('evaluation_attempts', function (Blueprint $table) {
            $table->foreignId('chapter_id')
                ->nullable()
                ->constrained('chapters')
                ->restrictOnDelete();

            $table->foreignId('evaluation_bank_id')
                ->nullable()
                ->constrained('evaluation_banks')
                ->restrictOnDelete();

            $table->index(
                ['user_id', 'chapter_id', 'id'],
                'eval_attempts_user_chapter_index'
            );
        });
    }

    public function down(): void
    {
        // Rollback hanya untuk lingkungan pengembangan
        // setelah dipastikan tidak ada data baru yang diperlukan.
        Schema::table('evaluation_attempts', function (Blueprint $table) {
            $table->dropIndex('eval_attempts_user_chapter_index');
            $table->dropConstrainedForeignId('evaluation_bank_id');
            $table->dropConstrainedForeignId('chapter_id');
        });

        Schema::dropIfExists('evaluation_bank_questions');
        Schema::dropIfExists('evaluation_banks');
    }
};
