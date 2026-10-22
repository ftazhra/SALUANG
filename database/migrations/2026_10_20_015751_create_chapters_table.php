<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chapters', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->unsignedInteger('nomor_bab');
            $table->string('judul');
            $table->longText('deskripsi')->nullable();
            $table->boolean('is_published')->default(false);

            $table->timestamps();

            $table->unique(['user_id', 'nomor_bab']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chapters');
    }
};