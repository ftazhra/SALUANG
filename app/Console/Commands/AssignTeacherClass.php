<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AssignTeacherClass extends Command
{
    protected $signature = 'saluang:assign-teacher-class
        {teacher_id : ID akun Guru}
        {class_name : Nama kelas persis seperti kelas Siswa, misalnya "VII A"}
        {--revoke : Hapus penugasan kelas yang ditentukan}';

    protected $description = 'Menetapkan atau mencabut kelas Guru secara eksplisit';

    public function handle(): int
    {
        if (!Schema::hasTable('teacher_class_assignments')) {
            $this->error('Migration teacher_class_assignments belum diterapkan.');
            return self::FAILURE;
        }

        $teacher = User::query()
            ->where('role', 'guru')
            ->find((int) $this->argument('teacher_id'));
        if (!$teacher) {
            $this->error('Akun Guru tidak ditemukan.');
            return self::FAILURE;
        }

        $className = trim((string) $this->argument('class_name'));
        if (!in_array($className, ['VII A', 'VII B', 'VII C', 'VII D', 'VII E'], true)) {
            $this->error('Kelas tidak tersedia pada filter Hubungi Orang Tua.');
            return self::FAILURE;
        }

        $assignment = DB::table('teacher_class_assignments')
            ->where('teacher_id', $teacher->id)
            ->where('class_name', $className);

        if ($this->option('revoke')) {
            $deleted = $assignment->delete();
            $this->info($deleted ? 'Penugasan kelas dicabut.' : 'Penugasan tersebut tidak ditemukan.');
            return self::SUCCESS;
        }

        $classExists = DB::table('users')
            ->where('role', 'siswa')
            ->where('kelas', $className)
            ->exists();
        if (!$classExists) {
            $this->error('Tidak ada Siswa terdaftar pada kelas tersebut.');
            return self::FAILURE;
        }

        DB::table('teacher_class_assignments')->insertOrIgnore([
            'teacher_id' => $teacher->id,
            'class_name' => $className,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->info('Penugasan Guru ke kelas tersimpan.');
        return self::SUCCESS;
    }
}