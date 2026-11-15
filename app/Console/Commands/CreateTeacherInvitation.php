<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CreateTeacherInvitation extends Command
{
    protected $signature = 'saluang:invite-teacher {school : Nama resmi sekolah} {--days=7 : Masa berlaku dalam hari}';
    protected $description = 'Buat kode undangan Guru satu kali pakai untuk sekolah tertentu';

    public function handle(): int
    {
        if (!Schema::hasTable('teacher_invitations')) {
            $this->error('Jalankan php artisan migrate terlebih dahulu.');
            return self::FAILURE;
        }
        $school = trim((string) $this->argument('school'));
        $days = (int) $this->option('days');
        if ($school === '' || mb_strlen($school) > 255 || $days < 1 || $days > 90) {
            $this->error('Sekolah wajib diisi (maksimal 255 karakter); masa berlaku 1–90 hari.');
            return self::FAILURE;
        }
        $code = Str::upper(Str::random(16));
        DB::table('teacher_invitations')->insert([
            'school_name' => $school,
            'code_hash' => hash('sha256', $code),
            'expires_at' => now()->addDays($days),
            'used_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->info('Kode undangan Guru (tunjukkan sekali saja): ' . $code);
        $this->line('Sekolah: ' . $school . ' | Berlaku ' . $days . ' hari.');
        return self::SUCCESS;
    }
}
