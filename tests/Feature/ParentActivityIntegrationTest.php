<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Discussion;
use App\Models\DiscussionReply;
use App\Models\QuizQuestion;
use App\Models\StudentGuardian;
use App\Models\StudentQuizAnswer;
use App\Models\StudentSubchapterProgress;
use App\Models\Subchapter;
use App\Models\User;
use App\Services\ParentDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ParentActivityIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_parent_activity_feed_combines_real_sources_and_maps_filter_context(): void
    {
        $teacher = $this->createUser('guru', 'activity-guru');
        $student = $this->createUser('siswa', 'activity-student');
        $otherStudent = $this->createUser('siswa', 'activity-other-student');
        $parent = $this->createUser('orang_tua', 'activity-parent');
        $chapter = Chapter::create([
            'user_id' => $teacher->id,
            'nomor_bab' => 6,
            'judul' => 'Bab VI Ekologi',
            'is_published' => true,
        ]);
        $subchapter = Subchapter::create([
            'chapter_id' => $chapter->id,
            'nomor_subbab' => 1,
            'judul' => 'Hubungan Ekosistem',
            'is_published' => true,
        ]);
        $this->link($student, $parent, 'approved');

        $openedAt = now()->subMinutes(50);
        $completedAt = now()->subMinutes(40);
        StudentSubchapterProgress::create([
            'user_id' => $student->id,
            'subchapter_id' => $subchapter->id,
            'status' => 'completed',
            'opened_at' => $openedAt,
            'completed_at' => $completedAt,
            'created_at' => $openedAt,
            'updated_at' => $completedAt,
        ]);

        $question = QuizQuestion::create([
            'subchapter_id' => $subchapter->id,
            'nomor_soal' => 1,
            'pertanyaan' => 'Pertanyaan aktivitas kuis',
            'pilihan_a' => 'A',
            'pilihan_b' => 'B',
            'pilihan_c' => 'C',
            'pilihan_d' => 'D',
            'kunci_jawaban' => 'A',
            'is_published' => true,
        ]);
        $quizAnswer = StudentQuizAnswer::create([
            'user_id' => $student->id,
            'subchapter_id' => $subchapter->id,
            'quiz_question_id' => $question->id,
            'answer' => 'A',
            'is_correct' => true,
        ]);
        DB::table('student_quiz_answers')->where('id', $quizAnswer->id)->update([
            'created_at' => now()->subMinutes(30),
            'updated_at' => now()->subMinutes(30),
        ]);

        $snapshot = collect(range(1, 15))->map(fn (int $number) => [
            'id' => $number,
            'user_id' => $teacher->id,
            'nomor_soal' => $number,
            'tipe_soal' => 'pilihan_ganda',
        ])->all();
        foreach ([
            ['score' => 40, 'correct_count' => 6, 'minutes_ago' => 25],
            ['score' => 80, 'correct_count' => 12, 'minutes_ago' => 20],
        ] as $attempt) {
            $attemptAt = now()->subMinutes($attempt['minutes_ago']);
            DB::table('evaluation_attempts')->insert([
                'user_id' => $student->id,
                'question_snapshot' => json_encode($snapshot),
                'answers' => json_encode([]),
                'ends_at' => $attemptAt,
                'submitted_at' => $attemptAt,
                'score' => $attempt['score'],
                'correct_count' => $attempt['correct_count'],
                'created_at' => $attemptAt->copy()->subMinutes(45),
                'updated_at' => $attemptAt,
            ]);
        }
        DB::table('student_assessment_results')->insert([
            'user_id' => $student->id,
            'assessment_type' => 'evaluation',
            'chapter_id' => $chapter->id,
            'subchapter_id' => null,
            'total_questions' => 15,
            'correct_answers' => 12,
            'score' => 80,
            'completed_at' => now()->subMinutes(20),
            'created_at' => now()->subMinutes(20),
            'updated_at' => now()->subMinutes(20),
        ]);

        $discussion = Discussion::create([
            'user_id' => $student->id,
            'chapter' => 'bab-6',
            'subchapter' => '6.1',
            'title' => 'Diskusi siswa terkait ekosistem',
            'body' => 'Pertanyaan yang dibuat oleh siswa.',
        ]);
        DB::table('discussions')->where('id', $discussion->id)->update([
            'created_at' => now()->subMinutes(10),
            'updated_at' => now()->subMinutes(10),
        ]);
        $studentReply = DiscussionReply::create([
            'discussion_id' => $discussion->id,
            'user_id' => $student->id,
            'body' => 'Balasan siswa pada forum.',
        ]);
        DiscussionReply::create([
            'discussion_id' => $discussion->id,
            'user_id' => $teacher->id,
            'body' => 'Balasan Guru bukan aktivitas anak.',
        ]);
        DB::table('discussion_replies')->where('id', $studentReply->id)->update([
            'created_at' => now()->subMinutes(5),
            'updated_at' => now()->subMinutes(5),
        ]);
        Discussion::create([
            'user_id' => $otherStudent->id,
            'chapter' => 'bab-6',
            'subchapter' => '6.1',
            'title' => 'Diskusi milik siswa lain',
            'body' => 'Tidak boleh muncul pada feed anak ini.',
        ]);
        DB::table('parent_teacher_messages')->insert([
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'sender_id' => $parent->id,
            'client_message_id' => 'd2a80123-4567-4abc-8def-0123456789ab',
            'body' => 'Chat bukan aktivitas forum.',
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
        ]);

        $dashboard = app(ParentDashboardService::class)->dashboard($parent);
        $events = collect($dashboard['activityHistory']);
        $this->assertSame($student->id, $dashboard['child']->id);
        $this->assertCount(6, $events);
        $this->assertSame(
            ['diskusi', 'diskusi', 'evaluasi', 'evaluasi', 'kuis', 'materi'],
            $events->pluck('type')->all()
        );
        $this->assertSame($chapter->id, $events->firstWhere('type', 'evaluasi')['chapter_id']);
        $this->assertNull($events->firstWhere('type', 'evaluasi')['subchapter_id']);
        $this->assertSame($subchapter->id, $events->firstWhere('type', 'materi')['subchapter_id']);
        $this->assertSame($subchapter->id, $events->firstWhere('type', 'kuis')['subchapter_id']);
        $this->assertSame($subchapter->id, $events->firstWhere('type', 'diskusi')['subchapter_id']);
        $this->assertSame('Diskusi siswa terkait ekosistem', $events->firstWhere('title', 'Membuat diskusi')['detail']);
        $this->assertSame('Menyelesaikan evaluasi', $events->firstWhere('type', 'evaluasi')['title']);
        $this->assertCount(2, $events->where('type', 'evaluasi'));
        $this->assertSame(
            $events->pluck('date')->map(fn ($date) => (string) $date)->sortDesc()->values()->all(),
            $events->pluck('date')->map(fn ($date) => (string) $date)->values()->all()
        );
        $this->assertStringNotContainsString('Chat bukan aktivitas forum', json_encode($dashboard['activityHistory']));
        $this->assertStringNotContainsString('Diskusi milik siswa lain', json_encode($dashboard['activityHistory']));

        $chapterEvents = $events->filter(fn (array $event) => $event['chapter_id'] === $chapter->id);
        $subchapterEvents = $events->filter(fn (array $event) => $event['subchapter_id'] === $subchapter->id);
        $this->assertCount(6, $chapterEvents);
        $this->assertCount(4, $subchapterEvents);
        $this->assertSame(80.0, (float) $events->firstWhere('type', 'evaluasi')['score']);

        $this->actingAs($parent)
            ->get('/dashboard?student_id=' . $otherStudent->id)
            ->assertOk()
            ->assertSee('Diskusi siswa terkait ekosistem')
            ->assertDontSee('Diskusi milik siswa lain');
    }

    public function test_pending_or_unrelated_parent_does_not_receive_a_child_activity_feed(): void
    {
        $teacher = $this->createUser('guru', 'activity-approval-guru');
        $student = $this->createUser('siswa', 'activity-approval-student');
        $parent = $this->createUser('orang_tua', 'activity-pending-parent');
        $unrelatedParent = $this->createUser('orang_tua', 'activity-unrelated-parent');
        $chapter = Chapter::create([
            'user_id' => $teacher->id,
            'nomor_bab' => 6,
            'judul' => 'Bab VI',
            'is_published' => true,
        ]);
        $subchapter = $chapter->subchapters()->create([
            'nomor_subbab' => 1,
            'judul' => 'Subbab',
            'is_published' => true,
        ]);
        StudentSubchapterProgress::create([
            'user_id' => $student->id,
            'subchapter_id' => $subchapter->id,
            'status' => 'completed',
            'completed_at' => now(),
        ]);
        $this->link($student, $parent, 'pending');

        $pendingDashboard = app(ParentDashboardService::class)->dashboard($parent);
        $unrelatedDashboard = app(ParentDashboardService::class)->dashboard($unrelatedParent);
        $this->assertNull($pendingDashboard['child']);
        $this->assertSame([], $pendingDashboard['activityHistory']);
        $this->assertNull($unrelatedDashboard['child']);
        $this->assertSame([], $unrelatedDashboard['activityHistory']);

        $this->actingAs($parent)
            ->get('/dashboard?student_id=' . $student->id)
            ->assertOk()
            ->assertDontSee('Aktivitas belajar pada materi');
    }

    public function test_approved_parent_with_no_activity_keeps_the_existing_empty_state(): void
    {
        $student = $this->createUser('siswa', 'activity-empty-student');
        $parent = $this->createUser('orang_tua', 'activity-empty-parent');
        $this->link($student, $parent, 'approved');

        $this->actingAs($parent)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Belum ada aktivitas belajar yang tercatat.')
            ->assertSee('Belum ada aktivitas sesuai filter.');
    }

    private function link(User $student, User $parent, string $status): StudentGuardian
    {
        return StudentGuardian::query()->create([
            'student_id' => $student->id,
            'guardian_id' => $parent->id,
            'relationship' => 'orang_tua',
            'status' => $status,
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
