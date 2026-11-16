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
        $teacher = $this->user('guru', 'teacher-six-fields', ['sekolah' => 'Sekolah Uji']);
        $student = $this->user('siswa', 'student-six-fields', [
            'sekolah' => 'Sekolah Uji',
            'nomor_telepon' => '081234567890',
            'password' => Hash::make('secret123'),
        ]);
        $this->assignClass($teacher, 'VII A');
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
                'sekolah' => 'Sekolah Uji',
            ])
            ->assertOk()
            ->assertJsonPath('student.name', 'Siswa Enam Field')
            ->assertJsonPath('student.sekolah', 'Sekolah Uji');

        $student->refresh();
        $this->assertSame('081234567890', $student->nomor_telepon);
        $this->assertSame($passwordHash, $student->password);
    }

    public function test_teacher_can_manage_students_across_classes_but_not_across_schools(): void
    {
        $teacher = $this->user('guru', 'teacher-student-list', ['sekolah' => 'Sekolah Uji']);
        $studentA = $this->user('siswa', 'student-in-scope', [
            'kelas' => 'VII A',
            'sekolah' => 'Sekolah Uji',
        ]);
        $studentB = $this->user('siswa', 'student-other-class', [
            'kelas' => 'VII B',
            'sekolah' => 'Sekolah Uji',
        ]);
        $otherSchoolStudent = $this->user('siswa', 'student-other-school', [
            'kelas' => 'VII A',
            'sekolah' => 'Sekolah B',
        ]);

        $response = $this->actingAs($teacher)
            ->getJson('/guru/students')
            ->assertOk()
            ->assertJsonPath('pagination.total', 2)
            ->assertJsonPath('registered_total', 2);

        $this->assertEqualsCanonicalizing(
            [$studentA->id, $studentB->id],
            collect($response->json('students'))->pluck('id')->all()
        );
        $this->assertEqualsCanonicalizing(['VII A', 'VII B'], $response->json('classes'));

        $this->getJson('/guru/students/' . $studentB->id)
            ->assertOk()
            ->assertJsonPath('student.kelas', 'VII B');

        $this->putJson('/guru/students/' . $studentB->id, [
            'name' => 'Siswa Lintas Kelas',
            'username' => $studentB->username,
            'nisn' => $studentB->nisn,
            'email' => $studentB->email,
            'kelas' => 'VII B',
            'sekolah' => 'Sekolah Uji',
        ])
            ->assertOk()
            ->assertJsonPath('student.name', 'Siswa Lintas Kelas');

        // Perubahan sekolah melalui Guru tetap ditolak.
        $this->putJson('/guru/students/' . $studentB->id, [
            'name' => 'Dipindah ke Sekolah B',
            'username' => $studentB->username,
            'nisn' => $studentB->nisn,
            'email' => $studentB->email,
            'kelas' => 'VII B',
            'sekolah' => 'Sekolah B',
        ])->assertUnprocessable();
        $this->assertSame('Sekolah Uji', $studentB->fresh()->sekolah);

        $this->getJson('/guru/students/' . $otherSchoolStudent->id)
            ->assertNotFound();
        $this->putJson('/guru/students/' . $otherSchoolStudent->id, [
            'name' => 'Tidak Boleh Dikelola',
            'username' => $otherSchoolStudent->username,
            'nisn' => $otherSchoolStudent->nisn,
            'email' => $otherSchoolStudent->email,
            'kelas' => 'VII A',
            'sekolah' => 'Sekolah B',
        ])->assertNotFound();

        $parent = $this->user('orang_tua', 'parent-other-school');
        DB::table('student_guardians')->insert([
            'student_id' => $otherSchoolStudent->id,
            'guardian_id' => $parent->id,
            'relationship' => 'orang_tua',
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->deleteJson('/guru/students/' . $otherSchoolStudent->id)
            ->assertNotFound();
        $this->assertNotSoftDeleted('users', ['id' => $otherSchoolStudent->id]);
        $this->assertDatabaseHas('student_guardians', [
            'student_id' => $otherSchoolStudent->id,
        ]);

        $this->postJson('/guru/students', [
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
    }

    public function test_matching_class_name_does_not_grant_cross_school_student_access(): void
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
            ->assertJsonPath('registered_total', 1)
            ->assertJsonPath('pagination.total', 1);

        $rows = collect($response->json('students'))->keyBy('id');
        $this->assertEqualsCanonicalizing(
            [$assignedStudent->id],
            $rows->keys()->all()
        );
        $this->assertTrue($rows[$assignedStudent->id]['can_manage']);
        $this->actingAs($teacher)
            ->getJson('/guru/students/' . $otherSchoolStudent->id)
            ->assertNotFound();
        $this->actingAs($teacher)
            ->putJson('/guru/students/' . $otherSchoolStudent->id, [
                'name' => 'Siswa Lintas Sekolah',
                'username' => 'student-school-scope-b',
                'nisn' => $otherSchoolStudent->nisn,
                'email' => $otherSchoolStudent->email,
                'kelas' => 'VII A',
                'sekolah' => 'Sekolah B',
            ])
            ->assertNotFound();

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
            ->assertNotFound();
        $this->actingAs($otherTeacher)
            ->getJson('/guru/students/' . $otherSchoolStudent->id)
            ->assertOk()
            ->assertJsonPath('student.sekolah', 'Sekolah B');
        $this->actingAs($otherTeacher)
            ->putJson('/guru/students/' . $assignedStudent->id, [
                'name' => 'Siswa Dikelola Guru B',
                'username' => 'student-school-scope-a',
                'nisn' => $assignedStudent->nisn,
                'email' => $assignedStudent->email,
                'kelas' => 'VII A',
                'sekolah' => 'Sekolah A',
            ])
            ->assertNotFound();
        $this->actingAs($otherTeacher)
            ->deleteJson('/guru/students/' . $assignedStudent->id)
            ->assertNotFound();
        $this->assertNotSoftDeleted('users', ['id' => $assignedStudent->id]);
        $this->assertNotSoftDeleted('users', ['id' => $otherSchoolStudent->id]);
        $this->assertDatabaseHas('student_guardians', [
            'student_id' => $assignedStudent->id,
        ]);
        $this->assertDatabaseHas('student_guardians', [
            'student_id' => $otherSchoolStudent->id,
        ]);
    }

    public function test_teacher_without_class_assignments_still_sees_own_school_students(): void
    {
        $teacher = $this->user('guru', 'teacher-global-student-count');
        $otherTeacher = $this->user('guru', 'teacher-global-student-count-two', [
            'sekolah' => 'Sekolah B',
        ]);
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
            ->assertJsonPath('registered_total', 1)
            ->assertJsonPath('pagination.total', 1);
        $this->assertSame([$firstStudent->id], collect($response->json('students'))->pluck('id')->all());

        $this->actingAs($otherTeacher)
            ->getJson('/guru/students')
            ->assertOk()
            ->assertJsonPath('registered_total', 1)
            ->assertJsonPath('pagination.total', 1);

        $this->actingAs($teacher)
            ->getJson('/guru/students/' . $firstStudent->id)
            ->assertOk();
        $this->getJson('/guru/students/' . $secondStudent->id)
            ->assertNotFound();
        $this->getJson('/guru/students/' . $thirdStudent->id)
            ->assertNotFound();
        $this->getJson('/guru/student-grades')
            ->assertOk()
            ->assertJsonPath('total', 1);
        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('id="gdTotalStudents"', false);

        $this->actingAs($otherTeacher)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('id="gdTotalStudents"', false);
    }

    public function test_assigned_student_list_keeps_search_class_filter_and_pagination(): void
    {
        $teacher = $this->user('guru', 'teacher-global-student-filters');
        $this->assignClass($teacher, 'VII A');
        $this->assignClass($teacher, 'VII B');

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
                'nomor_telepon' => '081298765432',
            ])
            ->assertOk()
            ->assertJsonPath('student.username', 'new-student-updated')
            ->assertJsonPath('student.sekolah', 'Sekolah A')
            ->assertJsonPath('student.nomor_telepon', '081298765432');
        $newStudent->refresh();
        $this->assertSame('vii a', $newStudent->kelas);
        $this->assertSame('Sekolah A', $newStudent->sekolah);
        $this->assertSame($originalPassword, $newStudent->password);
        $this->assertSame('Siswa Baru Diperbarui', $newStudent->name);
        $this->actingAs($teacher)
            ->getJson('/guru/students/' . $newStudent->id)
            ->assertOk()
            ->assertJsonPath('student.username', 'new-student-updated')
            ->assertJsonPath('student.sekolah', 'Sekolah A')
            ->assertJsonPath('student.nomor_telepon', '081298765432');

        $studentsResponse = $this->actingAs($teacher)
            ->getJson('/guru/students')
            ->assertOk()
            ->assertJsonPath('registered_total', 3)
            ->assertJsonPath('pagination.total', 3);
        $this->assertEqualsCanonicalizing(
            [$existingStudent->id, $otherClassStudent->id, $newStudent->id],
            collect($studentsResponse->json('students'))->pluck('id')->all()
        );
        $this->actingAs($otherTeacher)
            ->getJson('/guru/students')
            ->assertOk()
            ->assertJsonPath('registered_total', 0)
            ->assertJsonPath('pagination.total', 0);

        $gradeResponse = $this->actingAs($teacher)
            ->getJson('/guru/student-grades')
            ->assertOk()
            ->assertJsonPath('total', 3);
        $this->assertEqualsCanonicalizing(
            [$existingStudent->id, $otherClassStudent->id, $newStudent->id],
            collect($gradeResponse->json('data'))->pluck('id')->all()
        );
        $this->actingAs($teacher)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('id="gdTotalStudents"', false);

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
        $this->assignClass($teacher, 'VII A');

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
        $this->assignClass($teacher, 'VII C');
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
            'sekolah' => in_array($role, ['guru', 'siswa'], true)
                ? 'Sekolah Uji'
                : null,
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
