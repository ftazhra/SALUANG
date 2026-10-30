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

    public function show(Request $request, User $student): JsonResponse
    {
        $this->ensureGuru($request);
        $this->ensureSiswa($student);

        $data = $request->validate([
            'audience' => ['sometimes', 'in:orang_tua,siswa'],
        ]);

        $audience = $data['audience'] ?? 'orang_tua';

        $draft = DB::table('student_teacher_notes')
            ->where('student_id', $student->id)
            ->where('teacher_id', $request->user()->id)
            ->where('audience', $audience)
            ->whereNull('published_at')
            ->first(['note', 'updated_at']);

        return response()->json([
            'note' => $draft?->note ?? '',
            'updated_at' => $draft?->updated_at,
            'published_at' => null,
            'status' => $draft ? 'draf' : 'kosong',
        ]);
    }

    public function update(Request $request, User $student): JsonResponse
    {
        $this->ensureGuru($request);
        $this->ensureSiswa($student);

        $data = $request->validate([
            'note' => ['required', 'string', 'max:5000', 'not_regex:/^\s*$/'],
            'audience' => ['sometimes', 'in:orang_tua,siswa'],
            'action' => ['sometimes', 'in:draft,publish'],
        ]);

        $audience = $data['audience'] ?? 'orang_tua';
        $action = $data['action'] ?? 'draft';
        $note = trim($data['note']);
        $now = now();

        if ($action === 'draft') {
            DB::table('student_teacher_notes')->upsert(
                [[
                    'student_id' => $student->id,
                    'teacher_id' => $request->user()->id,
                    'audience' => $audience,
                    'note' => $note,
                    'published_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]],
                ['student_id', 'teacher_id', 'audience'],
                ['note', 'published_at', 'updated_at']
            );

            return response()->json([
                'message' => 'Draf catatan berhasil disimpan.',
                'note' => $note,
                'status' => 'draf',
                'published_at' => null,
            ]);
        }

        $message = DB::transaction(function () use ($student, $request, $audience, $note, $now) {
            $id = DB::table('student_teacher_note_messages')->insertGetId([
                'student_id' => $student->id,
                'teacher_id' => $request->user()->id,
                'audience' => $audience,
                'note' => $note,
                'published_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Kosongkan draf setelah catatan berhasil dikirim.
            DB::table('student_teacher_notes')
                ->where('student_id', $student->id)
                ->where('teacher_id', $request->user()->id)
                ->where('audience', $audience)
                ->delete();

            return $id;
        });

        return response()->json([
            'message' => 'Catatan berhasil dikirim ke Orang Tua/Wali.',
            'id' => $message,
            'note' => '',
            'status' => 'terkirim',
            'published_at' => $now->toIso8601String(),
        ]);
    }
}
