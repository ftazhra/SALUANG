<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('evaluation_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->longText('question_snapshot');
            $table->longText('answers');
            $table->timestamp('ends_at');
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedTinyInteger('score')->nullable();
            $table->unsignedTinyInteger('correct_count')->nullable();
            $table->timestamps();
            $table->index(['user_id','id']);
        });
    }
    public function down(): void { Schema::dropIfExists('evaluation_attempts'); }
};
