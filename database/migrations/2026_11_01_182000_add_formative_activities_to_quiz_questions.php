<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_questions', function (Blueprint $table) {
            $table->string('activity_type', 32)
                ->default('multiple_choice')
                ->after('nomor_soal');
            $table->json('activity_payload')
                ->nullable()
                ->after('kunci_jawaban');
        });
    }

    public function down(): void
    {
        throw new RuntimeException(
            'Export formative activity data before removing its quiz question columns.'
        );
    }
};
