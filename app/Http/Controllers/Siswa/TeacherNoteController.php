<?php
namespace App\Http\Controllers\Siswa;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class TeacherNoteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->role === 'siswa', 403);
        // Student id selalu berasal dari session, tidak menerima ID siswa lain.
        $notes = DB::table('student_teacher_notes as n')
            ->join('users as teacher', 'teacher.id', '=', 'n.teacher_id')
            ->where('n.student_id', $request->user()->id)
            ->where('teacher.role', 'guru')
            ->orderByDesc('n.updated_at')
            ->get(['teacher.name as teacher_name', 'n.note', 'n.updated_at']);
        return response()->json(['notes' => $notes]);
    }
}
