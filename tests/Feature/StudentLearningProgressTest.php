<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\QuizQuestion;
use App\Models\Subchapter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentLearningProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_material_and_subchapter_quiz_update_only_the_current_students_progress_and_grade(): void
    {
        $guru = $this->createUser('guru', 'guru-progress');
        $student = $this->createUser('siswa', 'siswa-progress');
        $otherStudent = $this->createUser('siswa', 'siswa-progress-lain');
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
            ->postJson("/siswa/quiz/questions/{$questionOne->id}/check", ['jawaban' => 'A'])
            ->assertStatus(422);
        $this->assertDatabaseMissing('student_quiz_answers', [
            'user_id' => $otherStudent->id,
            'quiz_question_id' => $questionOne->id,
        ]);
        $this->assertDatabaseMissing('student_subchapter_progress', [
            'user_id' => $otherStudent->id,
            'subchapter_id' => $subchapters[0]->id,
        ]);

        $this->actingAs($student);
        $this->postJson("/siswa/quiz/questions/{$questionOne->id}/check", ['jawaban' => 'C'])
            ->assertOk()
            ->assertJsonPath('completed', false);
        $this->assertDatabaseMissing('student_assessment_results', [
            'user_id' => $student->id,
            'assessment_type' => 'quiz',
        ]);

        $this->postJson("/siswa/quiz/questions/{$questionTwo->id}/check", ['jawaban' => 'B'])
            ->assertOk()
            ->assertJsonPath('completed', true)
            ->assertJsonPath('correct_answers', 1)
            ->assertJsonPath('total_questions', 2)
            ->assertJsonPath('score', 50);

        $this->assertDatabaseHas('student_quiz_answers', [
            'user_id' => $student->id,
            'quiz_question_id' => $questionOne->id,
            'answer' => 'C',
            'is_correct' => false,
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
            ->assertJsonPath('questions.0.student_answer.answer', 'C')
            ->assertJsonPath('questions.0.student_answer.correct', false);

        $this->postJson("/siswa/quiz/questions/{$questionOne->id}/check", ['jawaban' => 'A'])
            ->assertOk()
            ->assertJsonPath('completed', true)
            ->assertJsonPath('score', 100);

        $this->assertDatabaseCount('student_quiz_answers', 2);
        $this->assertDatabaseCount('student_assessment_results', 1);
        $this->assertDatabaseCount('student_subchapter_progress', 1);

        $this->actingAs($otherStudent)
            ->getJson('/siswa/progress')
            ->assertOk()
            ->assertJsonPath('completed_materials', 0)
            ->assertJsonPath('percentage', 0)
            ->assertJsonPath('activities', []);

        $this->actingAs($guru)
            ->getJson("/guru/student-grades/{$student->id}")
            ->assertOk()
            ->assertJsonPath('results.0.score', 100)
            ->assertJsonPath('results.0.subchapter_id', $subchapters[0]->id)
            ->assertJsonPath('results.0.subchapter_name', 'Subbab 6.1');
    }

    public function test_unquizzed_material_can_be_completed_once_from_its_material_page(): void
    {
        $guru = $this->createUser('guru', 'guru-no-quiz');
        $student = $this->createUser('siswa', 'siswa-no-quiz');
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
