<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Discussion;
use App\Models\Subchapter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SchoolAccessAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_student_lists_profiles_and_grades_require_active_school_class_assignment(): void
    {
        $teacherA = $this->user('guru', 'school-a-teacher', 'Sekolah A');
        $teacherB = $this->user('guru', 'school-b-teacher', 'Sekolah B');
        $studentA = $this->user('siswa', 'school-a-student', 'Sekolah A');
        $studentB = $this->user('siswa', 'school-b-student', 'Sekolah B');
        $this->assign($teacherA, 'VII A');
        $this->assign($teacherB, 'VII A');

        $response = $this->actingAs($teacherA)
            ->getJson('/guru/students')
            ->assertOk()
            ->assertJsonPath('registered_total', 1)
            ->assertJsonPath('pagination.total', 1);
        $this->assertSame([$studentA->id], collect($response->json('students'))->pluck('id')->all());
        $this->getJson("/guru/students/{$studentB->id}")->assertNotFound();
        $this->putJson("/guru/students/{$studentB->id}", [
            'name' => 'Tidak boleh diubah',
            'username' => $studentB->username,
            'nisn' => $studentB->nisn,
            'email' => $studentB->email,
            'kelas' => 'VII A',
            'sekolah' => 'Sekolah B',
        ])->assertNotFound();
        $this->deleteJson("/guru/students/{$studentB->id}")->assertNotFound();
        $this->putJson("/guru/students/{$studentA->id}", [
            'name' => $studentA->name,
            'username' => $studentA->username,
            'nisn' => $studentA->nisn,
            'email' => $studentA->email,
            'kelas' => 'VII A',
            'sekolah' => 'Sekolah B',
        ])->assertUnprocessable();
        $this->assertSame('Sekolah A', $studentA->fresh()->sekolah);

        $grades = $this->getJson('/guru/student-grades')
            ->assertOk()
            ->assertJsonPath('total', 1);
        $this->assertSame([$studentA->id], collect($grades->json('data'))->pluck('id')->all());
        $this->getJson("/guru/student-grades/{$studentB->id}")->assertNotFound();
        $excel = $this->get('/guru/student-grades/export/excel')
            ->assertOk()
            ->assertHeader(
                'Content-Type',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            );
        $this->assertStringContainsString(
            'attachment',
            strtolower($excel->headers->get('Content-Disposition', ''))
        );
        $binaryResponse = $excel->baseResponse;
        $this->assertInstanceOf(
            \Symfony\Component\HttpFoundation\BinaryFileResponse::class,
            $binaryResponse
        );
        $excelPath = $binaryResponse->getFile()->getPathname();
        $excelBytes = file_get_contents($excelPath);
        $this->assertIsString($excelBytes);
        $this->assertSame(
            (string) strlen($excelBytes),
            $excel->headers->get('Content-Length')
        );
        $this->assertStringStartsWith('PK', $excelBytes);
        $temporaryFile = tmpfile();
        $this->assertIsResource($temporaryFile);
        fwrite($temporaryFile, $excelBytes);
        rewind($temporaryFile);
        $workbook = \PhpOffice\PhpSpreadsheet\IOFactory::load(
            stream_get_meta_data($temporaryFile)['uri']
        );
        $this->assertSame(4, $workbook->getActiveSheet()->getHighestRow());
        $this->assertSame($studentA->name, $workbook->getActiveSheet()->getCell('A4')->getValue());
        $workbook->disconnectWorksheets();
        fclose($temporaryFile);
        if (is_file($excelPath)) {
            unlink($excelPath);
        }
        $pdf = $this->get('/guru/student-grades/export/pdf')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString(
            'attachment',
            strtolower($pdf->headers->get('Content-Disposition', ''))
        );
        $pdfBytes = $pdf->getContent();
        $this->assertStringStartsWith('%PDF-', $pdfBytes);
        $this->assertStringEndsWith('%%EOF', trim($pdfBytes));

        $this->actingAs($studentA)
            ->get('/guru/student-grades/export/excel')
            ->assertForbidden();
        $this->get('/guru/student-grades/export/pdf')
            ->assertForbidden();
    }

    public function test_teacher_and_student_cannot_change_school_or_class_through_self_service_profiles(): void
    {
        $teacher = $this->user('guru', 'profile-school-teacher', 'Sekolah A');
        $student = $this->user('siswa', 'profile-school-student', 'Sekolah A');

        $this->actingAs($teacher)
            ->patchJson('/guru/profile', [
                'name' => $teacher->name,
                'username' => $teacher->username,
                'email' => $teacher->email,
                'mata_pelajaran' => 'IPA',
                'sekolah' => 'Sekolah B',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sekolah');
        $this->assertSame('Sekolah A', $teacher->fresh()->sekolah);

        $this->actingAs($student)
            ->patchJson('/siswa/profil', [
                'name' => $student->name,
                'username' => $student->username,
                'email' => $student->email,
                'kelas' => 'VII B',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('kelas');
        $this->assertSame('VII A', $student->fresh()->kelas);
        $this->assertSame('Sekolah A', $student->fresh()->sekolah);

        $this->patchJson('/siswa/profil', [
            'name' => $student->name,
            'username' => $student->username,
            'email' => $student->email,
            'kelas' => 'VII A',
            'sekolah' => 'Sekolah B',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sekolah');
        $this->assertSame('VII A', $student->fresh()->kelas);
        $this->assertSame('Sekolah A', $student->fresh()->sekolah);
    }

    public function test_student_material_quiz_and_evaluation_access_is_scoped_to_school(): void
    {
        $teacherA = $this->user('guru', 'content-school-a-teacher', 'Sekolah A');
        $teacherB = $this->user('guru', 'content-school-b-teacher', 'Sekolah B');
        $studentA = $this->user('siswa', 'content-school-a-student', 'Sekolah A');
        $chapterA = $this->chapter($teacherA, 8, 'Materi Sekolah A');
        $chapterB = $this->chapter($teacherB, 8, 'Materi Sekolah B');
        $subchapterA = $this->subchapter($chapterA);
        $subchapterB = $this->subchapter($chapterB);

        $materials = $this->actingAs($studentA)
            ->getJson('/siswa/materials')
            ->assertOk();
        $this->assertSame([$chapterA->id], collect($materials->json('chapters'))->pluck('id')->all());
        $this->getJson("/siswa/quiz/subchapters/{$subchapterB->id}/questions")
            ->assertNotFound();
        $this->postJson("/siswa/subchapters/{$subchapterB->id}/open")
            ->assertNotFound();

        config(['app.evaluation_default_teacher_id' => $teacherB->id]);
        $this->getJson('/siswa/evaluations/teachers')
            ->assertOk()
            ->assertJsonPath('default_teacher_id', null)
            ->assertJsonPath('message', 'Evaluasi Akhir belum tersedia untuk sekolah ini.');
        $this->getJson("/siswa/evaluations/teachers/{$teacherB->id}/state")
            ->assertNotFound();
    }

    public function test_forum_lists_details_and_replies_are_isolated_by_school(): void
    {
        $teacherA = $this->user('guru', 'forum-school-a-teacher', 'Sekolah A');
        $teacherB = $this->user('guru', 'forum-school-b-teacher', 'Sekolah B');
        $studentA = $this->user('siswa', 'forum-school-a-student', 'Sekolah A');
        $chapterA = $this->chapter($teacherA, 9, 'Bab Forum A');
        $chapterB = $this->chapter($teacherB, 9, 'Bab Forum B');
        $this->subchapter($chapterA);
        $this->subchapter($chapterB);
        $discussionA = $this->discussion($studentA, 'Diskusi sekolah A');
        $studentB = $this->user('siswa', 'forum-school-b-student', 'Sekolah B');
        $discussionB = $this->discussion($studentB, 'Diskusi sekolah B');

        $this->actingAs($studentA)
            ->getJson('/forum/discussions')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $discussionA->id);
        $this->getJson("/forum/discussions/{$discussionB->id}")->assertNotFound();
        $this->postJson("/forum/discussions/{$discussionB->id}/replies", [
            'body' => 'Balasan lintas sekolah tidak diizinkan.',
        ])->assertNotFound();

        $this->actingAs($teacherA)
            ->getJson('/guru/discussions')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $discussionA->id);
        $this->getJson("/guru/discussions/{$discussionB->id}")->assertNotFound();
    }

    private function user(string $role, string $username, string $school): User
    {
        return User::create([
            'name' => ucfirst($role) . ' SALUANG',
            'username' => $username,
            'email' => "{$username}@example.test",
            'password' => 'secret123',
            'role' => $role,
            'kelas' => $role === 'siswa' ? 'VII A' : null,
            'sekolah' => $school,
        ]);
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

    private function chapter(User $teacher, int $number, string $title): Chapter
    {
        return Chapter::create([
            'user_id' => $teacher->id,
            'nomor_bab' => $number,
            'judul' => $title,
            'is_published' => true,
        ]);
    }

    private function subchapter(Chapter $chapter): Subchapter
    {
        return Subchapter::create([
            'chapter_id' => $chapter->id,
            'nomor_subbab' => 1,
            'judul' => 'Subbab',
            'konten' => 'Materi',
            'is_published' => true,
        ]);
    }

    private function discussion(User $author, string $title): Discussion
    {
        return Discussion::create([
            'user_id' => $author->id,
            'chapter' => 'bab-9',
            'subchapter' => '9.1',
            'title' => $title,
            'body' => 'Isi diskusi untuk pengujian.',
        ]);
    }
}
