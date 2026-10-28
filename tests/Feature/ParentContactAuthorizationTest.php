<?php

namespace Tests\Feature;

use App\Models\StudentGuardian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ParentContactAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_contacts_require_explicit_class_assignments_and_only_approved_links(): void
    {
        $this->getJson('/guru/parent-contacts?class=VII%20A')->assertUnauthorized();

        $teacherA = $this->createUser('guru', 'guru-contact-a');
        $teacherB = $this->createUser('guru', 'guru-contact-b');
        $multiClassTeacher = $this->createUser('guru', 'guru-contact-multi');
        $unassignedTeacher = $this->createUser('guru', 'guru-contact-none');
        $studentA = $this->createStudent('siswa-contact-a', 'VII A');
        $studentB = $this->createStudent('siswa-contact-b', 'VII B');
        $parentA = $this->createUser('orang_tua', 'parent-contact-a', ['nomor_telepon' => '0811111111']);
        $parentB = $this->createUser('orang_tua', 'parent-contact-b', ['nomor_telepon' => '0822222222']);
        $multiChildParent = $this->createUser('orang_tua', 'parent-contact-multi', ['nomor_telepon' => '0833333333']);
        $pendingParent = $this->createUser('orang_tua', 'parent-contact-pending', ['nomor_telepon' => '0844444444']);
        $rejectedParent = $this->createUser('orang_tua', 'parent-contact-rejected', ['nomor_telepon' => '0855555555']);

        $relationA = $this->link($studentA, $parentA, 'approved');
        $relationB = $this->link($studentB, $parentB, 'approved');
        $multiRelationA = $this->link($studentA, $multiChildParent, 'approved');
        $multiRelationB = $this->link($studentB, $multiChildParent, 'approved');
        $pendingRelation = $this->link($studentA, $pendingParent, 'pending');
        $rejectedRelation = $this->link($studentA, $rejectedParent, 'rejected');

        $this->assign($teacherA, 'VII A');
        $this->assign($teacherB, 'VII B');
        $this->assign($multiClassTeacher, 'VII A');
        $this->assign($multiClassTeacher, 'VII B');
        $this->assertDatabaseCount('teacher_class_assignments', 4);
        $this->assertDatabaseMissing('teacher_class_assignments', [
            'teacher_id' => $unassignedTeacher->id,
        ]);

        $contactsA = $this->actingAs($teacherA)
            ->getJson('/guru/parent-contacts?class=VII%20A')
            ->assertOk()
            ->assertJsonPath('total', 2)
            ->assertJsonFragment(['phone' => '0811111111'])
            ->assertJsonFragment(['phone' => '0833333333']);
        $this->assertSame(
            [$studentA->id, $studentA->id],
            collect($contactsA->json('data'))->pluck('student.id')->sort()->values()->all()
        );
        $this->assertStringNotContainsString('0844444444', $contactsA->getContent());
        $this->assertStringNotContainsString('0855555555', $contactsA->getContent());

        $this->getJson('/guru/parent-contacts?class=VII%20B')->assertForbidden();
        $this->getJson("/guru/parent-contacts/{$relationB->id}")->assertNotFound();
        $this->getJson("/guru/parent-contacts/{$multiRelationB->id}")->assertNotFound();
        $this->getJson("/guru/parent-contacts/{$pendingRelation->id}")->assertNotFound();
        $this->getJson("/guru/parent-contacts/{$rejectedRelation->id}")->assertNotFound();

        $contactsB = $this->actingAs($teacherB)
            ->getJson('/guru/parent-contacts?class=VII%20B')
            ->assertOk()
            ->assertJsonPath('total', 2)
            ->assertJsonFragment(['phone' => '0822222222'])
            ->assertJsonFragment(['phone' => '0833333333']);
        $this->assertSame(
            [$studentB->id, $studentB->id],
            collect($contactsB->json('data'))->pluck('student.id')->sort()->values()->all()
        );

        $multiA = $this->actingAs($multiClassTeacher)
            ->getJson('/guru/parent-contacts?class=VII%20A')
            ->assertOk()
            ->assertJsonPath('total', 2);
        $multiB = $this->getJson('/guru/parent-contacts?class=VII%20B')
            ->assertOk()
            ->assertJsonPath('total', 2);
        $this->assertSame($multiRelationA->id, $multiA->json('data.1.relationship_id'));
        $this->assertSame($multiRelationB->id, $multiB->json('data.1.relationship_id'));

        $this->actingAs($unassignedTeacher)
            ->getJson('/guru/parent-contacts?class=VII%20A')
            ->assertForbidden();
        $this->actingAs($studentA)
            ->getJson('/guru/parent-contacts?class=VII%20A')
            ->assertForbidden();
        $this->actingAs($parentA)
            ->getJson('/guru/parent-contacts?class=VII%20A')
            ->assertForbidden();

        $parentA->nomor_telepon = '0899999999';
        $parentA->save();
        $this->actingAs($teacherA)
            ->getJson('/guru/parent-contacts?class=VII%20A')
            ->assertOk()
            ->assertJsonFragment(['phone' => '0899999999'])
            ->assertJsonMissing(['phone' => '0811111111']);

        $this->assertSame(2, StudentGuardian::query()
            ->where('guardian_id', $multiChildParent->id)
            ->where('status', 'approved')
            ->count());
    }

    public function test_parent_registration_persists_phone_and_creates_only_pending_child_link(): void
    {
        $student = $this->createStudent('student-registration-target', 'VII A');
        $student->nisn = '1234567890';
        $student->save();

        $this->post('/daftar', [
            'role' => 'orang_tua',
            'name' => 'Orang Tua Uji',
            'username' => 'parent-registration-phone',
            'nomor_telepon' => '08123456789',
            'nisn_anak' => '1234567890',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertRedirect(route('login'));

        $parent = User::query()->where('username', 'parent-registration-phone')->firstOrFail();
        $this->assertSame('08123456789', $parent->nomor_telepon);
        $this->assertDatabaseHas('student_guardians', [
            'student_id' => $student->id,
            'guardian_id' => $parent->id,
            'status' => 'pending',
        ]);
    }

    public function test_assignment_command_requires_a_real_teacher_and_existing_supported_class(): void
    {
        $teacher = $this->createUser('guru', 'guru-command-valid');
        $student = $this->createStudent('student-command-valid', 'VII A');

        $this->artisan('saluang:assign-teacher-class', [
            'teacher_id' => $teacher->id,
            'class_name' => 'VII A',
        ])->assertExitCode(0);

        $this->assertDatabaseHas('teacher_class_assignments', [
            'teacher_id' => $teacher->id,
            'class_name' => $student->kelas,
        ]);

        $student->delete();

        $this->artisan('saluang:assign-teacher-class', [
            'teacher_id' => $teacher->id,
            'class_name' => 'VII E',
        ])->assertExitCode(1);

        $this->artisan('saluang:assign-teacher-class', [
            'teacher_id' => $teacher->id,
            'class_name' => 'VII A',
            '--revoke' => true,
        ])->assertExitCode(0);

        $this->assertDatabaseMissing('teacher_class_assignments', [
            'teacher_id' => $teacher->id,
            'class_name' => 'VII A',
        ]);
    }

    public function test_assignment_migration_has_no_default_grants_and_cascades_from_teacher(): void
    {
        $this->assertSame([], DB::table('teacher_class_assignments')->get()->all());
        $foreignKeys = collect(Schema::getForeignKeys('teacher_class_assignments'));
        $this->assertTrue($foreignKeys->contains(
            fn (array $key) => $key['columns'] === ['teacher_id']
                && $key['foreign_table'] === 'users'
                && $key['on_delete'] === 'cascade'
        ));
        $indexes = collect(Schema::getIndexes('teacher_class_assignments'));
        $this->assertTrue($indexes->contains(
            fn (array $index) => $index['unique']
                && $index['columns'] === ['teacher_id', 'class_name']
        ));
    }

    public function test_assignment_migration_refuses_rollback_while_assignments_exist(): void
    {
        $teacher = $this->createUser('guru', 'guru-assignment-rollback');
        $student = $this->createStudent('student-assignment-rollback', 'VII A');
        $this->assign($teacher, $student->kelas);

        $migration = require database_path('migrations/2026_10_28_140000_create_teacher_class_assignments_table.php');
        try {
            $migration->down();
            $this->fail('Rollback must not discard explicit class assignments.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('assignments exist', $exception->getMessage());
        }

        $this->assertTrue(Schema::hasTable('teacher_class_assignments'));
        $this->assertDatabaseCount('teacher_class_assignments', 1);
    }

    private function assign(User $teacher, string $className): void
    {
        DB::table('teacher_class_assignments')->insert([
            'teacher_id' => $teacher->id,
            'class_name' => $className,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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

    private function createStudent(string $username, string $class): User
    {
        return $this->createUser('siswa', $username, [
            'kelas' => $class,
            'nisn' => substr(str_pad((string) random_int(1, 9999999999), 10, '0', STR_PAD_LEFT), 0, 10),
        ]);
    }

    private function createUser(string $role, string $username, array $attributes = []): User
    {
        return User::create(array_merge([
            'name' => ucfirst($role) . ' SALUANG',
            'username' => $username,
            'password' => 'secret123',
            'role' => $role,
            'email' => null,
        ], $attributes));
    }
}