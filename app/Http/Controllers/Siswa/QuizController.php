<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\QuizQuestion;
use App\Models\StudentAssessmentResult;
use App\Models\StudentQuizAttempt;
use App\Models\StudentQuizAnswer;
use App\Models\StudentQuizActivityAnswer;
use App\Models\StudentSubchapterProgress;
use App\Models\Subchapter;
use App\Services\WebsiteNotificationService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class QuizController extends Controller
{
    private const ATTEMPT_QUESTION_SNAPSHOTS = '_question_snapshots';

    private function ensureSiswa(Request $request): void
    {
        abort_unless(
            $request->user()?->role === 'siswa',
            403,
            'Fitur kuis hanya dapat diakses oleh Siswa.'
        );
    }

    public function questions(
        Request $request,
        int $chapterNumber,
        int $subchapterNumber
    ): JsonResponse {
        $this->ensureSiswa($request);
        $chapters = Chapter::query()
            ->where('nomor_bab', $chapterNumber)
            ->where('is_published', true)
            ->whereHas('guru', fn ($query) => $query->where('role', 'guru'))
            ->whereHas('subchapters', fn ($query) => $query
                ->where('nomor_subbab', $subchapterNumber)
                ->where('is_published', true))
            ->orderBy('id')
            ->get();

        if ($chapters->count() > 1) {
            return response()->json([
                'message' => 'Terdapat lebih dari satu Bab dengan nomor tersebut. Buka kuis dari Subbab yang dipilih.',
            ], 409);
        }

        $questions = collect();
        foreach ($chapters as $chapter) {
            $subchapters = $chapter->subchapters()
                ->where('nomor_subbab', $subchapterNumber)
                ->where('is_published', true)
                ->get();
            if ($subchapters->count() > 1) {
                return response()->json([
                    'message' => 'Terdapat lebih dari satu Subbab dengan nomor tersebut. Buka kuis dari Subbab yang dipilih.',
                ], 409);
            }
            $subchapter = $subchapters->first();
            if (!$subchapter) {
                continue;
            }
            $questions = $questions->concat($subchapter->quizQuestions()
                ->where('is_published', true)
                ->where('activity_type', 'multiple_choice')
                ->orderBy('nomor_soal')
                ->get([
                    'id',
                    'nomor_soal',
                    'activity_type',
                    'pertanyaan',
                    'pilihan_a',
                    'pilihan_b',
                    'pilihan_c',
                    'pilihan_d',
                ]));
        }

        return response()->json([
            'chapter_number' => $chapterNumber,
            'subchapter_number' => $subchapterNumber,
            'questions' => $questions->values(),
        ]);
    }

    public function questionsForSubchapter(
        Request $request,
        Subchapter $subchapter
    ): JsonResponse {
        $this->ensureSiswa($request);
        $this->ensurePublishedSubchapter($subchapter);

        $attemptStorageAvailable = Schema::hasTable('student_quiz_attempts');
        // Resume the student's active attempt when the page has no attempt ID yet.
        $requestedAttemptId = $request->integer('attempt_id');
        $activeAttemptQuery = $attemptStorageAvailable
            ? StudentQuizAttempt::query()
                ->where('user_id', $request->user()->id)
                ->where('subchapter_id', $subchapter->id)
                ->where('status', 'in_progress')
            : null;
        $activeAttempt = $activeAttemptQuery
            ? ($requestedAttemptId > 0
                ? $activeAttemptQuery
                ->whereKey($requestedAttemptId)
                ->first()
                : $activeAttemptQuery->latest('id')->first())
            : null;
        $questions = $this->questionsForAttempt($subchapter, $activeAttempt);
        $latestAttempt = $attemptStorageAvailable ? StudentQuizAttempt::query()
            ->where('user_id', $request->user()->id)
            ->where('subchapter_id', $subchapter->id)
            ->where('status', 'completed')
            ->latest('completed_at')
            ->latest('id')
            ->first() : null;
        $legacyResult = $latestAttempt ? null : StudentAssessmentResult::query()
            ->where('user_id', $request->user()->id)
            ->where('assessment_type', 'quiz')
            ->where('subchapter_id', $subchapter->id)
            ->whereNotNull('completed_at')
            ->latest('completed_at')
            ->latest('id')
            ->first();

        $items = $questions->map(function (QuizQuestion $question) use ($activeAttempt) {
            $item = $question->activity_type === 'multiple_choice'
                ? [
                    'id' => $question->id,
                    'nomor_soal' => $question->nomor_soal,
                    'activity_type' => 'multiple_choice',
                    'pertanyaan' => $question->pertanyaan,
                    'pilihan_a' => $question->pilihan_a,
                    'pilihan_b' => $question->pilihan_b,
                    'pilihan_c' => $question->pilihan_c,
                    'pilihan_d' => $question->pilihan_d,
                ]
                : $this->publicActivity($question);

            $answer = $activeAttempt?->answers[(string) $question->id] ?? null;
            $item['student_answer'] = $answer === null
                ? null
                : ($question->activity_type === 'multiple_choice'
                    ? ['answer' => $answer['answer'] ?? null]
                    : ($answer['answers'] ?? null));
            if (
                $answer !== null
                && $question->activity_type === 'multiple_choice'
            ) {
                $selected = $answer['answer'] ?? null;
                $item['answer_feedback'] = [
                    'correct' => $selected === $question->kunci_jawaban,
                    'correct_answer' => $question->kunci_jawaban,
                    'explanation' => $question->pembahasan ?? '',
                ];
            } elseif (
                $answer !== null
                && $question->activity_type !== 'multiple_choice'
                && $this->hasCompleteActivityAnswer($question, $answer['answers'] ?? [])
            ) {
                $item['activity_feedback'] = $this->activityFeedback(
                    $question,
                    $answer['answers'] ?? []
                );
            }

            return $item;
        })->values();

        $attempts = $attemptStorageAvailable ? StudentQuizAttempt::query()
            ->where('user_id', $request->user()->id)
            ->where('subchapter_id', $subchapter->id)
            ->where('status', 'completed')
            ->latest('completed_at')
            ->latest('id')
            ->get()
            ->map(fn (StudentQuizAttempt $attempt) => $this->attemptSummary($attempt))
            ->values() : collect();

        $latestResult = $latestAttempt
            ? $this->attemptSummary($latestAttempt)
            : ($legacyResult ? $this->legacyResultSummary($legacyResult) : null);
        if (!$activeAttempt && $latestAttempt) {
            $latestResult['review'] = $latestAttempt->review ?? [];
        } elseif (!$activeAttempt && $legacyResult) {
            $latestResult['review'] = $this->legacyReview($subchapter, $request->user()->id);
        }

        return response()->json([
            'chapter_number' => $subchapter->chapter->nomor_bab,
            'subchapter_number' => $subchapter->nomor_subbab,
            'questions' => $items,
            'attempt_storage_available' => $attemptStorageAvailable,
            'active_attempt' => $activeAttempt
                ? $this->attemptSummary($activeAttempt)
                : null,
            'latest_result' => $latestResult,
            'attempt_history' => $attempts,
        ]);
    }

    public function startAttempt(
        Request $request,
        Subchapter $subchapter
    ): JsonResponse {
        $this->ensureSiswa($request);
        $this->ensurePublishedSubchapter($subchapter);
        abort_unless(
            Schema::hasTable('student_quiz_attempts'),
            503,
            'Penyimpanan attempt Kuis belum tersedia. Hubungi administrator untuk menerapkan skema attempt yang disetujui.'
        );

        $data = $request->validate([
            'restart' => ['sometimes', 'boolean'],
        ]);
        $restart = (bool) ($data['restart'] ?? false);

        $attempt = DB::transaction(function () use ($request, $subchapter, $restart) {
            $lockedSubchapter = Subchapter::query()
                ->whereKey($subchapter->id)
                ->lockForUpdate()
                ->firstOrFail();
            $this->ensurePublishedSubchapter($lockedSubchapter);

            $progress = StudentSubchapterProgress::query()
                ->where('user_id', $request->user()->id)
                ->where('subchapter_id', $lockedSubchapter->id)
                ->lockForUpdate()
                ->first();
            abort_unless(
                $progress?->opened_at,
                422,
                'Buka dan pelajari materi Subbab ini sebelum mengerjakan Kuis.'
            );
            $questions = $lockedSubchapter->quizQuestions()
                ->where('is_published', true)
                ->orderBy('nomor_soal')
                ->get();
            abort_if(
                $questions->isEmpty(),
                409,
                'Belum ada soal kuis aktif pada Subbab ini.'
            );

            $activeAttempt = StudentQuizAttempt::query()
                ->where('user_id', $request->user()->id)
                ->where('subchapter_id', $lockedSubchapter->id)
                ->where('status', 'in_progress')
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if ($activeAttempt && !$restart) {
                return $activeAttempt;
            }

            return StudentQuizAttempt::query()->create([
                'user_id' => $request->user()->id,
                'subchapter_id' => $lockedSubchapter->id,
                'status' => 'in_progress',
                'answers' => [],
                'review' => [
                    self::ATTEMPT_QUESTION_SNAPSHOTS => $questions
                        ->map(fn (QuizQuestion $question) => $this->randomizeMatchingSnapshot(
                            $question->getAttributes()
                        ))
                        ->all(),
                ],
            ]);
        });

        return response()->json([
            'attempt' => $this->attemptSummary($attempt),
        ], 201);
    }

    public function showAttempt(
        Request $request,
        StudentQuizAttempt $attempt
    ): JsonResponse {
        $this->ensureSiswa($request);
        abort_unless(
            Schema::hasTable('student_quiz_attempts'),
            503,
            'Penyimpanan attempt Kuis belum tersedia.'
        );
        abort_unless((int) $attempt->user_id === (int) $request->user()->id, 404);
        abort_unless($attempt->status === 'completed', 409, 'Kuis ini belum selesai.');

        return response()->json([
            'result' => [
                ...$this->attemptSummary($attempt),
                'review' => $attempt->review ?? [],
            ],
        ]);
    }

    public function checkAnswer(
        Request $request,
        int $question,
        WebsiteNotificationService $notifications
    ): JsonResponse {
        $this->ensureSiswa($request);
        abort_unless(
            Schema::hasTable('student_quiz_attempts'),
            503,
            'Penyimpanan attempt Kuis belum tersedia.'
        );
        $data = $request->validate([
            'attempt_id' => ['required', 'integer'],
            'jawaban' => ['required', Rule::in(['A', 'B', 'C', 'D'])],
        ]);
        $quizQuestion = $this->findAnswerableQuestion(
            $request,
            (int) $data['attempt_id'],
            $question,
            ['multiple_choice']
        );

        return $this->saveAttemptAnswer(
            $request,
            (int) $data['attempt_id'],
            $quizQuestion,
            ['type' => 'multiple_choice', 'answer' => $data['jawaban']],
            $notifications
        );
    }

    public function saveActivityAnswer(
        Request $request,
        int $question,
        WebsiteNotificationService $notifications
    ): JsonResponse {
        $this->ensureSiswa($request);
        abort_unless(
            Schema::hasTable('student_quiz_attempts'),
            503,
            'Penyimpanan attempt Kuis belum tersedia.'
        );
        $data = $request->validate([
            'attempt_id' => ['required', 'integer'],
        ]);
        $activity = $this->findAnswerableQuestion(
            $request,
            (int) $data['attempt_id'],
            $question,
            ['drag_drop', 'matching']
        );
        $rules = [
            'attempt_id' => ['required', 'integer'],
            'answers' => $activity->activity_type === 'drag_drop'
                ? ['present', 'array']
                : ['present', 'array', 'max:144'],
        ];
        if ($activity->activity_type === 'drag_drop') {
            $rules['answers.*'] = ['required', 'string', 'max:80'];
        } else {
            $rules['answers.*.left_id'] = ['required', 'string', 'max:80'];
            $rules['answers.*.right_id'] = ['required', 'string', 'max:80'];
        }
        $data = $request->validate($rules);

        $payload = $activity->activity_payload ?? [];
        $targetField = $activity->activity_type === 'drag_drop' ? 'categories' : 'right_items';
        $itemField = $activity->activity_type === 'drag_drop' ? 'items' : 'left_items';
        $itemIds = collect($payload[$itemField] ?? [])->pluck('id')->all();
        $targetIds = collect($payload[$targetField] ?? [])->pluck('id')->all();
        $answers = $data['answers'];
        if ($activity->activity_type === 'matching') {
            $seenPairs = [];
            foreach ($answers as $pair) {
                abort_unless(
                    is_array($pair)
                    && in_array($pair['left_id'] ?? null, $itemIds, true)
                    && in_array($pair['right_id'] ?? null, $targetIds, true),
                    422,
                    'Relasi berisi objek atau pasangan yang tidak tersedia.'
                );
                $key = $pair['left_id'] . ':' . $pair['right_id'];
                abort_unless(!isset($seenPairs[$key]), 422, 'Relasi pasangan tidak boleh duplikat.');
                $seenPairs[$key] = true;
            }
        } else {
            foreach ($answers as $itemId => $targetId) {
                abort_unless(
                    in_array((string) $itemId, $itemIds, true)
                    && in_array($targetId, $targetIds, true),
                    422,
                    'Jawaban berisi objek atau kategori yang tidak tersedia.'
                );
            }
        }

        $result = $this->saveAttemptAnswer(
            $request,
            (int) $data['attempt_id'],
            $activity,
            ['type' => $activity->activity_type, 'answers' => $answers],
            $notifications
        );
        if ($this->hasCompleteActivityAnswer($activity, $answers)) {
            $result->setData([
                ...$result->getData(true),
                'activity_feedback' => $this->activityFeedback($activity, $answers),
            ]);
        }

        return $result;
    }

    private function saveAttemptAnswer(
        Request $request,
        int $attemptId,
        QuizQuestion $question,
        array $answer,
        WebsiteNotificationService $notifications
    ): JsonResponse {
        $payload = DB::transaction(function () use (
            $request,
            $attemptId,
            $question,
            $answer,
            $notifications
        ) {
            Subchapter::query()
                ->whereKey($question->subchapter_id)
                ->lockForUpdate()
                ->firstOrFail();

            $storedQuestion = QuizQuestion::withTrashed()
                ->whereKey($question->id)
                ->where('subchapter_id', $question->subchapter_id)
                ->lockForUpdate()
                ->firstOrFail();

            $attempt = StudentQuizAttempt::query()
                ->whereKey($attemptId)
                ->where('user_id', $request->user()->id)
                ->where('subchapter_id', $question->subchapter_id)
                ->lockForUpdate()
                ->firstOrFail();
            abort_unless($attempt->status === 'in_progress', 409, 'Pengerjaan ini sudah selesai.');
            abort_unless(!$storedQuestion->trashed(), 404);
            $snapshots = $this->attemptQuestionSnapshots($attempt);
            if ($snapshots !== null) {
                $snapshot = $this->findQuestionSnapshot($snapshots, (int) $question->id);
                abort_unless($snapshot, 404);
                $question = QuizQuestion::hydrate([$snapshot])->firstOrFail();
            } else {
                abort_unless(!$storedQuestion->trashed() && $storedQuestion->is_published, 404);
                $question = $storedQuestion;
            }
            $answers = $attempt->answers ?? [];
            $answers[(string) $question->id] = $answer;
            $attempt->answers = $answers;
            $attempt->save();

            return $this->completeAttemptIfReady($attempt, $notifications);
        });

        if (($answer['type'] ?? null) === 'multiple_choice') {
            $selected = $answer['answer'];
            $payload['answer_feedback'] = [
                'selected' => $selected,
                'correct' => $selected === $question->kunci_jawaban,
                'correct_answer' => $question->kunci_jawaban,
                'explanation' => $question->pembahasan ?? '',
            ];
        }

        return response()->json($payload);
    }

    private function findAnswerableQuestion(
        Request $request,
        int $attemptId,
        int $questionId,
        array $allowedTypes
    ): QuizQuestion {
        $attempt = StudentQuizAttempt::query()
            ->whereKey($attemptId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();
        abort_unless($attempt->status === 'in_progress', 409, 'Pengerjaan ini sudah selesai.');
        $snapshots = $this->attemptQuestionSnapshots($attempt);
        $snapshot = $snapshots === null
            ? null
            : $this->findQuestionSnapshot($snapshots, $questionId);
        if ($snapshots !== null) {
            abort_unless($snapshot, 404);
        }

        $questionQuery = QuizQuestion::withTrashed()
            ->whereKey($questionId)
            ->where('subchapter_id', $attempt->subchapter_id)
            ->whereHas('subchapter', fn ($query) => $query
                ->where('is_published', true)
                ->whereHas('chapter', fn ($chapterQuery) => $chapterQuery
                    ->where('is_published', true)
                    ->whereHas('guru', fn ($guruQuery) => $guruQuery->where('role', 'guru'))));
        if ($snapshots === null) {
            $questionQuery->where('is_published', true);
        }

        $storedQuestion = $questionQuery->firstOrFail();
        abort_unless(!$storedQuestion->trashed(), 404);
        if ($snapshots === null) {
            $question = $storedQuestion;
        } else {
            $question = QuizQuestion::hydrate([$snapshot])->first();
        }

        abort_unless(
            $question && in_array($question->activity_type, $allowedTypes, true),
            404
        );

        return $question;
    }

    private function questionsForAttempt(
        Subchapter $subchapter,
        ?StudentQuizAttempt $attempt
    ): EloquentCollection {
        $snapshots = $attempt
            ? $this->attemptQuestionSnapshots($attempt)
            : null;
        if ($attempt && $snapshots === null) {
            $snapshots = $subchapter->quizQuestions()
                ->where('is_published', true)
                ->orderBy('nomor_soal')
                ->get()
                ->map(fn (QuizQuestion $question) => $this->randomizeMatchingSnapshot(
                    $question->getAttributes()
                ))
                ->all();
            $review = $attempt->review ?? [];
            $review[self::ATTEMPT_QUESTION_SNAPSHOTS] = $snapshots;
            $attempt->review = $review;
            $attempt->save();
        }
        if ($snapshots !== null) {
            $questionIds = collect($snapshots)
                ->filter(fn ($snapshot) => is_array($snapshot) && isset($snapshot['id']))
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();
            $activeQuestionIds = QuizQuestion::query()
                ->where('subchapter_id', $subchapter->id)
                ->whereIn('id', $questionIds)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $snapshots = array_values(array_filter(
                $snapshots,
                fn ($snapshot) => is_array($snapshot)
                    && in_array((int) ($snapshot['id'] ?? 0), $activeQuestionIds, true)
            ));
            $randomizedSnapshots = array_map(
                fn (array $snapshot) => $this->randomizeMatchingSnapshot($snapshot),
                $snapshots
            );
            if ($randomizedSnapshots !== $snapshots) {
                $review = $attempt->review ?? [];
                $review[self::ATTEMPT_QUESTION_SNAPSHOTS] = array_map(
                    function (array $snapshot) use ($snapshots, $randomizedSnapshots) {
                        foreach ($snapshots as $index => $original) {
                            if ((int) ($original['id'] ?? 0) === (int) ($snapshot['id'] ?? 0)) {
                                return $randomizedSnapshots[$index];
                            }
                        }
                        return $snapshot;
                    },
                    $attempt->review[self::ATTEMPT_QUESTION_SNAPSHOTS]
                );
                $attempt->review = $review;
                $attempt->save();
            }

            return QuizQuestion::hydrate($randomizedSnapshots)->sortBy('nomor_soal')->values();
        }

        return $subchapter->quizQuestions()
            ->where('is_published', true)
            ->orderBy('nomor_soal')
            ->get();
    }

    private function attemptQuestionSnapshots(StudentQuizAttempt $attempt): ?array
    {
        $review = $attempt->review ?? [];
        if (!is_array($review)
            || !array_key_exists(self::ATTEMPT_QUESTION_SNAPSHOTS, $review)
            || !is_array($review[self::ATTEMPT_QUESTION_SNAPSHOTS])
        ) {
            return null;
        }

        return $review[self::ATTEMPT_QUESTION_SNAPSHOTS];
    }

    private function findQuestionSnapshot(array $snapshots, int $questionId): ?array
    {
        foreach ($snapshots as $snapshot) {
            if (is_array($snapshot) && (int) ($snapshot['id'] ?? 0) === $questionId) {
                return $snapshot;
            }
        }

        return null;
    }

    private function randomizeMatchingSnapshot(array $snapshot): array
    {
        if (($snapshot['activity_type'] ?? null) !== 'matching'
            || !empty($snapshot['_matching_order_randomized'])
        ) {
            return $snapshot;
        }

        $question = QuizQuestion::hydrate([$snapshot])->first();
        $payload = $question?->activity_payload ?? [];
        $targets = $payload['right_items'] ?? [];
        if (count($targets) > 1) {
            $pairs = $this->normalizeMatchingPairs($payload['pairs'] ?? []);
            $leftItems = $payload['left_items'] ?? [];
            $originalTargets = $targets;
            $attempts = 0;
            do {
                shuffle($targets);
                $attempts++;
            } while (
                $attempts < 20
                && $this->matchingOrderIsFullyAligned($leftItems, $targets, $pairs)
            );

            if ($this->matchingOrderIsFullyAligned($leftItems, $targets, $pairs)) {
                $swaps = [];
                for ($left = 0; $left < count($targets); $left++) {
                    for ($right = $left + 1; $right < count($targets); $right++) {
                        $candidate = $targets;
                        [$candidate[$left], $candidate[$right]] = [
                            $candidate[$right],
                            $candidate[$left],
                        ];
                        if (!$this->matchingOrderIsFullyAligned($leftItems, $candidate, $pairs)) {
                            $swaps[] = [$left, $right];
                        }
                    }
                }
                if ($swaps) {
                    [$left, $right] = $swaps[random_int(0, count($swaps) - 1)];
                    [$targets[$left], $targets[$right]] = [
                        $targets[$right],
                        $targets[$left],
                    ];
                }
            }

            $payload['right_items'] = $targets;
            $snapshot['activity_payload'] = json_encode($payload);
        } else {
            $snapshot['activity_payload'] = is_string($snapshot['activity_payload'] ?? null)
                ? $snapshot['activity_payload']
                : json_encode($payload);
        }

        $snapshot['_matching_order_randomized'] = true;

        return $snapshot;
    }

    private function matchingOrderIsFullyAligned(array $leftItems, array $rightItems, array $pairs): bool
    {
        if (count($leftItems) < 2 || count($rightItems) < 2) {
            return false;
        }

        $rightPositions = [];
        foreach ($rightItems as $index => $item) {
            $rightPositions[(string) ($item['id'] ?? '')] = $index;
        }

        foreach ($leftItems as $index => $leftItem) {
            $leftId = (string) ($leftItem['id'] ?? '');
            $aligned = collect($pairs)->contains(
                fn (array $pair) => (string) $pair['left_id'] === $leftId
                    && ($rightPositions[(string) $pair['right_id']] ?? null) === $index
            );
            if (!$aligned) {
                return false;
            }
        }

        return true;
    }

    private function completeAttemptIfReady(
        StudentQuizAttempt $attempt,
        WebsiteNotificationService $notifications
    ): array {
        $subchapter = Subchapter::query()->findOrFail($attempt->subchapter_id);
        $questions = $this->questionsForAttempt($subchapter, $attempt);
        $answers = $attempt->answers ?? [];
        $complete = $questions->isNotEmpty() && $questions->every(function (QuizQuestion $question) use ($answers) {
            $answer = $answers[(string) $question->id] ?? null;
            if (!$answer) {
                return false;
            }
            if ($question->activity_type === 'multiple_choice') {
                return in_array($answer['answer'] ?? null, ['A', 'B', 'C', 'D'], true);
            }
            return $this->hasCompleteActivityAnswer($question, $answer['answers'] ?? []);
        });

        if (!$complete) {
            return [
                'saved' => true,
                'completed' => false,
                'attempt_id' => $attempt->id,
            ];
        }

        $review = [];
        $correctCount = 0;
        $totalQuestions = 0;
        foreach ($questions as $question) {
            $answer = $answers[(string) $question->id];
            if ($question->activity_type === 'multiple_choice') {
                $selected = $answer['answer'];
                $correct = $selected === $question->kunci_jawaban;
                $correctCount += $correct ? 1 : 0;
                $totalQuestions++;
                $review[] = [
                    'type' => 'multiple_choice',
                    'number' => $question->nomor_soal,
                    'prompt' => $question->pertanyaan,
                    'student_answer' => $selected,
                    'student_answer_text' => $this->choiceText($question, $selected),
                    'correct_answer' => $question->kunci_jawaban,
                    'correct_answer_text' => $this->choiceText($question, $question->kunci_jawaban),
                    'is_correct' => $correct,
                    'explanation' => $question->pembahasan ?? '',
                ];
                continue;
            }

            $activityReview = $this->activityReview($question, $answer['answers']);
            $review[] = [
                'type' => $question->activity_type,
                'number' => $question->nomor_soal,
                'prompt' => $question->pertanyaan,
                ...$activityReview,
            ];
        }

        $now = now();
        $attempt->fill([
            'status' => 'completed',
            'review' => $review,
            'score' => $totalQuestions > 0 ? round(($correctCount / $totalQuestions) * 100, 2) : null,
            'correct_answers' => $totalQuestions > 0 ? $correctCount : null,
            'total_questions' => $totalQuestions > 0 ? $totalQuestions : null,
            'completed_at' => $now,
        ])->save();
        StudentSubchapterProgress::query()
            ->where('user_id', $attempt->user_id)
            ->where('subchapter_id', $attempt->subchapter_id)
            ->update([
                'status' => 'completed',
                'completed_at' => $now,
                'updated_at' => $now,
            ]);

        $score = null;
        if ($totalQuestions > 0) {
            $subchapter = Subchapter::query()->findOrFail($attempt->subchapter_id);
            $assessment = StudentAssessmentResult::query()->create([
                'user_id' => $attempt->user_id,
                'assessment_type' => 'quiz',
                'chapter_id' => $subchapter->chapter_id,
                'subchapter_id' => $subchapter->id,
                'total_questions' => $totalQuestions,
                'correct_answers' => $correctCount,
                'score' => $attempt->score,
                'completed_at' => $now,
            ]);
            $score = (float) $attempt->score;
            $notifications->completedAssessment(
                (int) $attempt->user_id,
                'quiz',
                (int) $assessment->id
            );
        }

        return [
            'saved' => true,
            'completed' => true,
            'attempt_id' => $attempt->id,
            'score' => $score,
            'correct_answers' => $totalQuestions > 0 ? $correctCount : null,
            'total_questions' => $totalQuestions,
            'review' => $review,
        ];
    }

    private function activityReview(QuizQuestion $question, array $answers): array
    {
        $payload = $question->activity_payload ?? [];
        if ($question->activity_type === 'drag_drop') {
            $assignments = $payload['assignments'] ?? [];
            $items = collect($payload['items'] ?? [])->map(function (array $item) use ($answers, $assignments, $payload) {
                $selected = $answers[$item['id']] ?? null;
                $correct = $assignments[$item['id']] ?? null;
                $labels = collect($payload['categories'] ?? [])->keyBy('id');
                return [
                    'item' => $item['label'],
                    'student_category' => $labels->get($selected)['label'] ?? null,
                    'correct_category' => $labels->get($correct)['label'] ?? null,
                    'is_correct' => $selected === $correct,
                ];
            })->all();

            return ['items' => $items];
        }

        $left = collect($payload['left_items'] ?? [])->keyBy('id');
        $right = collect($payload['right_items'] ?? [])->keyBy('id');
        $normalizePairs = static fn (array $pairs) => array_is_list($pairs)
            ? collect($pairs)
                ->filter(fn ($pair) => is_array($pair)
                    && isset($pair['left_id'], $pair['right_id']))
                ->map(fn (array $pair) => [
                    'left_id' => $pair['left_id'],
                    'right_id' => $pair['right_id'],
                ])
                ->values()
            : collect($pairs)->flatMap(function ($rightIds, $leftId) {
                return collect(is_array($rightIds) ? $rightIds : [$rightIds])
                    ->map(fn ($rightId) => [
                        'left_id' => (string) $leftId,
                        'right_id' => (string) $rightId,
                    ]);
            })->values();
        $correctPairs = $normalizePairs($payload['pairs'] ?? []);
        $normalizedAnswers = $normalizePairs($answers);
        $studentPairs = $normalizedAnswers->map(fn (array $pair) => [
            'left_id' => $pair['left_id'],
            'right_id' => $pair['right_id'],
        ])->unique(fn (array $pair) => $pair['left_id'] . ':' . $pair['right_id'])->values();
        $pairLabel = static fn (array $pair) => [
            'left' => $left->get($pair['left_id'])['label'] ?? $pair['left_id'],
            'right' => $right->get($pair['right_id'])['label'] ?? $pair['right_id'],
            'is_correct' => $correctPairs->contains(
                fn (array $correctPair) => $correctPair['left_id'] === $pair['left_id']
                    && $correctPair['right_id'] === $pair['right_id']
            ),
        ];

        return [
            'student_pairs' => $studentPairs->map($pairLabel)->all(),
            'correct_pairs' => collect($correctPairs)->map($pairLabel)->all(),
        ];
    }

    private function activityFeedback(QuizQuestion $question, array $answers): array
    {
        return [
            'type' => $question->activity_type,
            ...$this->activityReview($question, $answers),
        ];
    }

    private function hasCompleteActivityAnswer(QuizQuestion $question, array $answers): bool
    {
        $payload = $question->activity_payload ?? [];
        if ($question->activity_type === 'drag_drop') {
            $items = $payload['items'] ?? [];
            $categories = collect($payload['categories'] ?? [])->pluck('id')->all();
            if (count($answers) !== count($items)) {
                return false;
            }
            foreach ($items as $item) {
                $itemId = $item['id'] ?? null;
                if (!is_string($itemId)
                    || !array_key_exists($itemId, $answers)
                    || !in_array($answers[$itemId], $categories, true)
                ) {
                    return false;
                }
            }
            return true;
        }

        $leftIds = collect($payload['left_items'] ?? [])->pluck('id')->all();
        $rightIds = collect($payload['right_items'] ?? [])->pluck('id')->all();
        if (!$answers || !array_is_list($answers)) {
            return false;
        }
        $connectedLeftIds = [];
        foreach ($answers as $pair) {
            if (!is_array($pair)
                || !in_array($pair['left_id'] ?? null, $leftIds, true)
                || !in_array($pair['right_id'] ?? null, $rightIds, true)
            ) {
                return false;
            }
            $connectedLeftIds[$pair['left_id']] = true;
        }
        $expectedPairCount = count($this->normalizeMatchingPairs($payload['pairs'] ?? []));
        return $expectedPairCount > 0
            && count($answers) >= $expectedPairCount
            && count($connectedLeftIds) === count($leftIds);
    }

    private function normalizeMatchingPairs(array $pairs): array
    {
        if (array_is_list($pairs)) {
            return collect($pairs)
                ->filter(fn ($pair) => is_array($pair)
                    && isset($pair['left_id'], $pair['right_id']))
                ->map(fn (array $pair) => [
                    'left_id' => (string) $pair['left_id'],
                    'right_id' => (string) $pair['right_id'],
                ])
                ->unique(fn (array $pair) => $pair['left_id'] . ':' . $pair['right_id'])
                ->values()
                ->all();
        }

        return collect($pairs)->flatMap(function ($rightIds, $leftId) {
            return collect(is_array($rightIds) ? $rightIds : [$rightIds])
                ->map(fn ($rightId) => [
                    'left_id' => (string) $leftId,
                    'right_id' => (string) $rightId,
                ]);
        })->unique(fn (array $pair) => $pair['left_id'] . ':' . $pair['right_id'])
            ->values()
            ->all();
    }

    private function choiceText(QuizQuestion $question, string $choice): ?string
    {
        return match ($choice) {
            'A' => $question->pilihan_a,
            'B' => $question->pilihan_b,
            'C' => $question->pilihan_c,
            'D' => $question->pilihan_d,
            default => null,
        };
    }

    private function attemptSummary(StudentQuizAttempt $attempt): array
    {
        return [
            'id' => (int) $attempt->id,
            'status' => $attempt->status,
            'score' => $attempt->score === null ? null : (float) $attempt->score,
            'correct_answers' => $attempt->correct_answers,
            'total_questions' => $attempt->total_questions,
            'completed_at' => $attempt->completed_at,
        ];
    }

    private function legacyResultSummary(StudentAssessmentResult $result): array
    {
        return [
            'id' => (int) $result->id,
            'status' => 'completed',
            'score' => $result->score === null ? null : (float) $result->score,
            'correct_answers' => $result->correct_answers,
            'total_questions' => $result->total_questions,
            'completed_at' => $result->completed_at,
            'legacy' => true,
        ];
    }

    private function legacyReview(Subchapter $subchapter, int $userId): array
    {
        $multipleChoiceAnswers = StudentQuizAnswer::query()
            ->where('user_id', $userId)
            ->where('subchapter_id', $subchapter->id)
            ->get()
            ->keyBy('quiz_question_id');
        $activityAnswers = StudentQuizActivityAnswer::query()
            ->where('user_id', $userId)
            ->where('subchapter_id', $subchapter->id)
            ->get()
            ->keyBy('quiz_question_id');
        $questionIds = $multipleChoiceAnswers->keys()
            ->merge($activityAnswers->keys())
            ->unique()
            ->values();
        $questions = QuizQuestion::withTrashed()
            ->where('subchapter_id', $subchapter->id)
            ->whereIn('id', $questionIds)
            ->orderBy('nomor_soal')
            ->get();

        return $questions->map(function (QuizQuestion $question) use ($multipleChoiceAnswers, $activityAnswers) {
            if ($question->activity_type === 'multiple_choice') {
                $answer = $multipleChoiceAnswers->get($question->id);
                $selected = $answer?->answer;
                return [
                    'type' => 'multiple_choice',
                    'number' => $question->nomor_soal,
                    'prompt' => $question->pertanyaan,
                    'student_answer' => $selected,
                    'student_answer_text' => $selected ? $this->choiceText($question, $selected) : null,
                    'correct_answer' => $question->kunci_jawaban,
                    'correct_answer_text' => $this->choiceText($question, $question->kunci_jawaban),
                    'is_correct' => (bool) ($answer?->is_correct),
                    'explanation' => $question->pembahasan ?? '',
                ];
            }
            $saved = $activityAnswers->get($question->id);
            return [
                'type' => $question->activity_type,
                'number' => $question->nomor_soal,
                'prompt' => $question->pertanyaan,
                ...$this->activityReview($question, $saved?->answers ?? []),
            ];
        })->all();
    }

    private function ensurePublishedSubchapter(Subchapter $subchapter): void
    {
        abort_unless(
            $subchapter->is_published
                && $subchapter->chapter?->is_published
                && $subchapter->chapter?->guru?->role === 'guru',
            404
        );
    }

    private function publicActivity(QuizQuestion $activity): array
    {
        $payload = $activity->activity_payload ?? [];
        $publicItems = static fn (array $items): array => array_map(
            static fn (array $item): array => [
                'id' => $item['id'],
                'label' => $item['label'],
                'image_url' => $item['image_url'] ?? null,
            ],
            $items
        );
        $result = [
            'id' => $activity->id,
            'nomor_soal' => $activity->nomor_soal,
            'activity_type' => $activity->activity_type,
            'pertanyaan' => $activity->pertanyaan,
            'instruction' => $payload['instruction'] ?? '',
        ];
        if ($activity->activity_type === 'drag_drop') {
            $result['items'] = $publicItems($payload['items'] ?? []);
            $result['categories'] = $publicItems($payload['categories'] ?? []);
        } else {
            $result['left_items'] = $publicItems($payload['left_items'] ?? []);
            $result['right_items'] = $publicItems($payload['right_items'] ?? []);
            $result['required_answer_count'] = count(
                $this->normalizeMatchingPairs($payload['pairs'] ?? [])
            );
        }
        return $result;
    }
}
