<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TeacherStudentAccessService
{
    public function studentsForTeacher(User $teacher): Builder
    {
        abort_unless($teacher->role === 'guru', 403);

        $classes = Schema::hasTable('teacher_class_assignments')
            ? DB::table('teacher_class_assignments')
                ->where('teacher_id', $teacher->id)
                ->pluck('class_name')
            : collect();

        $query = User::query()
            ->where('role', 'siswa');
        if ($classes->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        $query->where(function (Builder $builder) use ($classes) {
            foreach ($classes as $className) {
                $builder->orWhereRaw('LOWER(TRIM(kelas)) = ?', [
                    mb_strtolower(trim((string) $className)),
                ]);
            }
        });

        if ($teacher->sekolah === null) {
            return $query->whereNull('sekolah');
        }

        return $query->whereRaw('LOWER(TRIM(sekolah)) = ?', [
            mb_strtolower(trim((string) $teacher->sekolah)),
        ]);
    }

    public function teacherIdsForStudent(User $student): array
    {
        if (
            $student->role !== 'siswa'
            || !$student->kelas
            || !Schema::hasTable('teacher_class_assignments')
        ) {
            return [];
        }

        $query = DB::table('teacher_class_assignments as assignment')
            ->join('users as teacher', 'teacher.id', '=', 'assignment.teacher_id')
            ->whereRaw('LOWER(TRIM(assignment.class_name)) = ?', [
                mb_strtolower(trim((string) $student->kelas)),
            ])
            ->where('teacher.role', 'guru');

        if ($student->sekolah === null) {
            $query->whereNull('teacher.sekolah');
        } else {
            $query->whereRaw('LOWER(TRIM(teacher.sekolah)) = ?', [
                mb_strtolower(trim((string) $student->sekolah)),
            ]);
        }

        return $query->orderBy('teacher.id')
            ->pluck('teacher.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function isAssignedToTeacher(User $student, User $teacher): bool
    {
        if ($teacher->role !== 'guru' || $student->role !== 'siswa') {
            return false;
        }

        return $this->studentsForTeacher($teacher)
            ->whereKey($student->id)
            ->exists();
    }
}
