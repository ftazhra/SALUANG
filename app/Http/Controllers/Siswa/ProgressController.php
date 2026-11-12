<?php
namespace App\Http\Controllers\Siswa;
use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\QuizQuestion;
use App\Models\StudentAssessmentResult;
use App\Models\StudentSubchapterProgress;
use App\Models\Subchapter;
use App\Models\User;
use App\Services\EvaluationResultService;
use App\Services\StudentGradeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class ProgressController extends Controller
{
    public function index(
        Request $request,
        StudentGradeService $grades,
        EvaluationResultService $evaluationResults
    ): JsonResponse
    {
        $this->ensureSiswa($request);
        $chapter = Chapter::query()
            ->whereIn(
                'user_id',
                User::query()->where('role', 'guru')->select('id')
            )
            ->where('is_published', true)
            ->whereHas('guru', fn ($query) => $query->where('role', 'guru'))
            ->whereHas('subchapters', fn ($query) => $query->where('is_published', true))
            ->with(['subchapters' => fn ($query) => $query
                ->where('is_published', true)
                ->orderBy('nomor_subbab')])
            ->orderByRaw('CASE WHEN nomor_bab = 6 THEN 0 ELSE 1 END')
            ->orderBy('nomor_bab')
            ->first();
        $progress = collect();
        $completedCount = 0;
        $totalCount = 0;
        $percentage = 0;
        $status = 'Belum Dimulai';
        if ($chapter) {
            $subchapterIds = $chapter->subchapters->pluck('id');
            $progress = StudentSubchapterProgress::query()
                ->where('user_id', $request->user()->id)
                ->whereIn('subchapter_id', $subchapterIds)
                ->with('subchapter')
                ->get();
            $completedCount = $progress->where('status', 'completed')->count();
            $totalCount = $chapter->subchapters->count();
            $percentage = $totalCount > 0
                ? (int) round(($completedCount / $totalCount) * 100)
                : 0;
            if ($totalCount > 0 && $completedCount === $totalCount) {
                $status = 'Selesai';
            } elseif ($progress->isNotEmpty()) {
                $status = 'Sedang Dipelajari';
            }
        }
        $results = StudentAssessmentResult::query()
            ->where('user_id', $request->user()->id)
            ->where('assessment_type', 'quiz')
            ->whereNotNull('score')
            ->whereBetween('score', [0, 100])
            ->whereNotNull('completed_at')
            ->where('total_questions', '>', 0)
            ->where('correct_answers', '>=', 0)
            ->whereColumn('correct_answers', '<=', 'total_questions')
            ->with(['subchapter', 'chapter'])
            ->orderByDesc('completed_at')
            ->get();
        $evaluationAttempts = collect(
            $evaluationResults->validCompletedForStudent((int) $request->user()->id)
        );
        $activityProgress = StudentSubchapterProgress::query()
            ->where('user_id', $request->user()->id)
            ->with('subchapter')
            ->get();
        $activities = $activityProgress->map(function (StudentSubchapterProgress $item) {
            return [
                'type' => $item->status === 'completed' ? 'material_completed' : 'material_opened',
                'title' => $item->status === 'completed' ? 'Materi selesai' : 'Mulai mempelajari materi',
                'description' => $item->subchapter?->judul,
                'occurred_at' => $item->completed_at ?? $item->opened_at ?? $item->updated_at,
            ];
        })->concat($results->map(function (StudentAssessmentResult $result) {
            $description = $result->subchapter?->judul
                ?? $result->chapter?->judul
                ?? 'Hasil penilaian tersimpan';
            if ($result->score !== null) {
                $description .= ' · Nilai ' . rtrim(
                    rtrim(number_format((float) $result->score, 2, '.', ''), '0'),
                    '.'
                );
            }

            return [
                'type' => $result->assessment_type,
                'title' => $result->assessment_type === 'quiz' ? 'Kuis selesai' : 'Evaluasi selesai',
                'description' => $description,
                'occurred_at' => $result->completed_at,
            ];
        }))->concat($evaluationAttempts->map(function (array $attempt) {
            $attribution = $attempt['teacher_id'] === null
                ? 'Atribusi Guru belum terverifikasi'
                : 'Guru ' . $attempt['teacher_name'];

            return [
                'type' => 'evaluation',
                'title' => 'Evaluasi selesai',
                'description' => $attribution . ' · Nilai '
                    . rtrim(rtrim(number_format($attempt['score'], 2, '.', ''), '0'), '.'),
                'occurred_at' => $attempt['completed_at'],
            ];
        }))->sortByDesc(fn (array $activity) => $activity['occurred_at'])->values();
        return response()->json([
            'chapter' => $chapter ? [
                'id' => $chapter->id,
                'number' => $chapter->nomor_bab,
                'title' => $chapter->judul,
            ] : null,
            'completed_materials' => $completedCount,
            'total_materials' => $totalCount,
            'percentage' => $percentage,
            'status' => $status,
            'completed_quizzes' => $results
                ->where('assessment_type', 'quiz')
                ->pluck('subchapter_id')
                ->filter()
                ->unique()
                ->count(),
            'completed_evaluations' => $evaluationAttempts->count(),
            'activities' => $activities,
            'final_grades' => $grades->summariesForAssignedTeachers($request->user()),
            'subchapters' => $progress->map(fn (StudentSubchapterProgress $item) => [
                'subchapter_id' => $item->subchapter_id,
                'status' => $item->status,
            ])->values(),
        ]);
    }
    public function openMaterial(
        Request $request,
        Subchapter $subchapter
    ): JsonResponse
    {
        $this->ensureSiswa($request);
        $this->ensurePublishedSubchapter(
            $subchapter
        );
        $progress = StudentSubchapterProgress::query()->firstOrCreate(
            [
                'user_id' => $request->user()->id,
                'subchapter_id' => $subchapter->id,
            ],
            [
                'status' => 'in_progress',
                'opened_at' => now(),
            ]
        );
        if (!$progress->opened_at) {
            $progress->opened_at = now();
            $progress->save();
        }
        return response()->json(['status' => $progress->status]);
    }
    public function completeMaterial(
        Request $request,
        Subchapter $subchapter
    ): JsonResponse
    {
        $this->ensureSiswa($request);
        $this->ensurePublishedSubchapter(
            $subchapter
        );
        abort_if(
            $subchapter->quizQuestions()
                ->where('is_published', true)
                ->where('activity_type', 'multiple_choice')
                ->exists(),
            422,
            'Subbab ini harus diselesaikan melalui kuis wajib.'
        );
        $progress = StudentSubchapterProgress::query()->firstOrCreate(
            [
                'user_id' => $request->user()->id,
                'subchapter_id' => $subchapter->id,
            ],
            [
                'status' => 'in_progress',
                'opened_at' => now(),
            ]
        );
        $progress->status = 'completed';
        $progress->completed_at = now();
        $progress->save();
        return response()->json(['status' => 'completed']);
    }
    private function ensureSiswa(Request $request): void
    {
        abort_unless($request->user()?->role === 'siswa', 403);
    }
    private function ensurePublishedSubchapter(
        Subchapter $subchapter
    ): void
    {
        abort_unless(
            $subchapter->is_published
                && $subchapter->chapter?->is_published
                && $subchapter->chapter?->guru?->role === 'guru',
            404
        );
    }
}
