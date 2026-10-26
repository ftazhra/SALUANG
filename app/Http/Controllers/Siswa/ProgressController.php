<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\QuizQuestion;
use App\Models\StudentAssessmentResult;
use App\Models\StudentSubchapterProgress;
use App\Models\Subchapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->ensureSiswa($request);

        $chapter = Chapter::query()
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
            ->with(['subchapter', 'chapter'])
            ->orderByDesc('completed_at')
            ->get();

        $activities = $progress->map(function (StudentSubchapterProgress $item) {
            return [
                'type' => $item->status === 'completed' ? 'material_completed' : 'material_opened',
                'title' => $item->status === 'completed' ? 'Materi selesai' : 'Mulai mempelajari materi',
                'description' => $item->subchapter?->judul,
                'occurred_at' => $item->completed_at ?? $item->opened_at ?? $item->updated_at,
            ];
        })->concat($results->map(function (StudentAssessmentResult $result) {
            return [
                'type' => $result->assessment_type,
                'title' => $result->assessment_type === 'quiz' ? 'Kuis selesai' : 'Evaluasi selesai',
                'description' => $result->subchapter?->judul ?? $result->chapter?->judul ?? 'Hasil penilaian tersimpan',
                'occurred_at' => $result->completed_at,
            ];
        }))->sortByDesc(fn (array $activity) => $activity['occurred_at'])->take(5)->values();

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
            'completed_quizzes' => $results->where('assessment_type', 'quiz')->count(),
            'completed_evaluations' => $results->where('assessment_type', 'evaluation')->count(),
            'activities' => $activities,
            'subchapters' => $progress->map(fn (StudentSubchapterProgress $item) => [
                'subchapter_id' => $item->subchapter_id,
                'status' => $item->status,
            ])->values(),
        ]);
    }

    public function openMaterial(Request $request, Subchapter $subchapter): JsonResponse
    {
        $this->ensureSiswa($request);
        $this->ensurePublishedSubchapter($subchapter);

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

    public function completeMaterial(Request $request, Subchapter $subchapter): JsonResponse
    {
        $this->ensureSiswa($request);
        $this->ensurePublishedSubchapter($subchapter);

        abort_if(
            $subchapter->quizQuestions()->where('is_published', true)->exists(),
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

    private function ensurePublishedSubchapter(Subchapter $subchapter): void
    {
        abort_unless(
            $subchapter->is_published
                && $subchapter->chapter?->is_published
                && $subchapter->chapter?->guru?->role === 'guru',
            404
        );
    }
}
