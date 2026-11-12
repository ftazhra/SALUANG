<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ParentDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentAssessmentTeacherNotesTest extends TestCase
{
    use RefreshDatabase;

    public function test_notes_are_drafted_and_published_for_their_exact_assessment_result(): void
    {
        $teacher = $this->createUser('guru', 'guru-assessment-notes');
        $otherTeacher = $this->createUser('guru', 'guru-assessment-notes-other');
        $student = $this->createUser('siswa', 'siswa-assessment-notes');
        $otherStudent = $this->createUser('siswa', 'siswa-assessment-notes-other');
        $student->update(['kelas' => 'VII A']);
        $parent = $this->createUser('orang_tua', 'parent-assessment-notes');
        $this->link($student, $parent);

        [$chapter, $subchapter] = $this->createChapter($teacher);
        $quizResultId = $this->createQuizResult($student, $chapter, $subchapter);
        $evaluationResultId = $this->createEvaluationResult($student, $teacher);
        $otherStudentQuizResultId = $this->createQuizResult($otherStudent, $chapter, $subchapter);
        DB::table('teacher_class_assignments')
            ->where('teacher_id', $teacher->id)
            ->delete();

        $baseUrl = "/guru/student-grades/{$student->id}/feedback";

        $gradeDetail = $this->actingAs($teacher)
            ->getJson("/guru/student-grades/{$student->id}")
            ->assertOk();
        $this->assertSame(
            80.0,
            (float) collect($gradeDetail->json('results'))
                ->firstWhere('assessment_type', 'quiz')['score']
        );

        $this->actingAs($teacher)
            ->putJson($baseUrl, [
                'note' => 'Draf khusus kuis',
                'action' => 'draft',
                'assessment_type' => 'quiz',
                'assessment_result_id' => $quizResultId,
            ])
            ->assertOk()
            ->assertJsonPath('assessment_type', 'quiz')
            ->assertJsonPath('assessment_result_id', $quizResultId);

        $this->putJson($baseUrl, [
            'note' => 'Draf khusus evaluasi',
            'action' => 'draft',
            'assessment_type' => 'evaluation',
            'assessment_result_id' => $evaluationResultId,
        ])->assertOk();

        $this->getJson("{$baseUrl}?assessment_type=quiz&assessment_result_id={$quizResultId}")
            ->assertOk()
            ->assertJsonPath('note', 'Draf khusus kuis');
        $this->getJson("{$baseUrl}?assessment_type=evaluation&assessment_result_id={$evaluationResultId}")
            ->assertOk()
            ->assertJsonPath('note', 'Draf khusus evaluasi');

        $this->putJson($baseUrl, [
            'note' => 'Catatan kuis pertama',
            'action' => 'publish',
            'assessment_type' => 'quiz',
            'assessment_result_id' => $quizResultId,
        ])->assertOk()->assertJsonPath('note', '');

        $this->assertDatabaseHas('student_teacher_notes', [
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'assessment_type' => 'evaluation',
            'assessment_result_id' => $evaluationResultId,
            'note' => 'Draf khusus evaluasi',
        ]);

        $this->putJson($baseUrl, [
            'note' => 'Catatan kuis kedua',
            'action' => 'publish',
            'assessment_type' => 'quiz',
            'assessment_result_id' => $quizResultId,
        ])->assertOk();

        $this->putJson($baseUrl, [
            'note' => 'Catatan evaluasi',
            'action' => 'publish',
            'assessment_type' => 'evaluation',
            'assessment_result_id' => $evaluationResultId,
        ])->assertOk();

        $this->putJson($baseUrl, [
            'note' => 'Draf belum terkirim',
            'action' => 'draft',
            'assessment_type' => 'quiz',
            'assessment_result_id' => $quizResultId,
        ])->assertOk();

        $this->actingAs($otherTeacher)
            ->putJson($baseUrl, [
                'note' => 'Tidak berwenang',
                'action' => 'publish',
                'assessment_type' => 'quiz',
                'assessment_result_id' => $quizResultId,
            ])
            ->assertNotFound();

        $this->actingAs($teacher)
            ->putJson($baseUrl, [
                'note' => 'Siswa tidak sesuai',
                'action' => 'publish',
                'assessment_type' => 'quiz',
                'assessment_result_id' => $otherStudentQuizResultId,
            ])
            ->assertNotFound();

        DB::table('student_teacher_note_messages')->insert([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'audience' => 'orang_tua',
            'note' => 'Catatan umum lama',
            'published_at' => now()->subDay(),
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $this->assertDatabaseCount('student_teacher_note_messages', 4);
        $dashboard = app(ParentDashboardService::class)->dashboard($parent);
        $this->assertCount(2, $dashboard['teacherNotes']['assessments']);
        $this->assertCount(1, $dashboard['teacherNotes']['general']);

        $quizNotes = collect($dashboard['teacherNotes']['assessments'])
            ->firstWhere('assessment_type', 'quiz');
        $evaluationNotes = collect($dashboard['teacherNotes']['assessments'])
            ->firstWhere('assessment_type', 'evaluation');

        $this->assertSame('Bab VI', $quizNotes['chapter_title']);
        $this->assertSame('Subbab Perubahan', $quizNotes['subchapter_title']);
        $this->assertSame(80.0, $quizNotes['score']);
        $this->assertSame(
            ['Catatan kuis kedua', 'Catatan kuis pertama'],
            array_column($quizNotes['notes'], 'body')
        );
        $this->assertNull($evaluationNotes['chapter_title']);
        $this->assertSame($teacher->name, $evaluationNotes['teacher_name']);
        $this->assertSame(
            ['Catatan evaluasi'],
            array_column($evaluationNotes['notes'], 'body')
        );
        $this->assertSame('Catatan umum lama', $dashboard['teacherNotes']['general'][0]['body']);
        $this->assertStringNotContainsString(
            'Draf belum terkirim',
            json_encode($dashboard['teacherNotes'])
        );

        $this->assertDatabaseHas('student_teacher_note_messages', [
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'assessment_type' => 'quiz',
            'assessment_result_id' => $quizResultId,
            'note' => 'Catatan kuis pertama',
        ]);
        $this->assertDatabaseHas('student_teacher_note_messages', [
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'assessment_type' => 'evaluation',
            'assessment_result_id' => $evaluationResultId,
            'note' => 'Catatan evaluasi',
        ]);
    }

    public function test_parent_note_cannot_be_published_without_approved_guardian_and_saved_draft_is_preserved(): void
    {
        $teacher = $this->createUser('guru', 'guru-notes-no-parent');
        $student = $this->createUser('siswa', 'student-notes-no-parent');
        [$chapter, $subchapter] = $this->createChapter($teacher);
        $quizResultId = $this->createQuizResult($student, $chapter, $subchapter);
        $url = "/guru/student-grades/{$student->id}/feedback";

        $this->actingAs($teacher)
            ->putJson($url, [
                'note' => 'Draf untuk Orang Tua',
                'action' => 'draft',
                'audience' => 'orang_tua',
                'assessment_type' => 'quiz',
                'assessment_result_id' => $quizResultId,
            ])
            ->assertOk();

        $this->putJson($url, [
            'note' => 'Draf untuk Orang Tua',
            'action' => 'publish',
            'audience' => 'orang_tua',
            'assessment_type' => 'quiz',
            'assessment_result_id' => $quizResultId,
        ])
            ->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'Belum ada akun Orang Tua terhubung dan disetujui. Draf tetap tersimpan; hubungkan Orang Tua sebelum mengirim catatan.'
            );

        $this->assertDatabaseHas('student_teacher_notes', [
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'audience' => 'orang_tua',
            'assessment_type' => 'quiz',
            'assessment_result_id' => $quizResultId,
            'note' => 'Draf untuk Orang Tua',
            'published_at' => null,
        ]);
        $this->assertDatabaseCount('student_teacher_note_messages', 0);
    }

    private function createChapter(User $teacher): array
    {
        DB::table('teacher_class_assignments')->insert([
            'teacher_id' => $teacher->id,
            'class_name' => 'VII A',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $chapterId = DB::table('chapters')->insertGetId([
            'user_id' => $teacher->id,
            'nomor_bab' => 6,
            'judul' => 'Bab VI',
            'is_published' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $subchapterId = DB::table('subchapters')->insertGetId([
            'chapter_id' => $chapterId,
            'nomor_subbab' => 1,
            'judul' => 'Subbab Perubahan',
            'is_published' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [(object) ['id' => $chapterId], (object) ['id' => $subchapterId]];
    }

    private function createQuizResult(User $student, object $chapter, object $subchapter): int
    {
        return DB::table('student_assessment_results')->insertGetId([
            'user_id' => $student->id,
            'assessment_type' => 'quiz',
            'chapter_id' => $chapter->id,
            'subchapter_id' => $subchapter->id,
            'total_questions' => 10,
            'correct_answers' => 8,
            'score' => 80,
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createEvaluationResult(User $student, User $teacher): int
    {
        $snapshot = array_map(
            fn (int $number) => [
                'id' => $number,
                'user_id' => $teacher->id,
                'nomor_soal' => $number,
            ],
            range(1, 15)
        );

        return DB::table('evaluation_attempts')->insertGetId([
            'user_id' => $student->id,
            'teacher_id' => $teacher->id,
            'question_snapshot' => json_encode($snapshot),
            'answers' => json_encode([]),
            'ends_at' => now(),
            'submitted_at' => now(),
            'score' => 87,
            'correct_count' => 13,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function link(User $student, User $parent): void
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
