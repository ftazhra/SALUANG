
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel pertanyaan diskusi.
        Schema::create('discussions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('chapter', 30);
            $table->string('subchapter', 30);

            $table->string('title', 200);
            $table->text('body');

            $table->timestamps();

            $table->index(['chapter', 'subchapter']);
            $table->index('created_at');
        });

        // Tabel tanggapan terhadap diskusi.
        Schema::create('discussion_replies', function (Blueprint $table) {
            $table->id();

            $table->foreignId('discussion_id')
                ->constrained('discussions')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->text('body');

            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discussion_replies');
        Schema::dropIfExists('discussions');
    }
};