<?php

namespace App\Services;

use App\Models\ParentTeacherMessage;
use App\Models\StudentGuardian;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ParentTeacherChatService
{
    private function ensureSchema(): void
    {
        abort_unless(
            Schema::hasTable('parent_teacher_messages')
                && Schema::hasTable('student_guardians')
                && Schema::hasTable('teacher_class_assignments')
                && Schema::hasColumn('parent_teacher_messages', 'client_message_id'),
            503,
            'Migration chat dan penugasan kelas belum diterapkan.'
        );
    }

    public function teacherThreads(User $parent): Collection
    {
        abort_unless($parent->role === 'orang_tua', 403);
        $this->ensureSchema();

        return DB::table('student_guardians as relation')
            ->join('users as student', 'student.id', '=', 'relation.student_id')
            ->join('teacher_class_assignments as assignment', 'assignment.class_name', '=', 'student.kelas')
            ->join('users as teacher', 'teacher.id', '=', 'assignment.teacher_id')
            ->where('relation.guardian_id', $parent->id)
            ->where('relation.status', 'approved')
            ->where('student.role', 'siswa')
            ->where('teacher.role', 'guru')
            ->orderBy('student.name')
            ->orderBy('teacher.name')
            ->distinct()
            ->get([
                'relation.id as relationship_id',
                'student.id as student_id',
                'student.name as student_name',
                'student.kelas as class_name',
                'teacher.id as id',
                'teacher.name as name',
            ]);
    }

    public function parentThread(User $parent, int $studentId, int $teacherId): array
    {
        abort_unless($parent->role === 'orang_tua', 403);
        $this->ensureSchema();

        $relation = StudentGuardian::query()
            ->where('guardian_id', $parent->id)
            ->where('student_id', $studentId)
            ->where('status', 'approved')
            ->whereHas('student', fn ($query) => $query->where('role', 'siswa'))
            ->first();
        abort_unless((bool) $relation, 404, 'Hubungan anak belum disetujui.');

        $student = User::query()->where('role', 'siswa')->findOrFail($studentId);
        $teacher = User::query()->where('role', 'guru')->findOrFail($teacherId);
        $this->assertAssigned($teacher, $student);

        return [
            'parent_id' => (int) $parent->id,
            'student_id' => (int) $student->id,
            'teacher_id' => (int) $teacher->id,
        ];
    }

    public function teacherThread(User $teacher, int $relationshipId): array
    {
        abort_unless($teacher->role === 'guru', 403);
        $this->ensureSchema();

        $relation = StudentGuardian::query()
            ->whereKey($relationshipId)
            ->where('status', 'approved')
            ->whereHas('student', fn ($query) => $query->where('role', 'siswa'))
            ->whereHas('guardian', fn ($query) => $query->where('role', 'orang_tua'))
            ->with(['student:id,name,kelas,role', 'guardian:id,name,role'])
            ->firstOrFail();

        $this->assertAssigned($teacher, $relation->student);

        return [
            'parent_id' => (int) $relation->guardian_id,
            'student_id' => (int) $relation->student_id,
            'teacher_id' => (int) $teacher->id,
            'relationship_id' => (int) $relation->id,
        ];
    }

    public function messages(array $thread, User $viewer): Collection
    {
        $this->ensureSchema();
        abort_unless(
            (int) $viewer->id === $thread['parent_id'] || (int) $viewer->id === $thread['teacher_id'],
            403
        );
        $this->assertCurrentThread($thread);

        return ParentTeacherMessage::query()
            ->with('sender:id,name')
            ->where('parent_id', $thread['parent_id'])
            ->where('student_id', $thread['student_id'])
            ->where('teacher_id', $thread['teacher_id'])
            ->orderBy('created_at')
            ->orderBy('id')
            ->limit(500)
            ->get()
            ->map(fn ($message) => [
                'id' => $message->id,
                'body' => $message->body,
                'is_mine' => (int) $message->sender_id === (int) $viewer->id,
                'sender_name' => $message->sender?->name,
                'created_at' => $message->created_at?->toIso8601String(),
            ]);
    }

    public function send(array $thread, User $sender, string $body, string $clientMessageId): array
    {
        $this->ensureSchema();
        $this->assertCurrentThread($thread);
        abort_unless(
            (int) $sender->id === $thread['parent_id'] || (int) $sender->id === $thread['teacher_id'],
            403
        );

        $message = DB::transaction(function () use ($thread, $sender, $body, $clientMessageId) {
            DB::table('parent_teacher_messages')->insertOrIgnore([
                'parent_id' => $thread['parent_id'],
                'student_id' => $thread['student_id'],
                'teacher_id' => $thread['teacher_id'],
                'sender_id' => $sender->id,
                'client_message_id' => $clientMessageId,
                'body' => trim($body),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $message = ParentTeacherMessage::query()
                ->where('client_message_id', $clientMessageId)
                ->firstOrFail();

            abort_unless(
                (int) $message->parent_id === $thread['parent_id']
                    && (int) $message->student_id === $thread['student_id']
                    && (int) $message->teacher_id === $thread['teacher_id']
                    && (int) $message->sender_id === (int) $sender->id,
                409,
                'ID permintaan pesan telah digunakan pada pesan lain.'
            );

            return $message;
        });

        return ['id' => (int) $message->id, 'created_at' => $message->created_at?->toIso8601String()];
    }

    private function assertAssigned(User $teacher, User $student): void
    {
        $assigned = DB::table('teacher_class_assignments')
            ->where('teacher_id', $teacher->id)
            ->where('class_name', $student->kelas)
            ->exists();
        abort_unless($assigned, 404, 'Guru tidak ditugaskan pada kelas Siswa ini.');
    }

    private function assertCurrentThread(array $thread): void
    {
        $approved = StudentGuardian::query()
            ->where('guardian_id', $thread['parent_id'])
            ->where('student_id', $thread['student_id'])
            ->where('status', 'approved')
            ->whereHas('student', fn ($query) => $query->where('role', 'siswa'))
            ->whereHas('guardian', fn ($query) => $query->where('role', 'orang_tua'))
            ->exists();
        abort_unless($approved, 404, 'Hubungan Orang Tua-Siswa tidak lagi disetujui.');

        $student = User::query()->where('role', 'siswa')->findOrFail($thread['student_id']);
        $teacher = User::query()->where('role', 'guru')->findOrFail($thread['teacher_id']);
        $this->assertAssigned($teacher, $student);
    }
}