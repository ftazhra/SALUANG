<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_subchapter_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('subchapter_id')->constrained('subchapters')->cascadeOnDelete();
            $table->string('status', 24)->default('in_progress');
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'subchapter_id']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('student_quiz_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('subchapter_id')->constrained('subchapters')->cascadeOnDelete();
            $table->foreignId('quiz_question_id')->constrained('quiz_questions')->cascadeOnDelete();
            $table->enum('answer', ['A', 'B', 'C', 'D']);
            $table->boolean('is_correct');
            $table->timestamps();

            $table->unique(['user_id', 'quiz_question_id']);
            $table->index(['user_id', 'subchapter_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_quiz_answers');
        Schema::dropIfExists('student_subchapter_progress');
    }
};
