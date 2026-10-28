<?php

namespace App\Http\Controllers\OrangTua;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChatController extends Controller
{
  private function linkedStudent(Request $request): int
  {
    abort_unless($request->user()?->role === 'orang_tua', 403);
    $link = DB::table('student_guardians')
      ->where('guardian_id', $request->user()->id)
      ->where('status', 'approved')->orderBy('id')->first();
    abort_unless($link && DB::table('users')->where('id', $link->student_id)->where('role', 'siswa')->exists(), 403, 'Hubungan akun anak belum disetujui.');
    return (int)$link->student_id;
  }
  private function teacher(int $id): void
  {
    abort_unless(DB::table('users')->where('id', $id)->where('role', 'guru')->exists(), 404, 'Guru tidak ditemukan.');
  }
  public function teachers(Request $request): JsonResponse
  {
    $this->linkedStudent($request);
    return response()->json(['teachers' => DB::table('users')->where('role', 'guru')->orderBy('name')->get(['id', 'name'])]);
  }
  public function messages(Request $request): JsonResponse
  {
    $student = $this->linkedStudent($request);
    $data = $request->validate(['teacher_id' => 'required|integer']);
    $teacher = (int)$data['teacher_id'];
    $this->teacher($teacher);
    $rows = DB::table('parent_teacher_messages as m')
      ->join('users as u', 'u.id', '=', 'm.sender_id')
      ->where('m.parent_id', $request->user()->id)->where('m.student_id', $student)->where('m.teacher_id', $teacher)
      ->orderBy('m.id')->limit(200)
      ->get(['m.id', 'm.body', 'm.sender_id', 'm.created_at', 'u.name as sender_name']);
    return response()->json(['messages' => $rows->map(fn($r) => [
      'id' => $r->id,
      'body' => $r->body,
      'is_mine' => (int)$r->sender_id === (int)$request->user()->id,
      'sender_name' => $r->sender_name,
      'created_at' => (string)$r->created_at,
    ])]);
  }
  public function store(Request $request): JsonResponse
  {
    $student = $this->linkedStudent($request);
    $data = $request->validate(['teacher_id' => 'required|integer', 'body' => 'required|string|max:2000']);
    $teacher = (int)$data['teacher_id'];
    $this->teacher($teacher);
    $id = DB::table('parent_teacher_messages')->insertGetId([
      'parent_id' => $request->user()->id,
      'student_id' => $student,
      'teacher_id' => $teacher,
      'sender_id' => $request->user()->id,
      'body' => trim($data['body']),
      'created_at' => now(),
      'updated_at' => now(),
    ]);
    return response()->json(['message' => 'Pesan terkirim.', 'id' => $id], 201);
  }
}
