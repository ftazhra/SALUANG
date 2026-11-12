<?php

namespace Tests\Feature;

use App\Models\Chapter;
use App\Models\Subchapter;
use App\Models\User;
use App\Services\ParentDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EvaluationResultsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_grade_uses_latest_quiz_per_published_subchapter_and_waits_for_all_components(): void
    {
        $teacher = $this->createUser('guru', 'guru-final-grade');
        $completeStudent = $this->createUser('siswa', 'siswa-final-grade-complete');
        $incompleteStudent = $this->createUser('siswa', 'siswa-final-grade-incomplete');
        $parent = $this->createUser('orang_tua', 'parent-final-grade');
        $completeStudent->update(['kelas' => 'VII A']);
        $incompleteStudent->update(['kelas' => 'VII A']);

        $chapter = Chapter::create([
            'user_id' => $teacher->id,
            'nomor_bab' => 6,
            'judul' => 'Bab VI Nilai',
            'is_published' => true,
        ]);
        $subchapters = collect([1, 2])->map(fn (int $number) => Subchapter::create([
            'chapter_id' => $chapter->id,
            'nomor_subbab' => $number,
            'judul' => "Subbab {$number}",
            'is_published' => true,
        ]));
        DB::table('teacher_class_assignments')->insert([
            'teacher_id' => $teacher->id,
            'class_name' => 'VII A',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($subchapters as $subchapter) {
            DB::table('quiz_questions')->insert([
                'subchapter_id' => $subchapter->id,
                'nomor_soal' => 1,
                'activity_type' => 'multiple_choice',
                'activity_payload' => null,
                'pertanyaan' => "Soal {$subchapter->nomor_subbab}",
                'pilihan_a' => 'A',
                'pilihan_b' => 'B',
                'pilihan_c' => 'C',
                'pilihan_d' => 'D',
                'kunci_jawaban' => 'A',
                'pembahasan' => null,
                'is_published' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $recordQuiz = function (User $student, Subchapter $subchapter, float $score, \Carbon\CarbonInterface $completedAt) use ($chapter): void {
            DB::table('student_assessment_results')->insert([
                'user_id' => $student->id,
                'assessment_type' => 'quiz',
                'chapter_id' => $chapter->id,
                'subchapter_id' => $subchapter->id,
                'total_questions' => 10,
                'correct_answers' => (int) round($score / 10),
                'score' => $score,
                'completed_at' => $completedAt,
                'created_at' => $completedAt,
                'updated_at' => $completedAt,
            ]);
        };
        $recordQuiz($completeStudent, $subchapters[0], 100, now()->subDays(2));
        $recordQuiz($completeStudent, $subchapters[0], 60, now()->subDay());
        $recordQuiz($completeStudent, $subchapters[1], 80, now());
        $recordQuiz($incompleteStudent, $subchapters[0], 90, now());
        $evaluationId = $this->createCompletedAttempt(
            $completeStudent,
            $teacher,
            true,
            $chapter->id
        );
        DB::table('evaluation_questions')
            ->where('user_id', $teacher->id)
            ->where('nomor_soal', 1)
            ->update([
                'pertanyaan' => 'Soal evaluasi diperbarui tanpa mengganti bank',
                'updated_at' => now(),
            ]);
        DB::table('evaluation_attempts')->where('id', $evaluationId)->update([
            'score' => 90,
            'correct_count' => 13,
        ]);
        $this->link($completeStudent, $parent, 'approved');

        $response = $this->actingAs($teacher)
            ->getJson('/guru/student-grades')
            ->assertOk()
            ->assertJsonPath('grading_configured', true);
        $students = collect($response->json('data'))->keyBy('id');

        $complete = $students[$completeStudent->id];
        $this->assertSame(70.0, (float) $complete['quiz_score']);
        $this->assertSame(90.0, (float) $complete['evaluation_score']);
        $this->assertSame(80.0, (float) $complete['final_score']);
        $this->assertSame('tuntas', $complete['completion_status']);
        $this->assertSame(2, $complete['quiz_completed_count']);
        $this->assertSame(2, $complete['quiz_required_count']);
        $this->actingAs($completeStudent)
            ->getJson('/siswa/progress')
            ->assertOk()
            ->assertJsonPath('final_grades.0.final_score', 80)
            ->assertJsonPath('final_grades.0.completion_status', 'tuntas');
        $parentDashboard = app(ParentDashboardService::class)->dashboard($parent);
        $this->assertSame(80.0, (float) $parentDashboard['finalGrades'][0]['final_score']);
        $this->assertSame(80.0, (float) $parentDashboard['stats']['average']);

        $incomplete = $students[$incompleteStudent->id];
        $this->assertNull($incomplete['final_score']);
        $this->assertSame('belum_dinilai', $incomplete['completion_status']);
    }

    public function test_archived_evaluation_attempt_remains_in_history_but_does_not_count_for_grading(): void
    {
        $teacher = $this->createUser('guru', 'guru-archived-evaluation');
        $student = $this->createUser('siswa', 'siswa-archived-evaluation');
        $freshStudent = $this->createUser('siswa', 'siswa-fresh-evaluation');
        $parent = $this->createUser('orang_tua', 'parent-archived-evaluation');
        $student->update(['name' => 'Siswa Arsip']);
        $freshStudent->update(['name' => 'Siswa Baru']);
        $student->update(['kelas' => 'VII B']);
        $freshStudent->update(['kelas' => 'VII B']);

        $chapter = Chapter::create([
            'user_id' => $teacher->id,
            'nomor_bab' => 6,
            'judul' => 'Bab VI Arsip Evaluasi',
            'is_published' => true,
        ]);
        $subchapter = Subchapter::create([
            'chapter_id' => $chapter->id,
            'nomor_subbab' => 1,
            'judul' => 'Subbab Aktif',
            'is_published' => true,
        ]);
        DB::table('teacher_class_assignments')->insert([
            'teacher_id' => $teacher->id,
            'class_name' => 'VII B',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->link($student, $parent, 'approved');
        DB::table('quiz_questions')->insert([
            'subchapter_id' => $subchapter->id,
            'nomor_soal' => 1,
            'activity_type' => 'multiple_choice',
            'activity_payload' => null,
            'pertanyaan' => 'Soal Kuis',
            'pilihan_a' => 'A',
            'pilihan_b' => 'B',
            'pilihan_c' => 'C',
            'pilihan_d' => 'D',
            'kunci_jawaban' => 'A',
            'pembahasan' => null,
            'is_published' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('student_assessment_results')->insert([
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
        DB::table('student_assessment_results')->insert([
            'user_id' => $freshStudent->id,
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

        $attemptId = $this->createCompletedAttempt(
            $student,
            $teacher,
            true,
            $chapter->id
        );
        $snapshot = json_decode(
            DB::table('evaluation_attempts')->where('id', $attemptId)->value('question_snapshot'),
            true
        );
        $archivedQuestionId = (int) $snapshot[0]['id'];
        $submittedAt = now()->subMinute();
        DB::table('evaluation_attempts')->where('id', $attemptId)->update([
            'submitted_at' => $submittedAt,
        ]);
        DB::table('evaluation_question_archives')->insert([
            'question_id' => $archivedQuestionId,
            'teacher_id' => $teacher->id,
            'question_snapshot' => json_encode([
                'id' => 88001,
                '_attempt_id_cutoff' => $attemptId,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $freshAttemptId = $this->createCompletedAttempt(
            $freshStudent,
            $teacher,
            false,
            $chapter->id
        );
        DB::table('evaluation_attempts')->where('id', $freshAttemptId)->update([
            'submitted_at' => now(),
        ]);

        $results = app(\App\Services\EvaluationResultService::class)
            ->completedForTeacher((int) $teacher->id, [(int) $student->id]);
        $this->assertCount(1, $results);
        $this->assertFalse($results[0]['valid_for_grading']);
        $this->assertSame([], app(\App\Services\EvaluationResultService::class)
            ->validCompletedForTeacher((int) $teacher->id, [(int) $student->id]));
        $this->assertSame([], app(\App\Services\EvaluationResultService::class)
            ->latestByStudentForTeacher((int) $teacher->id, [(int) $student->id]));
        $freshLatest = app(\App\Services\EvaluationResultService::class)
            ->latestByStudentForTeacher((int) $teacher->id, [(int) $freshStudent->id]);
        $this->assertTrue($freshLatest[$freshStudent->id]['valid_for_grading']);

        $studentGrade = collect($this->actingAs($teacher)
            ->getJson('/guru/student-grades')
            ->assertOk()
            ->json('data'))->firstWhere('id', $student->id);
        $this->assertNull($studentGrade['evaluation_score']);
        $this->assertNull($studentGrade['final_score']);
        $this->assertSame('belum_dinilai', $studentGrade['completion_status']);
        $this->getJson("/guru/student-grades/{$student->id}")
            ->assertOk()
            ->assertJsonMissing([
                'assessment_type' => 'evaluation',
                'score' => 100,
            ]);
        $studentProgress = $this->actingAs($student)
            ->getJson('/siswa/progress')
            ->assertOk()
            ->assertJsonPath('completed_evaluations', 0)
            ->assertJsonPath('final_grades.0.quiz_score', 80)
            ->assertJsonPath('final_grades.0.evaluation_score', null)
            ->assertJsonPath('final_grades.0.final_score', null)
            ->assertJsonPath('final_grades.0.completion_status', 'belum_dinilai');
        $this->assertFalse(collect($studentProgress->json('activities'))
            ->contains(fn (array $activity) => $activity['type'] === 'evaluation'));
        $parentDashboard = app(ParentDashboardService::class)->dashboard($parent);
        $this->assertSame($student->id, $parentDashboard['child']->id);
        $this->assertSame(80.0, (float) $parentDashboard['learningResults'][0]['score']);
        $this->assertSame('kuis', $parentDashboard['learningResults'][0]['type']);
        $this->assertSame([], collect($parentDashboard['learningResults'])
            ->where('type', 'evaluasi')
            ->values()
            ->all());
        $excel = $this->actingAs($teacher)
            ->get('/guru/student-grades/export/excel')
            ->assertOk();
        $temporaryFile = tmpfile();
        $this->assertIsResource($temporaryFile);
        fwrite($temporaryFile, $excel->streamedContent());
        rewind($temporaryFile);
        $workbook = \PhpOffice\PhpSpreadsheet\IOFactory::load(
            stream_get_meta_data($temporaryFile)['uri']
        );
        $sheet = $workbook->getActiveSheet();
        $studentRow = null;
        for ($row = 4; $row <= $sheet->getHighestRow(); $row++) {
            if ($sheet->getCell("A{$row}")->getValue() === $student->name) {
                $studentRow = $row;
                break;
            }
        }
        $this->assertNotNull($studentRow);
        $this->assertNull($sheet->getCell("D{$studentRow}")->getValue());
        $this->assertSame('Belum Dinilai', $sheet->getCell("E{$studentRow}")->getValue());
        $this->assertSame('Riwayat tersedia', $sheet->getCell("F{$studentRow}")->getValue());
        $workbook->disconnectWorksheets();
        fclose($temporaryFile);
        $freshGrade = collect($this->actingAs($teacher)
            ->getJson('/guru/student-grades')
            ->assertOk()
            ->json('data'))->firstWhere('id', $freshStudent->id);
        $this->assertSame(0.0, (float) $freshGrade['evaluation_score']);
        $this->assertSame(40.0, (float) $freshGrade['final_score']);
        $this->assertDatabaseHas('evaluation_attempts', [
            'id' => $attemptId,
            'score' => 100,
        ]);
    }

    public function test_bank_attempt_counts_only_while_its_published_bank_is_active_for_the_student_class(): void
    {
        $teacher = $this->createUser('guru', 'guru-active-evaluation-bank');
        $student = $this->createUser('siswa', 'student-active-evaluation-bank');
        $student->update(['kelas' => 'VII C']);
        $chapter = Chapter::create([
            'user_id' => $teacher->id,
            'nomor_bab' => 6,
            'judul' => 'Bab VI Bank Aktif',
            'is_published' => true,
        ]);
        $bankId = DB::table('evaluation_banks')->insertGetId([
            'chapter_id' => $chapter->id,
            'teacher_id' => $teacher->id,
            'class_name' => 'VII C',
            'title' => 'Evaluasi Aktif',
            'is_published' => true,
            'active_slot' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $attemptId = $this->createCompletedAttempt(
            $student,
            $teacher,
            true,
            $chapter->id,
            $bankId
        );
        $results = app(\App\Services\EvaluationResultService::class);
        $this->assertSame(
            $attemptId,
            $results->validCompletedForStudent((int) $student->id)[0]['id']
        );

        DB::table('evaluation_banks')->where('id', $bankId)->update([
            'is_published' => false,
            'active_slot' => null,
            'updated_at' => now(),
        ]);
        $this->assertSame([], $results->validCompletedForStudent((int) $student->id));
        $this->assertDatabaseHas('evaluation_attempts', [
            'id' => $attemptId,
            'score' => 100,
        ]);
    }

    public function test_legacy_evaluation_ownership_is_inferred_from_complete_snapshot_without_mutating_history(): void
    {
        $teacher = $this->createUser('guru', 'guru-legacy-attribution');
        $otherTeacher = $this->createUser('guru', 'guru-legacy-attribution-other');
        $student = $this->createUser('siswa', 'siswa-legacy-attribution');
        $parent = $this->createUser('orang_tua', 'parent-legacy-attribution');
        $unrelatedParent = $this->createUser('orang_tua', 'parent-legacy-attribution-other');
        $chapter = Chapter::create([
            'user_id' => $teacher->id,
            'nomor_bab' => 6,
            'judul' => 'Bab VI Integrasi',
            'is_published' => true,
        ]);
        $subchapter = Subchapter::create([
            'chapter_id' => $chapter->id,
            'nomor_subbab' => 1,
            'judul' => 'Subbab Integrasi',
            'is_published' => true,
        ]);
        DB::table('quiz_questions')->insert([
            'subchapter_id' => $subchapter->id,
            'nomor_soal' => 1,
            'activity_type' => 'multiple_choice',
            'activity_payload' => null,
            'pertanyaan' => 'Pertanyaan Kuis',
            'pilihan_a' => 'A',
            'pilihan_b' => 'B',
            'pilihan_c' => 'C',
            'pilihan_d' => 'D',
            'kunci_jawaban' => 'A',
            'pembahasan' => null,
            'is_published' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('student_assessment_results')->insert([
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
        $verifiedLegacyAttemptId = $this->createCompletedAttempt(
            $student,
            $teacher,
            true,
            $chapter->id
        );
        DB::table('evaluation_attempts')
            ->where('id', $verifiedLegacyAttemptId)
            ->update([
                'teacher_id' => null,
                'score' => 90,
                'correct_count' => 13,
            ]);

        $unverifiedAttemptId = $this->createCompletedAttempt(
            $student,
            $teacher,
            false,
            $chapter->id
        );
        $unverifiedSnapshot = json_decode(
            DB::table('evaluation_attempts')
                ->where('id', $unverifiedAttemptId)
                ->value('question_snapshot'),
            true
        );
        foreach ($unverifiedSnapshot as &$question) {
            unset($question['user_id']);
        }
        unset($question);
        DB::table('evaluation_attempts')
            ->where('id', $unverifiedAttemptId)
            ->update([
                'teacher_id' => null,
                'question_snapshot' => json_encode($unverifiedSnapshot),
            ]);
        $this->link($student, $parent, 'approved');

        $teacherRows = $this->actingAs($teacher)
            ->getJson('/guru/student-grades')
            ->assertOk()
            ->json('data');
        $teacherGrade = collect($teacherRows)->firstWhere('id', $student->id);
        $this->assertSame(80.0, (float) $teacherGrade['quiz_score']);
        $this->assertSame(90.0, (float) $teacherGrade['evaluation_score']);
        $this->assertSame(85.0, (float) $teacherGrade['final_score']);
        $this->assertSame('tuntas', $teacherGrade['completion_status']);

        $teacherDetail = $this->getJson("/guru/student-grades/{$student->id}")
            ->assertOk()
            ->assertJsonCount(2, 'results');
        $this->assertSame(
            [90.0],
            collect($teacherDetail->json('results'))
                ->where('assessment_type', 'evaluation')
                ->pluck('score')
                ->map(fn ($score) => (float) $score)
                ->values()
                ->all()
        );
        $this->actingAs($otherTeacher)
            ->getJson("/guru/student-grades/{$student->id}")
            ->assertOk()
            ->assertJsonCount(0, 'results')
            ->assertJsonPath('student.grading.evaluation_score', null);

        $studentProgress = $this->actingAs($student)
            ->getJson('/siswa/progress')
            ->assertOk()
            ->assertJsonPath('final_grades.0.evaluation_score', 90)
            ->assertJsonPath('final_grades.0.final_score', 85)
            ->assertJsonPath('completed_evaluations', 1);

        $parentDashboard = app(ParentDashboardService::class)->dashboard($parent);
        $this->assertSame(85.0, (float) $parentDashboard['finalGrades'][0]['final_score']);
        $parentEvaluations = collect($parentDashboard['learningResults'])
            ->where('type', 'evaluasi');
        $this->assertCount(1, $parentEvaluations);
        $this->assertSame(
            ['verified'],
            $parentEvaluations->pluck('attribution_status')->values()->all()
        );
        $this->assertNull(
            app(ParentDashboardService::class)->dashboard($unrelatedParent)['child']
        );

        $excel = $this->actingAs($teacher)
            ->get('/guru/student-grades/export/excel')
            ->assertOk();
        $temporaryFile = tmpfile();
        $this->assertIsResource($temporaryFile);
        fwrite($temporaryFile, $excel->streamedContent());
        rewind($temporaryFile);
        $workbook = \PhpOffice\PhpSpreadsheet\IOFactory::load(
            stream_get_meta_data($temporaryFile)['uri']
        );
        $this->assertSame(
            85.0,
            (float) $workbook->getActiveSheet()->getCell('D4')->getValue()
        );
        $this->assertSame(
            'Tuntas',
            $workbook->getActiveSheet()->getCell('E4')->getValue()
        );
        $workbook->disconnectWorksheets();
        fclose($temporaryFile);
        $this->assertSame(2, DB::table('evaluation_attempts')
            ->where('user_id', $student->id)
            ->whereNull('teacher_id')
            ->count());
    }

    public function test_global_grade_list_includes_unassigned_students_and_quiz_results_follow_the_content_owner(): void
    {
        $teacher = $this->createUser('guru', 'guru-global-grade-owner');
        $otherTeacher = $this->createUser('guru', 'guru-global-grade-other');
        $firstStudent = $this->createUser('siswa', 'siswa-global-grade-one');
        $secondStudent = $this->createUser('siswa', 'siswa-global-grade-luna');
        $thirdStudent = $this->createUser('siswa', 'siswa-global-grade-three');
        $parent = $this->createUser('orang_tua', 'parent-global-grade');
        $unrelatedParent = $this->createUser('orang_tua', 'parent-global-grade-unrelated');

        $firstStudent->update(['kelas' => 'VII A']);
        $secondStudent->update(['kelas' => 'IX C', 'sekolah' => 'Sekolah Berbeda']);
        $thirdStudent->update(['kelas' => 'VI B']);

        $chapter = Chapter::create([
            'user_id' => $teacher->id,
            'nomor_bab' => 6,
            'judul' => 'Bab VI Kuis',
            'is_published' => true,
        ]);
        $subchapter = Subchapter::create([
            'chapter_id' => $chapter->id,
            'nomor_subbab' => 1,
            'judul' => 'Subbab Kuis',
            'is_published' => true,
        ]);
        DB::table('quiz_questions')->insert([
            'subchapter_id' => $subchapter->id,
            'nomor_soal' => 1,
            'activity_type' => 'multiple_choice',
            'activity_payload' => null,
            'pertanyaan' => 'Pertanyaan Kuis',
            'pilihan_a' => 'A',
            'pilihan_b' => 'B',
            'pilihan_c' => 'C',
            'pilihan_d' => 'D',
            'kunci_jawaban' => 'A',
            'pembahasan' => null,
            'is_published' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('student_assessment_results')->insert([
            'user_id' => $secondStudent->id,
            'assessment_type' => 'quiz',
            'chapter_id' => $chapter->id,
            'subchapter_id' => $subchapter->id,
            'total_questions' => 1,
            'correct_answers' => 1,
            'score' => 100,
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->link($secondStudent, $parent, 'approved');

        $teacherGrades = $this->actingAs($teacher)
            ->getJson('/guru/student-grades')
            ->assertOk()
            ->assertJsonPath('total', 3);
        $gradeRows = collect($teacherGrades->json('data'))->keyBy('id');
        $this->assertSame(100.0, (float) $gradeRows[$secondStudent->id]['quiz_score']);
        $this->assertSame('belum_dinilai', $gradeRows[$secondStudent->id]['completion_status']);
        $this->assertSame('belum_dinilai', $gradeRows[$firstStudent->id]['completion_status']);
        $this->assertSame('belum_dinilai', $gradeRows[$thirdStudent->id]['completion_status']);

        $this->getJson("/guru/student-grades/{$secondStudent->id}")
            ->assertOk()
            ->assertJsonPath('results.0.score', 100)
            ->assertJsonPath('results.0.assessment_type', 'quiz');
        $this->actingAs($otherTeacher)
            ->getJson("/guru/student-grades/{$secondStudent->id}")
            ->assertOk()
            ->assertJsonCount(0, 'results')
            ->assertJsonPath('student.grading.quiz_score', null);

        $parentDashboard = app(ParentDashboardService::class)->dashboard($parent);
        $this->assertSame($secondStudent->id, $parentDashboard['child']->id);
        $this->assertSame($teacher->id, $parentDashboard['finalGrades'][0]['teacher_id']);
        $this->assertSame(100.0, (float) $parentDashboard['learningResults'][0]['score']);
        $this->assertSame($teacher->id, $parentDashboard['learningResults'][0]['teacher_id']);
        $this->assertNull(app(ParentDashboardService::class)->dashboard($unrelatedParent)['child']);
    }

    public function test_completed_attempts_are_shared_with_the_authorized_teacher_and_approved_parent_without_duplicate_results(): void
    {
        $teacher = $this->createUser('guru', 'guru-evaluation-owner');
        $otherTeacher = $this->createUser('guru', 'guru-evaluation-other');
        $student = $this->createUser('siswa', 'siswa-evaluation');
        $parent = $this->createUser('orang_tua', 'parent-evaluation');
        $unrelatedParent = $this->createUser('orang_tua', 'parent-unrelated-evaluation');
        $pendingParent = $this->createUser('orang_tua', 'parent-pending-evaluation');
        $student->update(['kelas' => 'VII A']);

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
        $historicalBankId = DB::table('evaluation_banks')->insertGetId([
            'chapter_id' => $chapter->id,
            'teacher_id' => $teacher->id,
            'class_name' => 'VII A',
            'title' => 'Arsip Evaluasi Bab VI',
            'is_published' => false,
            'active_slot' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('teacher_class_assignments')->insert([
            'teacher_id' => $teacher->id,
            'class_name' => 'VII A',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->link($student, $parent, 'approved');
        $this->link($student, $pendingParent, 'pending');

        $firstAttemptId = $this->createCompletedAttempt($student, $teacher, false);
        $secondAttemptId = $this->createCompletedAttempt(
            $student,
            $teacher,
            true,
            $chapter->id,
            $historicalBankId
        );

        $this->assertNotSame($firstAttemptId, $secondAttemptId);
        $this->assertDatabaseCount('evaluation_attempts', 2);
        $this->assertDatabaseCount('student_assessment_results', 0);

        $studentState = $this->actingAs($student)
            ->getJson("/siswa/evaluations/teachers/{$teacher->id}/state")
            ->assertOk()
            ->assertJsonPath('status', 'completed')
            ->assertJsonPath('passed', false);
        $this->assertSame(0.0, (float) $studentState->json('attempt.score'));

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
            ->assertJsonPath('data.0.evaluation_score', 0);
        $this->assertSame($student->id, $teacherIndex->json('data.0.id'));

        $teacherDetail = $this->getJson("/guru/student-grades/{$student->id}")->assertOk();
        $teacherScores = collect($teacherDetail->json('results'))
            ->where('assessment_type', 'evaluation')
            ->pluck('score')
            ->map(fn ($score) => (float) $score)
            ->values()
            ->all();
        $this->assertSame([0.0], $teacherScores);
        $this->assertSame([null], collect($teacherDetail->json('results'))
            ->where('assessment_type', 'evaluation')
            ->pluck('chapter_id')
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
            ->assertOk()
            ->assertJsonCount(0, 'results')
            ->assertJsonPath('student.grading.evaluation_score', null);
        $this->actingAs($otherTeacher)
            ->getJson('/guru/student-grades')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.evaluation_score', null);

        $parentDashboard = app(ParentDashboardService::class)->dashboard($parent);
        $this->assertSame($student->id, $parentDashboard['child']->id);
        $this->assertCount(1, $parentDashboard['learningResults']);
        $this->assertNull($parentDashboard['stats']['average']);
        $parentScores = collect($parentDashboard['learningResults'])
            ->pluck('score')
            ->map(fn ($score) => (float) $score)
            ->sort()
            ->values()
            ->all();
        $this->assertSame([0.0], $parentScores);

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

    private function createCompletedAttempt(
        User $student,
        User $teacher,
        bool $correct,
        ?int $chapterId = null,
        ?int $bankId = null
    ): int
    {
        $activeQuestionIds = [];
        foreach (range(1, 15) as $number) {
            $question = DB::table('evaluation_questions')
                ->where('user_id', $teacher->id)
                ->where('nomor_soal', $number)
                ->first(['id']);
            if ($question) {
                $activeQuestionIds[] = (int) $question->id;
                continue;
            }

            $activeQuestionIds[] = DB::table('evaluation_questions')->insertGetId([
                'user_id' => $teacher->id,
                'nomor_soal' => $number,
                'tipe_soal' => 'pilihan_ganda',
                'pertanyaan' => "Pertanyaan aktif {$number}",
                'konten_soal' => json_encode([
                    'pilihan_a' => 'A',
                    'pilihan_b' => 'B',
                    'pilihan_c' => 'C',
                    'pilihan_d' => 'D',
                ]),
                'kunci_jawaban' => json_encode(['jawaban' => 'A']),
                'pembahasan' => null,
                'is_published' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $snapshot = [];
        foreach (range(1, 15) as $number) {
            $snapshot[] = [
                'id' => $activeQuestionIds[$number - 1],
                'user_id' => $teacher->id,
                'nomor_soal' => $number,
                'tipe_soal' => 'pilihan_ganda',
                'pertanyaan' => "Pertanyaan {$number}",
                'konten_soal' => [
                    'pilihan_a' => 'A',
                    'pilihan_b' => 'B',
                    'pilihan_c' => 'C',
                    'pilihan_d' => 'D',
                ],
                'kunci_jawaban' => ['jawaban' => 'A'],
                'pembahasan' => null,
            ];
        }

        $now = now();

        return DB::table('evaluation_attempts')->insertGetId([
            'user_id' => $student->id,
            'teacher_id' => $teacher->id,
            'chapter_id' => $chapterId,
            'evaluation_bank_id' => $bankId,
            'question_snapshot' => json_encode($snapshot),
            'answers' => json_encode($this->answers($correct)),
            'ends_at' => $now->copy()->subMinute(),
            'submitted_at' => $now,
            'score' => $correct ? 100 : 0,
            'correct_count' => $correct ? 15 : 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function answers(bool $correct): array
    {
        $answers = [];
        foreach (range(1, 15) as $number) {
            $answers[(string) $number] = $correct ? 'A' : 'B';
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