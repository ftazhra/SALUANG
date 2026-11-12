<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ORIGINAL_UNIQUE_INDEX = 'quiz_questions_subchapter_id_nomor_soal_unique';
    private const ACTIVE_UNIQUE_INDEX = 'quiz_questions_subchapter_active_number_unique';
    private const SUBCHAPTER_INDEX = 'quiz_questions_subchapter_id_index';

    public function up(): void
    {
        if (! Schema::hasColumn('quiz_questions', 'deleted_at')) {
            Schema::table('quiz_questions', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (! Schema::hasColumn('quiz_questions', 'active_nomor_soal')) {
            Schema::table('quiz_questions', function (Blueprint $table) {
                $table->unsignedInteger('active_nomor_soal')
                    ->nullable()
                    ->virtualAs('CASE WHEN deleted_at IS NULL THEN nomor_soal ELSE NULL END');
            });
        }

        if (! Schema::hasIndex('quiz_questions', self::SUBCHAPTER_INDEX)) {
            Schema::table('quiz_questions', function (Blueprint $table) {
                $table->index('subchapter_id', self::SUBCHAPTER_INDEX);
            });
        }

        if (! Schema::hasIndex('quiz_questions', self::ACTIVE_UNIQUE_INDEX)) {
            Schema::table('quiz_questions', function (Blueprint $table) {
                $table->unique(
                    ['subchapter_id', 'active_nomor_soal'],
                    self::ACTIVE_UNIQUE_INDEX
                );
            });
        }

        if (Schema::hasIndex('quiz_questions', self::ORIGINAL_UNIQUE_INDEX)) {
            Schema::table('quiz_questions', function (Blueprint $table) {
                $table->dropUnique(self::ORIGINAL_UNIQUE_INDEX);
            });
        }
    }

    public function down(): void
    {
        if (
            DB::table('quiz_questions')
                ->whereNotNull('deleted_at')
                ->exists()
        ) {
            throw new LogicException(
                'Archived quiz questions must remain preserved; restore them before rolling back this migration.'
            );
        }

        if (
            DB::table('quiz_questions')
                ->select('subchapter_id', 'nomor_soal')
                ->groupBy('subchapter_id', 'nomor_soal')
                ->havingRaw('COUNT(*) > 1')
                ->exists()
        ) {
            throw new LogicException(
                'Active quiz question numbers are duplicated; the original unique index cannot be restored.'
            );
        }

        if (Schema::hasIndex('quiz_questions', self::ACTIVE_UNIQUE_INDEX)) {
            Schema::table('quiz_questions', function (Blueprint $table) {
                $table->dropUnique(self::ACTIVE_UNIQUE_INDEX);
            });
        }

        if (Schema::hasColumn('quiz_questions', 'active_nomor_soal')) {
            Schema::table('quiz_questions', function (Blueprint $table) {
                $table->dropColumn('active_nomor_soal');
            });
        }

        if (Schema::hasColumn('quiz_questions', 'deleted_at')) {
            Schema::table('quiz_questions', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (! Schema::hasIndex('quiz_questions', self::ORIGINAL_UNIQUE_INDEX)) {
            Schema::table('quiz_questions', function (Blueprint $table) {
                $table->unique(
                    ['subchapter_id', 'nomor_soal'],
                    self::ORIGINAL_UNIQUE_INDEX
                );
            });
        }

        if (Schema::hasIndex('quiz_questions', self::SUBCHAPTER_INDEX)) {
            Schema::table('quiz_questions', function (Blueprint $table) {
                $table->dropIndex(self::SUBCHAPTER_INDEX);
            });
        }
    }
};
