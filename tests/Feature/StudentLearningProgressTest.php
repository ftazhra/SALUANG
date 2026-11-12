<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\QuizQuestion;
use App\Models\Subchapter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentLearningProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_student_can_access_published_material_across_teachers_and_schools(): void
    {
        $teacher = $this->createUser('guru', 'guru-material-scope');
        $teacher->update(['sekolah' => 'Sekolah A']);
        $otherTeacher = $this->createUser('guru', 'guru-material-other-school');
        $otherTeacher->update(['sekolah' => 'Sekolah B']);
        $student = $this->createUser('siswa', 'siswa-material-scope');
        $student->update([
            'kelas' => 'VII A',
            'sekolah' => 'Sekolah A',
        ]);
        $chapter = Chapter::create([
            'user_id' => $teacher->id,
            'nomor_bab' => 1,
            'judul' => 'Materi Guru A',
            'is_published' => true,
        ]);
        $otherChapter = Chapter::create([
            'user_id' => $otherTeacher->id,
            'nomor_bab' => 1,
            'judul' => 'Materi Guru B',
            'is_published' => true,
        ]);
        $subchapter = Subchapter::create([
            'chapter_id' => $otherChapter->id,
            'nomor_subbab' => 1,
            'judul' => 'Subbab Guru B',
            'konten' => 'Materi',
            'is_published' => true,
        ]);

        $this->actingAs($student)
            ->getJson('/siswa/materials')
            ->assertOk()
            ->assertJsonCount(2, 'chapters')
            ->assertJsonFragment(['id' => $chapter->id])
            ->assertJsonFragment(['id' => $otherChapter->id]);

        $this->postJson("/siswa/subchapters/{$subchapter->id}/open")
            ->assertOk()
            ->assertJsonPath('status', 'in_progress');
        $this->assertDatabaseHas('student_subchapter_progress', [
            'user_id' => $student->id,
            'subchapter_id' => $subchapter->id,
        ]);
    }

    public function test_soft_deleted_student_cannot_access_learning_endpoints(): void
    {
        $teacher = $this->createUser('guru', 'guru-deleted-student-content');
        $student = $this->createUser('siswa', 'deleted-student-content');
        $chapter = Chapter::create([
            'user_id' => $teacher->id,
            'nomor_bab' => 6,
            'judul' => 'Bab Aktif',
            'is_published' => true,
        ]);
        $subchapter = Subchapter::create([
            'chapter_id' => $chapter->id,
            'nomor_subbab' => 1,
            'judul' => 'Subbab Aktif',
            'konten' => 'Materi',
            'is_published' => true,
        ]);
        $student->delete();

        $this->actingAs($student)
            ->getJson('/siswa/materials')
            ->assertForbidden();
        $this->actingAs($student)
            ->getJson('/siswa/progress')
            ->assertForbidden();
        $this->actingAs($student)
            ->getJson("/siswa/quiz/subchapters/{$subchapter->id}/questions")
            ->assertForbidden();
    }

    public function test_recent_activity_includes_all_materials_for_the_logged_in_student(): void
    {
        $teacher = $this->createUser('guru', 'guru-recent-activity');
        $student = $this->createUser('siswa', 'siswa-recent-activity');
        $otherStudent = $this->createUser('siswa', 'siswa-other-recent-activity');
        $chapters = collect([6, 7])->map(fn (int $number) => Chapter::create([
            'user_id' => $teacher->id,
            'nomor_bab' => $number,
            'judul' => "Bab {$number}",
            'is_published' => true,
        ]));
        $subchapters = collect(range(1, 7))->map(function (int $number) use ($chapters) {
            $chapter = $number === 1 ? $chapters[0] : $chapters[1];
            return Subchapter::create([
                'chapter_id' => $chapter->id,
                'nomor_subbab' => $number,
                'judul' => "Aktivitas subbab {$number}",
                'is_published' => true,
            ]);
        });

        foreach ($subchapters as $index => $subchapter) {
            $occurredAt = now()->subMinutes($index + 1);
            DB::table('student_subchapter_progress')->insert([
                'user_id' => $student->id,
                'subchapter_id' => $subchapter->id,
                'status' => $index === 0 ? 'completed' : 'in_progress',
                'opened_at' => $occurredAt,
                'completed_at' => $index === 0 ? $occurredAt : null,
                'created_at' => $occurredAt,
                'updated_at' => $occurredAt,
            ]);
        }
        DB::table('student_subchapter_progress')->insert([
            'user_id' => $otherStudent->id,
            'subchapter_id' => $subchapters[0]->id,
            'status' => 'completed',
            'opened_at' => now(),
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($student)
            ->getJson('/siswa/progress')
            ->assertOk()
            ->assertJsonCount(7, 'activities')
            ->assertJsonPath('activities.0.description', 'Aktivitas subbab 1');
        $this->assertSame(
            7,
            collect($response->json('activities'))
                ->pluck('description')
                ->unique()
                ->count()
        );
        $this->assertFalse(
            collect($response->json('activities'))
                ->contains(fn (array $activity) => str_contains(
                    $activity['description'],
                    'siswa lain'
                ))
        );
    }

    public function test_material_and_subchapter_quiz_update_only_the_current_students_progress_and_grade(): void
    {
        $guru = $this->createUser('guru', 'guru-progress');
        $student = $this->createUser('siswa', 'siswa-progress');
        $otherStudent = $this->createUser('siswa', 'siswa-progress-lain');
        $student->update(['kelas' => 'VII A']);
        $chapter = Chapter::create([
            'user_id' => $guru->id,
            'nomor_bab' => 6,
            'judul' => 'Bab VI',
            'is_published' => true,
        ]);

        $subchapters = collect(range(1, 4))->map(fn (int $number) => Subchapter::create([
            'chapter_id' => $chapter->id,
            'nomor_subbab' => $number,
            'judul' => "Subbab 6.{$number}",
            'konten' => 'Materi',
            'is_published' => true,
        ]));

        $this->actingAs($student)
            ->getJson('/siswa/progress')
            ->assertOk()
            ->assertJsonPath('completed_materials', 0)
            ->assertJsonPath('total_materials', 4)
            ->assertJsonPath('percentage', 0)
            ->assertJsonPath('status', 'Belum Dimulai')
            ->assertJsonPath('activities', []);

        $this->postJson("/siswa/subchapters/{$subchapters[0]->id}/open")
            ->assertOk()
            ->assertJsonPath('status', 'in_progress');

        $progress = $this->getJson('/siswa/progress');
        $progress->assertOk()
            ->assertJsonPath('percentage', 0)
            ->assertJsonPath('status', 'Sedang Dipelajari')
            ->assertJsonPath('activities.0.title', 'Mulai mempelajari materi');

        $questionOne = $this->createQuestion($subchapters[0], 1, 'A');
        $questionTwo = $this->createQuestion($subchapters[0], 2, 'B');

        $this->getJson("/siswa/quiz/subchapters/{$subchapters[0]->id}/questions")
            ->assertOk()
            ->assertJsonMissingPath('questions.0.kunci_jawaban')
            ->assertJsonCount(2, 'questions');

        $this->actingAs($otherStudent)
            ->getJson("/siswa/quiz/subchapters/{$subchapters[0]->id}/questions")
            ->assertOk()
            ->assertJsonCount(2, 'questions');
        $this->actingAs($otherStudent)
            ->postJson("/siswa/quiz/subchapters/{$subchapters[0]->id}/attempts")
            ->assertStatus(422)
            ->assertJsonPath('message', 'Buka dan pelajari materi Subbab ini sebelum mengerjakan Kuis.');
        $this->assertDatabaseMissing('student_quiz_answers', [
            'user_id' => $otherStudent->id,
            'quiz_question_id' => $questionOne->id,
        ]);
        $this->assertDatabaseMissing('student_subchapter_progress', [
            'user_id' => $otherStudent->id,
            'subchapter_id' => $subchapters[0]->id,
        ]);

        $this->actingAs($student);
        $attemptOne = $this->postJson("/siswa/quiz/subchapters/{$subchapters[0]->id}/attempts")
            ->assertCreated()
            ->json('attempt.id');
        $this->postJson("/siswa/quiz/questions/{$questionOne->id}/check", [
            'attempt_id' => $attemptOne,
            'jawaban' => 'C',
        ])
            ->assertOk()
            ->assertJsonPath('completed', false)
            ->assertJsonMissingPath('correct_answer')
            ->assertJsonMissingPath('explanation');
        $this->getJson("/siswa/quiz/subchapters/{$subchapters[0]->id}/questions")
            ->assertOk()
            ->assertJsonPath('active_attempt.id', $attemptOne)
            ->assertJsonPath('questions.0.student_answer.answer', 'C')
            ->assertJsonMissingPath('questions.0.student_answer.correct')
            ->assertJsonMissingPath('questions.0.kunci_jawaban');
        $this->assertDatabaseMissing('student_assessment_results', [
            'user_id' => $student->id,
            'assessment_type' => 'quiz',
        ]);

        $this->postJson("/siswa/quiz/questions/{$questionTwo->id}/check", [
            'attempt_id' => $attemptOne,
            'jawaban' => 'B',
        ])
            ->assertOk()
            ->assertJsonPath('completed', true)
            ->assertJsonPath('correct_answers', 1)
            ->assertJsonPath('total_questions', 2)
            ->assertJsonPath('score', 50)
            ->assertJsonPath('review.0.correct_answer', 'A');
        $this->getJson("/siswa/quiz/attempts/{$attemptOne}")
            ->assertOk()
            ->assertJsonPath('result.review.0.student_answer', 'C')
            ->assertJsonPath('result.review.0.correct_answer', 'A');

        $this->assertDatabaseMissing('student_quiz_answers', [
            'user_id' => $student->id,
            'quiz_question_id' => $questionOne->id,
        ]);
        $this->assertDatabaseHas('student_assessment_results', [
            'user_id' => $student->id,
            'assessment_type' => 'quiz',
            'chapter_id' => $chapter->id,
            'subchapter_id' => $subchapters[0]->id,
            'score' => 50,
            'total_questions' => 2,
            'correct_answers' => 1,
        ]);

        $this->getJson('/siswa/progress')
            ->assertOk()
            ->assertJsonPath('completed_materials', 1)
            ->assertJsonPath('percentage', 25)
            ->assertJsonPath('status', 'Sedang Dipelajari');

        $this->getJson("/siswa/quiz/subchapters/{$subchapters[0]->id}/questions")
            ->assertOk()
            ->assertJsonPath('active_attempt', null)
            ->assertJsonPath('latest_result.id', $attemptOne)
            ->assertJsonPath('latest_result.review.0.student_answer', 'C')
            ->assertJsonMissingPath('questions.0.student_answer.answer');

        $attemptTwo = $this->postJson("/siswa/quiz/subchapters/{$subchapters[0]->id}/attempts")
            ->assertCreated()
            ->json('attempt.id');
        $this->assertNotSame($attemptOne, $attemptTwo);
        $this->getJson("/siswa/quiz/subchapters/{$subchapters[0]->id}/questions")
            ->assertOk()
            ->assertJsonPath('active_attempt.id', $attemptTwo)
            ->assertJsonPath('questions.0.student_answer', null)
            ->assertJsonPath('questions.1.student_answer', null)
            ->assertJsonMissingPath('active_attempt.review');

        $this->postJson("/siswa/quiz/questions/{$questionOne->id}/check", [
            'attempt_id' => $attemptTwo,
            'jawaban' => 'A',
        ])->assertOk()->assertJsonPath('completed', false);
        $this->postJson("/siswa/quiz/questions/{$questionTwo->id}/check", [
            'attempt_id' => $attemptTwo,
            'jawaban' => 'B',
        ])
            ->assertOk()
            ->assertJsonPath('completed', true)
            ->assertJsonPath('score', 100);
        $this->getJson('/siswa/progress')
            ->assertOk()
            ->assertJsonPath('completed_quizzes', 1);

        $this->assertDatabaseCount('student_quiz_answers', 0);
        $this->assertDatabaseCount('student_assessment_results', 2);
        $this->assertDatabaseCount('student_quiz_attempts', 2);
        $this->assertDatabaseHas('student_quiz_attempts', [
            'id' => $attemptOne,
            'status' => 'completed',
            'score' => 50,
        ]);
        $this->assertDatabaseHas('student_quiz_attempts', [
            'id' => $attemptTwo,
            'status' => 'completed',
            'score' => 100,
        ]);
        $attemptThree = $this->postJson("/siswa/quiz/subchapters/{$subchapters[0]->id}/attempts")
            ->assertCreated()
            ->json('attempt.id');
        $this->getJson("/siswa/quiz/subchapters/{$subchapters[0]->id}/questions")
            ->assertOk()
            ->assertJsonPath('active_attempt.id', $attemptThree)
            ->assertJsonPath('questions.0.student_answer', null)
            ->assertJsonPath('latest_result.score', 100)
            ->assertJsonMissingPath('latest_result.review');
        $this->assertDatabaseCount('student_quiz_attempts', 3);
        $this->assertDatabaseCount('student_subchapter_progress', 1);

        $this->actingAs($otherStudent)
            ->getJson('/siswa/progress')
            ->assertOk()
            ->assertJsonPath('completed_materials', 0)
            ->assertJsonPath('percentage', 0)
            ->assertJsonPath('activities', []);

        $evaluationSnapshot = [];
        foreach (range(1, 15) as $number) {
            $questionId = DB::table('evaluation_questions')->insertGetId([
                'user_id' => $guru->id,
                'nomor_soal' => $number,
                'tipe_soal' => 'pilihan_ganda',
                'pertanyaan' => "Evaluasi {$number}",
                'konten_soal' => json_encode([
                    'pilihan_a' => 'A',
                    'pilihan_b' => 'B',
                    'pilihan_c' => 'C',
                    'pilihan_d' => 'D',
                ]),
                'kunci_jawaban' => json_encode(['jawaban' => 'A']),
                'pembahasan' => null,
                'is_published' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $evaluationSnapshot[] = [
                'id' => $questionId,
                'nomor_soal' => $number,
                'user_id' => $guru->id,
            ];
        }
        DB::table('evaluation_attempts')->insert([
            'user_id' => $student->id,
            'teacher_id' => $guru->id,
            'question_snapshot' => json_encode($evaluationSnapshot),
            'answers' => json_encode([]),
            'ends_at' => now(),
            'submitted_at' => now()->subMinute(),
            'score' => 80,
            'correct_count' => 12,
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
        ]);
        $this->actingAs($guru)
            ->getJson("/guru/student-grades/{$student->id}")
            ->assertOk()
            ->assertJsonPath('student.grading.quiz_score', 100)
            ->assertJsonPath('student.grading.evaluation_score', 80)
            ->assertJsonPath('student.grading.final_score', 90)
            ->assertJsonPath('results.0.score', 100)
            ->assertJsonPath('results.0.subchapter_id', $subchapters[0]->id)
            ->assertJsonPath('results.0.subchapter_name', 'Subbab 6.1');
        $parent = $this->createUser('orang_tua', 'orang-tua-progress-grade');
        DB::table('student_guardians')->insert([
            'student_id' => $student->id,
            'guardian_id' => $parent->id,
            'relationship' => 'orang_tua',
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $parentGrades = app(\App\Services\ParentDashboardService::class)
            ->dashboard($parent)['finalGrades'];
        $this->assertSame(100.0, $parentGrades[0]['quiz_score']);
        $this->assertSame(90.0, $parentGrades[0]['final_score']);
    }

    public function test_unquizzed_material_can_be_completed_once_from_its_material_page(): void
    {
        $guru = $this->createUser('guru', 'guru-no-quiz');
        $student = $this->createUser('siswa', 'siswa-no-quiz');
        $student->update(['kelas' => 'VII A']);
        DB::table('teacher_class_assignments')->insert([
            'teacher_id' => $guru->id,
            'class_name' => 'VII A',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $chapter = Chapter::create([
            'user_id' => $guru->id,
            'nomor_bab' => 6,
            'judul' => 'Bab VI',
            'is_published' => true,
        ]);

        $subchapter = Subchapter::create([
            'chapter_id' => $chapter->id,
            'nomor_subbab' => 1,
            'judul' => 'Materi tanpa kuis',
            'konten' => 'Materi',
            'is_published' => true,
        ]);

        $this->actingAs($student)
            ->postJson("/siswa/subchapters/{$subchapter->id}/open")
            ->assertOk();

        $this->postJson("/siswa/subchapters/{$subchapter->id}/complete")
            ->assertOk()
            ->assertJsonPath('status', 'completed');

        $this->postJson("/siswa/subchapters/{$subchapter->id}/complete")
            ->assertOk();

        $this->assertDatabaseCount('student_subchapter_progress', 1);
        $this->getJson('/siswa/progress')
            ->assertOk()
            ->assertJsonPath('completed_materials', 1)
            ->assertJsonPath('percentage', 100)
            ->assertJsonPath('status', 'Selesai');
    }

    private function createQuestion(Subchapter $subchapter, int $number, string $answer): QuizQuestion
    {
        return QuizQuestion::create([
            'subchapter_id' => $subchapter->id,
            'nomor_soal' => $number,
            'pertanyaan' => "Pertanyaan {$number}",
            'pilihan_a' => 'Pilihan A',
            'pilihan_b' => 'Pilihan B',
            'pilihan_c' => 'Pilihan C',
            'pilihan_d' => 'Pilihan D',
            'kunci_jawaban' => $answer,
            'pembahasan' => 'Pembahasan',
            'is_published' => true,
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
        ]);
    }
}
