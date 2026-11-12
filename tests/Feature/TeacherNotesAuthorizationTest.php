<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ParentDashboardService;
use App\Services\WebsiteNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Tests\TestCase;

class TeacherNotesAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_notes_are_isolated_by_audience_and_approved_parent_child_link(): void
    {
        $teacher = $this->createUser('guru', 'guru-notes');
        $student = $this->createUser('siswa', 'siswa-notes');
        $parent = $this->createUser('orang_tua', 'parent-notes');
        $otherStudent = $this->createUser('siswa', 'siswa-notes-other');
        $otherParent = $this->createUser('orang_tua', 'parent-notes-other');

        $this->link($student, $parent, 'approved');
        $this->link($otherStudent, $otherParent, 'approved');

        $this->actingAs($teacher)
            ->putJson("/guru/student-grades/{$student->id}/feedback", [
                'note' => 'Catatan untuk Orang Tua',
                'action' => 'publish',
            ])
            ->assertOk();

        $this->assertDatabaseHas('student_teacher_note_messages', [
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'audience' => 'orang_tua',
            'note' => 'Catatan untuk Orang Tua',
        ]);

        $this->actingAs($teacher)
            ->putJson("/guru/student-grades/{$student->id}/feedback", [
                'note' => 'Catatan untuk Siswa',
                'audience' => 'siswa',
                'action' => 'publish',
            ])
            ->assertOk();

        DB::commit();

        $studentNoteId = DB::table('student_teacher_note_messages')
            ->where('student_id', $student->id)
            ->where('audience', 'siswa')
            ->value('id');
        $parentNoteId = DB::table('student_teacher_note_messages')
            ->where('student_id', $student->id)
            ->where('audience', 'orang_tua')
            ->value('id');
        app(WebsiteNotificationService::class)->publishedTeacherNote(
            (int) $student->id,
            (int) $studentNoteId
        );
        app(WebsiteNotificationService::class)->publishedTeacherNote(
            (int) $student->id,
            (int) $parentNoteId
        );
        $studentNotification = DB::table('website_notifications')
            ->where('recipient_id', $student->id)
            ->where('event_type', 'teacher_note')
            ->where('source_id', $studentNoteId)
            ->first();
        $parentNotification = DB::table('website_notifications')
            ->where('recipient_id', $parent->id)
            ->where('event_type', 'teacher_note')
            ->where('source_id', $parentNoteId)
            ->first();

        $this->assertNotNull($studentNotification);
        $this->assertNotNull($parentNotification);
        $this->assertSame('siswa', $studentNotification->recipient_role);
        $this->assertSame('orang_tua', $parentNotification->recipient_role);
        $this->assertSame('/dashboard', $studentNotification->target_url);
        $this->assertSame(1, DB::table('website_notifications')
            ->where('recipient_id', $student->id)
            ->where('source_id', $studentNoteId)
            ->count());
        $this->assertSame(1, DB::table('website_notifications')
            ->where('recipient_id', $parent->id)
            ->where('source_id', $parentNoteId)
            ->count());
        $this->assertSame(0, DB::table('website_notifications')
            ->where('recipient_id', $parent->id)
            ->where('source_id', $studentNoteId)
            ->count());

        $this->actingAs($student)
            ->getJson('/siswa/teacher-notes')
            ->assertOk()
            ->assertJsonCount(1, 'notes')
            ->assertJsonPath('notes.0.note', 'Catatan untuk Siswa');

        $this->actingAs($student)
            ->get("/notifications/{$studentNotification->id}/open")
            ->assertRedirect('/dashboard');
        $this->actingAs($parent)
            ->get("/notifications/{$parentNotification->id}/open")
            ->assertRedirect("/dashboard?page=hasil&child_id={$student->id}");
        $this->assertNotNull(DB::table('website_notifications')
            ->where('id', $studentNotification->id)
            ->value('read_at'));
        $this->assertNotNull(DB::table('website_notifications')
            ->where('id', $parentNotification->id)
            ->value('read_at'));

        $this->actingAs($parent)
            ->get("/notifications/{$studentNotification->id}/open")
            ->assertNotFound();

        $parentDashboard = app(ParentDashboardService::class)->dashboard($parent);
        $this->assertSame($student->id, $parentDashboard['child']->id);
        $this->assertCount(1, $parentDashboard['teacherNotes']['general']);
        $this->assertSame(
            'Catatan untuk Orang Tua',
            $parentDashboard['teacherNotes']['general'][0]['body']
        );

        $otherParentDashboard = app(ParentDashboardService::class)->dashboard($otherParent);
        $this->assertSame($otherStudent->id, $otherParentDashboard['child']->id);
        $this->assertSame([], $otherParentDashboard['teacherNotes']['general']);

        $unapprovedParent = $this->createUser('orang_tua', 'parent-notes-pending');
        $this->link($student, $unapprovedParent, 'pending');
        $unapprovedDashboard = app(ParentDashboardService::class)->dashboard($unapprovedParent);
        $this->assertNull($unapprovedDashboard['child']);
        $this->assertSame([], $unapprovedDashboard['teacherNotes']['general']);
    }

    public function test_only_guru_can_manage_notes_and_a_guru_reads_only_their_own_note(): void
    {
        $teacher = $this->createUser('guru', 'guru-notes-owner');
        $otherTeacher = $this->createUser('guru', 'guru-notes-other');
        $student = $this->createUser('siswa', 'siswa-notes-access');
        $parent = $this->createUser('orang_tua', 'parent-notes-access');

        $this->actingAs($teacher)
            ->putJson("/guru/student-grades/{$student->id}/feedback", [
                'note' => 'Catatan privat',
            ])
            ->assertOk();

        $this->actingAs($student)
            ->getJson("/guru/student-grades/{$student->id}/feedback")
            ->assertForbidden();

        $this->actingAs($parent)
            ->putJson("/guru/student-grades/{$student->id}/feedback", [
                'note' => 'Tidak boleh menulis catatan',
            ])
            ->assertForbidden();

        $this->actingAs($otherTeacher)
            ->getJson("/guru/student-grades/{$student->id}/feedback")
            ->assertOk()
            ->assertJsonPath('note', '');

        $this->assertDatabaseCount('student_teacher_notes', 1);
        $this->assertSame(0, DB::table('student_teacher_notes')
            ->where('teacher_id', $otherTeacher->id)
            ->count());
    }

    public function test_legacy_published_notes_without_assessment_context_are_general(): void
    {
        $teacher = $this->createUser('guru', 'guru-notes-legacy');
        $student = $this->createUser('siswa', 'siswa-notes-legacy');
        $parent = $this->createUser('orang_tua', 'parent-notes-legacy');
        $this->link($student, $parent, 'approved');

        $this->actingAs($teacher)
            ->putJson("/guru/student-grades/{$student->id}/feedback", [
                'note' => 'Catatan legacy untuk Orang Tua',
                'action' => 'publish',
            ])
            ->assertOk();

        $this->actingAs($student)
            ->getJson('/siswa/teacher-notes')
            ->assertOk()
            ->assertJsonCount(0, 'notes');

        $parentDashboard = app(ParentDashboardService::class)->dashboard($parent);
        $this->assertSame(
            'Catatan legacy untuk Orang Tua',
            $parentDashboard['teacherNotes']['general'][0]['body']
        );
        $this->assertDatabaseHas('student_teacher_note_messages', [
            'student_id' => $student->id,
            'assessment_type' => null,
            'assessment_result_id' => null,
            'note' => 'Catatan legacy untuk Orang Tua',
        ]);
    }

    public function test_audience_migration_refuses_rollback_when_separate_audience_notes_exist(): void
    {
        $teacher = $this->createUser('guru', 'guru-notes-rollback');
        $student = $this->createUser('siswa', 'siswa-notes-rollback');

        DB::table('student_teacher_notes')->insert([
            [
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'audience' => 'orang_tua',
                'note' => 'Catatan untuk Orang Tua',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'student_id' => $student->id,
                'teacher_id' => $teacher->id,
                'audience' => 'siswa',
                'note' => 'Catatan untuk Siswa',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $migration = require database_path('migrations/2026_10_28_130000_add_audience_to_student_teacher_notes.php');
        try {
            $migration->down();
            $this->fail('Rollback must refuse to collapse distinct audience notes.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString(
                'catatan untuk beberapa audience',
                $exception->getMessage()
            );
        }

        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('student_teacher_notes', 'audience'));
        $this->assertDatabaseCount('student_teacher_notes', 2);
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