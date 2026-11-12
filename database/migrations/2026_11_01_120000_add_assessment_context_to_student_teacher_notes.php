<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('student_teacher_notes')) {
            if (!Schema::hasColumn('student_teacher_notes', 'assessment_type')) {
                Schema::table('student_teacher_notes', function (Blueprint $table) {
                    $table->string('assessment_type', 20)->nullable();
                });
            }

            if (!Schema::hasColumn('student_teacher_notes', 'assessment_result_id')) {
                Schema::table('student_teacher_notes', function (Blueprint $table) {
                    $table->unsignedBigInteger('assessment_result_id')->nullable();
                });
            }

            // Indeks komposit baru tetap diawali student_id sehingga dapat
            // mengambil alih dukungan FK sebelum indeks lama dilepas.
            $this->ensureIndex(
                'student_teacher_notes',
                'stn_assessment_scope_unique',
                ['student_id', 'teacher_id', 'audience', 'assessment_type', 'assessment_result_id'],
                true
            );

            foreach ([
                'student_teacher_notes_audience_unique',
                'student_teacher_notes_student_id_teacher_id_unique',
            ] as $index) {
                if ($this->indexExists('student_teacher_notes', $index)) {
                    Schema::table('student_teacher_notes', function (Blueprint $table) use ($index) {
                        $table->dropUnique($index);
                    });
                }
            }

        }

        if (Schema::hasTable('student_teacher_note_messages')) {
            if (!Schema::hasColumn('student_teacher_note_messages', 'assessment_type')) {
                Schema::table('student_teacher_note_messages', function (Blueprint $table) {
                    $table->string('assessment_type', 20)->nullable();
                });
            }

            if (!Schema::hasColumn('student_teacher_note_messages', 'assessment_result_id')) {
                Schema::table('student_teacher_note_messages', function (Blueprint $table) {
                    $table->unsignedBigInteger('assessment_result_id')->nullable();
                });
            }

            if (!$this->indexExists('student_teacher_note_messages', 'stnm_assessment_result_idx')) {
                Schema::table('student_teacher_note_messages', function (Blueprint $table) {
                    $table->index(
                        ['student_id', 'assessment_type', 'assessment_result_id'],
                        'stnm_assessment_result_idx'
                    );
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['student_teacher_notes', 'student_teacher_note_messages'] as $tableName) {
            if (
                Schema::hasTable($tableName)
                && Schema::hasColumn($tableName, 'assessment_type')
                && DB::table($tableName)->where(function ($query) use ($tableName) {
                    if (Schema::hasColumn($tableName, 'assessment_result_id')) {
                        $query->whereNotNull('assessment_type')
                            ->orWhereNotNull('assessment_result_id');
                    } else {
                        $query->whereNotNull('assessment_type');
                    }
                })->exists()
            ) {
                throw new RuntimeException(
                    'Rollback dibatalkan agar konteks catatan hasil pengerjaan tidak hilang.'
                );
            }
        }

        if (Schema::hasTable('student_teacher_notes')) {
            $hasDuplicateDraftScopes = DB::table('student_teacher_notes')
                ->select('student_id', 'teacher_id', 'audience')
                ->groupBy('student_id', 'teacher_id', 'audience')
                ->havingRaw('COUNT(*) > 1')
                ->exists();

            if ($hasDuplicateDraftScopes) {
                throw new RuntimeException(
                    'Rollback dibatalkan karena terdapat draf untuk beberapa hasil pengerjaan.'
                );
            }

            if (!$this->indexExists('student_teacher_notes', 'student_teacher_notes_audience_unique')) {
                Schema::table('student_teacher_notes', function (Blueprint $table) {
                    $table->unique(
                        ['student_id', 'teacher_id', 'audience'],
                        'student_teacher_notes_audience_unique'
                    );
                });
            }

            if ($this->indexExists('student_teacher_notes', 'stn_assessment_scope_unique')) {
                Schema::table('student_teacher_notes', function (Blueprint $table) {
                    $table->dropUnique('stn_assessment_scope_unique');
                });
            }

            Schema::table('student_teacher_notes', function (Blueprint $table) {
                $table->dropColumn(['assessment_type', 'assessment_result_id']);
            });
        }

        if (Schema::hasTable('student_teacher_note_messages')) {
            if ($this->indexExists('student_teacher_note_messages', 'stnm_assessment_result_idx')) {
                Schema::table('student_teacher_note_messages', function (Blueprint $table) {
                    $table->dropIndex('stnm_assessment_result_idx');
                });
            }

            Schema::table('student_teacher_note_messages', function (Blueprint $table) {
                $table->dropColumn(['assessment_type', 'assessment_result_id']);
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return collect(Schema::getIndexes($table))
            ->contains(fn (array $index) => $index['name'] === $indexName);
    }

    private function ensureIndex(
        string $table,
        string $indexName,
        array $columns,
        bool $unique
    ): void {
        $existing = collect(Schema::getIndexes($table))
            ->first(fn (array $index) => $index['name'] === $indexName);

        if ($existing !== null) {
            if (
                $existing['columns'] !== $columns
                || (bool) $existing['unique'] !== $unique
            ) {
                throw new RuntimeException(
                    "Indeks {$indexName} sudah ada dengan definisi yang berbeda."
                );
            }

            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName, $unique) {
            if ($unique) {
                $blueprint->unique($columns, $indexName);
            } else {
                $blueprint->index($columns, $indexName);
            }
        });
    }
};
