<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasIndex('subchapter_media', 'subchapter_media_type_index')) {
            Schema::table('subchapter_media', function (Blueprint $table) {
                $table->index(
                    ['subchapter_id', 'media_type'],
                    'subchapter_media_type_index'
                );
            });
        }

        if (Schema::hasIndex(
            'subchapter_media',
            'subchapter_media_type_unique',
            'unique'
        )) {
            Schema::table('subchapter_media', function (Blueprint $table) {
                $table->dropUnique('subchapter_media_type_unique');
            });
        }

        Schema::table('subchapters', function (Blueprint $table) {
            if (!Schema::hasColumn('subchapters', 'content_blocks_enabled')) {
                $table->boolean('content_blocks_enabled')
                    ->default(false)
                    ->after('konten');
            }
        });

        if (!Schema::hasTable('subchapter_content_blocks')) {
            Schema::create('subchapter_content_blocks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('subchapter_id')
                    ->constrained('subchapters')
                    ->cascadeOnDelete();
                $table->string('type', 32);
                $table->unsignedInteger('position');
                $table->json('payload')->nullable();
                $table->foreignId('media_id')
                    ->nullable()
                    ->constrained('subchapter_media')
                    ->nullOnDelete();
                $table->timestamps();

                $table->unique(
                    ['subchapter_id', 'position'],
                    'subchapter_content_blocks_position_unique'
                );
                $table->index(['subchapter_id', 'type']);
            });
        }
    }

    public function down(): void
    {
        if (
            Schema::hasTable('subchapter_content_blocks')
            && DB::table('subchapter_content_blocks')->exists()
        ) {
            throw new \RuntimeException(
                'Export or remove content blocks before rolling back this migration.'
            );
        }

        $hasDuplicateMediaTypes = DB::table('subchapter_media')
            ->select('subchapter_id', 'media_type')
            ->groupBy('subchapter_id', 'media_type')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasDuplicateMediaTypes) {
            throw new \RuntimeException(
                'Resolve duplicate media types before restoring the legacy unique index.'
            );
        }

        if (!Schema::hasIndex(
            'subchapter_media',
            'subchapter_media_type_unique',
            'unique'
        )) {
            Schema::table('subchapter_media', function (Blueprint $table) {
                $table->unique(
                    ['subchapter_id', 'media_type'],
                    'subchapter_media_type_unique'
                );
            });
        }

        if (Schema::hasIndex('subchapter_media', 'subchapter_media_type_index')) {
            Schema::table('subchapter_media', function (Blueprint $table) {
                $table->dropIndex('subchapter_media_type_index');
            });
        }

        if (Schema::hasTable('subchapter_content_blocks')) {
            Schema::drop('subchapter_content_blocks');
        }

        if (Schema::hasColumn('subchapters', 'content_blocks_enabled')) {
            Schema::table('subchapters', function (Blueprint $table) {
                $table->dropColumn('content_blocks_enabled');
            });
        }
    }
};
