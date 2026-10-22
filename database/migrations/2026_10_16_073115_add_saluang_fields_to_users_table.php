
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique();
            $table->enum('role', ['siswa', 'guru', 'orang_tua'])
                ->default('siswa');

            $table->string('nisn', 20)->nullable()->unique();
            $table->string('nip_nipk', 30)->nullable()->unique();
            $table->string('kelas', 20)->nullable();
            $table->string('sekolah')->nullable();
            $table->string('mata_pelajaran')->nullable();
            $table->string('nomor_telepon', 20)->nullable();
            $table->string('hubungan_anak')->nullable();
            $table->string('foto_profil')->nullable();

            // Akun guru tidak langsung aktif tanpa persetujuan.
            $table->boolean('is_approved')->default(false);

            // Registrasi tidak otomatis memverifikasi identitas.
            $table->timestamp('identity_verified_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'username',
                'role',
                'nisn',
                'nip_nipk',
                'kelas',
                'sekolah',
                'mata_pelajaran',
                'nomor_telepon',
                'hubungan_anak',
                'foto_profil',
                'is_approved',
                'identity_verified_at',
            ]);
        });
    }
};
