
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_questions', function (Blueprint $table) {
            $table->id();

            // Guru yang membuat soal.
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Urutan soal dalam Evaluasi Akhir.
            $table->unsignedTinyInteger('nomor_soal');

            // Jenis soal.
            $table->enum('tipe_soal', [
                'pilihan_ganda',
                'drag_drop',
                'menghubungkan',
                'pilih_gambar',
            ]);

            // Pertanyaan yang ditampilkan kepada Siswa.
            $table->text('pertanyaan');

            // Data opsi, gambar, dan target jawaban.
            $table->json('konten_soal')->nullable();

            // Kunci jawaban (JSON untuk mendukung soal interaktif).
            $table->json('kunci_jawaban');

            // Pembahasan setelah evaluasi selesai.
            $table->text('pembahasan')->nullable();

            // Hanya soal yang dipublikasikan yang terlihat Siswa.
            $table->boolean('is_published')->default(false);

            $table->timestamps();

            // Satu nomor soal per Guru.
            $table->unique(
                ['user_id', 'nomor_soal'],
                'evaluation_questions_guru_number_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_questions');
    }
};