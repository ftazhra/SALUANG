<?php
namespace App\Http\Controllers\Guru;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class StudentFeedbackController extends Controller
{
    private function ensureGuru(Request $request): void
    {
        abort_unless($request->user()?->role === 'guru', 403, 'Hanya Guru yang dapat mengelola catatan.');
    }
    private function ensureSiswa(User $student): void
    {
        abort_unless($student->role === 'siswa', 404, 'Siswa tidak ditemukan.');
    }
    public function show(Request $request, User $student): JsonResponse
    {
        $this->ensureGuru($request);
        $this->ensureSiswa($student);
        $note = DB::table('student_teacher_notes')
            ->where('student_id', $student->id)
            ->where('teacher_id', $request->user()->id)
            ->first(['note', 'updated_at']);
        return response()->json([
            'note' => $note?->note ?? '',
            'updated_at' => $note?->updated_at,
        ]);
    }
    public function update(Request $request, User $student): JsonResponse
    {
        $this->ensureGuru($request);
        $this->ensureSiswa($student);
        $data = $request->validate([
            'note' => ['required', 'string', 'min:1', 'max:5000'],
        ]);
        $now = now();
        DB::table('student_teacher_notes')->upsert([[
            'student_id' => $student->id,
            'teacher_id' => $request->user()->id,
            'note' => trim($data['note']),
            'created_at' => $now,
            'updated_at' => $now,
        ]], ['student_id', 'teacher_id'], ['note', 'updated_at']);
        return response()->json([
            'message' => 'Catatan Guru berhasil disimpan.',
            'note' => trim($data['note']),
        ]);
    }
}
