<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('parent_teacher_messages', function (Blueprint $table) {
      $table->id();
      $table->foreignId('parent_id')->constrained('users')->cascadeOnDelete();
      $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
      $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
      $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
      $table->text('body');
      $table->timestamps();
      $table->index(['parent_id', 'student_id', 'teacher_id', 'created_at'], 'ptm_thread_index');
    });
  }
  public function down(): void
  {
    Schema::dropIfExists('parent_teacher_messages');
  }
};
