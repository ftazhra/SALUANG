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
    public function __construct(
        private WebsiteNotificationService $notifications,
        private ChatPresenceService $presence,
        private EvaluationResultService $evaluationResults,
        private TeacherStudentAccessService $teacherStudentAccess
    ) {
    }

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

        $relations = StudentGuardian::query()
            ->where('guardian_id', $parent->id)
            ->where('status', 'approved')
            ->whereHas('student', fn ($query) => $query->where('role', 'siswa'))
            ->whereHas('guardian', fn ($query) => $query->where('role', 'orang_tua'))
            ->with('student:id,name,kelas,sekolah,role')
            ->get();
        $threads = collect();

        foreach ($relations as $relation) {
            $student = $relation->student;
            foreach ($this->teacherIdsForStudent($student) as $teacherId) {
                $teacher = User::query()->where('role', 'guru')->find($teacherId);
                if (!$teacher) {
                    continue;
                }

                $threadData = [
                    'parent_id' => (int) $parent->id,
                    'student_id' => (int) $student->id,
                    'teacher_id' => (int) $teacher->id,
                ];

                $threads->push([
                    'relationship_id' => (int) $relation->id,
                    'student_id' => (int) $student->id,
                    'student_name' => $student->name,
                    'class_name' => $student->kelas,
                    'id' => (int) $teacher->id,
                    'name' => $teacher->name,
                    ...$this->presence->forThread($threadData, $parent),
                    'unread_count' => $this->unreadCount($threadData, $parent),
                ]);
            }
        }

        return $threads
            ->sortBy(fn (array $thread) => mb_strtolower($thread['student_name'] . "\0" . $thread['name']))
            ->values();
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
        $this->assertTeacherHasLearningAccess($teacher, $student);

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
            ->with(['student:id,name,kelas,role,sekolah', 'guardian:id,name,role'])
            ->firstOrFail();

        $this->assertTeacherHasLearningAccess($teacher, $relation->student);

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
        $this->markThreadRead($thread, $viewer);

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

    public function presence(array $thread, User $viewer): array
    {
        $this->ensureSchema();
        $this->assertCurrentThread($thread);

        return $this->presence->forThread($thread, $viewer);
    }

    public function unreadCount(array $thread, User $viewer): int
    {
        $this->ensureSchema();
        abort_unless(
            (int) $viewer->id === $thread['parent_id'] || (int) $viewer->id === $thread['teacher_id'],
            403
        );
        $this->assertCurrentThread($thread);

        if (!\Illuminate\Support\Facades\Schema::hasTable('website_notifications')) {
            return 0;
        }

        return (int) DB::table('website_notifications as notification')
            ->join('parent_teacher_messages as message', 'message.id', '=', 'notification.source_id')
            ->where('notification.recipient_id', $viewer->id)
            ->where('notification.recipient_role', $viewer->role)
            ->where('notification.event_type', 'chat_message')
            ->where('notification.source_type', 'parent_teacher_message')
            ->whereNull('notification.read_at')
            ->where('message.parent_id', $thread['parent_id'])
            ->where('message.student_id', $thread['student_id'])
            ->where('message.teacher_id', $thread['teacher_id'])
            ->where('message.sender_id', '!=', $viewer->id)
            ->count();
    }

    private function markThreadRead(array $thread, User $viewer): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('website_notifications')) {
            return;
        }

        $incomingMessages = DB::table('parent_teacher_messages')
            ->select('id')
            ->where('parent_id', $thread['parent_id'])
            ->where('student_id', $thread['student_id'])
            ->where('teacher_id', $thread['teacher_id'])
            ->where('sender_id', '!=', $viewer->id);

        DB::table('website_notifications')
            ->where('recipient_id', $viewer->id)
            ->where('recipient_role', $viewer->role)
            ->where('event_type', 'chat_message')
            ->where('source_type', 'parent_teacher_message')
            ->whereNull('read_at')
            ->whereIn('source_id', $incomingMessages)
            ->update(['read_at' => now(), 'updated_at' => now()]);
    }

    public function send(array $thread, User $sender, string $body, string $clientMessageId): array
    {
        $this->ensureSchema();
        $this->assertCurrentThread($thread);
        $this->presence->recordActivity($sender);
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

        $this->notifications->chatMessageCreated((int) $message->id);

        return ['id' => (int) $message->id, 'created_at' => $message->created_at?->toIso8601String()];
    }

    public function studentIdsForTeacher(User $teacher): array
    {
        abort_unless($teacher->role === 'guru', 403);

        $studentIds = $this->teacherStudentAccess->studentsForTeacher($teacher)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        foreach ($this->evaluationResults->quizResultsForTeacher((int) $teacher->id) as $result) {
            $studentIds[] = (int) $result['user_id'];
        }

        return array_values(array_unique($studentIds));
    }

    private function teacherIdsForStudent(User $student): array
    {
        $teacherIds = $this->teacherStudentAccess->teacherIdsForStudent($student);

        foreach ($this->evaluationResults->quizResultsForStudent((int) $student->id) as $result) {
            $teacherIds[] = (int) $result['teacher_id'];
        }

        return array_values(array_unique($teacherIds));
    }

    private function assertTeacherHasLearningAccess(User $teacher, User $student): void
    {
        $isAssigned = $this->teacherStudentAccess->isAssignedToTeacher($student, $teacher);
        $ownsCompletedQuiz = $this->evaluationResults->quizResultsForTeacher(
            (int) $teacher->id,
            [(int) $student->id]
        ) !== [];

        abort_unless(
            $isAssigned || $ownsCompletedQuiz,
            404,
            'Guru tidak memiliki relasi pembelajaran dengan Siswa ini.'
        );
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
        $this->assertTeacherHasLearningAccess($teacher, $student);
    }
}