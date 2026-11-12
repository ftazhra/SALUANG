<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Subchapter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Services\ParentDashboardService;
use Tests\TestCase;

class TeacherStudentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_edit_form_uses_six_profile_fields_and_keeps_phone_and_password_unchanged(): void
    {
        $teacher = $this->user('guru', 'teacher-six-fields');
        $student = $this->user('siswa', 'student-six-fields', [
            'nomor_telepon' => '081234567890',
            'password' => Hash::make('secret123'),
        ]);
        $passwordHash = $student->password;

        $this->actingAs($teacher)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('id="gstuForm"', false)
            ->assertSee('name="name"', false)
            ->assertSee('name="username"', false)
            ->assertSee('name="nisn"', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="kelas"', false)
            ->assertSee('name="sekolah"', false)
            ->assertDontSee('name="password"', false)
            ->assertDontSee('name="nomor_telepon"', false);

        $this->actingAs($teacher)
            ->putJson('/guru/students/' . $student->id, [
                'name' => 'Siswa Enam Field',
                'username' => $student->username,
                'nisn' => $student->nisn,
                'email' => $student->email,
                'kelas' => $student->kelas,
                'sekolah' => 'Sekolah Baru',
            ])
            ->assertOk()
            ->assertJsonPath('student.name', 'Siswa Enam Field')
            ->assertJsonPath('student.sekolah', 'Sekolah Baru');

        $student->refresh();
        $this->assertSame('081234567890', $student->nomor_telepon);
        $this->assertSame($passwordHash, $student->password);
    }

    public function test_teacher_cannot_create_students_but_can_manage_any_registered_student(): void
    {
        $teacher = $this->user('guru', 'teacher-student-list');
        $this->assignClass($teacher, 'VII A');
        $student = $this->user('siswa', 'student-in-scope', ['kelas' => 'VII A']);
        $outOfScopeStudent = $this->user('siswa', 'student-out-of-scope', ['kelas' => 'VII B']);

        $this->actingAs($teacher)
            ->getJson('/guru/students')
            ->assertOk()
            ->assertJsonPath('pagination.total', 2)
            ->assertJsonPath('registered_total', 2)
            ->assertJsonPath('classes.0', 'VII A');

        $this->actingAs($teacher)
            ->getJson('/guru/students/' . $outOfScopeStudent->id)
            ->assertOk()
            ->assertJsonPath('student.name', $outOfScopeStudent->name)
            ->assertJsonPath('student.nisn', $outOfScopeStudent->nisn)
            ->assertJsonPath('student.email', $outOfScopeStudent->email)
            ->assertJsonPath('student.username', $outOfScopeStudent->username)
            ->assertJsonPath('student.sekolah', $outOfScopeStudent->sekolah)
            ->assertJsonPath('student.nomor_telepon', $outOfScopeStudent->nomor_telepon);

        $this->actingAs($teacher)
            ->putJson('/guru/students/' . $outOfScopeStudent->id, [
                'name' => 'Siswa Lintas Kelas',
                'username' => 'student-out-of-scope',
                'nisn' => $outOfScopeStudent->nisn,
                'email' => $outOfScopeStudent->email,
                'kelas' => 'VII B',
                'sekolah' => 'Sekolah B',
                'nomor_telepon' => '081234567890',
            ])
            ->assertOk()
            ->assertJsonPath('student.name', 'Siswa Lintas Kelas')
            ->assertJsonPath('student.username', $outOfScopeStudent->username)
            ->assertJsonPath('student.sekolah', 'Sekolah B')
            ->assertJsonPath('student.nomor_telepon', '081234567890');

        $parent = $this->user('orang_tua', 'parent-out-of-scope-student');
        DB::table('student_guardians')->insert([
            'student_id' => $outOfScopeStudent->id,
            'guardian_id' => $parent->id,
            'relationship' => 'orang_tua',
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->actingAs($teacher)
            ->deleteJson('/guru/students/' . $outOfScopeStudent->id)
            ->assertOk();
        $this->assertSoftDeleted('users', ['id' => $outOfScopeStudent->id]);
        $this->assertDatabaseHas('student_guardians', [
            'student_id' => $outOfScopeStudent->id,
        ]);

        $this->actingAs($teacher)
            ->postJson('/guru/students', [
                'name' => 'Siswa Uji',
                'username' => 'student-created-by-teacher',
                'nisn' => '0000000123',
                'email' => 'student-created@example.test',
                'kelas' => 'VII A',
                'password' => 'secret123',
            ])->assertForbidden();

        $this->assertDatabaseMissing('users', [
            'username' => 'student-created-by-teacher',
        ]);

        $this->actingAs($teacher)
            ->putJson('/guru/students/' . $student->id, [
                'name' => 'Siswa Diperbarui',
                'username' => 'student-in-scope',
                'nisn' => '1234567890',
                'email' => 'student-in-scope@example.test',
                'kelas' => 'VII A',
            ])
            ->assertOk()
            ->assertJsonPath('student.name', 'Siswa Diperbarui');
    }

    public function test_registered_students_in_another_school_are_manageable_by_multiple_teachers(): void
    {
        $teacher = $this->user('guru', 'teacher-school-scope', [
            'sekolah' => 'Sekolah A',
        ]);
        $otherTeacher = $this->user('guru', 'teacher-school-scope-b', [
            'sekolah' => 'Sekolah B',
        ]);
        $this->assignClass($teacher, 'VII A');
        $this->assignClass($otherTeacher, 'VII A');
        $assignedStudent = $this->user('siswa', 'student-school-scope-a', [
            'kelas' => 'VII A',
            'sekolah' => 'Sekolah A',
        ]);
        $otherSchoolStudent = $this->user('siswa', 'student-school-scope-b', [
            'kelas' => 'VII A',
            'sekolah' => 'Sekolah B',
        ]);

        $response = $this->actingAs($teacher)
            ->getJson('/guru/students')
            ->assertOk()
            ->assertJsonPath('registered_total', 2)
            ->assertJsonPath('pagination.total', 2);

        $rows = collect($response->json('students'))->keyBy('id');
        $this->assertEqualsCanonicalizing(
            [$assignedStudent->id, $otherSchoolStudent->id],
            $rows->keys()->all()
        );
        $this->assertTrue($rows[$assignedStudent->id]['can_manage']);
        $this->assertTrue($rows[$otherSchoolStudent->id]['can_manage']);
        $this->assertSame($otherSchoolStudent->email, $rows[$otherSchoolStudent->id]['email']);
        $this->actingAs($teacher)
            ->getJson('/guru/students/' . $otherSchoolStudent->id)
            ->assertOk()
            ->assertJsonPath('student.email', $otherSchoolStudent->email)
            ->assertJsonPath('student.sekolah', 'Sekolah B');
        $this->actingAs($teacher)
            ->putJson('/guru/students/' . $otherSchoolStudent->id, [
                'name' => 'Siswa Lintas Sekolah',
                'username' => 'student-school-scope-b',
                'nisn' => $otherSchoolStudent->nisn,
                'email' => $otherSchoolStudent->email,
                'kelas' => 'VII A',
                'sekolah' => 'Sekolah B',
            ])
            ->assertOk()
            ->assertJsonPath('student.name', 'Siswa Lintas Sekolah');

        $firstParent = $this->user('orang_tua', 'parent-school-scope-a');
        $secondParent = $this->user('orang_tua', 'parent-school-scope-b');
        foreach ([[$assignedStudent, $firstParent], [$otherSchoolStudent, $secondParent]] as [$student, $parent]) {
            DB::table('student_guardians')->insert([
                'student_id' => $student->id,
                'guardian_id' => $parent->id,
                'relationship' => 'orang_tua',
                'status' => 'approved',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $this->actingAs($teacher)
            ->deleteJson('/guru/students/' . $otherSchoolStudent->id)
            ->assertOk();
        $this->actingAs($otherTeacher)
            ->putJson('/guru/students/' . $assignedStudent->id, [
                'name' => 'Siswa Dikelola Guru B',
                'username' => 'student-school-scope-a',
                'nisn' => $assignedStudent->nisn,
                'email' => $assignedStudent->email,
                'kelas' => 'VII A',
                'sekolah' => 'Sekolah A',
            ])
            ->assertOk()
            ->assertJsonPath('student.name', 'Siswa Dikelola Guru B');
        $this->actingAs($otherTeacher)
            ->deleteJson('/guru/students/' . $assignedStudent->id)
            ->assertOk();
        $this->assertSoftDeleted('users', ['id' => $assignedStudent->id]);
        $this->assertSoftDeleted('users', ['id' => $otherSchoolStudent->id]);
        $this->assertDatabaseHas('student_guardians', [
            'student_id' => $assignedStudent->id,
        ]);
        $this->assertDatabaseHas('student_guardians', [
            'student_id' => $otherSchoolStudent->id,
        ]);
    }

    public function test_global_student_list_and_total_do_not_require_teacher_assignment(): void
    {
        $teacher = $this->user('guru', 'teacher-global-student-count');
        $otherTeacher = $this->user('guru', 'teacher-global-student-count-two');
        $firstStudent = $this->user('siswa', 'global-student-one');
        $secondStudent = $this->user('siswa', 'global-student-two', [
            'kelas' => 'IX C',
            'sekolah' => 'Sekolah B',
        ]);
        $thirdStudent = $this->user('siswa', 'global-student-three', [
            'kelas' => 'VI A',
            'sekolah' => 'Sekolah C',
            'nisn' => null,
            'email' => null,
        ]);

        $response = $this->actingAs($teacher)
            ->getJson('/guru/students')
            ->assertOk()
            ->assertJsonPath('registered_total', 3)
            ->assertJsonPath('pagination.total', 3);
        $rows = collect($response->json('students'))->keyBy('id');
        $this->assertEqualsCanonicalizing(
            [$firstStudent->id, $secondStudent->id, $thirdStudent->id],
            $rows->keys()->all()
        );
        $this->assertTrue($rows[$firstStudent->id]['can_manage']);
        $this->assertTrue($rows[$secondStudent->id]['can_manage']);
        $this->assertTrue($rows[$thirdStudent->id]['can_manage']);
        $this->assertSame($firstStudent->nisn, $rows[$firstStudent->id]['nisn']);
        $this->assertSame($firstStudent->email, $rows[$firstStudent->id]['email']);
        $this->assertSame($secondStudent->nisn, $rows[$secondStudent->id]['nisn']);
        $this->assertSame($secondStudent->email, $rows[$secondStudent->id]['email']);
        $this->assertNull($rows[$thirdStudent->id]['nisn']);
        $this->assertNull($rows[$thirdStudent->id]['email']);

        $this->actingAs($otherTeacher)
            ->getJson('/guru/students')
            ->assertOk()
            ->assertJsonPath('registered_total', 3)
            ->assertJsonPath('pagination.total', 3);

        $this->actingAs($teacher)
            ->getJson('/guru/students/' . $firstStudent->id)
            ->assertOk()
            ->assertJsonPath('student.id', $firstStudent->id)
            ->assertJsonPath('student.name', $firstStudent->name)
            ->assertJsonPath('student.username', $firstStudent->username)
            ->assertJsonPath('student.nisn', $firstStudent->nisn)
            ->assertJsonPath('student.kelas', $firstStudent->kelas)
            ->assertJsonPath('student.email', $firstStudent->email)
            ->assertJsonPath('student.sekolah', $firstStudent->sekolah)
            ->assertJsonPath('student.nomor_telepon', $firstStudent->nomor_telepon);
        $this->actingAs($teacher)
            ->getJson('/guru/student-grades')
            ->assertOk()
            ->assertJsonPath('total', 3)
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.completion_status', 'belum_dinilai')
            ->assertJsonPath('data.1.completion_status', 'belum_dinilai')
            ->assertJsonPath('data.2.completion_status', 'belum_dinilai');
        $this->actingAs($teacher)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('id="gdTotalStudents">3</strong>', false)
            ->assertDontSee('Daftar ini memuat semua akun Siswa terdaftar');

        $this->actingAs($otherTeacher)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('id="gdTotalStudents">3</strong>', false);
    }

    public function test_global_student_list_keeps_search_class_filter_and_pagination(): void
    {
        $teacher = $this->user('guru', 'teacher-global-student-filters');

        foreach (range(1, 17) as $number) {
            $this->user('siswa', "global-filter-student-{$number}", [
                'name' => "Siswa Global {$number}",
                'nisn' => str_pad((string) $number, 10, '0', STR_PAD_LEFT),
                'kelas' => $number % 2 === 0 ? 'VII B' : 'VII A',
            ]);
        }

        $firstPage = $this->actingAs($teacher)
            ->getJson('/guru/students')
            ->assertOk()
            ->assertJsonPath('registered_total', 17)
            ->assertJsonPath('pagination.total', 17)
            ->assertJsonPath('pagination.current_page', 1)
            ->assertJsonPath('pagination.last_page', 2);
        $this->assertCount(15, $firstPage->json('students'));

        $this->getJson('/guru/students?page=2')
            ->assertOk()
            ->assertJsonPath('pagination.current_page', 2)
            ->assertJsonCount(2, 'students');

        $this->getJson('/guru/students?search=0000000017')
            ->assertOk()
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('students.0.nisn', '0000000017');

        $this->getJson('/guru/students?class=VII%20B')
            ->assertOk()
            ->assertJsonPath('pagination.total', 8);
    }

    public function test_new_registration_is_scoped_consistently_for_teacher_dashboard_grades_progress_and_evaluation(): void
    {
        $teacher = $this->user('guru', 'teacher-new-student-scope', [
            'sekolah' => 'Sekolah A',
        ]);
        $otherTeacher = $this->user('guru', 'teacher-new-student-scope-two');
        $this->assignClass($teacher, 'VII A');
        $existingStudent = $this->user('siswa', 'existing-student-scope', [
            'kelas' => 'VII A',
            'sekolah' => 'Sekolah A',
        ]);
        $otherSchoolStudent = $this->user('siswa', 'other-school-student-scope', [
            'kelas' => 'VII A',
            'sekolah' => 'Sekolah B',
        ]);
        $otherClassStudent = $this->user('siswa', 'other-class-student-scope', [
            'kelas' => 'VII B',
            'sekolah' => 'Sekolah A',
        ]);

        foreach (range(1, 15) as $number) {
            $this->actingAs($teacher)
                ->postJson('/guru/evaluations/questions', $this->evaluationQuestion($number))
                ->assertCreated();
        }

        $this->app['auth']->logout();
        $this->post(route('register.store'), [
            'role' => 'siswa',
            'name' => 'Siswa Baru',
            'username' => 'new-student-scope',
            'nisn' => '9876543210',
            'email' => 'new-student-scope@example.test',
            'kelas' => ' vii a ',
            'sekolah' => ' Sekolah A ',
            'nomor_telepon' => '081234567890',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertRedirect(route('login'));

        $newStudent = User::query()
            ->where('username', 'new-student-scope')
            ->firstOrFail();
        $this->assertSame('vii a', $newStudent->kelas);
        $this->assertSame('Sekolah A', $newStudent->sekolah);
        $this->assertSame('081234567890', $newStudent->nomor_telepon);
        $this->actingAs($teacher)
            ->getJson('/guru/students/' . $newStudent->id)
            ->assertOk()
            ->assertJsonPath('student.username', 'new-student-scope')
            ->assertJsonPath('student.sekolah', 'Sekolah A')
            ->assertJsonPath('student.nomor_telepon', '081234567890');
        $originalPassword = $newStudent->password;
        $this->actingAs($newStudent)
            ->patchJson('/siswa/profil', [
                'name' => 'Siswa Baru Diperbarui',
                'username' => 'new-student-updated',
                'email' => 'new-student-updated@example.test',
                'kelas' => 'VII C',
                'sekolah' => 'Sekolah C',
                'nomor_telepon' => '081298765432',
            ])
            ->assertOk()
            ->assertJsonPath('student.username', 'new-student-updated')
            ->assertJsonPath('student.sekolah', 'Sekolah C')
            ->assertJsonPath('student.nomor_telepon', '081298765432');
        $newStudent->refresh();
        $this->assertSame($originalPassword, $newStudent->password);
        $this->assertSame('Siswa Baru Diperbarui', $newStudent->name);
        $this->actingAs($teacher)
            ->getJson('/guru/students/' . $newStudent->id)
            ->assertOk()
            ->assertJsonPath('student.username', 'new-student-updated')
            ->assertJsonPath('student.sekolah', 'Sekolah C')
            ->assertJsonPath('student.nomor_telepon', '081298765432');

        $studentsResponse = $this->actingAs($teacher)
            ->getJson('/guru/students')
            ->assertOk()
            ->assertJsonPath('registered_total', 4)
            ->assertJsonPath('pagination.total', 4);
        $this->assertEqualsCanonicalizing(
            [$existingStudent->id, $newStudent->id, $otherSchoolStudent->id, $otherClassStudent->id],
            collect($studentsResponse->json('students'))->pluck('id')->all()
        );
        $this->actingAs($otherTeacher)
            ->getJson('/guru/students')
            ->assertOk()
            ->assertJsonPath('registered_total', 4)
            ->assertJsonPath('pagination.total', 4);

        $gradeResponse = $this->actingAs($teacher)
            ->getJson('/guru/student-grades')
            ->assertOk()
            ->assertJsonPath('total', 4);
        $this->assertEqualsCanonicalizing(
            [$existingStudent->id, $newStudent->id, $otherSchoolStudent->id, $otherClassStudent->id],
            collect($gradeResponse->json('data'))->pluck('id')->all()
        );
        $this->actingAs($teacher)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('id="gdTotalStudents">4</strong>', false);

        $this->actingAs($newStudent)
            ->getJson('/siswa/evaluations/teachers')
            ->assertOk()
            ->assertJsonPath('default_teacher_id', $teacher->id)
            ->assertJsonPath('ready', true);
        $started = $this->postJson("/siswa/evaluations/teachers/{$teacher->id}/start")
            ->assertOk()
            ->assertJsonCount(15, 'questions');

        $this->putJson("/siswa/evaluations/teachers/{$teacher->id}/answers", [
            'answers' => array_fill_keys(array_map('strval', range(1, 15)), 'A'),
        ])->assertOk();
        $this->postJson("/siswa/evaluations/teachers/{$teacher->id}/finish")
            ->assertOk()
            ->assertJsonPath('score', 100);
        $this->assertDatabaseHas('evaluation_attempts', [
            'id' => $started->json('attempt_id'),
            'user_id' => $newStudent->id,
            'teacher_id' => $teacher->id,
            'score' => 100,
        ]);

        $this->actingAs($newStudent)
            ->getJson('/siswa/progress')
            ->assertOk()
            ->assertJsonCount(1, 'final_grades')
            ->assertJsonPath('final_grades.0.teacher_id', $teacher->id);

        $parent = $this->user('orang_tua', 'parent-new-student-scope');
        DB::table('student_guardians')->insert([
            'student_id' => $newStudent->id,
            'guardian_id' => $parent->id,
            'relationship' => 'orang_tua',
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $parentDashboard = app(ParentDashboardService::class)->dashboard($parent);
        $this->assertSame($newStudent->id, $parentDashboard['child']->id);
        $parentEvaluation = collect($parentDashboard['learningResults'])
            ->firstWhere('teacher_id', $teacher->id);
        $this->assertNotNull($parentEvaluation);
        $this->assertSame(100.0, (float) $parentEvaluation['score']);
    }

    public function test_student_without_history_is_removed_from_active_lists_and_cannot_login(): void
    {
        $teacher = $this->user('guru', 'teacher-delete-student');
        $student = $this->user('siswa', 'student-with-parent', ['kelas' => 'VII A']);

        $this->actingAs($teacher)
            ->deleteJson('/guru/students/' . $student->id)
            ->assertOk()
            ->assertJsonPath('message', 'Akun siswa dinonaktifkan. Riwayat dan data terkait tetap disimpan.');

        $this->assertSoftDeleted('users', ['id' => $student->id]);
        $this->assertDatabaseHas('users', ['id' => $student->id]);
        $this->getJson('/guru/students')
            ->assertOk()
            ->assertJsonPath('registered_total', 0)
            ->assertJsonPath('pagination.total', 0);
        $this->getJson('/guru/student-grades')
            ->assertOk()
            ->assertJsonPath('total', 0)
            ->assertJsonCount(0, 'data');
        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('id="gdTotalStudents">0</strong>', false);

        $this->app['auth']->logout();
        $this->post(route('login.store'), [
            'role' => 'siswa',
            'identifier' => $student->nisn,
            'password' => 'secret123',
        ])->assertSessionHasErrors('identifier');
    }

    public function test_student_with_parent_and_learning_history_is_deactivated_without_losing_data(): void
    {
        $teacher = $this->user('guru', 'teacher-safe-delete');
        $student = $this->user('siswa', 'student-safe-delete', ['kelas' => 'VII C']);
        $parent = $this->user('orang_tua', 'parent-safe-delete');
        DB::table('student_guardians')->insert([
            'student_id' => $student->id,
            'guardian_id' => $parent->id,
            'relationship' => 'orang_tua',
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $chapter = Chapter::create([
            'user_id' => $teacher->id,
            'nomor_bab' => 6,
            'judul' => 'Bab VI',
            'is_published' => true,
        ]);
        $subchapter = Subchapter::create([
            'chapter_id' => $chapter->id,
            'nomor_subbab' => 1,
            'judul' => 'Subbab 1',
            'konten' => 'Materi',
            'is_published' => true,
        ]);
        DB::table('student_subchapter_progress')->insert([
            'user_id' => $student->id,
            'subchapter_id' => $subchapter->id,
            'status' => 'completed',
            'opened_at' => now(),
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($teacher)
            ->deleteJson('/guru/students/' . $student->id)
            ->assertOk();

        $this->assertSoftDeleted('users', ['id' => $student->id]);
        $this->assertDatabaseHas('users', ['id' => $student->id]);
        $this->assertDatabaseHas('student_guardians', ['student_id' => $student->id]);
        $this->assertDatabaseHas('student_subchapter_progress', [
            'user_id' => $student->id,
            'status' => 'completed',
        ]);
        $this->actingAs($teacher)
            ->getJson('/guru/students')
            ->assertOk()
            ->assertJsonPath('registered_total', 0)
            ->assertJsonPath('pagination.total', 0);
        $this->actingAs($teacher)
            ->getJson('/guru/student-grades')
            ->assertOk()
            ->assertJsonPath('total', 0)
            ->assertJsonCount(0, 'data');
        $this->assertNull(
            app(ParentDashboardService::class)->dashboard($parent)['child']
        );

        $this->app['auth']->logout();
        $this->post(route('login.store'), [
            'role' => 'orang_tua',
            'identifier' => $parent->username,
            'password' => 'secret123',
            'nisn_anak' => $student->nisn,
        ])->assertSessionHasErrors('nisn_anak');
        $this->post(route('login.store'), [
            'role' => 'siswa',
            'identifier' => $student->nisn,
            'password' => 'secret123',
        ])->assertSessionHasErrors('identifier');
    }

    private function assignClass(User $teacher, string $className): void
    {
        DB::table('teacher_class_assignments')->insert([
            'teacher_id' => $teacher->id,
            'class_name' => $className,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function user(string $role, string $username, array $attributes = []): User
    {
        return User::create(array_merge([
            'name' => ucfirst($role) . ' Uji',
            'username' => $username,
            'email' => $username . '@example.test',
            'password' => 'secret123',
            'role' => $role,
            'nisn' => $role === 'siswa'
                ? str_pad((string) (abs(crc32($username)) % 10000000000), 10, '0', STR_PAD_LEFT)
                : null,
            'kelas' => $role === 'siswa' ? 'VII A' : null,
        ], $attributes));
    }

    private function evaluationQuestion(int $number): array
    {
        return [
            'nomor_soal' => $number,
            'tipe_soal' => 'pilihan_ganda',
            'pertanyaan' => "Pertanyaan {$number}",
            'konten_soal' => [
                'pilihan_a' => 'Pilihan A',
                'pilihan_b' => 'Pilihan B',
                'pilihan_c' => 'Pilihan C',
                'pilihan_d' => 'Pilihan D',
            ],
            'kunci_jawaban' => ['jawaban' => 'A'],
            'is_published' => true,
        ];
    }
}
