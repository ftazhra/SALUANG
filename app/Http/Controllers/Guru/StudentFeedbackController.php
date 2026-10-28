<?php
namespace App\Http\Controllers\Guru;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
        $data = $request->validate([
            'audience' => ['sometimes', 'in:orang_tua,siswa'],
        ]);
        $audience = $data['audience'] ?? 'orang_tua';
        $supportsAudience = Schema::hasColumn('student_teacher_notes', 'audience');
        abort_if(!$supportsAudience && $audience !== 'orang_tua', 409, 'Pemisahan audiens catatan belum tersedia.');

        $query = DB::table('student_teacher_notes')
            ->where('student_id', $student->id)
            ->where('teacher_id', $request->user()->id);
        if ($supportsAudience) {
            $query->where('audience', $audience);
        }
        $note = $query->first(['note', 'updated_at']);
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
            'audience' => ['sometimes', 'in:orang_tua,siswa'],
        ]);
        $now = now();
        $audience = $data['audience'] ?? 'orang_tua';
        $supportsAudience = Schema::hasColumn('student_teacher_notes', 'audience');
        abort_if(!$supportsAudience && $audience !== 'orang_tua', 409, 'Pemisahan audiens catatan belum tersedia.');

        $row = [
            'student_id' => $student->id,
            'teacher_id' => $request->user()->id,
            'note' => trim($data['note']),
            'created_at' => $now,
            'updated_at' => $now,
        ];
        $uniqueBy = ['student_id', 'teacher_id'];
        if ($supportsAudience) {
            $row['audience'] = $audience;
            $uniqueBy[] = 'audience';
        }
        DB::table('student_teacher_notes')->upsert([$row], $uniqueBy, ['note', 'updated_at']);
        return response()->json([
            'message' => 'Catatan Guru berhasil disimpan.',
            'note' => trim($data['note']),
        ]);
    }
}
