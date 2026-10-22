
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('subchapter_id')
                ->constrained('subchapters')
                ->cascadeOnDelete();

            $table->unsignedInteger('nomor_soal');

            $table->longText('pertanyaan');

            $table->text('pilihan_a');
            $table->text('pilihan_b');
            $table->text('pilihan_c');
            $table->text('pilihan_d');

            $table->enum('kunci_jawaban', ['A', 'B', 'C', 'D']);

            $table->longText('pembahasan')->nullable();

            $table->boolean('is_published')->default(false);

            $table->timestamps();

            $table->unique(['subchapter_id', 'nomor_soal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_questions');
    }
};
