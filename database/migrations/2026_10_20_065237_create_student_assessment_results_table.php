
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('student_assessment_results', function (Blueprint $table) {
            $table->id();

            // Siswa yang mengerjakan.
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Jenis penilaian.
            $table->enum('assessment_type', [
                'quiz',
                'evaluation',
            ]);

            // Bab terkait. Evaluasi umum boleh tanpa Bab.
            $table->foreignId('chapter_id')
                ->nullable()
                ->constrained('chapters')
                ->nullOnDelete();

            // Hasil pengerjaan.
            $table->unsignedSmallInteger('total_questions');

            $table->unsignedSmallInteger('correct_answers');

            $table->decimal('score', 5, 2);

            // Waktu siswa menyelesaikan pengerjaan.
            $table->timestamp('completed_at');

            $table->timestamps();

            // Mempercepat pencarian riwayat nilai.
            $table->index([
                'user_id',
                'assessment_type',
            ], 'sar_user_type_index');

            $table->index(
                'completed_at',
                'sar_completed_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_assessment_results');
    }
};
