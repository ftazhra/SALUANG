<?php
namespace App\Http\Controllers\Guru;
use App\Http\Controllers\Controller;
use App\Models\StudentGuardian;
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
    public function index(Request $request): JsonResponse
    {
        $this->ensureGuru($request);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:200'],
            'class'  => ['required', 'in:VII A,VII B,VII C,VII D,VII E'],
        ]);
        abort_unless(Schema::hasTable('teacher_class_assignments'), 503, 'Penugasan Guru-kelas belum dikonfigurasi.');
        $assignedClass = DB::table('teacher_class_assignments')
            ->where('teacher_id', $request->user()->id)
            ->where('class_name', $filters['class'])
            ->exists();
        abort_unless($assignedClass, 403, 'Guru tidak ditugaskan pada kelas tersebut.');

        $query = StudentGuardian::query()
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
        $contacts = $query
            ->orderBy('student_id')
            ->get()
            ->map(function ($relation) {
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
                        'relationship' =>
                        $relation->relationship
                            ?: $relation->guardian->hubungan_anak
                            ?: 'Wali',
                        'phone' =>
                        $relation->guardian->nomor_telepon,
                    ],
                    'status' => $relation->status,
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
        StudentGuardian $studentGuardian
    ): JsonResponse {
        $this->ensureGuru($request);
        abort_unless(Schema::hasTable('teacher_class_assignments'), 503, 'Penugasan Guru-kelas belum dikonfigurasi.');
        $assignedClasses = DB::table('teacher_class_assignments')
            ->where('teacher_id', $request->user()->id)
            ->pluck('class_name');
        abort_unless(
            $studentGuardian->status === 'approved'
                && $assignedClasses->isNotEmpty()
                && $studentGuardian->student()
                    ->where('role', 'siswa')
                    ->whereIn('kelas', $assignedClasses)
                    ->exists(),
            404,
            'Hubungan atau kewenangan kelas tidak valid.'
        );
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
}
