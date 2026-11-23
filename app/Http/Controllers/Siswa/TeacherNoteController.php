<?php
namespace App\Http\Controllers\Siswa;
use App\Http\Controllers\Controller;
use App\Services\EvaluationResultService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
class TeacherNoteController extends Controller
{
    public function index(
        Request $request,
        EvaluationResultService $evaluationResults
    ): JsonResponse
    {
        abort_unless($request->user()?->role === 'siswa', 403);
        if (
            !Schema::hasTable('student_teacher_note_messages')
            || !Schema::hasColumn('student_teacher_note_messages', 'audience')
            || !Schema::hasColumn('student_teacher_note_messages', 'published_at')
        ) {
            return response()->json(['notes' => []]);
        }
        $hasAssessmentContext = Schema::hasColumn('student_teacher_note_messages', 'assessment_type')
            && Schema::hasColumn('student_teacher_note_messages', 'assessment_result_id');
        $columns = [
            'n.teacher_id',
            'teacher.name as teacher_name',
            'n.note',
            'n.published_at as updated_at',
        ];
        if ($hasAssessmentContext) {
            $columns[] = 'n.assessment_type';
            $columns[] = 'n.assessment_result_id';
        }
        $notes = DB::table('student_teacher_note_messages as n')
            ->join('users as teacher', 'teacher.id', '=', 'n.teacher_id')
            ->where('n.student_id', $request->user()->id)
            ->where('n.audience', 'siswa')
            ->whereNotNull('n.published_at')
            ->where('teacher.role', 'guru')
            ->orderByDesc('n.published_at')
            ->orderByDesc('n.id')
            ->get($columns);

        $quizResults = collect($evaluationResults->quizResultsForStudent(
            (int) $request->user()->id
        ))->keyBy('id');
        $evaluationAttempts = collect($evaluationResults->validCompletedForStudent(
            (int) $request->user()->id
        ))->keyBy('id');
        $notes = $notes->map(function (object $note) use (
            $hasAssessmentContext,
            $quizResults,
            $evaluationAttempts
        ): array {
            $type = $hasAssessmentContext ? $note->assessment_type : null;
            $resultId = $hasAssessmentContext ? (int) ($note->assessment_result_id ?? 0) : 0;
            $result = $type === 'quiz'
                ? $quizResults->get($resultId)
                : ($type === 'evaluation' ? $evaluationAttempts->get($resultId) : null);
            if ($result && (int) ($result['teacher_id'] ?? 0) !== (int) $note->teacher_id) {
                $result = null;
            }

            return [
                'teacher_name' => $note->teacher_name,
                'note' => $note->note,
                'updated_at' => $note->updated_at,
                'assessment_label' => $type === 'quiz'
                    ? 'Kuis Subbab'
                    : ($type === 'evaluation' ? 'Evaluasi Akhir' : 'Catatan umum'),
                'material' => $type === 'evaluation'
                    ? 'Evaluasi Akhir'
                    : ($result['subchapter_title'] ?? $result['chapter_title'] ?? null),
            ];
        });

        return response()->json(['notes' => $notes]);
    }
}
