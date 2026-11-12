<?php
namespace App\Http\Controllers\Siswa;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
class TeacherNoteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->role === 'siswa', 403);
        if (
            !Schema::hasTable('student_teacher_note_messages')
            || !Schema::hasColumn('student_teacher_note_messages', 'audience')
            || !Schema::hasColumn('student_teacher_note_messages', 'published_at')
        ) {
            return response()->json(['notes' => []]);
        }
        $notes = DB::table('student_teacher_note_messages as n')
            ->join('users as teacher', 'teacher.id', '=', 'n.teacher_id')
            ->where('n.student_id', $request->user()->id)
            ->where('n.audience', 'siswa')
            ->whereNotNull('n.published_at')
            ->where('teacher.role', 'guru')
            ->orderByDesc('n.published_at')
            ->orderByDesc('n.id')
            ->get([
                'teacher.name as teacher_name',
                'n.note',
                'n.published_at as updated_at',
            ]);

        return response()->json(['notes' => $notes]);
    }
}
