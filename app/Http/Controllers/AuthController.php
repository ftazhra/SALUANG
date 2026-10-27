<?php
namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
class AuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | HALAMAN LOGIN
    |--------------------------------------------------------------------------
    */
    public function showLogin(): View
    {
        return view('auth.login');
    }
    /*
    |--------------------------------------------------------------------------
    | HALAMAN DAFTAR
    |--------------------------------------------------------------------------
    */
    public function showRegister(): View
    {
        return view('auth.register');
    }
    /*
    |--------------------------------------------------------------------------
    | REGISTRASI SISWA, GURU (TOKEN), DAN ORANG TUA
    |--------------------------------------------------------------------------
    */
    public function register(Request $request): RedirectResponse
    {
        // Validasi berdasarkan peran yang dipilih.
        $data = $request->validate([
            'role' => [
                'required',
                Rule::in(['siswa', 'guru', 'orang_tua']),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'username' => [
                'required',
                'alpha_dash:ascii',
                'max:50',
                Rule::unique('users', 'username'),
            ],
            // Khusus Siswa
            'nisn' => [
                'required_if:role,siswa',
                'nullable',
                'digits:10',
                Rule::unique('users', 'nisn'),
            ],
            // Email siswa
            'email' => [
                'required_if:role,siswa',
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],
            // Kelas siswa
            'kelas' => [
                'required_if:role,siswa',
                'nullable',
                'string',
                'max:20',
            ],
            // Sekolah siswa
            'sekolah' => [
                'required_if:role,siswa',
                'nullable',
                'string',
                'max:255',
            ],
            // Guru: NIP/NIPK dan token undangan wajib diisi.
            'nip_nipk' => [
                'required_if:role,guru',
                'nullable',
                'string',
                'max:50',
                Rule::unique('users', 'nip_nipk'),
            ],
            'guru_token' => [
                'required_if:role,guru',
                'nullable',
                'string',
                'max:32',
            ],
            // Khusus Orang Tua
            'nomor_telepon' => [
                'required_if:role,orang_tua',
                'nullable',
                'string',
                'max:20',
            ],
            'nisn_anak' => [
                'required_if:role,orang_tua',
                'nullable',
                'digits:10',
            ],
            // Semua peran
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ]);
        // Kode pendaftaran Guru tetap yang diminta untuk SALUANG.
        // Dibatasi hanya pada registrasi, bukan login.
        if (($data['role'] ?? null) === 'guru' &&
            !hash_equals('SMPN21', (string) ($data['guru_token'] ?? ''))) {
            throw ValidationException::withMessages([
                'guru_token' => 'Kode pendaftaran Guru salah.',
            ]);
        }
        $role = $data['role'];
        /*
        |--------------------------------------------------------------------------
        | VERIFIKASI AWAL NISN ANAK
        |--------------------------------------------------------------------------
        |
        | Orang Tua hanya dapat mengajukan hubungan
        | dengan siswa yang sudah terdaftar.
        |
        | Hubungan tidak langsung disetujui.
        |
        */
        $student = null;
        if ($role === 'orang_tua') {
            $student = User::query()
                ->where('role', 'siswa')
                ->where('nisn', $data['nisn_anak'])
                ->first();
            if (!$student) {
                return back()
                    ->withErrors([
                        'nisn_anak' =>
                        'NISN anak belum terdaftar di SALUANG.',
                    ])
                    ->withInput(
                        $request->except([
                            'password',
                            'password_confirmation',
                        ])
                    );
            }
        }
        /*
        |--------------------------------------------------------------------------
        | SIMPAN AKUN KE DATABASE
        |--------------------------------------------------------------------------
        */
        $user = DB::transaction(function () use (
            $data,
            $role,
            $student
        ) {
            $user = new User();
            $user->name = $data['name'];
            $user->username = $data['username'];
            // Formulir Figma tidak menggunakan Email.
            // Email diambil dari pendaftaran siswa.
            $user->email = $role === 'siswa'
                ? $data['email']
                : null;
            // Password disimpan sebagai hash.
            $user->password = Hash::make($data['password']);
            $user->role = $role;
            $user->sekolah = $role === 'siswa'
                ? $data['sekolah']
                : 'SMP Negeri 21 Banjarmasin';
            // Akun belum diverifikasi identitasnya.
            $user->is_approved = false;
            /*
            | DATA KHUSUS SISWA
            */
            if ($role === 'siswa') {
                $user->nisn = $data['nisn'];
                $user->kelas = $data['kelas'];
            }
            /*
            | DATA KHUSUS GURU
            */
            if ($role === 'guru') {
                $user->nip_nipk = $data['nip_nipk'];
            }
            /*
            | DATA KHUSUS ORANG TUA
            */
            if ($role === 'orang_tua') {
                $user->nomor_telepon = $data['nomor_telepon'];
            }
            $user->save();

            /*
            |--------------------------------------------------------------------------
            | HUBUNGKAN ORANG TUA DENGAN SISWA
            |--------------------------------------------------------------------------
            |
            | Hubungan baru berstatus pending.
            | Pihak sekolah perlu memverifikasinya.
            |
            */
            if ($role === 'orang_tua' && $student) {
                DB::table('student_guardians')->insert([
                    'student_id' => $student->id,
                    'guardian_id' => $user->id,
                    'relationship' => 'orang_tua',
                    'status' => 'pending',
                    'verified_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            return $user;
        });
        /*
        |--------------------------------------------------------------------------
        | SETELAH PENDAFTARAN
        |--------------------------------------------------------------------------
        */
        /*
|--------------------------------------------------------------------------
| SETELAH PENDAFTARAN BERHASIL
|--------------------------------------------------------------------------
|
| Pengguna diarahkan ke halaman Login.
| Tidak otomatis masuk ke Dashboard.
|
*/
        return redirect()
            ->route('login')
            ->with(
                'status',
                'Pendaftaran berhasil! Silakan masuk menggunakan akun yang baru didaftarkan.'
            );
    }
    /*
    |--------------------------------------------------------------------------
    | LOGIN SISWA, GURU, DAN ORANG TUA
    |--------------------------------------------------------------------------
    */
    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'role' => [
                'required',
                Rule::in(['siswa', 'guru', 'orang_tua']),
            ],
            'identifier' => [
                'required',
                'string',
            ],
            'password' => [
                'required',
                'string',
            ],
            'nisn_anak' => [
                'required_if:role,orang_tua',
                'nullable',
                'digits:10',
            ],
        ]);
        $role = $data['role'];
        $identifier = trim($data['identifier']);
        /*
        |--------------------------------------------------------------------------
        | IDENTITAS LOGIN SESUAI FIGMA
        |--------------------------------------------------------------------------
        |
        | Siswa     : NISN
        | Guru      : NIPK
        | Orang Tua : No. HP atau Username
        |
        */
        $userQuery = User::query()
            ->where('role', $role);
        if ($role === 'siswa') {
            $userQuery->where('nisn', $identifier);
        } elseif ($role === 'guru') {
            $userQuery->where('nip_nipk', $identifier);
        } else {
            $userQuery->where(
                function ($query) use ($identifier) {
                    $query
                        ->where('username', $identifier)
                        ->orWhere('nomor_telepon', $identifier);
                }
            );
        }
        $user = $userQuery->first();
        /*
        |--------------------------------------------------------------------------
        | PERIKSA KATA SANDI
        |--------------------------------------------------------------------------
        */
        if (
            !$user ||
            !Hash::check($data['password'], $user->password)
        ) {
            return back()
                ->withErrors([
                    'identifier' =>
                    'Data login atau kata sandi tidak sesuai.',
                ])
                ->onlyInput(
                    'identifier',
                    'role',
                    'nisn_anak'
                );
        }
        /*
        |--------------------------------------------------------------------------
        | HUBUNGAN ORANG TUA DAN ANAK
        |--------------------------------------------------------------------------
        */
        if ($role === 'orang_tua') {
            $linked = DB::table('student_guardians')
                ->join(
                    'users as siswa',
                    'siswa.id',
                    '=',
                    'student_guardians.student_id'
                )
                ->where(
                    'student_guardians.guardian_id',
                    $user->id
                )
                ->where(
                    'siswa.role',
                    'siswa'
                )
                ->where(
                    'siswa.nisn',
                    $data['nisn_anak']
                )
                ->whereIn(
                    'student_guardians.status',
                    ['pending', 'approved']
                )
                ->exists();
            if (!$linked) {
                return back()
                    ->withErrors([
                        'nisn_anak' =>
                        'NISN anak belum terhubung dengan akun ini.',
                    ])
                    ->onlyInput(
                        'identifier',
                        'role',
                        'nisn_anak'
                    );
            }
            /*
            | Status pending hanya mengizinkan
            | login ke akun Orang Tua.
            |
            | Akses ke data siswa nantinya harus
            | mensyaratkan status approved.
            */
        }
        /*
        |--------------------------------------------------------------------------
        | LOGIN BERHASIL
        |--------------------------------------------------------------------------
        */
        Auth::login($user);
        $request->session()->regenerate();
        return redirect()
            ->intended(route('dashboard'));
    }
    /*
    |--------------------------------------------------------------------------
    | LOGOUT
    |--------------------------------------------------------------------------
    */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('beranda');
    }
}
