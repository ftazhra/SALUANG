
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chapters', function (Blueprint $table) {

            // Pengantar Bab yang ditampilkan kepada Siswa.
            $table->longText('pengantar')->nullable();

            // Daftar tujuan pembelajaran dalam format JSON.
            $table->json('tujuan_pembelajaran')->nullable();

            // Pertanyaan pemantik pada halaman pengenalan Bab.
            $table->longText('pertanyaan_pemantik')->nullable();

        });
    }

    public function down(): void
    {
        Schema::table('chapters', function (Blueprint $table) {
            $table->dropColumn([
                'pengantar',
                'tujuan_pembelajaran',
                'pertanyaan_pemantik',
            ]);
        });
    }
};