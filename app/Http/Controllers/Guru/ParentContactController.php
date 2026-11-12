<?php
namespace App\Http\Controllers\Guru;
use App\Http\Controllers\Controller;
use App\Models\StudentGuardian;
use App\Services\ChatPresenceService;
use App\Services\ParentTeacherChatService;
use App\Services\TeacherStudentAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
class ParentContactController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | 01. PEMERIKSAAN AKSES GURU
    |--------------------------------------------------------------------------
    */
    private function ensureGuru(Request $request): void
    {
        abort_unless(
            $request->user()?->role === 'guru',
            403,
            'Halaman Hubungi Orang Tua hanya tersedia untuk Guru.'
        );
    }
    /*
    |--------------------------------------------------------------------------
    | 02. DAFTAR NARAHUBUNG ORANG TUA
    |--------------------------------------------------------------------------
    */
    public function index(
        Request $request,
        ChatPresenceService $presence,
        ParentTeacherChatService $chat
    ): JsonResponse
    {
        $this->ensureGuru($request);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:200'],
            'class'  => ['required', 'in:VII A,VII B,VII C,VII D,VII E'],
        ]);
        abort_unless(Schema::hasTable('teacher_class_assignments'), 503, 'Penugasan Guru-kelas belum dikonfigurasi.');
        $studentIds = $chat->studentIdsForTeacher($request->user());

        $query = StudentGuardian::query()
            ->whereIn('student_id', $studentIds)
            ->where('status', 'approved')
            ->whereHas('student', function ($builder) {
                $builder->where('role', 'siswa');
            })
            ->whereHas('student', function ($builder) use ($filters) {
                $builder->where('role', 'siswa')
                    ->where('kelas', $filters['class']);
            })
            ->whereHas('guardian', function ($builder) {
                $builder->where('role', 'orang_tua');
            })
            ->with([
                'student:id,name,nisn,kelas',
                'guardian:id,name,nomor_telepon,hubungan_anak',
            ]);
        if (!empty($filters['search'])) {
            $keyword = $filters['search'];
            $query->where(function ($builder) use ($keyword) {
                $builder
                    ->whereHas(
                        'student',
                        function ($studentQuery) use ($keyword) {
                            $studentQuery->where(
                                'name',
                                'like',
                                "%{$keyword}%"
                            );
                        }
                    )
                    ->orWhereHas(
                        'guardian',
                        function ($guardianQuery) use ($keyword) {
                            $guardianQuery->where(
                                'name',
                                'like',
                                "%{$keyword}%"
                            );
                        }
                    );
            });
        }
        $relations = $query->orderBy('student_id')->get();
        $unreadCounts = collect();
        if ($relations->isNotEmpty() && Schema::hasTable('website_notifications')) {
            $unreadCounts = DB::table('website_notifications as notification')
                ->join('parent_teacher_messages as message', 'message.id', '=', 'notification.source_id')
                ->where('notification.recipient_id', $request->user()->id)
                ->where('notification.recipient_role', 'guru')
                ->where('notification.event_type', 'chat_message')
                ->where('notification.source_type', 'parent_teacher_message')
                ->whereNull('notification.read_at')
                ->where('message.teacher_id', $request->user()->id)
                ->where('message.sender_id', '!=', $request->user()->id)
                ->where(function ($query) use ($relations) {
                    foreach ($relations as $relation) {
                        $query->orWhere(function ($pair) use ($relation) {
                            $pair->where('message.parent_id', $relation->guardian_id)
                                ->where('message.student_id', $relation->student_id);
                        });
                    }
                })
                ->selectRaw('message.parent_id, message.student_id, COUNT(*) as unread_count')
                ->groupBy('message.parent_id', 'message.student_id')
                ->get()
                ->mapWithKeys(fn ($row) => [
                    $row->parent_id . ':' . $row->student_id => (int) $row->unread_count,
                ]);
        }
        $contacts = $relations->map(function ($relation) use ($presence, $unreadCounts) {
                return [
                    'relationship_id' => $relation->id,
                    'student' => [
                        'id' => $relation->student->id,
                        'name' => $relation->student->name,
                        'nisn' => $relation->student->nisn,
                        'class' => $relation->student->kelas,
                    ],
                    'guardian' => [
                        'id' => $relation->guardian->id,
                        'name' => $relation->guardian->name,
                        'relationship' => $relation->relationship
                            ?: $relation->guardian->hubungan_anak
                            ?: 'Wali',
                        'phone' => $relation->guardian->nomor_telepon,
                    ],
                    'status' => $relation->status,
                    'presence' => $presence->forUser((int) $relation->guardian_id),
                    'unread_count' => $unreadCounts->get(
                        $relation->guardian_id . ':' . $relation->student_id,
                        0
                    ),
                ];
        });
        return response()->json([
            'data' => $contacts->values(),
            'total' => $contacts->count(),
        ]);
    }
    /*
    |--------------------------------------------------------------------------
    | 03. DETAIL KONTAK ORANG TUA
    |--------------------------------------------------------------------------
    */
    public function show(
        Request $request,
        StudentGuardian $studentGuardian,
        ParentTeacherChatService $chat
    ): JsonResponse {
        $this->ensureGuru($request);
        $chat->teacherThread($request->user(), (int) $studentGuardian->id);
        $studentGuardian->load([
            'student:id,name,nisn,kelas,role',
            'guardian:id,name,nomor_telepon,hubungan_anak,role',
        ]);
        abort_unless(
            $studentGuardian->student?->role === 'siswa'
                && $studentGuardian->guardian?->role === 'orang_tua',
            404,
            'Data narahubung tidak ditemukan.'
        );
        return response()->json([
            'data' => [
                'relationship_id' => $studentGuardian->id,
                'student' => [
                    'id' => $studentGuardian->student->id,
                    'name' => $studentGuardian->student->name,
                    'nisn' => $studentGuardian->student->nisn,
                    'class' => $studentGuardian->student->kelas,
                ],
                'guardian' => [
                    'id' => $studentGuardian->guardian->id,
                    'name' => $studentGuardian->guardian->name,
                    'relationship' =>
                    $studentGuardian->relationship
                        ?: $studentGuardian->guardian->hubungan_anak
                        ?: 'Wali',
                    'phone' =>
                    $studentGuardian->guardian->nomor_telepon,
                ],
            ],
        ]);
    }

    public function presence(
        Request $request,
        StudentGuardian $studentGuardian,
        ParentTeacherChatService $chat
    ): JsonResponse {
        $this->ensureGuru($request);
        $thread = $chat->teacherThread($request->user(), (int) $studentGuardian->id);

        return response()->json([
            ...$chat->presence($thread, $request->user()),
            'unread_count' => $chat->unreadCount($thread, $request->user()),
        ]);
    }
}
