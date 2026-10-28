<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ParentDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TeacherNotesAuthorizationTest extends TestCase
{
    use RefreshDatabase;

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
            ])
            ->assertOk();

        $this->assertDatabaseHas('student_teacher_notes', [
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'audience' => 'orang_tua',
            'note' => 'Catatan untuk Orang Tua',
        ]);

        $this->actingAs($teacher)
            ->putJson("/guru/student-grades/{$student->id}/feedback", [
                'note' => 'Catatan untuk Siswa',
                'audience' => 'siswa',
            ])
            ->assertOk();

        $this->actingAs($student)
            ->getJson('/siswa/teacher-notes')
            ->assertOk()
            ->assertJsonCount(1, 'notes')
            ->assertJsonPath('notes.0.note', 'Catatan untuk Siswa');

        $parentDashboard = app(ParentDashboardService::class)->dashboard($parent);
        $this->assertSame($student->id, $parentDashboard['child']->id);
        $this->assertCount(1, $parentDashboard['teacherNotes']);
        $this->assertSame('Catatan untuk Orang Tua', $parentDashboard['teacherNotes'][0]['body']);

        $otherParentDashboard = app(ParentDashboardService::class)->dashboard($otherParent);
        $this->assertSame($otherStudent->id, $otherParentDashboard['child']->id);
        $this->assertSame([], $otherParentDashboard['teacherNotes']);

        $unapprovedParent = $this->createUser('orang_tua', 'parent-notes-pending');
        $this->link($student, $unapprovedParent, 'pending');
        $unapprovedDashboard = app(ParentDashboardService::class)->dashboard($unapprovedParent);
        $this->assertNull($unapprovedDashboard['child']);
        $this->assertSame([], $unapprovedDashboard['teacherNotes']);
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

    public function test_notes_remain_safe_while_the_audience_migration_is_pending(): void
    {
        $migration = require database_path('migrations/2026_10_28_130000_add_audience_to_student_teacher_notes.php');
        $migration->down();

        $teacher = $this->createUser('guru', 'guru-notes-legacy');
        $student = $this->createUser('siswa', 'siswa-notes-legacy');
        $parent = $this->createUser('orang_tua', 'parent-notes-legacy');
        $this->link($student, $parent, 'approved');

        $this->actingAs($teacher)
            ->putJson("/guru/student-grades/{$student->id}/feedback", [
                'note' => 'Catatan legacy untuk Orang Tua',
            ])
            ->assertOk();

        $this->actingAs($teacher)
            ->getJson("/guru/student-grades/{$student->id}/feedback")
            ->assertOk()
            ->assertJsonPath('note', 'Catatan legacy untuk Orang Tua');

        $this->actingAs($student)
            ->getJson('/siswa/teacher-notes')
            ->assertOk()
            ->assertJsonCount(0, 'notes');

        $parentDashboard = app(ParentDashboardService::class)->dashboard($parent);
        $this->assertSame('Catatan legacy untuk Orang Tua', $parentDashboard['teacherNotes'][0]['body']);
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
            $this->assertStringContainsString('separate audience notes exist', $exception->getMessage());
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