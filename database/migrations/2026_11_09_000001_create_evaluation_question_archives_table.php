<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_question_archives', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('question_id')->index();
            $table->unsignedBigInteger('teacher_id')->index();
            $table->json('question_snapshot');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        if (
            Schema::hasTable('evaluation_question_archives')
            && DB::table('evaluation_question_archives')->exists()
        ) {
            throw new \LogicException(
                'Evaluation question archives contain preserved question data and cannot be dropped.'
            );
        }

        Schema::dropIfExists('evaluation_question_archives');
    }
};
