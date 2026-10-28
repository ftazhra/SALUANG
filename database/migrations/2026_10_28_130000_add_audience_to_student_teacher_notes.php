<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_teacher_notes', function (Blueprint $table) {
            $table->string('audience', 20)->default('orang_tua');
            $table->dropUnique('student_teacher_notes_student_id_teacher_id_unique');
            $table->unique(
                ['student_id', 'teacher_id', 'audience'],
                'student_teacher_notes_audience_unique'
            );
        });
    }

    public function down(): void
    {
        $hasAudienceDuplicates = DB::table('student_teacher_notes')
            ->select('student_id', 'teacher_id')
            ->groupBy('student_id', 'teacher_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasAudienceDuplicates) {
            throw new RuntimeException(
                'Cannot roll back note audiences while separate audience notes exist. Merge them before retrying.'
            );
        }

        Schema::table('student_teacher_notes', function (Blueprint $table) {
            $table->dropUnique('student_teacher_notes_audience_unique');
            $table->dropColumn('audience');
            $table->unique(
                ['student_id', 'teacher_id'],
                'student_teacher_notes_student_id_teacher_id_unique'
            );
        });
    }
};