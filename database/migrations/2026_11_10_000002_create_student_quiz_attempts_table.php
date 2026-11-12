<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('subchapter_id')->constrained('subchapters')->cascadeOnDelete();
            $table->string('status', 20)->default('in_progress');
            $table->json('answers');
            $table->json('review')->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->unsignedSmallInteger('correct_answers')->nullable();
            $table->unsignedSmallInteger('total_questions')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'subchapter_id', 'status'], 'sqa_user_subchapter_status_index');
            $table->index(['user_id', 'subchapter_id', 'completed_at'], 'sqa_user_subchapter_completed_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_quiz_attempts');
    }
};
