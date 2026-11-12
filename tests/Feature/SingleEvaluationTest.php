<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\User;
use App\Services\EvaluationResultService;
use App\Services\ParentDashboardService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SingleEvaluationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_evaluation_screen_has_one_start_action_without_teacher_list(): void
    {
        $student = $this->createUser('siswa', 'single-eval-screen-student');

        $response = $this->actingAs($student)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Mulai Evaluasi')
            ->assertSee('15 Soal')
            ->assertSee('45 Menit')
            ->assertDontSee('Evaluasi yang tersedia')
            ->assertDontSee('Pilih Evaluasi Guru yang tersedia untukmu.')
            ->assertDontSee('Riwayat Evaluasi Sebelumnya')
            ->assertDontSee('sd-eval-teacher-options');
        $response->assertSee('id="sd-eval-quiz-next"', false)
            ->assertDontSee('id="sd-eval-quiz-finish"', false);
        $this->assertSame(
            1,
            substr_count($response->getContent(), 'data-eval-open="quiz"')
        );
        $this->assertSame(
            1,
            substr_count($response->getContent(), 'Kembali ke Evaluasi')
        );
    }

    public function test_teacher_evaluation_editor_has_no_question_type_dropdown(): void
    {
        $teacher = $this->createUser('guru', 'single-eval-editor-teacher');

        $this->actingAs($teacher)
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('<select id="geQuestionType"', false)
            ->assertSee('<input type="hidden" id="geQuestionType"', false);
    }

    public function test_soft_deleted_student_cannot_access_evaluation_endpoints(): void
    {
        $teacher = $this->createUser('guru', 'soft-delete-eval-teacher');
        $student = $this->createUser('siswa', 'soft-delete-eval-student');
        foreach (range(1, 15) as $number) {
            $this->actingAs($teacher)
                ->postJson('/guru/evaluations/questions', $this->question($number))
                ->assertCreated();
        }

        $student->delete();
        $this->actingAs($student)
            ->getJson('/siswa/evaluations/teachers')
            ->assertForbidden();
        $this->postJson("/siswa/evaluations/teachers/{$teacher->id}/start")
            ->assertForbidden();
        $this->assertDatabaseCount('evaluation_attempts', 0);
    }

    public function test_all_fifteen_questions_are_mcq_and_attempts_autosave_retry_and_lock_at_kkm(): void
    {
        $teacher = $this->createUser('guru', 'single-eval-teacher');
        $student = $this->createUser('siswa', 'single-eval-student');

        foreach (range(1, 15) as $number) {
            $this->actingAs($teacher)
                ->postJson('/guru/evaluations/questions', $this->question($number))
                ->assertCreated()
                ->assertJsonPath('question.tipe_soal', 'pilihan_ganda');
        }

        $this->actingAs($teacher)
            ->getJson('/guru/evaluations/questions')
            ->assertOk()
            ->assertJsonCount(15, 'questions');

        $this->postJson('/guru/evaluations/questions', $this->question(16))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nomor_soal']);
        $this->assertSame(
            15,
            DB::table('evaluation_questions')->where('user_id', $teacher->id)->count()
        );

        $invalidType = $this->question(1);
        $invalidType['tipe_soal'] = 'drag_drop';
        $this->postJson('/guru/evaluations/questions', $invalidType)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tipe_soal']);

        $initialQuestion = DB::table('evaluation_questions')
            ->where('user_id', $teacher->id)
            ->where('nomor_soal', 1)
            ->first();

        $this->actingAs($student)
            ->getJson('/siswa/evaluations/teachers')
            ->assertOk()
            ->assertJsonPath('default_teacher_id', $teacher->id)
            ->assertJsonPath('ready', true);

        $this->actingAs($student)
            ->getJson("/siswa/evaluations/teachers/{$teacher->id}/state")
            ->assertOk()
            ->assertJsonPath('ready', true)
            ->assertJsonPath('kkm', 70);

        $this->travelTo(now());
        $startTime = now();
        $started = $this->postJson("/siswa/evaluations/teachers/{$teacher->id}/start")
            ->assertOk()
            ->assertJsonCount(15, 'questions');
        $this->assertSame(
            15,
            collect($started->json('questions'))
                ->where('tipe_soal', 'pilihan_ganda')
                ->count()
        );
        $attemptId = (int) $started->json('attempt_id');
        $this->assertSame(
            $startTime->copy()->addMinutes(45)->timestamp,
            Carbon::parse($started->json('ends_at'))->timestamp
        );

        $updatedQuestion = $this->question(1);
        $updatedQuestion['pertanyaan'] = 'Perubahan setelah attempt dimulai';
        $this->actingAs($teacher)
            ->putJson("/guru/evaluations/questions/{$initialQuestion->id}", $updatedQuestion)
            ->assertOk();

        $answers = array_fill_keys(array_map('strval', range(1, 15)), 'B');
        foreach (range(1, 10) as $number) {
            $answers[(string) $number] = 'A';
        }
        $this->actingAs($student)
            ->putJson("/siswa/evaluations/teachers/{$teacher->id}/answers", ['answers' => $answers])
            ->assertOk();

        $this->getJson("/siswa/evaluations/teachers/{$teacher->id}/state")
            ->assertOk()
            ->assertJsonPath('status', 'active')
            ->assertJsonPath('attempt.id', $attemptId)
            ->assertJsonPath('attempt.answers.1', 'A')
            ->assertJsonPath('questions.0.pertanyaan', 'Pertanyaan 1');

        $this->postJson("/siswa/evaluations/teachers/{$teacher->id}/finish")
            ->assertOk()
            ->assertJsonPath('correct_count', 10)
            ->assertJsonPath('score', 67);
        $this->assertDatabaseCount('evaluation_attempts', 1);
        $this->assertDatabaseHas('evaluation_attempts', [
            'id' => $attemptId,
            'score' => 67,
        ]);

        $retryStarted = $this->postJson("/siswa/evaluations/teachers/{$teacher->id}/start")
            ->assertOk();
        $retryId = (int) $retryStarted->json('attempt_id');
        $retryAnswers = array_fill_keys(array_map('strval', range(1, 15)), 'B');
        foreach (range(1, 11) as $number) {
            $retryAnswers[(string) $number] = 'A';
        }
        $this->putJson("/siswa/evaluations/teachers/{$teacher->id}/answers", ['answers' => $retryAnswers])
            ->assertOk();
        $this->postJson("/siswa/evaluations/teachers/{$teacher->id}/finish")
            ->assertOk()
            ->assertJsonPath('correct_count', 11)
            ->assertJsonPath('score', 73);

        $this->assertDatabaseCount('evaluation_attempts', 2);
        $this->assertDatabaseHas('evaluation_attempts', ['id' => $retryId, 'score' => 73]);
        $this->getJson("/siswa/evaluations/teachers/{$teacher->id}/state")
            ->assertOk()
            ->assertJsonPath('passed', true)
            ->assertJsonPath('can_retry', false);
        $this->postJson("/siswa/evaluations/teachers/{$teacher->id}/start")->assertStatus(409);
    }

    public function test_published_evaluation_with_formative_activity_types_is_not_startable(): void
    {
        $teacher = $this->createUser('guru', 'mixed-eval-teacher');
        $student = $this->createUser('siswa', 'mixed-eval-student');

        foreach (range(1, 15) as $number) {
            $this->actingAs($teacher)
                ->postJson('/guru/evaluations/questions', $this->question($number))
                ->assertCreated();
        }
        DB::table('evaluation_questions')
            ->where('user_id', $teacher->id)
            ->whereBetween('nomor_soal', [6, 10])
            ->update(['tipe_soal' => 'drag_drop']);
        DB::table('evaluation_questions')
            ->where('user_id', $teacher->id)
            ->whereBetween('nomor_soal', [11, 15])
            ->update(['tipe_soal' => 'menghubungkan']);

        $this->actingAs($student)
            ->getJson('/siswa/evaluations/teachers')
            ->assertOk()
            ->assertJsonPath('default_teacher_id', $teacher->id)
            ->assertJsonPath('ready', false);
        $this->getJson("/siswa/evaluations/teachers/{$teacher->id}/state")
            ->assertOk()
            ->assertJsonPath('ready', false);
        $this->postJson("/siswa/evaluations/teachers/{$teacher->id}/start")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('evaluasi');

        $this->assertDatabaseCount('evaluation_attempts', 0);
    }

    public function test_teacher_can_explicitly_convert_legacy_questions_before_student_attempt(): void
    {
        $teacher = $this->createUser('guru', 'convert-eval-teacher');
        $otherTeacher = $this->createUser('guru', 'convert-eval-other-teacher');
        $unassignedStudent = $this->createUser('siswa', 'convert-eval-unassigned-student');

        foreach (range(1, 15) as $number) {
            $question = $this->question($number);
            $question['is_published'] = false;
            $this->actingAs($teacher)
                ->postJson('/guru/evaluations/questions', $question)
                ->assertCreated();
        }

        DB::table('evaluation_questions')
            ->where('user_id', $teacher->id)
            ->whereBetween('nomor_soal', [6, 10])
            ->update([
                'tipe_soal' => 'drag_drop',
                'konten_soal' => json_encode([
                    'items' => [['id' => 'item-1', 'label' => 'Gambar lama']],
                    'targets' => [['id' => 'target-1', 'label' => 'Kategori lama']],
                ]),
                'kunci_jawaban' => json_encode(['pairs' => ['item-1' => 'target-1']]),
            ]);
        DB::table('evaluation_questions')
            ->where('user_id', $teacher->id)
            ->whereBetween('nomor_soal', [11, 15])
            ->update([
                'tipe_soal' => 'menghubungkan',
                'konten_soal' => json_encode([
                    'items' => [['id' => 'item-2', 'label' => 'Pasangan lama']],
                    'targets' => [['id' => 'target-2', 'label' => 'Tujuan lama']],
                ]),
                'kunci_jawaban' => json_encode(['pairs' => ['item-2' => 'target-2']]),
            ]);

        $legacy = DB::table('evaluation_questions')
            ->where('user_id', $teacher->id)
            ->where('nomor_soal', 6)
            ->first();
        $this->assertSame('drag_drop', $legacy->tipe_soal);
        $this->assertSame(
            'Gambar lama',
            json_decode($legacy->konten_soal, true)['items'][0]['label']
        );

        $this->actingAs($otherTeacher)
            ->putJson("/guru/evaluations/questions/{$legacy->id}", $this->question(6))
            ->assertNotFound();
        $this->assertDatabaseHas('evaluation_questions', [
            'id' => $legacy->id,
            'user_id' => $teacher->id,
            'tipe_soal' => 'drag_drop',
            'is_published' => false,
        ]);
        $incompleteConversion = $this->question(6);
        unset($incompleteConversion['konten_soal']);
        $this->actingAs($teacher)
            ->putJson("/guru/evaluations/questions/{$legacy->id}", $incompleteConversion)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('konten_soal');
        $this->assertDatabaseHas('evaluation_questions', [
            'id' => $legacy->id,
            'user_id' => $teacher->id,
            'tipe_soal' => 'drag_drop',
            'is_published' => false,
        ]);

        foreach (range(1, 15) as $number) {
            $payload = $this->question($number);
            $payload['pertanyaan'] = "Soal pilihan ganda hasil konversi {$number}";
            $this->actingAs($teacher)
                ->putJson('/guru/evaluations/questions/'
                    .DB::table('evaluation_questions')
                        ->where('user_id', $teacher->id)
                        ->where('nomor_soal', $number)
                        ->value('id'), $payload)
                ->assertOk()
                ->assertJsonPath('question.tipe_soal', 'pilihan_ganda')
                ->assertJsonPath('question.is_published', true);
        }

        $this->assertSame(
            15,
            DB::table('evaluation_questions')
                ->where('user_id', $teacher->id)
                ->where('tipe_soal', 'pilihan_ganda')
                ->where('is_published', true)
                ->count()
        );
        $archive = DB::table('evaluation_question_archives')
            ->where('question_id', $legacy->id)
            ->first();
        $this->assertNotNull($archive);
        $archivedQuestion = json_decode($archive->question_snapshot, true);
        $this->assertSame('drag_drop', $archivedQuestion['tipe_soal']);
        $this->assertSame(
            'Gambar lama',
            json_decode($archivedQuestion['konten_soal'], true)['items'][0]['label']
        );
        $this->actingAs($unassignedStudent)
            ->getJson('/siswa/evaluations/teachers')
            ->assertOk()
            ->assertJsonPath('default_teacher_id', $teacher->id)
            ->assertJsonPath('ready', true);

        $path = "/siswa/evaluations/teachers/{$teacher->id}";
        $started = $this->postJson("{$path}/start")
            ->assertOk()
            ->assertJsonCount(15, 'questions');
        $attemptId = (int) $started->json('attempt_id');
        $this->putJson("{$path}/answers", [
            'answers' => array_fill_keys(array_map('strval', range(1, 15)), 'A'),
        ])->assertOk();
        $this->postJson("{$path}/finish")
            ->assertOk()
            ->assertJsonPath('score', 100);
        $this->assertDatabaseHas('evaluation_attempts', [
            'id' => $attemptId,
            'user_id' => $unassignedStudent->id,
            'teacher_id' => $teacher->id,
            'score' => 100,
        ]);
    }

    public function test_teacherless_historical_attempt_is_preserved_without_automatic_attribution(): void
    {
        $teacher = $this->createUser('guru', 'historical-eval-teacher');
        $student = $this->createUser('siswa', 'historical-eval-student');
        $this->assignTeacher($teacher, $student);
        $chapter = Chapter::create([
            'user_id' => $teacher->id,
            'nomor_bab' => 6,
            'judul' => 'Bab VI',
            'is_published' => true,
        ]);
        $now = now();
        $bankId = DB::table('evaluation_banks')->insertGetId([
            'chapter_id' => $chapter->id,
            'teacher_id' => $teacher->id,
            'class_name' => 'VII A',
            'title' => 'Arsip Evaluasi',
            'is_published' => false,
            'active_slot' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $snapshot = $this->legacySnapshot($teacher);
        $attemptId = DB::table('evaluation_attempts')->insertGetId([
            'user_id' => $student->id,
            'chapter_id' => $chapter->id,
            'evaluation_bank_id' => $bankId,
            'question_snapshot' => json_encode($snapshot),
            'answers' => json_encode(new \stdClass),
            'ends_at' => $now->copy()->addMinutes(30),
            'submitted_at' => null,
            'score' => null,
            'correct_count' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->actingAs($student)
            ->getJson("/siswa/evaluations/teachers/{$teacher->id}/state")
            ->assertNotFound();

        $this->assertDatabaseHas('evaluation_attempts', [
            'id' => $attemptId,
            'evaluation_bank_id' => $bankId,
            'teacher_id' => null,
            'submitted_at' => null,
            'score' => null,
        ]);
        $savedSnapshot = json_decode(
            DB::table('evaluation_attempts')->where('id', $attemptId)->value('question_snapshot'),
            true
        );
        $this->assertSame($snapshot, $savedSnapshot);
    }

    public function test_shared_default_evaluation_is_selected_explicitly_and_other_banks_are_not_mixed(): void
    {
        $firstTeacher = $this->createUser('guru', 'multi-eval-teacher-one');
        $secondTeacher = $this->createUser('guru', 'multi-eval-teacher-two');
        $unassignedTeacher = $this->createUser('guru', 'multi-eval-teacher-three');
        $student = $this->createUser('siswa', 'multi-eval-student');
        $parent = $this->createUser('orang_tua', 'multi-eval-parent');
        $this->assignTeacher($firstTeacher, $student);
        $this->assignTeacher($secondTeacher, $student);
        $this->linkParent($student, $parent);

        foreach ([$firstTeacher, $secondTeacher] as $teacher) {
            foreach (range(1, 15) as $number) {
                $this->actingAs($teacher)
                    ->postJson('/guru/evaluations/questions', $this->question($number))
                    ->assertCreated();
            }
        }

        config(['app.evaluation_default_teacher_id' => null]);
        $this->actingAs($student)
            ->getJson('/siswa/evaluations/teachers')
            ->assertOk()
            ->assertJsonPath('default_teacher_id', null)
            ->assertJsonPath('message', 'Bank Evaluasi bersama belum ditetapkan.');
        config(['app.evaluation_default_teacher_id' => $firstTeacher->id]);
        $this->actingAs($student)
            ->getJson('/siswa/evaluations/teachers')
            ->assertOk()
            ->assertJsonPath('default_teacher_id', $firstTeacher->id)
            ->assertJsonPath('ready', true);

        $firstPath = "/siswa/evaluations/teachers/{$firstTeacher->id}";
        $secondPath = "/siswa/evaluations/teachers/{$secondTeacher->id}";
        $this->getJson("{$firstPath}/state")
            ->assertOk()
            ->assertJsonPath('ready', true)
            ->assertJsonPath('passed', false);
        $this->getJson("{$secondPath}/state")->assertNotFound();

        $firstStart = $this->postJson("{$firstPath}/start")
            ->assertOk()
            ->assertJsonCount(15, 'questions');
        $this->postJson("{$secondPath}/start")->assertNotFound();
        $this->assertDatabaseHas('evaluation_attempts', [
            'id' => $firstStart->json('attempt_id'),
            'user_id' => $student->id,
            'teacher_id' => $firstTeacher->id,
        ]);
        $this->putJson("{$firstPath}/answers", [
            'answers' => array_fill_keys(array_map('strval', range(1, 15)), 'A'),
        ])->assertOk();
        $this->postJson("{$firstPath}/finish")
            ->assertOk()
            ->assertJsonPath('score', 100);

        $this->getJson("{$firstPath}/state")
            ->assertOk()
            ->assertJsonPath('passed', true)
            ->assertJsonPath('attempt.score', 100);
        $this->postJson("{$firstPath}/start")->assertStatus(409);

        $results = app(EvaluationResultService::class);
        $this->assertSame(
            [$firstTeacher->id],
            array_column($results->completedForTeacher((int) $firstTeacher->id, [$student->id]), 'teacher_id')
        );
        $this->assertSame([], $results->completedForTeacher((int) $secondTeacher->id, [$student->id]));
        $parentDashboard = app(ParentDashboardService::class)->dashboard($parent);
        $this->assertEqualsCanonicalizing(
            [$firstTeacher->id, $secondTeacher->id],
            array_column($parentDashboard['finalGrades'], 'teacher_id')
        );
        $this->assertEqualsCanonicalizing(
            [$firstTeacher->id],
            array_column($parentDashboard['learningResults'], 'teacher_id')
        );
        $this->getJson("/siswa/evaluations/teachers/{$unassignedTeacher->id}/state")
            ->assertNotFound();
    }

    public function test_ambiguous_legacy_attempts_are_preserved_without_teacher_attribution(): void
    {
        $firstTeacher = $this->createUser('guru', 'ambiguous-eval-teacher-one');
        $secondTeacher = $this->createUser('guru', 'ambiguous-eval-teacher-two');
        $student = $this->createUser('siswa', 'ambiguous-eval-student');
        $this->assignTeacher($firstTeacher, $student);
        $this->assignTeacher($secondTeacher, $student);
        foreach (range(1, 15) as $number) {
            $this->actingAs($firstTeacher)
                ->postJson('/guru/evaluations/questions', $this->question($number))
                ->assertCreated();
        }

        $snapshot = [];
        foreach (range(1, 15) as $number) {
            $snapshot[] = [
                'id' => $number,
                'user_id' => $number <= 8 ? $firstTeacher->id : $secondTeacher->id,
                'nomor_soal' => $number,
            ];
        }

        $attemptId = DB::table('evaluation_attempts')->insertGetId([
            'user_id' => $student->id,
            'teacher_id' => null,
            'question_snapshot' => json_encode($snapshot),
            'answers' => json_encode([]),
            'ends_at' => now()->subMinute(),
            'submitted_at' => now(),
            'score' => 100,
            'correct_count' => 15,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $unambiguousSnapshotId = DB::table('evaluation_attempts')->insertGetId([
            'user_id' => $student->id,
            'teacher_id' => null,
            'question_snapshot' => json_encode(array_map(
                fn (int $number) => [
                    'user_id' => $firstTeacher->id,
                    'nomor_soal' => $number,
                ],
                range(1, 15)
            )),
            'answers' => json_encode([]),
            'ends_at' => now()->subMinute(),
            'submitted_at' => now(),
            'score' => 100,
            'correct_count' => 15,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $otherStudent = $this->createUser('siswa', 'other-legacy-eval-student');
        DB::table('evaluation_attempts')->insert([
            'user_id' => $otherStudent->id,
            'teacher_id' => null,
            'question_snapshot' => json_encode([]),
            'answers' => json_encode([]),
            'ends_at' => now()->subMinute(),
            'submitted_at' => now(),
            'score' => 80,
            'correct_count' => 12,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $teacherResponse = $this->actingAs($student)
            ->getJson('/siswa/evaluations/teachers')
            ->assertOk()
            ->assertJsonPath('default_teacher_id', $firstTeacher->id)
            ->assertJsonPath('ready', true);
        $this->assertArrayNotHasKey('previous_attempts', $teacherResponse->json());
        $legacyResults = app(EvaluationResultService::class)
            ->completedForStudent((int) $student->id);
        $this->assertCount(2, $legacyResults);
        $this->assertSame(
            [$firstTeacher->id, null],
            array_column($legacyResults, 'teacher_id')
        );
        $this->assertSame(
            ['verified', 'unverified'],
            array_column($legacyResults, 'attribution_status')
        );
        $this->assertDatabaseHas('evaluation_attempts', [
            'id' => $attemptId,
            'teacher_id' => null,
            'score' => 100,
        ]);
        $this->assertDatabaseHas('evaluation_attempts', [
            'id' => $unambiguousSnapshotId,
            'teacher_id' => null,
            'score' => 100,
        ]);
        $this->actingAs($student);
        $this->postJson("/siswa/evaluations/teachers/{$firstTeacher->id}/start")
            ->assertOk()
            ->assertJsonCount(15, 'questions');
    }

    public function test_deleting_evaluation_question_archives_its_snapshot(): void
    {
        $teacher = $this->createUser('guru', 'evaluation-delete-archive-teacher');
        $question = $this->question(1);
        $question['is_published'] = false;

        $created = $this->actingAs($teacher)
            ->postJson('/guru/evaluations/questions', $question)
            ->assertCreated();
        $questionId = (int) $created->json('question.id');

        $this->deleteJson("/guru/evaluations/questions/{$questionId}")
            ->assertOk();

        $this->assertDatabaseHas('evaluation_question_archives', [
            'question_id' => $questionId,
            'teacher_id' => $teacher->id,
        ]);
        $this->assertDatabaseMissing('evaluation_questions', [
            'id' => $questionId,
        ]);
    }

    private function question(int $number): array
    {
        return [
            'nomor_soal' => $number,
            'tipe_soal' => 'pilihan_ganda',
            'pertanyaan' => "Pertanyaan {$number}",
            'konten_soal' => [
                'pilihan_a' => 'Pilihan A',
                'pilihan_b' => 'Pilihan B',
                'pilihan_c' => 'Pilihan C',
                'pilihan_d' => 'Pilihan D',
            ],
            'kunci_jawaban' => ['jawaban' => 'A'],
            'pembahasan' => '',
            'is_published' => true,
        ];
    }

    private function legacySnapshot(User $teacher): array
    {
        $snapshot = [];
        foreach (range(1, 15) as $number) {
            if ($number <= 5) {
                $snapshot[] = [
                    'id' => $number,
                    'user_id' => $teacher->id,
                    'nomor_soal' => $number,
                    'tipe_soal' => 'pilihan_ganda',
                    'pertanyaan' => "Soal lama {$number}",
                    'konten_soal' => [
                        'pilihan_a' => 'A',
                        'pilihan_b' => 'B',
                        'pilihan_c' => 'C',
                        'pilihan_d' => 'D',
                    ],
                    'kunci_jawaban' => ['jawaban' => 'A'],
                ];

                continue;
            }

            $snapshot[] = [
                'id' => $number,
                'user_id' => $teacher->id,
                'chapter_id' => 1,
                'nomor_soal' => $number,
                'tipe_soal' => $number <= 10 ? 'drag_drop' : 'menghubungkan',
                'pertanyaan' => "Soal lama {$number}",
                'konten_soal' => [
                    'items' => [['id' => "item-{$number}", 'label' => 'Objek']],
                    'targets' => [['id' => "target-{$number}", 'label' => 'Kategori']],
                ],
                'kunci_jawaban' => [
                    'pairs' => ["item-{$number}" => "target-{$number}"],
                ],
            ];
        }

        return $snapshot;
    }

    private function createUser(string $role, string $username): User
    {
        return User::create([
            'name' => ucfirst($role).' SALUANG',
            'username' => $username,
            'password' => 'secret123',
            'role' => $role,
            'email' => null,
        ]);
    }

    private function assignTeacher(User $teacher, User $student): void
    {
        $student->update(['kelas' => 'VII A']);
        DB::table('teacher_class_assignments')->insert([
            'teacher_id' => $teacher->id,
            'class_name' => 'VII A',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function linkParent(User $student, User $parent): void
    {
        DB::table('student_guardians')->insert([
            'student_id' => $student->id,
            'guardian_id' => $parent->id,
            'relationship' => 'orang_tua',
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
