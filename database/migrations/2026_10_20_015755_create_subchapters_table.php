<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subchapters', function (Blueprint $table) {
            $table->id();

            $table->foreignId('chapter_id')
                ->constrained('chapters')
                ->cascadeOnDelete();

            $table->unsignedInteger('nomor_subbab');
            $table->string('judul');
            $table->longText('konten')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->unique(['chapter_id', 'nomor_subbab']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subchapters');
    }
};
