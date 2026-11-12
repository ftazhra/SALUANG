<?php
namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use App\Services\WebsiteNotificationService;

class EvaluationController extends Controller
{
    private function ensureSiswa(Request $request): int
    {
        abort_unless($request->user() && $request->user()->role === 'siswa', 403);
        return (int) $request->user()->id;
    }

    private function requireAttemptSchema(): void
    {
        abort_unless(
            Schema::hasColumn('evaluation_attempts', 'teacher_id'),
            503,
            'Evaluasi Akhir per Guru belum tersedia saat ini.'
        );
    }

    private function evaluationTeacher(
        int $studentId,
        int $teacherId
    ): User {
        $student = User::query()->where('role', 'siswa')->findOrFail($studentId);
        $teacher = User::query()->where('role', 'guru')->findOrFail($teacherId);
        $hasExistingAttempt = DB::table('evaluation_attempts')
            ->where('user_id', $studentId)
            ->where('teacher_id', $teacherId)
            ->exists();
        [$defaultTeacherId] = $this->defaultEvaluationTeacher();
        abort_unless(
            $teacherId === $defaultTeacherId || $hasExistingAttempt,
            404
        );

        return $teacher;
    }

    private function questions(int $teacherId)
    {
        return DB::table('evaluation_questions')
            ->where('user_id', $teacherId)
            ->where('is_published', true)
            ->whereBetween('nomor_soal', [1, 15])
            ->orderBy('nomor_soal')
            ->get();
    }

    private function defaultEvaluationTeacher(): array
    {
        $configuredTeacherId = config('app.evaluation_default_teacher_id');
        if ($configuredTeacherId !== null && $configuredTeacherId !== '') {
            $teacher = User::query()
                ->where('role', 'guru')
                ->find((int) $configuredTeacherId);

            return [
                $teacher?->id ? (int) $teacher->id : null,
                $teacher ? null : 'Evaluasi Akhir belum tersedia.',
            ];
        }

        $publishedTeacherIds = User::query()
            ->where('role', 'guru')
            ->whereIn('id', DB::table('evaluation_questions')
                ->where('is_published', true)
                ->distinct()
                ->pluck('user_id'))
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($publishedTeacherIds->count() === 1) {
            return [$publishedTeacherIds->first(), null];
        }

        return [
            null,
            $publishedTeacherIds->isEmpty()
                ? 'Evaluasi Akhir belum tersedia.'
                : 'Bank Evaluasi bersama belum ditetapkan.',
        ];
    }

    private function isReady($questions): bool
    {
        if (
            $questions->count() !== 15
            || $questions->pluck('nomor_soal')->map(fn ($n) => (int) $n)->all() !== range(1, 15)
        ) {
            return false;
        }

        foreach ($questions as $question) {
            $content = is_array($question->konten_soal)
                ? $question->konten_soal
                : (json_decode((string) $question->konten_soal, true) ?: []);
            $key = is_array($question->kunci_jawaban)
                ? $question->kunci_jawaban
                : (json_decode((string) $question->kunci_jawaban, true) ?: []);

            if ($question->tipe_soal !== 'pilihan_ganda') {
                return false;
            }
            if (!is_string($question->pertanyaan)
                || trim($question->pertanyaan) === '') {
                return false;
            }
            foreach (['a', 'b', 'c', 'd'] as $letter) {
                if (!is_string($content['pilihan_'.$letter] ?? null)
                    || trim($content['pilihan_'.$letter]) === '') {
                    return false;
                }
            }
            if (!in_array($key['jawaban'] ?? null, ['A', 'B', 'C', 'D'], true)) {
                return false;
            }
        }

        return true;
    }

    private function requireReady($questions): void
    {
        if (!$this->isReady($questions)) {
            throw ValidationException::withMessages([
                'evaluasi' => 'Evaluasi belum siap. Guru harus memublikasikan tepat 15 soal pilihan ganda A–D yang lengkap.',
            ]);
        }
    }

    /**
     * Normalize JSON columns into arrays for scoring and saved answers.
     * The database query builder returns these fields as JSON strings.
     */
    private function normalizeSnapshot($questions): array
    {
        return collect($questions)->map(function ($q) {
            $row = (array) $q;
            foreach (['konten_soal', 'kunci_jawaban'] as $field) {
                if (is_string($row[$field] ?? null)) {
                    $row[$field] = json_decode($row[$field], true) ?: [];
                }
            }
            return $row;
        })->all();
    }

    private function publicQuestions($questions): array
    {
        return $questions->map(function ($q) {
            $row = is_array($q) ? $q : (array) $q;
            $content = $row['konten_soal'] ?? [];

            return [
                'id' => $row['id'] ?? null,
                'nomor_soal' => (int) $row['nomor_soal'],
                'tipe_soal' => $row['tipe_soal'],
                'pertanyaan' => $row['pertanyaan'],
                'konten_soal' => is_array($content)
                    ? $content
                    : (json_decode((string) $content, true) ?: []),
            ];
        })->all();
    }

    private function current(int $id, int $teacherId)
    {
        $attempts = DB::table('evaluation_attempts')
            ->where('user_id', $id)
            ->where('teacher_id', $teacherId)
            ->orderByDesc('id')
            ->get()
            ->filter(fn (object $attempt) => $this->snapshotTeacherId($attempt) === $teacherId)
            ->values();

        $inProgress = $attempts->first(
            fn (object $attempt) => $attempt->submitted_at === null
        );
        if ($inProgress) {
            return $inProgress;
        }

        $validAttemptIds = collect(
            app(\App\Services\EvaluationResultService::class)
                ->validCompletedForStudent($id)
        )
            ->where('teacher_id', $teacherId)
            ->pluck('id')
            ->all();

        return $attempts->first(
            fn (object $attempt) => in_array((int) $attempt->id, $validAttemptIds, true)
        );
    }

    private function snapshotTeacherId(object $attempt): ?int
    {
        $snapshot = json_decode((string) $attempt->question_snapshot, true);
        if (!is_array($snapshot) || count($snapshot) !== 15) {
            return null;
        }

        $numbers = [];
        $teacherIds = [];
        foreach ($snapshot as $question) {
            if (!is_array($question)
                || !isset($question['nomor_soal'], $question['user_id'])
                || !is_numeric($question['nomor_soal'])
                || !is_numeric($question['user_id'])) {
                return null;
            }

            $numbers[] = (int) $question['nomor_soal'];
            $teacherIds[] = (int) $question['user_id'];
        }

        sort($numbers);
        $teacherIds = array_values(array_unique($teacherIds));

        return $numbers === range(1, 15) && count($teacherIds) === 1
            ? $teacherIds[0]
            : null;
    }

    private function status($attempt): string
    {
        if (!$attempt) return 'none';
        if ($attempt->submitted_at) return 'completed';
        if (Carbon::parse($attempt->ends_at)->isPast()) return 'expired';
        return 'active';
    }

    /** Siswa yang memperoleh nilai >= 70 tidak perlu mengulang Evaluasi Guru tersebut. */
    private const KKM = 70;

    private function passingAttempt(int $userId, int $teacherId)
    {
        $attemptId = collect(
            app(\App\Services\EvaluationResultService::class)
                ->validCompletedForStudent($userId)
        )
            ->first(fn (array $attempt) => $attempt['teacher_id'] === $teacherId
                && $attempt['score'] >= self::KKM)['id'] ?? null;

        return $attemptId === null
            ? null
            : DB::table('evaluation_attempts')->where('id', $attemptId)->first();
    }

    public function teachers(Request $request): JsonResponse
    {
        $studentId = $this->ensureSiswa($request);
        $this->requireAttemptSchema();
        [$teacherId, $message] = $this->defaultEvaluationTeacher();
        $attempt = $teacherId ? $this->current($studentId, $teacherId) : null;
        $questions = $teacherId ? $this->questions($teacherId) : collect();

        return response()->json([
            'default_teacher_id' => $teacherId,
            'status' => $attempt ? $this->status($attempt) : 'none',
            'passed' => $teacherId
                ? $this->passingAttempt($studentId, $teacherId) !== null
                : false,
            'ready' => $teacherId && $attempt && $this->status($attempt) === 'active'
                ? true
                : $this->isReady($questions),
            'message' => $message,
        ]);
    }

    public function state(
        Request $request,
        int $teacher,
        WebsiteNotificationService $notifications
    ): JsonResponse
    {
        $id = $this->ensureSiswa($request);
        $this->requireAttemptSchema();
        $owner = $this->evaluationTeacher($id, $teacher);
        $attempt = $this->current($id, $teacher);
        $status=$this->status($attempt);
        if ($status === 'expired') {
            $this->finishAttempt($attempt, $notifications);
            $attempt=$this->current($id, $teacher);
            $status=$this->status($attempt);
        }
        $questions = $status === 'active'
            ? collect($this->normalizeSnapshot(
                collect(json_decode((string) $attempt->question_snapshot, true) ?: [])
            ))
            : $this->questions($teacher);
        $passing = $this->passingAttempt($id, $teacher);
        // Gunakan nilai kelulusan jika ditemukan pada percobaan sebelumnya.
        if ($passing && (!$attempt || $status !== 'active')) {
            $attempt = $passing;
            $status = 'completed';
        }
        return response()->json([
            'teacher' => ['id' => $owner->id, 'name' => $owner->name],
            'status'=>$status,
            'passed' => (bool) $passing,
            'can_retry' => !$passing,
            'kkm' => self::KKM,
            'ready' => $status === 'active' || $this->isReady($questions),
            'questions'=>$status==='active'?$this->publicQuestions($questions):[],
            'attempt'=>$attempt ? [
                'id'=>$attempt->id, 'ends_at'=>Carbon::parse($attempt->ends_at)->toIso8601String(),
                'answers'=>$status==='active'?(json_decode($attempt->answers, true) ?: []):[],
                'score'=>$status==='completed'?$attempt->score:null,
                'correct_count'=>$status==='completed'?$attempt->correct_count:null,
                'details'=>$status==='completed'?$this->buildAttemptDetails($attempt):[],
            ]:null,
        ]);
    }

    public function start(
        Request $request,
        int $teacher,
        WebsiteNotificationService $notifications
    ): JsonResponse
    {
        $id = $this->ensureSiswa($request);
        $this->requireAttemptSchema();
        $this->evaluationTeacher($id, $teacher);
        [$defaultTeacherId] = $this->defaultEvaluationTeacher();
        $existingAttempt = $this->current($id, $teacher);
        abort_unless(
            $teacher === $defaultTeacherId
                || ($existingAttempt && $this->status($existingAttempt) === 'active'),
            404
        );

        return DB::transaction(function () use ($id, $teacher, $notifications) {
            DB::table('users')->where('id', $id)->lockForUpdate()->first(['id']);
            $current = $this->current($id, $teacher);

            if ($current && $this->status($current) === 'expired') {
                $this->finishAttempt($current, $notifications);
                $current = null;
            }

            if ($current && $this->status($current) === 'active') {
                $snapshot = collect(
                    json_decode((string) $current->question_snapshot, true) ?: []
                );

                return response()->json([
                    'message' => 'Evaluasi dilanjutkan.',
                    'attempt_id' => (int) $current->id,
                    'ends_at' => Carbon::parse($current->ends_at)->toIso8601String(),
                    'questions' => $this->publicQuestions($snapshot),
                    'answers' => json_decode((string) $current->answers, true) ?: [],
                ]);
            }

            if ($this->passingAttempt($id, $teacher)) {
                return response()->json([
                    'message' => 'Evaluasi Guru ini sudah tuntas. Kamu tidak dapat mengerjakannya lagi.',
                    'passed' => true,
                    'kkm' => self::KKM,
                ], 409);
            }

            $questions = $this->questions($teacher);
            $this->requireReady($questions);

            $now=now();
            $attemptId=DB::table('evaluation_attempts')->insertGetId([
                'user_id'=>$id,
                'teacher_id'=>$teacher,
                'answers'=>json_encode(new \stdClass()),
                'question_snapshot'=>json_encode($this->normalizeSnapshot($questions)),
                'ends_at'=>$now->copy()->addMinutes(45),
                'created_at'=>$now,'updated_at'=>$now,
            ]);
            return response()->json(['message'=>'Evaluasi dimulai.','attempt_id'=>$attemptId,'ends_at'=>$now->copy()->addMinutes(45)->toIso8601String(), 'questions'=>$this->publicQuestions($questions)]);
        });
    }

    public function save(
        Request $request,
        int $teacher,
        WebsiteNotificationService $notifications
    ): JsonResponse
    {
        $id = $this->ensureSiswa($request);
        $this->requireAttemptSchema();
        $this->evaluationTeacher($id, $teacher);
        $data=$request->validate(['answers'=>['required','array']]);
        return DB::transaction(function () use ($id, $teacher, $data, $notifications) {
        $attempt=DB::table('evaluation_attempts')->where('user_id',$id)
            ->where('teacher_id', $teacher)
            ->whereNull('submitted_at')->orderByDesc('id')->lockForUpdate()->first();
            if (!$attempt || $attempt->submitted_at) return response()->json(['message'=>'Tidak ada evaluasi aktif.'],409);
            if (Carbon::parse($attempt->ends_at)->isPast()) {
                $this->finishAttempt($attempt, $notifications);
                return response()->json(['message'=>'Waktu evaluasi habis. Jawaban terakhir sebelum batas waktu sudah dinilai.'],409);
            }
            $questions=collect(json_decode($attempt->question_snapshot,true) ?: []);
            $valid=[];
            foreach ($questions as $q) {
                $n=(string)$q['nomor_soal'];
                $answer=$data['answers'][$n] ?? null;
                if ($q['tipe_soal']==='pilihan_ganda') {
                    if (in_array($answer,['A','B','C','D'],true)) $valid[$n]=$answer;
                } elseif (is_array($answer)) {
                    $items=collect($q['konten_soal']['items'] ?? [])->pluck('id')->map(fn($v)=>(string)$v)->all();
                    $targets=collect($q['konten_soal']['targets'] ?? [])->pluck('id')->map(fn($v)=>(string)$v)->all();
                    $pairs=[];
                    foreach ($answer as $key=>$value) {
                        if (in_array((string)$key,$items,true) && is_string($value) && in_array($value,$targets,true)) $pairs[(string)$key]=$value;
                    }
                    $valid[$n]=$pairs;
                }
            }
            DB::table('evaluation_attempts')->where('id',$attempt->id)->update(['answers'=>json_encode($valid),'updated_at'=>now()]);
            return response()->json(['message'=>'Jawaban tersimpan.']);
        });
    }

    private function finishAttempt(
        $attempt,
        WebsiteNotificationService $notifications
    ): array
    {
        return DB::transaction(function () use ($attempt, $notifications) {
            $current = DB::table('evaluation_attempts')
                ->where('id', $attempt->id)
                ->lockForUpdate()
                ->first();

            if (!$current) {
                return ['score' => null, 'correct_count' => null];
            }
            if ($current->submitted_at) {
                return [
                    'score' => $current->score,
                    'correct_count' => $current->correct_count,
                ];
            }

            $snapshotTeacherId = $this->snapshotTeacherId($current);
            abort_unless(
                $snapshotTeacherId !== null
                    && $current->teacher_id !== null
                    && (int) $current->teacher_id === $snapshotTeacherId,
                409,
                'Identitas Guru pada snapshot Evaluasi tidak valid.'
            );

            $questions = json_decode($current->question_snapshot, true) ?: [];
            $answers = json_decode($current->answers, true) ?: [];
            $correct = 0;
            foreach ($questions as $question) {
                $number = (string) $question['nomor_soal'];
                $expected = $question['kunci_jawaban'] ?? null;
                if (is_string($expected)) {
                    $expected = json_decode($expected, true);
                }
                $response = $answers[$number] ?? null;
                if ($question['tipe_soal'] === 'pilihan_ganda') {
                    if (
                        $response !== null
                        && $response === ($expected['jawaban'] ?? null)
                    ) {
                        $correct++;
                    }
                } else {
                    $pairs = $expected['pairs'] ?? [];
                    if (
                        is_array($response)
                        && count($pairs) > 0
                        && count($response) === count($pairs)
                        && !array_diff_assoc($pairs, $response)
                    ) {
                        $correct++;
                    }
                }
            }

            $score = count($questions)
                ? round(100 * $correct / count($questions))
                : 0;
            $now = now();
            $updated = DB::table('evaluation_attempts')
                ->where('id', $current->id)
                ->whereNull('submitted_at')
                ->update([
                    'score' => $score,
                    'correct_count' => $correct,
                    'submitted_at' => $now,
                    'updated_at' => $now,
                ]);
            if ($updated === 1) {
                $notifications->completedAssessment(
                    (int) $current->user_id,
                    'evaluation',
                    (int) $current->id
                );
            }

            return ['score' => $score, 'correct_count' => $correct];
        });
    }

    /** Rincian hasil hanya dikirim setelah evaluasi dikumpulkan. */
    private function buildAttemptDetails($attempt): array
    {
        $questions = json_decode($attempt->question_snapshot, true) ?: [];
        $answers = json_decode($attempt->answers, true) ?: [];
        $details = [];

        foreach ($questions as $question) {
            $number = (int) ($question['nomor_soal'] ?? 0);
            $type = $question['tipe_soal'] ?? '';
            $content = $question['konten_soal'] ?? [];
            $key = $question['kunci_jawaban'] ?? [];
            if (is_string($content)) $content = json_decode($content, true) ?: [];
            if (is_string($key)) $key = json_decode($key, true) ?: [];
            $answer = $answers[(string) $number] ?? null;
            $entry = [
                'nomor_soal' => $number,
                'tipe_soal' => $type,
                'pertanyaan' => $question['pertanyaan'] ?? '',
                'pembahasan' => $question['pembahasan'] ?? '',
                'pairs' => [],
            ];

            if ($type === 'pilihan_ganda') {
                $correct = $key['jawaban'] ?? null;
                $choices = [
                    'A' => $content['pilihan_a'] ?? '',
                    'B' => $content['pilihan_b'] ?? '',
                    'C' => $content['pilihan_c'] ?? '',
                    'D' => $content['pilihan_d'] ?? '',
                ];
                $entry['benar'] = is_string($answer) && $answer === $correct;
                $entry['jawaban_siswa'] = $answer ? $answer . '. ' . ($choices[$answer] ?? '') : 'Belum dijawab';
                $entry['jawaban_benar'] = $correct ? $correct . '. ' . ($choices[$correct] ?? '') : '-';
            } else {
                $studentPairs = is_array($answer) ? $answer : [];
                $correctPairs = $key['pairs'] ?? [];
                $targetLabels = collect($content['targets'] ?? [])->mapWithKeys(fn ($target) => [
                    (string) ($target['id'] ?? '') => $target['label'] ?? ''
                ])->all();
                foreach ($content['items'] ?? [] as $item) {
                    $itemId = (string) ($item['id'] ?? '');
                    $student = $studentPairs[$itemId] ?? null;
                    $expected = $correctPairs[$itemId] ?? null;
                    $entry['pairs'][] = [
                        'label' => $item['label'] ?? $itemId,
                        'jawaban_siswa' => $student !== null ? ($targetLabels[$student] ?? $student) : 'Belum dijawab',
                        'jawaban_benar' => $expected !== null ? ($targetLabels[$expected] ?? $expected) : '-',
                        'benar' => $student !== null && $student === $expected,
                    ];
                }
                $entry['benar'] = count($correctPairs) > 0
                    && count($studentPairs) === count($correctPairs)
                    && !array_diff_assoc($correctPairs, $studentPairs);
            }
            $details[] = $entry;
        }
        usort($details, fn ($a, $b) => $a['nomor_soal'] <=> $b['nomor_soal']);
        return $details;
    }

    public function finish(
        Request $request,
        int $teacher,
        WebsiteNotificationService $notifications
    ): JsonResponse
    {
        $id = $this->ensureSiswa($request);
        $this->requireAttemptSchema();
        $this->evaluationTeacher($id, $teacher);

        return DB::transaction(function () use ($id, $teacher, $notifications) {
            $attempt=DB::table('evaluation_attempts')->where('user_id',$id)
                ->where('teacher_id', $teacher)
                ->whereNull('submitted_at')->orderByDesc('id')->lockForUpdate()->first();
            if (!$attempt) return response()->json(['message'=>'Belum ada evaluasi.'],409);
            $result=$this->finishAttempt($attempt, $notifications);
            $saved = DB::table('evaluation_attempts')->where('id', $attempt->id)->first();
            return response()->json(['message'=>'Evaluasi selesai.'] + $result + [
                'details' => $this->buildAttemptDetails($saved),
            ]);
        });
    }
}
