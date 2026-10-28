<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\EvaluationQuestion;
use App\Models\Subchapter;
use App\Models\User;
use App\Services\ParentDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EvaluationResultsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_attempts_are_shared_with_the_authorized_teacher_and_approved_parent_without_duplicate_results(): void
    {
        $teacher = $this->createUser('guru', 'guru-evaluation-owner');
        $otherTeacher = $this->createUser('guru', 'guru-evaluation-other');
        $student = $this->createUser('siswa', 'siswa-evaluation');
        $parent = $this->createUser('orang_tua', 'parent-evaluation');
        $unrelatedParent = $this->createUser('orang_tua', 'parent-unrelated-evaluation');
        $pendingParent = $this->createUser('orang_tua', 'parent-pending-evaluation');

        $chapter = Chapter::create([
            'user_id' => $teacher->id,
            'nomor_bab' => 6,
            'judul' => 'Bab VI',
            'is_published' => true,
        ]);
        Subchapter::create([
            'chapter_id' => $chapter->id,
            'nomor_subbab' => 1,
            'judul' => 'Subbab Evaluasi',
            'is_published' => true,
        ]);
        $this->createEvaluationBank($teacher);
        $this->link($student, $parent, 'approved');
        $this->link($student, $pendingParent, 'pending');

        $firstStart = $this->actingAs($student)
            ->postJson('/siswa/evaluations/start')
            ->assertOk();
        $firstAttemptId = $firstStart->json('attempt_id');
        $this->putJson('/siswa/evaluations/answers', [
            'answers' => $this->answers(false),
        ])->assertOk();
        $this->postJson('/siswa/evaluations/finish')
            ->assertOk()
            ->assertJsonPath('score', 0);

        $secondStart = $this->postJson('/siswa/evaluations/start')->assertOk();
        $secondAttemptId = $secondStart->json('attempt_id');
        $this->putJson('/siswa/evaluations/answers', [
            'answers' => $this->answers(true),
        ])->assertOk();
        $this->postJson('/siswa/evaluations/finish')
            ->assertOk()
            ->assertJsonPath('score', 100);
        $this->postJson('/siswa/evaluations/finish')
            ->assertOk()
            ->assertJsonPath('score', 100);

        $this->assertNotSame($firstAttemptId, $secondAttemptId);
        $this->assertDatabaseCount('evaluation_attempts', 2);
        $this->assertDatabaseCount('student_assessment_results', 0);

        $studentState = $this->getJson('/siswa/evaluations/state')->assertOk();
        $this->assertSame(100.0, (float) $studentState->json('attempt.score'));

        DB::table('student_assessment_results')->insert([
            'user_id' => $student->id,
            'assessment_type' => 'evaluation',
            'chapter_id' => $chapter->id,
            'subchapter_id' => null,
            'total_questions' => 15,
            'correct_answers' => 15,
            'score' => 100,
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $teacherIndex = $this->actingAs($teacher)
            ->getJson('/guru/student-grades')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $student->id)
            ->assertJsonPath('data.0.evaluation_score', 100);
        $this->assertSame($student->id, $teacherIndex->json('data.0.id'));

        $teacherDetail = $this->getJson("/guru/student-grades/{$student->id}")->assertOk();
        $teacherScores = collect($teacherDetail->json('results'))
            ->where('assessment_type', 'evaluation')
            ->pluck('score')
            ->map(fn ($score) => (float) $score)
            ->sort()
            ->values()
            ->all();
        $this->assertSame([0.0, 100.0], $teacherScores);
        $this->assertSame([$chapter->id, $chapter->id], collect($teacherDetail->json('results'))
            ->where('assessment_type', 'evaluation')
            ->pluck('chapter_id')
            ->sort()
            ->values()
            ->all());

        $excel = $this->get('/guru/student-grades/export/excel');
        $excel->assertOk();
        $this->assertStringStartsWith('PK', $excel->streamedContent());
        $pdf = $this->get('/guru/student-grades/export/pdf');
        $pdf->assertOk();
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());

        $this->actingAs($otherTeacher)
            ->getJson("/guru/student-grades/{$student->id}")
            ->assertNotFound();
        $this->actingAs($otherTeacher)
            ->getJson('/guru/student-grades')
            ->assertOk()
            ->assertJsonPath('total', 0);

        $parentDashboard = app(ParentDashboardService::class)->dashboard($parent);
        $this->assertSame($student->id, $parentDashboard['child']->id);
        $this->assertCount(2, $parentDashboard['learningResults']);
        $this->assertSame(50.0, (float) $parentDashboard['stats']['average']);
        $parentScores = collect($parentDashboard['learningResults'])
            ->pluck('score')
            ->map(fn ($score) => (float) $score)
            ->sort()
            ->values()
            ->all();
        $this->assertSame([0.0, 100.0], $parentScores);

        $this->assertNull(app(ParentDashboardService::class)->dashboard($unrelatedParent)['child']);
        $pendingDashboard = app(ParentDashboardService::class)->dashboard($pendingParent);
        $this->assertNull($pendingDashboard['child']);
        $this->assertSame([], $pendingDashboard['learningResults']);

        $this->assertSame(1, DB::table('evaluation_attempts')
            ->where('id', $secondAttemptId)
            ->where('user_id', $student->id)
            ->where('score', 100)
            ->count());
    }

    private function createEvaluationBank(User $teacher): void
    {
        foreach (range(1, 15) as $number) {
            $type = $number <= 5
                ? 'pilihan_ganda'
                : ($number <= 10 ? 'drag_drop' : 'menghubungkan');
            if ($type === 'pilihan_ganda') {
                $content = [
                    'pilihan_a' => 'A',
                    'pilihan_b' => 'B',
                    'pilihan_c' => 'C',
                    'pilihan_d' => 'D',
                ];
                $key = ['jawaban' => 'A'];
            } else {
                $content = [
                    'items' => [['id' => "item-{$number}", 'label' => "Gambar {$number}"]],
                    'targets' => [
                        ['id' => "target-{$number}", 'label' => 'Benar'],
                        ['id' => "wrong-{$number}", 'label' => 'Lainnya'],
                    ],
                ];
                $key = ['pairs' => ["item-{$number}" => "target-{$number}"]];
            }

            EvaluationQuestion::create([
                'user_id' => $teacher->id,
                'nomor_soal' => $number,
                'tipe_soal' => $type,
                'pertanyaan' => "Pertanyaan {$number}",
                'konten_soal' => $content,
                'kunci_jawaban' => $key,
                'is_published' => true,
            ]);
        }
    }

    private function answers(bool $correct): array
    {
        $answers = [];
        foreach (range(1, 15) as $number) {
            if ($number <= 5) {
                $answers[(string) $number] = $correct ? 'A' : 'B';
            } else {
                $answers[(string) $number] = [
                    "item-{$number}" => $correct ? "target-{$number}" : "wrong-{$number}",
                ];
            }
        }
        return $answers;
    }

    private function link(User $student, User $parent, string $status): void
    {
        DB::table('student_guardians')->insert([
            'student_id' => $student->id,
            'guardian_id' => $parent->id,
            'relationship' => 'orang_tua',
            'status' => $status,
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