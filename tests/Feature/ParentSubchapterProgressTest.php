<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Subchapter;
use App\Models\User;
use App\Services\ParentDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ParentSubchapterProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_subchapter_progress_counts_material_and_valid_quiz_as_separate_activities(): void
    {
        $teacher = $this->createUser('guru', 'guru-parent-progress');
        $student = $this->createUser('siswa', 'siswa-parent-progress');
        $parent = $this->createUser('orang_tua', 'parent-progress');
        $chapter = Chapter::create([
            'user_id' => $teacher->id,
            'nomor_bab' => 6,
            'judul' => 'Bab VI',
            'is_published' => true,
        ]);
        $materialSubchapter = $this->createSubchapter($chapter, 1, 'Materi selesai');
        $quizSubchapter = $this->createSubchapter($chapter, 2, 'Kuis selesai');

        DB::table('student_guardians')->insert([
            'student_id' => $student->id,
            'guardian_id' => $parent->id,
            'relationship' => 'orang_tua',
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('student_subchapter_progress')->insert([
            [
                'user_id' => $student->id,
                'subchapter_id' => $materialSubchapter->id,
                'status' => 'completed',
                'opened_at' => now()->subHour(),
                'completed_at' => now(),
                'created_at' => now()->subHour(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $student->id,
                'subchapter_id' => $quizSubchapter->id,
                'status' => 'in_progress',
                'opened_at' => now()->subHour(),
                'completed_at' => null,
                'created_at' => now()->subHour(),
                'updated_at' => now(),
            ],
        ]);
        DB::table('student_assessment_results')->insert([
            'user_id' => $student->id,
            'assessment_type' => 'quiz',
            'chapter_id' => $chapter->id,
            'subchapter_id' => $quizSubchapter->id,
            'total_questions' => 5,
            'correct_answers' => 4,
            'score' => 80,
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $dashboard = app(ParentDashboardService::class)->dashboard($parent);
        $chapterProgress = $dashboard['chapters'][0];

        $this->assertSame(3, $chapterProgress['finished']);
        $this->assertSame(4, $chapterProgress['total']);
        $this->assertSame(75, $chapterProgress['percentage']);
        $this->assertSame(2, $chapterProgress['material_finished']);
        $this->assertSame(1, $chapterProgress['quiz_finished']);
        $this->assertTrue($chapterProgress['subchapters'][0]['done']);
        $this->assertFalse($chapterProgress['subchapters'][0]['quiz_done']);
        $this->assertTrue($chapterProgress['subchapters'][1]['done']);
        $this->assertFalse($chapterProgress['subchapters'][1]['material_recorded_done']);
        $this->assertTrue($chapterProgress['subchapters'][1]['quiz_done']);
        $quizSubchapterActivity = collect($dashboard['activityHistory'])
            ->firstWhere('subchapter_id', $quizSubchapter->id);
        $this->assertSame('Membuka materi', $quizSubchapterActivity['title']);
        $this->assertSame(2, $dashboard['stats']['materials']);
        $this->assertSame(1, $dashboard['stats']['quizzes']);
        $this->assertSame(4, $dashboard['stats']['total_activities']);
        $this->assertSame(75, $dashboard['stats']['completion']);
        $this->assertDatabaseHas('student_subchapter_progress', [
            'user_id' => $student->id,
            'subchapter_id' => $quizSubchapter->id,
            'status' => 'in_progress',
            'completed_at' => null,
        ]);
    }

    private function createSubchapter(Chapter $chapter, int $number, string $title): Subchapter
    {
        return Subchapter::create([
            'chapter_id' => $chapter->id,
            'nomor_subbab' => $number,
            'judul' => $title,
            'konten' => 'Materi',
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
