<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EvaluationResultService;
use App\Services\StudentGradeService;
use App\Services\WebsiteNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StudentFeedbackController extends Controller
{
    private function ensureGuru(Request $request): void
    {
        abort_unless(
            $request->user()?->role === 'guru',
            403,
            'Hanya Guru yang dapat mengelola catatan.'
        );
    }

    private function ensureSiswa(User $student): void
    {
        abort_unless(
            $student->role === 'siswa',
            404,
            'Siswa tidak ditemukan.'
        );
    }

    public function show(
        Request $request,
        User $student,
        EvaluationResultService $evaluationResults,
        StudentGradeService $grades
    ): JsonResponse {
        $this->ensureGuru($request);
        $this->ensureSiswa($student);

        $data = $request->validate([
            'audience' => ['sometimes', 'in:orang_tua,siswa'],
            'assessment_type' => ['sometimes', 'required_with:assessment_result_id', 'in:quiz,evaluation'],
            'assessment_result_id' => ['sometimes', 'required_with:assessment_type', 'integer', 'min:1'],
        ]);

        $audience = $data['audience'] ?? 'orang_tua';
        $assessment = $this->resolveAssessment(
            $request,
            $student,
            $evaluationResults,
            $data
        );
        abort_unless(
            $assessment !== null
                || $grades->studentIsAssignedToTeacher((int) $request->user()->id, $student),
            404,
            'Siswa tidak ditemukan pada kelas yang ditugaskan kepada Guru ini.'
        );

        $query = DB::table('student_teacher_notes')
            ->where('student_id', $student->id)
            ->where('teacher_id', $request->user()->id)
            ->where('audience', $audience)
            ->whereNull('published_at');

        if ($assessment !== null) {
            $query->where('assessment_type', $assessment['assessment_type'])
                ->where('assessment_result_id', $assessment['id']);
        } else {
            $query->whereNull('assessment_type')
                ->whereNull('assessment_result_id');
        }

        $draft = $query->orderByDesc('id')->first(['note', 'updated_at']);

        return response()->json([
            'note' => $draft?->note ?? '',
            'updated_at' => $draft?->updated_at,
            'published_at' => null,
            'status' => $draft ? 'draf' : 'kosong',
        ]);
    }

    public function update(
        Request $request,
        User $student,
        EvaluationResultService $evaluationResults,
        WebsiteNotificationService $notifications,
        StudentGradeService $grades
    ): JsonResponse {
        $this->ensureGuru($request);
        $this->ensureSiswa($student);

        $data = $request->validate([
            'note' => ['required', 'string', 'max:5000', 'not_regex:/^\s*$/'],
            'audience' => ['sometimes', 'in:orang_tua,siswa'],
            'action' => ['sometimes', 'in:draft,publish'],
            'assessment_type' => ['sometimes', 'required_with:assessment_result_id', 'in:quiz,evaluation'],
            'assessment_result_id' => ['sometimes', 'required_with:assessment_type', 'integer', 'min:1'],
        ]);

        $audience = $data['audience'] ?? 'orang_tua';
        $action = $data['action'] ?? 'draft';
        $note = trim($data['note']);
        $now = now();
        $assessment = $this->resolveAssessment(
            $request,
            $student,
            $evaluationResults,
            $data
        );
        abort_unless(
            $assessment !== null
                || $grades->studentIsAssignedToTeacher((int) $request->user()->id, $student),
            404,
            'Siswa tidak ditemukan pada kelas yang ditugaskan kepada Guru ini.'
        );
        $assessmentType = $assessment['assessment_type'] ?? null;
        $assessmentResultId = $assessment['id'] ?? null;

        if ($action === 'draft') {
            if ($assessmentType !== null) {
                DB::table('student_teacher_notes')->upsert(
                    [[
                        'student_id' => $student->id,
                        'teacher_id' => $request->user()->id,
                        'audience' => $audience,
                        'assessment_type' => $assessmentType,
                        'assessment_result_id' => $assessmentResultId,
                        'note' => $note,
                        'published_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]],
                    [
                        'student_id',
                        'teacher_id',
                        'audience',
                        'assessment_type',
                        'assessment_result_id',
                    ],
                    ['note', 'published_at', 'updated_at']
                );
            } else {
                DB::transaction(function () use ($student, $request, $audience, $note, $now) {
                    $draftQuery = DB::table('student_teacher_notes')
                        ->where('student_id', $student->id)
                        ->where('teacher_id', $request->user()->id)
                        ->where('audience', $audience)
                        ->whereNull('published_at')
                        ->whereNull('assessment_type')
                        ->whereNull('assessment_result_id');

                    $draft = $draftQuery->orderByDesc('id')->lockForUpdate()->first();
                    if ($draft) {
                        DB::table('student_teacher_notes')
                            ->where('id', $draft->id)
                            ->update([
                                'note' => $note,
                                'published_at' => null,
                                'updated_at' => $now,
                            ]);
                    } else {
                        DB::table('student_teacher_notes')->insert([
                            'student_id' => $student->id,
                            'teacher_id' => $request->user()->id,
                            'audience' => $audience,
                            'assessment_type' => null,
                            'assessment_result_id' => null,
                            'note' => $note,
                            'published_at' => null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                });
            }

            return response()->json([
                'message' => 'Draf catatan berhasil disimpan.',
                'note' => $note,
                'status' => 'draf',
                'published_at' => null,
                'assessment_type' => $assessmentType,
                'assessment_result_id' => $assessmentResultId,
            ]);
        }

        if ($audience === 'orang_tua') {
            $hasApprovedGuardian = Schema::hasTable('student_guardians')
                && DB::table('student_guardians as relation')
                    ->join('users as guardian', 'guardian.id', '=', 'relation.guardian_id')
                    ->where('relation.student_id', $student->id)
                    ->where('relation.status', 'approved')
                    ->where('guardian.role', 'orang_tua')
                    ->exists();
            abort_if(
                !$hasApprovedGuardian,
                422,
                'Belum ada akun Orang Tua terhubung dan disetujui. Draf tetap tersimpan; hubungkan Orang Tua sebelum mengirim catatan.'
            );
        }

        $message = DB::transaction(function () use (
            $student,
            $request,
            $audience,
            $note,
            $now,
            $assessmentType,
            $assessmentResultId,
            $notifications
        ) {
            $id = DB::table('student_teacher_note_messages')->insertGetId([
                'student_id' => $student->id,
                'teacher_id' => $request->user()->id,
                'audience' => $audience,
                'assessment_type' => $assessmentType,
                'assessment_result_id' => $assessmentResultId,
                'note' => $note,
                'published_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $notifications->publishedTeacherNote((int) $student->id, $id);

            // Kosongkan draf setelah catatan berhasil dikirim.
            DB::table('student_teacher_notes')
                ->where('student_id', $student->id)
                ->where('teacher_id', $request->user()->id)
                ->where('audience', $audience)
                ->when(
                    $assessmentType !== null,
                    fn ($query) => $query
                        ->where('assessment_type', $assessmentType)
                        ->where('assessment_result_id', $assessmentResultId),
                    fn ($query) => $query
                        ->whereNull('assessment_type')
                        ->whereNull('assessment_result_id')
                )
                ->delete();

            return $id;
        });

        return response()->json([
            'message' => 'Catatan berhasil dikirim ke Orang Tua/Wali.',
            'id' => $message,
            'note' => '',
            'status' => 'terkirim',
            'published_at' => $now->toIso8601String(),
            'assessment_type' => $assessmentType,
            'assessment_result_id' => $assessmentResultId,
        ]);
    }

    private function resolveAssessment(
        Request $request,
        User $student,
        EvaluationResultService $evaluationResults,
        array $data
    ): ?array {
        $assessmentType = $data['assessment_type'] ?? null;
        $assessmentResultId = $data['assessment_result_id'] ?? null;

        if ($assessmentType === null && $assessmentResultId === null) {
            return null;
        }

        abort_if(
            $assessmentType === null || $assessmentResultId === null,
            422,
            'Jenis dan ID hasil pengerjaan harus dikirim bersama.'
        );

        $assessment = $evaluationResults->findForTeacher(
            (int) $request->user()->id,
            (int) $student->id,
            $assessmentType,
            (int) $assessmentResultId
        );

        abort_if(
            $assessment === null,
            404,
            'Hasil pengerjaan tidak ditemukan atau bukan wewenang Guru ini.'
        );

        return $assessment;
    }
}
