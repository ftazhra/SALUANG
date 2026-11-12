<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class StudentManagementController extends Controller
{
    private function students(): Builder
    {
        return User::query()
            ->where('role', 'siswa')
            ->select([
                'id',
                'name',
                'nisn',
                'kelas',
                'email',
            ]);
    }

    private function studentForManagement(
        Request $request,
        int $studentId
    ): User {
        abort_unless($request->user()?->role === 'guru', 403);

        return User::query()
            ->where('role', 'siswa')
            ->findOrFail($studentId);
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:200'],
            'class' => ['nullable', 'string', 'max:20'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = $this->students();
        $registeredTotal = (clone $query)->count();
        $registeredClasses = (clone $query)
            ->whereNotNull('kelas')
            ->where('kelas', '!=', '')
            ->distinct()
            ->orderBy('kelas')
            ->pluck('kelas')
            ->all();

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function (Builder $builder) use ($search) {
                $builder
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['class'])) {
            $normalizedClass = mb_strtolower(trim($filters['class']));
            $query->whereRaw('LOWER(TRIM(kelas)) = ?', [$normalizedClass]);
        }

        $students = $query
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->paginate(15)
            ->withQueryString();
        $students->getCollection()->transform(function (User $student) {
            $student->setAttribute('can_manage', true);
            return $student;
        });

        return response()->json([
            'students' => $students->items(),
            'pagination' => [
                'current_page' => $students->currentPage(),
                'last_page' => $students->lastPage(),
                'total' => $students->total(),
            ],
            'registered_total' => $registeredTotal,
            'classes' => $registeredClasses,
            'registered_classes' => $registeredClasses,
        ]);
    }

    public function show(
        Request $request,
        int $student
    ): JsonResponse {
        abort_unless($request->user()?->role === 'guru', 403);

        return response()->json([
            'student' => User::query()
                ->where('role', 'siswa')
                ->findOrFail($student)
                ->only([
                    'id',
                    'name',
                    'username',
                    'nisn',
                    'kelas',
                    'email',
                    'sekolah',
                    'nomor_telepon',
                ]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        abort(403, 'Guru tidak dapat menambahkan akun siswa melalui dashboard.');
    }

    public function update(
        Request $request,
        int $student
    ): JsonResponse {
        $student = $this->studentForManagement($request, $student);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'alpha_dash:ascii',
                'max:50',
                Rule::unique('users', 'username')->ignore($student->id)
            ],
            'nisn' => [
                'required',
                'digits:10',
                Rule::unique('users', 'nisn')->ignore($student->id)
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($student->id)
            ],
            'kelas' => [
                'required',
                'string',
                'max:20',
            ],
            'sekolah' => ['nullable', 'string', 'max:255'],
            'nomor_telepon' => ['nullable', 'string', 'max:20'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $student->fill(
            collect($data)->except('password')->all()
        );

        if (!empty($data['password'])) {
            $student->password = Hash::make($data['password']);
        }

        $student->save();

        return response()->json([
            'message' => 'Data siswa berhasil diperbarui.',
            'student' => $student->only([
                'id',
                'name',
                'username',
                'nisn',
                'kelas',
                'email',
                'sekolah',
                'nomor_telepon'
            ]),
        ]);
    }

    public function destroy(
        Request $request,
        int $student
    ): JsonResponse {
        abort_unless($request->user()?->role === 'guru', 403);

        $studentRecord = User::query()
            ->where('role', 'siswa')
            ->whereKey($student)
            ->firstOrFail();

        $studentRecord->delete();

        return response()->json([
            'message' => 'Akun siswa dinonaktifkan. Riwayat dan data terkait tetap disimpan.',
        ]);
    }
}
