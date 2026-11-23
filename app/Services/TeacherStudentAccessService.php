<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TeacherStudentAccessService
{
    public function __construct(
        private SchoolAccessService $schoolAccess
    ) {}

    // Mengambil daftar kelas yang tercatat.
    public function classNamesForTeacher(User $teacher): array
    {
        abort_unless($teacher->role === 'guru', 403);

        if (!Schema::hasTable('teacher_class_assignments')) {
            return [];
        }

        return DB::table('teacher_class_assignments')
            ->where('teacher_id', $teacher->id)
            ->orderBy('class_name')
            ->pluck('class_name')
            ->filter(fn($name) => trim((string) $name) !== '')
            ->map(fn($name) => (string) $name)
            ->all();
    }

    // Mengambil seluruh siswa dari sekolah Guru.
    public function studentsForTeacher(User $teacher): Builder
    {
        abort_unless($teacher->role === 'guru', 403);

        $query = User::query()->where('role', 'siswa');

        return $this->schoolAccess->constrainUsersToSchool(
            $query,
            $teacher
        );
    }

    // Mengambil seluruh Guru dari sekolah siswa.
    public function teacherIdsForStudent(User $student): array
    {
        if (
            $student->role !== 'siswa'
            || trim((string) $student->sekolah) === ''
        ) {
            return [];
        }

        return User::query()
            ->where('role', 'guru')
            ->whereNotNull('sekolah')
            ->whereNull('deleted_at')
            ->whereRaw('LOWER(TRIM(sekolah)) = ?', [
                mb_strtolower(trim((string) $student->sekolah))
            ])
            ->orderBy('id')
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->all();
    }

    // Memeriksa hak akses Guru terhadap siswa.
    public function isAssignedToTeacher(
        User $student,
        User $teacher
    ): bool {
        if (
            $teacher->role !== 'guru'
            || $student->role !== 'siswa'
        ) {
            return false;
        }

        if (!$this->isSameSchool($student, $teacher)) {
            return false;
        }

        return $this->studentsForTeacher($teacher)
            ->whereKey($student->id)
            ->exists();
    }

    public function isSameSchool(User $student, User $teacher): bool
    {
        return $student->role === 'siswa'
            && $teacher->role === 'guru'
            && $this->schoolAccess->sameSchool($student, $teacher);
    }

    // Memvalidasi kelas yang dikelola Guru.
    public function hasClassAssignment(
        User $teacher,
        string $className
    ): bool {
        return $teacher->role === 'guru'
            && trim((string) $teacher->sekolah) !== ''
            && trim($className) !== '';
    }
}
