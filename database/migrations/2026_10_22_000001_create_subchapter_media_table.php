<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subchapter_media', function (Blueprint $table) {
            $table->id();

            $table->foreignId('subchapter_id')
                ->constrained('subchapters')
                ->cascadeOnDelete();

            $table->enum('media_type', ['image', 'video']);
            $table->enum('source', ['upload', 'library', 'youtube']);
            $table->string('storage_path')->nullable();
            $table->string('library_path')->nullable();
            $table->string('youtube_video_id', 11)->nullable();
            $table->string('title', 160)->nullable();
            $table->text('caption')->nullable();
            $table->timestamps();

            $table->unique(
                ['subchapter_id', 'media_type'],
                'subchapter_media_type_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subchapter_media');
    }
};
