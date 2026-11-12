<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluation_attempts', function (Blueprint $table) {
            $table->foreignId('teacher_id')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->index(
                ['user_id', 'teacher_id', 'submitted_at', 'id'],
                'evaluation_attempts_student_teacher_status_index'
            );
            $table->index(
                ['teacher_id', 'user_id', 'submitted_at', 'id'],
                'evaluation_attempts_teacher_student_status_index'
            );
        });
    }

    public function down(): void
    {
        if (DB::table('evaluation_attempts')->whereNotNull('teacher_id')->exists()) {
            throw new \RuntimeException(
                'Evaluasi per Guru memiliki riwayat baru; ekspor/arsipkan atribusi sebelum rollback.'
            );
        }

        Schema::table('evaluation_attempts', function (Blueprint $table) {
            $table->dropIndex('evaluation_attempts_student_teacher_status_index');
            $table->dropIndex('evaluation_attempts_teacher_student_status_index');
            $table->dropConstrainedForeignId('teacher_id');
        });
    }
};
