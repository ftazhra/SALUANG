<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_class_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->string('class_name', 20);
            $table->timestamps();

            $table->unique(['teacher_id', 'class_name'], 'teacher_class_assignments_teacher_class_unique');
            $table->index('class_name');
        });
    }

    public function down(): void
    {
        if (
            Schema::hasTable('teacher_class_assignments')
            && DB::table('teacher_class_assignments')->exists()
        ) {
            throw new RuntimeException(
                'Cannot drop teacher class assignments while assignments exist. Remove or back them up first.'
            );
        }

        Schema::dropIfExists('teacher_class_assignments');
    }
};