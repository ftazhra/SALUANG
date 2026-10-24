<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chapters', function (Blueprint $table) {
            $table->string('gambar_pengenalan_judul')->nullable();
            $table->text('gambar_pengenalan_keterangan')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('chapters', function (Blueprint $table) {
            $table->dropColumn([
                'gambar_pengenalan_judul',
                'gambar_pengenalan_keterangan',
            ]);
        });
    }
};
