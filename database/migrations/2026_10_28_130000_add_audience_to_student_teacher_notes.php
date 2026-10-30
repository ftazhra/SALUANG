
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Migration sebelumnya mungkin telah menambahkan
        // kolom audience sebelum gagal menghapus indeks.
        if (!Schema::hasColumn('student_teacher_notes', 'audience')) {
            Schema::table('student_teacher_notes', function (Blueprint $table) {
                $table->string('audience', 20)
                    ->default('orang_tua');
            });
        }

        // Indeks baru juga mendukung foreign key student_id
        // karena student_id adalah kolom pertamanya.
        if (!$this->indexExists('student_teacher_notes_audience_unique')) {
            Schema::table('student_teacher_notes', function (Blueprint $table) {
                $table->unique(
                    ['student_id', 'teacher_id', 'audience'],
                    'student_teacher_notes_audience_unique'
                );
            });
        }

        // Setelah indeks pengganti ada, indeks lama
        // bisa dilepas tanpa kehilangan pendukung FK.
        if ($this->indexExists('student_teacher_notes_student_id_teacher_id_unique')) {
            Schema::table('student_teacher_notes', function (Blueprint $table) {
                $table->dropUnique(
                    'student_teacher_notes_student_id_teacher_id_unique'
                );
            });
        }
    }

    public function down(): void
    {
        $duplicates = DB::table('student_teacher_notes')
            ->select('student_id', 'teacher_id')
            ->groupBy('student_id', 'teacher_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($duplicates) {
            throw new RuntimeException(
                'Rollback dibatalkan karena terdapat catatan untuk beberapa audience.'
            );
        }

        // Kembalikan indeks lama sebelum menghapus
        // indeks baru agar foreign key tetap didukung.
        if (!$this->indexExists('student_teacher_notes_student_id_teacher_id_unique')) {
            Schema::table('student_teacher_notes', function (Blueprint $table) {
                $table->unique(
                    ['student_id', 'teacher_id'],
                    'student_teacher_notes_student_id_teacher_id_unique'
                );
            });
        }

        if ($this->indexExists('student_teacher_notes_audience_unique')) {
            Schema::table('student_teacher_notes', function (Blueprint $table) {
                $table->dropUnique('student_teacher_notes_audience_unique');
            });
        }

        if (Schema::hasColumn('student_teacher_notes', 'audience')) {
            Schema::table('student_teacher_notes', function (Blueprint $table) {
                $table->dropColumn('audience');
            });
        }
    }

    private function indexExists(string $indexName): bool
    {
        return DB::table('information_schema.statistics')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'student_teacher_notes')
            ->where('INDEX_NAME', $indexName)
            ->exists();
    }
};
