<?php

namespace App\Services;

use App\Models\User;
use App\Models\StudentGuardian;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ChatPresenceService
{
    private const ONLINE_WINDOW_SECONDS = 120;
    private const LAST_SEEN_TTL_DAYS = 30;
    private const ACTIVITY_THROTTLE_SECONDS = 30;

    public function __construct(
        private EvaluationResultService $evaluationResults,
        private TeacherStudentAccessService $teacherStudentAccess
    ) {
    }

    public function recordActivity(User $user): void
    {
        abort_unless(in_array($user->role, ['guru', 'orang_tua'], true), 403);
        abort_unless($this->hasCommunicationAccess($user), 403);

        $cache = Cache::store('file');
        $key = $this->presenceKey((int) $user->id);
        $throttleKey = 'chat-presence:throttle:' . (int) $user->id;

        if ($cache->add($throttleKey, true, now()->addSeconds(self::ACTIVITY_THROTTLE_SECONDS))) {
            $cache->put($key, now()->toIso8601String(), now()->addDays(self::LAST_SEEN_TTL_DAYS));
        }
    }

    public function forThread(array $thread, User $viewer): array
    {
        abort_unless(
            (int) $viewer->id === (int) $thread['parent_id']
                || (int) $viewer->id === (int) $thread['teacher_id'],
            403
        );

        $this->assertCurrentThread($thread);
        $otherUserId = (int) $viewer->id === (int) $thread['parent_id']
            ? (int) $thread['teacher_id']
            : (int) $thread['parent_id'];

        return $this->forUser($otherUserId);
    }

    public function forUser(int $userId): array
    {
        $lastSeen = Cache::store('file')->get($this->presenceKey($userId));
        if (!is_string($lastSeen)) {
            return ['is_online' => false, 'last_seen_at' => null];
        }

        $lastSeenAt = Carbon::parse($lastSeen);
        $secondsAgo = $lastSeenAt->diffInSeconds(now(), false);

        return [
            'is_online' => $secondsAgo >= 0 && $secondsAgo <= self::ONLINE_WINDOW_SECONDS,
            'last_seen_at' => $lastSeenAt->toIso8601String(),
        ];
    }

    private function hasCommunicationAccess(User $user): bool
    {
        if ($user->role === 'orang_tua') {
            return StudentGuardian::query()
                ->where('guardian_id', $user->id)
                ->where('status', 'approved')
                ->whereHas('student', fn ($query) => $query->where('role', 'siswa'))
                ->whereHas('guardian', fn ($query) => $query->where('role', 'orang_tua'))
                ->exists();
        }

        $studentIds = $this->studentIdsForTeacher($user);

        return StudentGuardian::query()
            ->whereIn('student_id', $studentIds)
            ->where('status', 'approved')
            ->whereHas('student', fn ($query) => $query->where('role', 'siswa'))
            ->whereHas('guardian', fn ($query) => $query->where('role', 'orang_tua'))
            ->exists();
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

        abort_unless($approved, 404, 'Percakapan tidak lagi tersedia.');

        $student = User::query()->where('role', 'siswa')->findOrFail($thread['student_id']);
        $teacher = User::query()->where('role', 'guru')->findOrFail($thread['teacher_id']);
        abort_unless(
            $this->teacherStudentAccess->isAssignedToTeacher($student, $teacher)
                || $this->evaluationResults->quizResultsForTeacher(
                    (int) $teacher->id,
                    [(int) $student->id]
                ) !== [],
            404,
            'Percakapan tidak lagi tersedia.'
        );
    }

    private function studentIdsForTeacher(User $teacher): array
    {
        $studentIds = $this->teacherStudentAccess->studentsForTeacher($teacher)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        foreach ($this->evaluationResults->quizResultsForTeacher((int) $teacher->id) as $result) {
            $studentIds[] = (int) $result['user_id'];
        }

        return array_values(array_unique($studentIds));
    }

    private function presenceKey(int $userId): string
    {
        return 'chat-presence:user:' . $userId;
    }
}
