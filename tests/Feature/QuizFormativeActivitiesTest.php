<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\QuizQuestion;
use App\Models\StudentAssessmentResult;
use App\Models\StudentQuizAttempt;
use App\Models\StudentQuizAnswer;
use App\Models\StudentSubchapterProgress;
use App\Models\Subchapter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QuizFormativeActivitiesTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_questions_still_load_when_attempt_schema_is_pending(): void
    {
        $teacher = $this->createUser('guru', 'guru-attempt-schema-pending');
        $student = $this->createUser('siswa', 'siswa-attempt-schema-pending');
        $subchapter = $this->createSubchapter($teacher, true);
        $question = $this->createQuestion($subchapter, 1, 'multiple_choice', [
            'pertanyaan' => 'Soal yang dipublikasikan',
            'pilihan_a' => 'A',
        ]);
        StudentSubchapterProgress::create([
            'user_id' => $student->id,
            'subchapter_id' => $subchapter->id,
            'status' => 'in_progress',
            'opened_at' => now(),
        ]);
        $this->actingAs($student);

        Schema::drop('student_quiz_attempts');

        $this->getJson("/siswa/quiz/subchapters/{$subchapter->id}/questions")
            ->assertOk()
            ->assertJsonPath('attempt_storage_available', false)
            ->assertJsonCount(1, 'questions')
            ->assertJsonPath('questions.0.id', $question->id);
        $this->postJson("/siswa/quiz/subchapters/{$subchapter->id}/attempts")
            ->assertStatus(503)
            ->assertJsonPath('message', 'Penyimpanan attempt Kuis belum tersedia. Hubungi administrator untuk menerapkan skema attempt yang disetujui.');
    }

    public function test_legacy_matching_attempt_review_accepts_map_shaped_pairs(): void
    {
        $teacher = $this->createUser('guru', 'guru-legacy-matching-review');
        $student = $this->createUser('siswa', 'siswa-legacy-matching-review');
        $subchapter = $this->createSubchapter($teacher, true);
        $matching = $this->createQuestion($subchapter, 1, 'matching', [
            'activity_payload' => [
                'left_items' => [
                    ['id' => 'left_1', 'label' => 'Ikan saluang'],
                    ['id' => 'left_2', 'label' => 'Katak'],
                ],
                'right_items' => [
                    ['id' => 'right_1', 'label' => 'Sungai'],
                    ['id' => 'right_2', 'label' => 'Dua alam'],
                ],
                'pairs' => [
                    'left_1' => ['right_1', 'right_2'],
                    'left_2' => 'right_1',
                ],
            ],
        ]);
        StudentAssessmentResult::create([
            'user_id' => $student->id,
            'assessment_type' => 'quiz',
            'chapter_id' => $subchapter->chapter_id,
            'subchapter_id' => $subchapter->id,
            'total_questions' => 1,
            'correct_answers' => 1,
            'score' => 100,
            'completed_at' => now(),
        ]);
        DB::table('student_quiz_activity_answers')->insert([
            'user_id' => $student->id,
            'subchapter_id' => $subchapter->id,
            'quiz_question_id' => $matching->id,
            'answers' => json_encode([
                'left_1' => ['right_1', 'right_2'],
                'left_2' => 'right_1',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($student)
            ->getJson("/siswa/quiz/subchapters/{$subchapter->id}/questions")
            ->assertOk()
            ->assertJsonPath('latest_result.review.0.student_pairs.0.left', 'Ikan saluang')
            ->assertJsonPath('latest_result.review.0.student_pairs.1.right', 'Dua alam')
            ->assertJsonCount(3, 'latest_result.review.0.correct_pairs');
    }

    public function test_teacher_can_manage_published_and_draft_activity_types_without_changing_legacy_quiz_creation(): void
    {
        $teacher = $this->createUser('guru', 'guru-activity-crud');
        $subchapter = $this->createSubchapter($teacher);
        $baseUrl = "/guru/subchapters/{$subchapter->id}/questions";

        $this->actingAs($teacher)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('id="gqActivityType"', false)
            ->assertSee('value="drag_drop"', false)
            ->assertSee('value="matching"', false);

        $legacyQuiz = $this->actingAs($teacher)->postJson($baseUrl, [
            'nomor_soal' => 1,
            'pertanyaan' => 'Apa habitat ikan saluang?',
            'pilihan_a' => 'Sungai',
            'pilihan_b' => 'Padang pasir',
            'pilihan_c' => 'Gurun es',
            'pilihan_d' => 'Langit',
            'kunci_jawaban' => 'A',
            'is_published' => true,
        ])->assertCreated()
            ->assertJsonPath('question.activity_type', 'multiple_choice');
        $this->assertDatabaseHas('quiz_questions', [
            'id' => $legacyQuiz->json('question.id'),
            'activity_type' => 'multiple_choice',
            'pertanyaan' => 'Apa habitat ikan saluang?',
        ]);
        $student = $this->createUser('siswa', 'siswa-activity-history');
        StudentQuizAnswer::create([
            'user_id' => $student->id,
            'subchapter_id' => $subchapter->id,
            'quiz_question_id' => $legacyQuiz->json('question.id'),
            'answer' => 'A',
            'is_correct' => true,
        ]);
        $this->deleteJson("{$baseUrl}/{$legacyQuiz->json('question.id')}")
            ->assertOk()
            ->assertJsonPath('action', 'archived');
        $this->assertSoftDeleted('quiz_questions', [
            'id' => $legacyQuiz->json('question.id'),
        ]);
        $this->assertDatabaseHas('student_quiz_answers', [
            'user_id' => $student->id,
            'quiz_question_id' => $legacyQuiz->json('question.id'),
        ]);

        $drag = $this->postJson($baseUrl, [
            'nomor_soal' => 2,
            'activity_type' => 'drag_drop',
            'pertanyaan' => 'Kelompokkan objek berikut.',
            'activity_instruction' => 'Pilih kategori yang sesuai.',
            'items' => [
                ['label' => 'Ikan saluang', 'image_url' => '/images/saluang.png', 'category_index' => 0],
                ['label' => 'Katak', 'image_url' => '', 'category_index' => 1],
            ],
            'categories' => [
                ['label' => 'Hidup di air'],
                ['label' => 'Hidup di dua alam'],
            ],
            'is_published' => false,
        ])->assertCreated()
            ->assertJsonPath('question.activity_type', 'drag_drop')
            ->assertJsonPath('question.is_published', false);
        $dragId = $drag->json('question.id');
        $this->assertDatabaseHas('quiz_questions', [
            'id' => $dragId,
            'activity_type' => 'drag_drop',
            'is_published' => false,
        ]);

        $this->putJson("{$baseUrl}/{$dragId}", [
            'nomor_soal' => 2,
            'activity_type' => 'drag_drop',
            'pertanyaan' => 'Kelompokkan makhluk hidup.',
            'activity_instruction' => 'Ketuk objek, lalu ketuk kategori.',
            'items' => [
                ['label' => 'Ikan saluang', 'image_url' => '', 'category_index' => 0],
                ['label' => 'Katak', 'image_url' => '', 'category_index' => 1],
            ],
            'categories' => [
                ['label' => 'Perairan'],
                ['label' => 'Amfibi'],
            ],
            'is_published' => true,
        ])->assertOk()
            ->assertJsonPath('question.is_published', true)
            ->assertJsonPath('question.activity_payload.assignments.item_1', 'category_1');

        $matching = $this->postJson($baseUrl, [
            'nomor_soal' => 3,
            'activity_type' => 'matching',
            'pertanyaan' => 'Hubungkan objek dengan habitatnya.',
            'left_items' => [
                ['label' => 'Ikan saluang', 'image_url' => '', 'right_indices' => [0, 1]],
                ['label' => 'Katak', 'image_url' => '', 'right_indices' => [0]],
            ],
            'right_items' => [
                ['label' => 'Sungai', 'image_url' => ''],
                ['label' => 'Darat dan air', 'image_url' => ''],
            ],
            'is_published' => true,
        ])->assertCreated()
            ->assertJsonPath('question.activity_type', 'matching')
            ->assertJsonPath('question.is_published', true)
            ->assertJsonCount(3, 'question.activity_payload.pairs');
        $this->assertSame(
            [
                ['left_id' => 'left_1', 'right_id' => 'right_1'],
                ['left_id' => 'left_1', 'right_id' => 'right_2'],
                ['left_id' => 'left_2', 'right_id' => 'right_1'],
            ],
            $matching->json('question.activity_payload.pairs')
        );

        $this->getJson($baseUrl)
            ->assertOk()
            ->assertJsonCount(2, 'questions')
            ->assertJsonPath('questions.0.activity_type', 'drag_drop')
            ->assertJsonPath('questions.1.activity_type', 'matching');
        $otherTeacher = $this->createUser('guru', 'guru-activity-owner');
        $otherSubchapter = $this->createSubchapter($otherTeacher);
        $this->actingAs($teacher)
            ->getJson("/guru/subchapters/{$otherSubchapter->id}/questions")
            ->assertNotFound();
        $this->postJson($baseUrl, [
            'nomor_soal' => 4,
            'activity_type' => 'matching',
            'pertanyaan' => 'Pasangkan.',
            'left_items' => [
                ['label' => 'A', 'right_indices' => [0]],
                ['label' => 'B', 'right_indices' => []],
            ],
            'right_items' => [
                ['label' => 'X'],
                ['label' => 'Y'],
            ],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('left_items.1.right_indices');
    }

    public function test_mixed_quiz_saves_activity_progress_and_scores_only_multiple_choice(): void
    {
        $teacher = $this->createUser('guru', 'guru-activity-student');
        $student = $this->createUser('siswa', 'siswa-activity-student');
        $subchapter = $this->createSubchapter($teacher, true);
        $quiz = $this->createQuestion($subchapter, 1, 'multiple_choice', [
            'pertanyaan' => 'Pilih habitat ikan saluang.',
            'pilihan_a' => 'Sungai',
            'pilihan_b' => 'Gurun',
            'pilihan_c' => 'Gunung',
            'pilihan_d' => 'Langit',
            'kunci_jawaban' => 'A',
            'pembahasan' => 'Ikan saluang hidup di sungai.',
        ]);
        $drag = $this->createQuestion($subchapter, 2, 'drag_drop', [
            'pertanyaan' => 'Kelompokkan objek.',
            'activity_payload' => [
                'instruction' => 'Tempatkan setiap objek.',
                'items' => [
                    ['id' => 'item_1', 'label' => 'Ikan saluang', 'image_url' => null],
                    ['id' => 'item_2', 'label' => 'Katak', 'image_url' => null],
                ],
                'categories' => [
                    ['id' => 'category_1', 'label' => 'Sungai'],
                    ['id' => 'category_2', 'label' => 'Dua alam'],
                ],
                'assignments' => [
                    'item_1' => 'category_1',
                    'item_2' => 'category_2',
                ],
            ],
        ]);
        $matching = $this->createQuestion($subchapter, 3, 'matching', [
            'pertanyaan' => 'Hubungkan objek dengan habitat.',
            'activity_payload' => [
                'instruction' => 'Pilih satu pasangan untuk setiap objek.',
                'left_items' => [
                    ['id' => 'left_1', 'label' => 'Ikan saluang', 'image_url' => null],
                    ['id' => 'left_2', 'label' => 'Katak', 'image_url' => null],
                ],
                'right_items' => [
                    ['id' => 'right_1', 'label' => 'Sungai', 'image_url' => null],
                    ['id' => 'right_2', 'label' => 'Dua alam', 'image_url' => null],
                ],
                'pairs' => [
                    ['left_id' => 'left_1', 'right_id' => 'right_1'],
                    ['left_id' => 'left_1', 'right_id' => 'right_2'],
                    ['left_id' => 'left_2', 'right_id' => 'right_1'],
                ],
            ],
        ]);

        $this->actingAs($student)
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('id="formativeActivitiesCard"', false)
            ->assertDontSee('id="formativeActivityList"', false)
            ->assertDontSee('Periksa Jawaban');
        $this->actingAs($student)
            ->postJson("/siswa/subchapters/{$subchapter->id}/open")
            ->assertOk();
        $this->getJson("/siswa/quiz/subchapters/{$subchapter->id}/questions")
            ->assertOk()
            ->assertJsonCount(3, 'questions')
            ->assertJsonPath('questions.0.id', $quiz->id)
            ->assertJsonPath('questions.0.activity_type', 'multiple_choice')
            ->assertJsonPath('questions.1.id', $drag->id)
            ->assertJsonPath('questions.1.activity_type', 'drag_drop')
            ->assertJsonPath('questions.2.id', $matching->id)
            ->assertJsonPath('questions.2.activity_type', 'matching')
            ->assertJsonPath('questions.2.required_answer_count', 3)
            ->assertJsonMissingPath('questions.1.assignments')
            ->assertJsonMissingPath('questions.2.pairs')
            ->assertJsonMissingPath('questions.1.activity_payload');
        $attemptId = $this->postJson("/siswa/quiz/subchapters/{$subchapter->id}/attempts")
            ->assertCreated()
            ->json('attempt.id');
        $newStudent = $this->createUser('siswa', 'siswa-new-activity-quiz');
        $this->actingAs($newStudent)
            ->getJson("/siswa/quiz/subchapters/{$subchapter->id}/questions")
            ->assertOk()
            ->assertJsonCount(3, 'questions')
            ->assertJsonPath('questions.0.id', $quiz->id)
            ->assertJsonPath('questions.1.id', $drag->id)
            ->assertJsonPath('questions.2.id', $matching->id)
            ->assertJsonPath('questions.1.student_answer', null)
            ->assertJsonPath('questions.2.student_answer', null);

        $this->actingAs($student);
        $this->postJson("/siswa/quiz/activities/{$drag->id}/answers", [
            'attempt_id' => $attemptId,
            'answers' => [],
        ])->assertOk()
            ->assertJsonPath('saved', true)
            ->assertJsonPath('completed', false);
        $this->postJson("/siswa/quiz/activities/{$drag->id}/answers", [
            'attempt_id' => $attemptId,
            'answers' => ['item_1' => 'category_1'],
        ])->assertOk()
            ->assertJsonPath('saved', true)
            ->assertJsonPath('completed', false);
        $this->postJson("/siswa/quiz/activities/{$drag->id}/answers", [
            'attempt_id' => $attemptId,
            'answers' => ['item_1' => 'category_1', 'item_2' => 'category_2'],
        ])->assertOk()
            ->assertJsonPath('saved', true)
            ->assertJsonPath('completed', false);
        $this->postJson("/siswa/quiz/questions/{$quiz->id}/check", [
            'attempt_id' => $attemptId,
            'jawaban' => 'B',
        ])->assertOk()
            ->assertJsonPath('saved', true)
            ->assertJsonPath('completed', false)
            ->assertJsonPath('answer_feedback.correct', false)
            ->assertJsonPath('answer_feedback.correct_answer', 'A')
            ->assertJsonPath('answer_feedback.explanation', 'Ikan saluang hidup di sungai.');
        $this->postJson("/siswa/quiz/questions/{$quiz->id}/check", [
            'attempt_id' => $attemptId,
            'jawaban' => 'A',
        ])->assertOk()
            ->assertJsonPath('saved', true)
            ->assertJsonPath('completed', false)
            ->assertJsonPath('answer_feedback.correct', true);
        $this->postJson("/siswa/quiz/activities/{$matching->id}/answers", [
            'attempt_id' => $attemptId,
            'answers' => [
                ['left_id' => 'left_1', 'right_id' => 'right_1'],
                ['left_id' => 'left_1', 'right_id' => 'right_2'],
            ],
        ])->assertOk()
            ->assertJsonPath('saved', true)
            ->assertJsonPath('completed', false);

        $this->postJson("/siswa/quiz/activities/{$matching->id}/answers", [
            'attempt_id' => $attemptId,
            'answers' => [],
        ])->assertOk()
            ->assertJsonPath('saved', true)
            ->assertJsonPath('completed', false);
        $this->getJson("/siswa/quiz/subchapters/{$subchapter->id}/questions")
            ->assertOk()
            ->assertJsonPath('questions.2.student_answer', [])
            ->assertJsonMissingPath('questions.2.activity_feedback');

        $this->postJson("/siswa/quiz/activities/{$matching->id}/answers", [
            'attempt_id' => $attemptId,
            'answers' => [
                ['left_id' => 'left_1', 'right_id' => 'right_1'],
                ['left_id' => 'left_1', 'right_id' => 'right_2'],
            ],
        ])->assertOk()
            ->assertJsonPath('saved', true)
            ->assertJsonPath('completed', false);

        $this->getJson("/siswa/quiz/subchapters/{$subchapter->id}/questions")
            ->assertOk()
            ->assertJsonPath('active_attempt.id', $attemptId)
            ->assertJsonPath('questions.2.student_answer.1.right_id', 'right_2')
            ->assertJsonMissingPath('questions.2.activity_feedback');

        $this->assertSame(
            [
                ['left_id' => 'left_1', 'right_id' => 'right_1'],
                ['left_id' => 'left_1', 'right_id' => 'right_2'],
            ],
            StudentQuizAttempt::query()
                ->whereKey($attemptId)
                ->firstOrFail()
                ->answers[(string) $matching->id]['answers']
        );
        $this->assertDatabaseMissing('student_quiz_answers', [
            'user_id' => $student->id,
            'quiz_question_id' => $drag->id,
        ]);
        $this->assertDatabaseMissing('student_quiz_answers', [
            'user_id' => $student->id,
            'quiz_question_id' => $matching->id,
        ]);
        $this->assertDatabaseCount('student_assessment_results', 0);
        $this->assertDatabaseHas('student_subchapter_progress', [
            'user_id' => $student->id,
            'subchapter_id' => $subchapter->id,
            'status' => 'in_progress',
        ]);

        $this->postJson("/siswa/quiz/activities/{$matching->id}/answers", [
            'attempt_id' => $attemptId,
            'answers' => [
                ['left_id' => 'left_1', 'right_id' => 'right_1'],
                ['left_id' => 'left_1', 'right_id' => 'right_2'],
                ['left_id' => 'left_2', 'right_id' => 'right_1'],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('saved', true)
            ->assertJsonPath('completed', true)
            ->assertJsonPath('total_questions', 1)
            ->assertJsonPath('score', 100)
            ->assertJsonPath('activity_feedback.student_pairs.0.is_correct', true)
            ->assertJsonPath('activity_feedback.student_pairs.1.is_correct', true)
            ->assertJsonPath('activity_feedback.student_pairs.2.is_correct', true)
            ->assertJsonPath('review.2.correct_pairs.0.left', 'Ikan saluang');
        $this->assertDatabaseCount('student_quiz_answers', 0);
        $this->assertDatabaseCount('student_quiz_activity_answers', 0);
        $this->assertDatabaseCount('student_quiz_attempts', 1);
        $this->assertDatabaseCount('student_assessment_results', 1);
        $this->assertDatabaseHas('student_assessment_results', [
            'user_id' => $student->id,
            'subchapter_id' => $subchapter->id,
            'score' => 100,
            'total_questions' => 1,
        ]);
        $this->actingAs($student);
        $nextAttemptId = $this->postJson("/siswa/quiz/subchapters/{$subchapter->id}/attempts")
            ->assertCreated()
            ->json('attempt.id');
        $this->assertNotSame($attemptId, $nextAttemptId);
        $this->getJson("/siswa/quiz/subchapters/{$subchapter->id}/questions")
            ->assertOk()
            ->assertJsonPath('active_attempt.id', $nextAttemptId)
            ->assertJsonPath('questions.0.student_answer', null)
            ->assertJsonPath('questions.1.student_answer', null)
            ->assertJsonPath('latest_result.score', 100)
            ->assertJsonMissingPath('latest_result.review');
        $this->postJson("/siswa/quiz/activities/{$drag->id}/answers", [
            'attempt_id' => $nextAttemptId,
            'answers' => ['item_1' => 'category_2'],
        ])->assertOk()
            ->assertJsonPath('completed', false);
        $this->getJson("/siswa/quiz/subchapters/{$subchapter->id}/questions")
            ->assertOk()
            ->assertJsonPath('active_attempt.id', $nextAttemptId)
            ->assertJsonPath('questions.0.student_answer', null)
            ->assertJsonPath('questions.1.student_answer.item_1', 'category_2')
            ->assertJsonPath('questions.2.student_answer', null);
        $this->assertSame(
            $nextAttemptId,
            $this->postJson("/siswa/quiz/subchapters/{$subchapter->id}/attempts")
                ->assertCreated()
                ->json('attempt.id')
        );
        $this->postJson("/siswa/quiz/activities/{$drag->id}/answers", [
            'attempt_id' => $nextAttemptId,
            'answers' => ['item_1' => 'category_1', 'item_2' => 'category_2'],
        ])->assertOk()
            ->assertJsonPath('completed', false);
        $this->postJson("/siswa/quiz/activities/{$matching->id}/answers", [
            'attempt_id' => $nextAttemptId,
            'answers' => [
                ['left_id' => 'left_1', 'right_id' => 'right_1'],
                ['left_id' => 'left_1', 'right_id' => 'right_2'],
                ['left_id' => 'left_2', 'right_id' => 'right_1'],
            ],
        ])->assertOk()
            ->assertJsonPath('completed', false);
        $this->postJson("/siswa/quiz/questions/{$quiz->id}/check", [
            'attempt_id' => $nextAttemptId,
            'jawaban' => 'B',
        ])->assertOk()
            ->assertJsonPath('completed', true)
            ->assertJsonPath('score', 0);

        $thirdAttemptId = $this->postJson("/siswa/quiz/subchapters/{$subchapter->id}/attempts")
            ->assertCreated()
            ->json('attempt.id');
        $this->assertNotSame($nextAttemptId, $thirdAttemptId);
        $this->getJson("/siswa/quiz/subchapters/{$subchapter->id}/questions")
            ->assertOk()
            ->assertJsonPath('active_attempt.id', $thirdAttemptId)
            ->assertJsonPath('questions.0.student_answer', null)
            ->assertJsonPath('questions.1.student_answer', null)
            ->assertJsonPath('questions.2.student_answer', null)
            ->assertJsonPath('latest_result.score', 0);
        $this->assertDatabaseHas('student_quiz_attempts', [
            'id' => $attemptId,
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('student_quiz_attempts', [
            'id' => $nextAttemptId,
            'status' => 'completed',
            'score' => 0,
        ]);
        $this->assertDatabaseHas('student_quiz_attempts', [
            'id' => $thirdAttemptId,
            'status' => 'in_progress',
            'answers' => '[]',
        ]);
    }

    public function test_matching_feedback_updates_for_one_to_many_pairs_without_scoring_activity(): void
    {
        $teacher = $this->createUser('guru', 'guru-matching-feedback');
        $student = $this->createUser('siswa', 'siswa-matching-feedback');
        $subchapter = $this->createSubchapter($teacher, true);
        $matching = $this->createQuestion($subchapter, 1, 'matching', [
            'activity_payload' => [
                'left_items' => [
                    ['id' => 'left_1', 'label' => 'Objek satu'],
                    ['id' => 'left_2', 'label' => 'Objek dua'],
                ],
                'right_items' => [
                    ['id' => 'right_1', 'label' => 'Pasangan satu'],
                    ['id' => 'right_2', 'label' => 'Pasangan dua'],
                ],
                'pairs' => [
                    ['left_id' => 'left_1', 'right_id' => 'right_1'],
                    ['left_id' => 'left_1', 'right_id' => 'right_2'],
                    ['left_id' => 'left_2', 'right_id' => 'right_1'],
                ],
            ],
        ]);
        $quiz = $this->createQuestion($subchapter, 2, 'multiple_choice', [
            'pertanyaan' => 'Pilih jawaban.',
            'pilihan_a' => 'Benar',
            'pilihan_b' => 'Salah',
            'pilihan_c' => 'Salah',
            'pilihan_d' => 'Salah',
            'kunci_jawaban' => 'A',
        ]);

        $this->actingAs($student)
            ->postJson("/siswa/subchapters/{$subchapter->id}/open")
            ->assertOk();
        $attemptId = $this->postJson("/siswa/quiz/subchapters/{$subchapter->id}/attempts")
            ->assertCreated()
            ->json('attempt.id');

        $this->postJson("/siswa/quiz/activities/{$matching->id}/answers", [
            'attempt_id' => $attemptId,
            'answers' => [
                ['left_id' => 'left_1', 'right_id' => 'right_1'],
                ['left_id' => 'left_1', 'right_id' => 'right_2'],
                ['left_id' => 'left_2', 'right_id' => 'right_1'],
            ],
        ])->assertOk()
            ->assertJsonPath('completed', false)
            ->assertJsonPath('activity_feedback.student_pairs.0.is_correct', true)
            ->assertJsonCount(3, 'activity_feedback.correct_pairs');
        $this->getJson("/siswa/quiz/subchapters/{$subchapter->id}/questions")
            ->assertOk()
            ->assertJsonPath('active_attempt.id', $attemptId)
            ->assertJsonPath('questions.0.activity_feedback.student_pairs.1.is_correct', true);

        $this->postJson("/siswa/quiz/activities/{$matching->id}/answers", [
            'attempt_id' => $attemptId,
            'answers' => [
                ['left_id' => 'left_2', 'right_id' => 'right_2'],
                ['left_id' => 'left_1', 'right_id' => 'right_2'],
                ['left_id' => 'left_2', 'right_id' => 'right_1'],
            ],
        ])->assertOk()
            ->assertJsonPath('completed', false)
            ->assertJsonPath('activity_feedback.student_pairs.0.is_correct', false)
            ->assertJsonPath('activity_feedback.student_pairs.1.is_correct', true)
            ->assertJsonPath('activity_feedback.student_pairs.2.is_correct', true)
            ->assertJsonPath('activity_feedback.correct_pairs.0.left', 'Objek satu');
        $this->getJson("/siswa/quiz/subchapters/{$subchapter->id}/questions")
            ->assertOk()
            ->assertJsonPath('questions.0.activity_feedback.student_pairs.0.is_correct', false);

        $this->postJson("/siswa/quiz/questions/{$quiz->id}/check", [
            'attempt_id' => $attemptId,
            'jawaban' => 'A',
        ])->assertOk()
            ->assertJsonPath('completed', true)
            ->assertJsonPath('score', 100);

        $this->assertDatabaseHas('student_quiz_attempts', [
            'id' => $attemptId,
            'status' => 'completed',
            'score' => 100,
        ]);
    }

    public function test_last_multiple_choice_answer_includes_feedback_when_it_completes_the_attempt(): void
    {
        $teacher = $this->createUser('guru', 'guru-final-question-feedback');
        $student = $this->createUser('siswa', 'siswa-final-question-feedback');
        $subchapter = $this->createSubchapter($teacher, true);
        $question = $this->createQuestion($subchapter, 1, 'multiple_choice', [
            'pertanyaan' => 'Apa habitat ikan saluang?',
            'pilihan_a' => 'Sungai',
            'pilihan_b' => 'Padang pasir',
            'pilihan_c' => 'Gurun es',
            'pilihan_d' => 'Langit',
            'kunci_jawaban' => 'A',
            'pembahasan' => 'Ikan saluang hidup di sungai.',
        ]);

        $this->actingAs($student)
            ->postJson("/siswa/subchapters/{$subchapter->id}/open")
            ->assertOk();
        $attemptId = $this->postJson("/siswa/quiz/subchapters/{$subchapter->id}/attempts")
            ->assertCreated()
            ->json('attempt.id');

        $this->postJson("/siswa/quiz/questions/{$question->id}/check", [
            'attempt_id' => $attemptId,
            'jawaban' => 'A',
        ])->assertOk()
            ->assertJsonPath('completed', true)
            ->assertJsonPath('answer_feedback.correct', true)
            ->assertJsonPath('answer_feedback.correct_answer', 'A')
            ->assertJsonPath('answer_feedback.explanation', 'Ikan saluang hidup di sungai.');
    }

    public function test_matching_attempt_keeps_randomized_right_order_and_scores_answers_by_pair_ids(): void
    {
        $teacher = $this->createUser('guru', 'guru-matching-order');
        $student = $this->createUser('siswa', 'siswa-matching-order');
        $subchapter = $this->createSubchapter($teacher, true);
        $question = $this->createQuestion($subchapter, 1, 'matching', [
            'activity_payload' => [
                'left_items' => [
                    ['id' => 'left_1', 'label' => 'Ikan besar'],
                    ['id' => 'left_2', 'label' => 'Lebah dan bunga'],
                    ['id' => 'left_3', 'label' => 'Paku pada pohon'],
                    ['id' => 'left_4', 'label' => 'Benalu pada pohon'],
                ],
                'right_items' => [
                    ['id' => 'right_1', 'label' => 'Predasi'],
                    ['id' => 'right_2', 'label' => 'Mutualisme'],
                    ['id' => 'right_3', 'label' => 'Komensalisme'],
                    ['id' => 'right_4', 'label' => 'Parasitisme'],
                ],
                'pairs' => [
                    ['left_id' => 'left_1', 'right_id' => 'right_1'],
                    ['left_id' => 'left_2', 'right_id' => 'right_2'],
                    ['left_id' => 'left_3', 'right_id' => 'right_3'],
                    ['left_id' => 'left_4', 'right_id' => 'right_4'],
                ],
            ],
        ]);
        StudentSubchapterProgress::create([
            'user_id' => $student->id,
            'subchapter_id' => $subchapter->id,
            'status' => 'in_progress',
            'opened_at' => now(),
        ]);

        $this->actingAs($student);
        $attemptId = $this->postJson("/siswa/quiz/subchapters/{$subchapter->id}/attempts")
            ->assertCreated()
            ->json('attempt.id');
        $questionsUrl = "/siswa/quiz/subchapters/{$subchapter->id}/questions?attempt_id={$attemptId}";
        $firstOrder = $this->getJson($questionsUrl)
            ->assertOk()
            ->assertJsonCount(1, 'questions')
            ->json('questions.0.right_items');
        $firstIds = array_column($firstOrder, 'id');
        $this->assertNotSame(['right_1', 'right_2', 'right_3', 'right_4'], $firstIds);
        $this->assertSame(
            $firstIds,
            array_column($this->getJson($questionsUrl)->json('questions.0.right_items'), 'id')
        );

        $this->postJson("/siswa/quiz/activities/{$question->id}/answers", [
            'attempt_id' => $attemptId,
            'answers' => [
                ['left_id' => 'left_1', 'right_id' => 'right_1'],
                ['left_id' => 'left_2', 'right_id' => 'right_2'],
                ['left_id' => 'left_3', 'right_id' => 'right_3'],
                ['left_id' => 'left_4', 'right_id' => 'right_4'],
            ],
        ])->assertOk()
            ->assertJsonPath('saved', true)
            ->assertJsonPath('completed', true);
        $this->assertSame(
            'completed',
            StudentQuizAttempt::findOrFail($attemptId)->status
        );
        $this->assertCount(
            4,
            StudentQuizAttempt::findOrFail($attemptId)->answers[(string) $question->id]['answers']
        );
    }

    public function test_activity_only_quiz_completes_without_creating_numeric_grade(): void
    {
        $teacher = $this->createUser('guru', 'guru-activity-only');
        $student = $this->createUser('siswa', 'siswa-activity-only');
        $subchapter = $this->createSubchapter($teacher, true);
        $otherSubchapter = $this->createSubchapter($teacher, true, 2);
        $activity = $this->createQuestion($subchapter, 1, 'drag_drop', [
            'activity_payload' => [
                'instruction' => '',
                'items' => [
                    ['id' => 'item_1', 'label' => 'Objek', 'image_url' => null],
                    ['id' => 'item_2', 'label' => 'Objek lain', 'image_url' => null],
                ],
                'categories' => [
                    ['id' => 'category_1', 'label' => 'Kategori'],
                    ['id' => 'category_2', 'label' => 'Kategori lain'],
                ],
                'assignments' => ['item_1' => 'category_1', 'item_2' => 'category_2'],
            ],
        ]);

        $this->actingAs($student)
            ->postJson("/siswa/subchapters/{$subchapter->id}/open")
            ->assertOk();
        $attemptId = $this->postJson("/siswa/quiz/subchapters/{$subchapter->id}/attempts")
            ->assertCreated()
            ->json('attempt.id');
        $this->postJson("/siswa/quiz/activities/{$activity->id}/answers", [
            'attempt_id' => $attemptId,
            'answers' => ['item_1' => 'category_2', 'item_2' => 'category_2'],
        ])
            ->assertOk()
            ->assertJsonPath('completed', true)
            ->assertJsonPath('total_questions', 0)
            ->assertJsonPath('activity_feedback.items.0.is_correct', false)
            ->assertJsonPath('activity_feedback.items.0.student_category', 'Kategori lain')
            ->assertJsonPath('activity_feedback.items.0.correct_category', 'Kategori')
            ->assertJsonPath('activity_feedback.items.1.is_correct', true);
        $this->getJson("/siswa/quiz/subchapters/{$otherSubchapter->id}/questions")
            ->assertOk()
            ->assertJsonCount(0, 'questions');

        $this->assertDatabaseCount('student_quiz_answers', 0);
        $this->assertDatabaseCount('student_assessment_results', 0);
        $this->assertDatabaseCount('student_quiz_activity_answers', 0);
        $this->assertDatabaseHas('student_quiz_attempts', [
            'id' => $attemptId,
            'subchapter_id' => $subchapter->id,
            'status' => 'completed',
        ]);
        $this->assertSame(1, StudentSubchapterProgress::query()
            ->where('user_id', $student->id)
            ->where('subchapter_id', $subchapter->id)
            ->where('status', 'completed')
            ->count());
        $this->assertDatabaseHas('quiz_questions', [
            'id' => $activity->id,
            'subchapter_id' => $subchapter->id,
            'activity_type' => 'drag_drop',
            'is_published' => true,
        ]);
    }

    public function test_teacher_can_delete_unanswered_questions_of_each_type_without_renumbering(): void
    {
        $teacher = $this->createUser('guru', 'guru-delete-unanswered');
        $subchapter = $this->createSubchapter($teacher);
        $multipleChoice = $this->createQuestion($subchapter, 1, 'multiple_choice');
        $dragDrop = $this->createQuestion($subchapter, 2, 'drag_drop');
        $matching = $this->createQuestion($subchapter, 3, 'matching');

        $baseUrl = "/guru/subchapters/{$subchapter->id}/questions";
        $this->actingAs($teacher)
            ->deleteJson("{$baseUrl}/{$dragDrop->id}")
            ->assertOk()
            ->assertJsonPath('action', 'deleted')
            ->assertJsonPath('message', 'Soal berhasil dihapus.');

        $this->assertDatabaseMissing('quiz_questions', ['id' => $dragDrop->id]);
        $this->getJson($baseUrl)
            ->assertOk()
            ->assertJsonCount(2, 'questions')
            ->assertJsonPath('questions.0.id', $multipleChoice->id)
            ->assertJsonPath('questions.0.nomor_soal', 1)
            ->assertJsonPath('questions.1.id', $matching->id)
            ->assertJsonPath('questions.1.nomor_soal', 3);

        $this->deleteJson("{$baseUrl}/{$multipleChoice->id}")->assertOk();
        $this->deleteJson("{$baseUrl}/{$matching->id}")->assertOk();
        $this->getJson($baseUrl)->assertOk()->assertJsonCount(0, 'questions');

        $created = $this->postJson($baseUrl, [
            'nomor_soal' => 4,
            'pertanyaan' => 'Soal baru setelah penghapusan.',
            'pilihan_a' => 'A',
            'pilihan_b' => 'B',
            'pilihan_c' => 'C',
            'pilihan_d' => 'D',
            'kunci_jawaban' => 'A',
        ])->assertCreated();

        $this->assertDatabaseHas('quiz_questions', [
            'id' => $created->json('question.id'),
            'nomor_soal' => 4,
        ]);
    }

    public function test_teacher_archives_questions_with_student_history_without_changing_scores(): void
    {
        $teacher = $this->createUser('guru', 'guru-delete-history');
        $student = $this->createUser('siswa', 'siswa-delete-history');
        $subchapter = $this->createSubchapter($teacher, true);
        $question = $this->createQuestion($subchapter, 1, 'matching');
        StudentQuizAnswer::create([
            'user_id' => $student->id,
            'subchapter_id' => $subchapter->id,
            'quiz_question_id' => $question->id,
            'answer' => 'A',
            'is_correct' => true,
        ]);
        $attempt = StudentQuizAttempt::create([
            'user_id' => $student->id,
            'subchapter_id' => $subchapter->id,
            'status' => 'completed',
            'answers' => [
                (string) $question->id => [
                    'type' => 'matching',
                    'answers' => ['left_1' => 'right_1'],
                ],
            ],
            'review' => [['type' => 'matching']],
            'score' => 100,
            'correct_answers' => 1,
            'total_questions' => 1,
            'completed_at' => now(),
        ]);

        $this->actingAs($teacher)
            ->deleteJson("/guru/subchapters/{$subchapter->id}/questions/{$question->id}")
            ->assertOk()
            ->assertJsonPath('action', 'archived')
            ->assertJsonPath('message', 'Soal berhasil diarsipkan. Riwayat dan nilai siswa tetap tersimpan.');

        $this->assertSoftDeleted('quiz_questions', ['id' => $question->id]);
        $this->assertDatabaseHas('student_quiz_answers', [
            'user_id' => $student->id,
            'quiz_question_id' => $question->id,
            'is_correct' => true,
        ]);
        $this->assertDatabaseHas('student_quiz_attempts', [
            'id' => $attempt->id,
            'score' => 100,
            'status' => 'completed',
        ]);
        $this->assertSame(
            [['type' => 'matching']],
            $attempt->fresh()->review
        );

        $activityQuestion = $this->createQuestion($subchapter, 2, 'drag_drop');
        DB::table('student_quiz_activity_answers')->insert([
            'user_id' => $student->id,
            'subchapter_id' => $subchapter->id,
            'quiz_question_id' => $activityQuestion->id,
            'answers' => json_encode(['item_1' => 'category_1']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->deleteJson("/guru/subchapters/{$subchapter->id}/questions/{$activityQuestion->id}")
            ->assertOk()
            ->assertJsonPath('action', 'archived');
        $this->assertSoftDeleted('quiz_questions', ['id' => $activityQuestion->id]);
        $this->assertDatabaseHas('student_quiz_activity_answers', [
            'user_id' => $student->id,
            'quiz_question_id' => $activityQuestion->id,
        ]);

        $this->actingAs($student)
            ->deleteJson("/guru/subchapters/{$subchapter->id}/questions/{$question->id}")
            ->assertForbidden();
        $this->actingAs($teacher)
            ->deleteJson("/guru/subchapters/{$subchapter->id}/questions/not-an-id")
            ->assertNotFound();
    }

    public function test_archived_questions_are_hidden_from_active_quizzes_and_allow_reusing_numbers(): void
    {
        $teacher = $this->createUser('guru', 'guru-archive-new-quiz');
        $student = $this->createUser('siswa', 'siswa-archive-new-quiz');
        $subchapter = $this->createSubchapter($teacher, true);
        $types = ['multiple_choice', 'drag_drop', 'matching'];
        $oldQuestions = collect(range(1, 7))->map(
            fn (int $number) => $this->createQuestion(
                $subchapter,
                $number,
                $types[($number - 1) % count($types)],
                ['is_published' => true]
            )
        );
        $answerMap = $oldQuestions->mapWithKeys(fn (QuizQuestion $question) => [
            (string) $question->id => [
                'type' => $question->activity_type,
                'answers' => [],
            ],
        ])->all();
        $attempt = StudentQuizAttempt::create([
            'user_id' => $student->id,
            'subchapter_id' => $subchapter->id,
            'status' => 'completed',
            'answers' => $answerMap,
            'review' => [['number' => 1, 'prompt' => 'Riwayat soal lama']],
            'score' => 80,
            'correct_answers' => 4,
            'total_questions' => 5,
            'completed_at' => now(),
        ]);
        StudentQuizAnswer::create([
            'user_id' => $student->id,
            'subchapter_id' => $subchapter->id,
            'quiz_question_id' => $oldQuestions[0]->id,
            'answer' => 'A',
            'is_correct' => true,
        ]);

        $baseUrl = "/guru/subchapters/{$subchapter->id}/questions";
        foreach ($oldQuestions as $question) {
            $this->actingAs($teacher)
                ->deleteJson("{$baseUrl}/{$question->id}")
                ->assertOk()
                ->assertJsonPath('action', 'archived');
            $this->assertSoftDeleted('quiz_questions', ['id' => $question->id]);
        }

        $this->assertSame(80.0, (float) $attempt->fresh()->score);
        $this->assertSame($answerMap, $attempt->fresh()->answers);
        $this->assertSame(
            [['number' => 1, 'prompt' => 'Riwayat soal lama']],
            $attempt->fresh()->review
        );
        $this->assertDatabaseHas('student_quiz_answers', [
            'quiz_question_id' => $oldQuestions[0]->id,
            'is_correct' => true,
        ]);
        $this->assertSame(
            $oldQuestions[0]->id,
            StudentQuizAnswer::query()
                ->where('quiz_question_id', $oldQuestions[0]->id)
                ->firstOrFail()
                ->question
                ->id
        );

        $this->getJson($baseUrl)
            ->assertOk()
            ->assertJsonCount(0, 'questions');
        StudentSubchapterProgress::create([
            'user_id' => $student->id,
            'subchapter_id' => $subchapter->id,
            'status' => 'in_progress',
            'opened_at' => now(),
        ]);
        $this->actingAs($student)
            ->getJson("/siswa/quiz/subchapters/{$subchapter->id}/questions")
            ->assertOk()
            ->assertJsonCount(0, 'questions')
            ->assertJsonPath('latest_result.review.0.prompt', 'Riwayat soal lama');
        $this->postJson("/siswa/quiz/subchapters/{$subchapter->id}/attempts")
            ->assertStatus(409)
            ->assertJsonPath('message', 'Belum ada soal kuis aktif pada Subbab ini.');

        $newQuestions = [];
        for ($number = 1; $number <= 5; $number++) {
            $newQuestions[] = $this->actingAs($teacher)
                ->postJson($baseUrl, [
                    'nomor_soal' => $number,
                    'pertanyaan' => "Soal baru {$number}.",
                    'pilihan_a' => 'A',
                    'pilihan_b' => 'B',
                    'pilihan_c' => 'C',
                    'pilihan_d' => 'D',
                    'kunci_jawaban' => 'A',
                    'is_published' => true,
                ])
                ->assertCreated()
                ->assertJsonPath('question.nomor_soal', $number)
                ->json('question.id');
        }

        $this->getJson($baseUrl)
            ->assertOk()
            ->assertJsonCount(5, 'questions')
            ->assertJsonPath('questions.0.nomor_soal', 1)
            ->assertJsonPath('questions.4.nomor_soal', 5);
        $this->actingAs($student)
            ->getJson("/siswa/quiz/subchapters/{$subchapter->id}/questions")
            ->assertOk()
            ->assertJsonCount(5, 'questions')
            ->assertJsonPath('questions.0.id', $newQuestions[0])
            ->assertJsonPath('questions.4.id', $newQuestions[4]);
    }

    public function test_archiving_question_preserves_and_completes_an_active_attempt_from_its_snapshot(): void
    {
        $teacher = $this->createUser('guru', 'guru-active-attempt-delete');
        $student = $this->createUser('siswa', 'siswa-active-attempt-delete');
        $subchapter = $this->createSubchapter($teacher, true);
        $question = $this->createQuestion($subchapter, 1, 'multiple_choice', [
            'is_published' => true,
            'pertanyaan' => 'Soal pilihan ganda yang diarsipkan.',
            'pilihan_a' => 'Jawaban benar',
        ]);
        $drag = $this->createQuestion($subchapter, 2, 'drag_drop', [
            'nomor_soal' => 3,
            'activity_payload' => [
                'items' => [['id' => 'item_1', 'label' => 'Ikan saluang']],
                'categories' => [['id' => 'category_1', 'label' => 'Sungai']],
                'assignments' => ['item_1' => 'category_1'],
            ],
        ]);
        $matching = $this->createQuestion($subchapter, 3, 'matching', [
            'nomor_soal' => 4,
            'activity_payload' => [
                'left_items' => [['id' => 'left_1', 'label' => 'Ikan saluang']],
                'right_items' => [['id' => 'right_1', 'label' => 'Sungai']],
                'pairs' => [['left_id' => 'left_1', 'right_id' => 'right_1']],
            ],
        ]);
        $activeMultipleChoice = $this->createQuestion($subchapter, 2, 'multiple_choice', [
            'pertanyaan' => 'Soal pilihan ganda yang tetap aktif.',
            'pilihan_a' => 'Jawaban benar',
        ]);
        $attempt = StudentQuizAttempt::create([
            'user_id' => $student->id,
            'subchapter_id' => $subchapter->id,
            'status' => 'in_progress',
            'answers' => [],
        ]);

        $this->actingAs($teacher)
            ->deleteJson("/guru/subchapters/{$subchapter->id}/questions/{$question->id}")
            ->assertOk()
            ->assertJsonPath('action', 'archived');
        $this->assertSoftDeleted('quiz_questions', ['id' => $question->id]);
        $this->assertDatabaseHas('quiz_questions', [
            'id' => $drag->id,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('student_quiz_attempts', [
            'id' => $attempt->id,
            'status' => 'in_progress',
            'score' => null,
        ]);
        $this->assertSame([], $attempt->fresh()->answers);
        $this->assertCount(4, $attempt->fresh()->review['_question_snapshots']);

        $replacement = $this->actingAs($teacher)->postJson(
            "/guru/subchapters/{$subchapter->id}/questions",
            [
                'nomor_soal' => 1,
                'pertanyaan' => 'Soal pengganti.',
                'pilihan_a' => 'A',
                'pilihan_b' => 'B',
                'pilihan_c' => 'C',
                'pilihan_d' => 'D',
                'kunci_jawaban' => 'A',
                'is_published' => true,
            ]
        )->assertCreated()->json('question');

        StudentSubchapterProgress::create([
            'user_id' => $student->id,
            'subchapter_id' => $subchapter->id,
            'status' => 'in_progress',
            'opened_at' => now(),
        ]);
        $this->actingAs($student);
        $this->postJson("/siswa/quiz/subchapters/{$subchapter->id}/attempts")
            ->assertCreated()
            ->assertJsonPath('attempt.id', $attempt->id);
        $this->getJson("/siswa/quiz/subchapters/{$subchapter->id}/questions")
            ->assertOk()
            ->assertJsonCount(3, 'questions')
            ->assertJsonPath('questions.0.id', $activeMultipleChoice->id)
            ->assertJsonPath('questions.0.pertanyaan', 'Soal pilihan ganda yang tetap aktif.')
            ->assertJsonPath('questions.1.id', $drag->id)
            ->assertJsonPath('questions.2.id', $matching->id);
        $this->getJson("/siswa/quiz/subchapters/{$subchapter->id}/questions")
            ->assertOk()
            ->assertJsonCount(3, 'questions')
            ->assertJsonMissing(['id' => $replacement['id']]);
        $this->postJson("/siswa/quiz/questions/{$question->id}/check", [
            'attempt_id' => $attempt->id,
            'jawaban' => 'A',
        ])->assertNotFound();
        $this->postJson("/siswa/quiz/questions/{$activeMultipleChoice->id}/check", [
            'attempt_id' => $attempt->id,
            'jawaban' => 'A',
        ])->assertOk()
            ->assertJsonPath('saved', true)
            ->assertJsonPath('completed', false)
            ->assertJsonPath('answer_feedback.correct', true);
        $this->postJson("/siswa/quiz/activities/{$drag->id}/answers", [
            'attempt_id' => $attempt->id,
            'answers' => ['item_1' => 'category_1'],
        ])->assertOk()
            ->assertJsonPath('saved', true)
            ->assertJsonPath('completed', false);
        $this->postJson("/siswa/quiz/activities/{$matching->id}/answers", [
            'attempt_id' => $attempt->id,
            'answers' => [['left_id' => 'left_1', 'right_id' => 'right_1']],
        ])->assertOk()
            ->assertJsonPath('saved', true)
            ->assertJsonPath('completed', true)
            ->assertJsonPath('score', 100)
            ->assertJsonPath('total_questions', 1);

        $completedAttempt = $attempt->fresh();
        $this->assertSame('completed', $completedAttempt->status);
        $this->assertSame(100.0, (float) $completedAttempt->score);
        $this->assertCount(3, $completedAttempt->answers);
        $this->assertCount(3, $completedAttempt->review);
        $this->assertSame('Soal pilihan ganda yang tetap aktif.', $completedAttempt->review[0]['prompt']);
        $this->assertDatabaseHas('student_assessment_results', [
            'user_id' => $student->id,
            'subchapter_id' => $subchapter->id,
            'score' => 100,
        ]);

        $this->assertNotSame($question->id, $replacement['id']);
        $newStudent = $this->createUser('siswa', 'siswa-new-attempt-after-archive');
        $this->actingAs($newStudent)
            ->getJson("/siswa/quiz/subchapters/{$subchapter->id}/questions")
            ->assertOk()
            ->assertJsonCount(4, 'questions')
            ->assertJsonPath('questions.0.id', $replacement['id'])
            ->assertJsonPath('questions.1.id', $activeMultipleChoice->id);
    }

    public function test_refresh_starts_a_clean_attempt_without_overwriting_old_answers_or_showing_archived_questions(): void
    {
        $teacher = $this->createUser('guru', 'guru-refresh-quiz');
        $student = $this->createUser('siswa', 'siswa-refresh-quiz');
        $subchapter = $this->createSubchapter($teacher, true);
        $archivedQuestion = $this->createQuestion($subchapter, 1, 'multiple_choice', [
            'pertanyaan' => 'Soal lama yang akan diarsipkan.',
        ]);
        $activeQuestion = $this->createQuestion($subchapter, 2, 'multiple_choice', [
            'pertanyaan' => 'Soal aktif untuk attempt baru.',
        ]);
        StudentSubchapterProgress::create([
            'user_id' => $student->id,
            'subchapter_id' => $subchapter->id,
            'status' => 'in_progress',
            'opened_at' => now(),
        ]);

        $oldAttemptId = $this->actingAs($student)
            ->postJson("/siswa/quiz/subchapters/{$subchapter->id}/attempts")
            ->assertCreated()
            ->json('attempt.id');
        $this->postJson("/siswa/quiz/questions/{$archivedQuestion->id}/check", [
            'attempt_id' => $oldAttemptId,
            'jawaban' => 'A',
        ])->assertOk()
            ->assertJsonPath('saved', true)
            ->assertJsonPath('completed', false);

        $this->actingAs($teacher)
            ->deleteJson("/guru/subchapters/{$subchapter->id}/questions/{$archivedQuestion->id}")
            ->assertOk()
            ->assertJsonPath('action', 'archived');

        $newAttemptId = $this->actingAs($student)
            ->postJson("/siswa/quiz/subchapters/{$subchapter->id}/attempts", [
                'restart' => true,
            ])
            ->assertCreated()
            ->json('attempt.id');
        $this->assertNotSame($oldAttemptId, $newAttemptId);
        $this->assertDatabaseHas('student_quiz_attempts', [
            'id' => $oldAttemptId,
            'status' => 'in_progress',
        ]);
        $this->assertSame(
            ['type' => 'multiple_choice', 'answer' => 'A'],
            StudentQuizAttempt::findOrFail($oldAttemptId)->answers[(string) $archivedQuestion->id]
        );

        $this->getJson(
            "/siswa/quiz/subchapters/{$subchapter->id}/questions?attempt_id={$newAttemptId}"
        )->assertOk()
            ->assertJsonCount(1, 'questions')
            ->assertJsonPath('questions.0.id', $activeQuestion->id)
            ->assertJsonPath('questions.0.student_answer', null)
            ->assertJsonMissingPath('questions.0.answer_feedback');

        $this->postJson("/siswa/quiz/questions/{$activeQuestion->id}/check", [
            'attempt_id' => $newAttemptId,
            'jawaban' => 'B',
        ])->assertOk()
            ->assertJsonPath('saved', true)
            ->assertJsonPath('completed', true);

        $this->assertSame(
            ['type' => 'multiple_choice', 'answer' => 'A'],
            StudentQuizAttempt::findOrFail($oldAttemptId)->answers[(string) $archivedQuestion->id]
        );
        $this->assertSame(
            ['type' => 'multiple_choice', 'answer' => 'B'],
            StudentQuizAttempt::findOrFail($newAttemptId)->answers[(string) $activeQuestion->id]
        );
        $this->assertSoftDeleted('quiz_questions', ['id' => $archivedQuestion->id]);
    }

    public function test_legacy_result_review_keeps_archived_question_content_and_answer(): void
    {
        $teacher = $this->createUser('guru', 'guru-legacy-archive-review');
        $student = $this->createUser('siswa', 'siswa-legacy-archive-review');
        $subchapter = $this->createSubchapter($teacher, true);
        $question = $this->createQuestion($subchapter, 1, 'multiple_choice', [
            'pertanyaan' => 'Soal lama untuk riwayat.',
            'pilihan_a' => 'Jawaban benar',
            'is_published' => true,
        ]);
        StudentQuizAnswer::create([
            'user_id' => $student->id,
            'subchapter_id' => $subchapter->id,
            'quiz_question_id' => $question->id,
            'answer' => 'A',
            'is_correct' => true,
        ]);
        StudentAssessmentResult::create([
            'user_id' => $student->id,
            'assessment_type' => 'quiz',
            'chapter_id' => $subchapter->chapter_id,
            'subchapter_id' => $subchapter->id,
            'total_questions' => 1,
            'correct_answers' => 1,
            'score' => 100,
            'completed_at' => now(),
        ]);

        $this->actingAs($teacher)
            ->deleteJson("/guru/subchapters/{$subchapter->id}/questions/{$question->id}")
            ->assertOk()
            ->assertJsonPath('action', 'archived');

        $this->actingAs($student)
            ->getJson("/siswa/quiz/subchapters/{$subchapter->id}/questions")
            ->assertOk()
            ->assertJsonCount(0, 'questions')
            ->assertJsonPath('latest_result.score', 100)
            ->assertJsonPath('latest_result.review.0.prompt', 'Soal lama untuk riwayat.')
            ->assertJsonPath('latest_result.review.0.student_answer', 'A')
            ->assertJsonPath('latest_result.review.0.student_answer_text', 'Jawaban benar');
    }

    private function createQuestion(
        Subchapter $subchapter,
        int $number,
        string $type,
        array $overrides = []
    ): QuizQuestion {
        return QuizQuestion::create(array_merge([
            'subchapter_id' => $subchapter->id,
            'nomor_soal' => $number,
            'activity_type' => $type,
            'activity_payload' => null,
            'pertanyaan' => "Pertanyaan {$number}",
            'pilihan_a' => '',
            'pilihan_b' => '',
            'pilihan_c' => '',
            'pilihan_d' => '',
            'kunci_jawaban' => 'A',
            'is_published' => true,
        ], $overrides));
    }

    private function createSubchapter(User $teacher, bool $published = false, int $number = 1): Subchapter
    {
        DB::table('teacher_class_assignments')->insertOrIgnore([
            'teacher_id' => $teacher->id,
            'class_name' => 'VII A',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $chapter = Chapter::query()->firstOrCreate(
            [
                'user_id' => $teacher->id,
                'nomor_bab' => 6,
            ],
            [
                'judul' => 'Bab VI',
                'is_published' => true,
            ]
        );
        return Subchapter::create([
            'chapter_id' => $chapter->id,
            'nomor_subbab' => $number,
            'judul' => "Subbab {$number}",
            'konten' => 'Materi',
            'is_published' => $published,
        ]);
    }

    private function createUser(string $role, string $username): User
    {
        return User::create([
            'name' => ucfirst($role) . ' SALUANG',
            'username' => $username,
            'password' => 'secret123',
            'role' => $role,
            'email' => null,
            'kelas' => $role === 'siswa' ? 'VII A' : null,
        ]);
    }
}
