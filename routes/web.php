<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\BerandaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordRecoveryController;
use App\Http\Controllers\Siswa\DiscussionController;
use App\Http\Controllers\Siswa\ProfileController;
use App\Http\Controllers\Siswa\QuizController;
use App\Http\Controllers\Siswa\MaterialController as SiswaMaterialController;
use App\Http\Controllers\Siswa\ProgressController as SiswaProgressController;
use App\Http\Controllers\Guru\MaterialController;
use App\Http\Controllers\Guru\QuizQuestionController;
use App\Http\Controllers\Guru\EvaluationController;
use App\Http\Controllers\Guru\DiscussionController as GuruDiscussionController;
use App\Http\Controllers\Guru\StudentGradeController;
use App\Http\Controllers\Guru\StudentGradeExportController;
use App\Http\Controllers\Guru\StudentGradePdfController;
use App\Http\Controllers\Guru\ParentContactController;
use App\Http\Controllers\Guru\ProfileController as GuruProfileController;

/*
|--------------------------------------------------------------------------
| 01. BERANDA SALUANG
|--------------------------------------------------------------------------
*/

Route::get('/', [BerandaController::class, 'index'])->name('beranda');

/*
|--------------------------------------------------------------------------
| 02. LOGIN, DAFTAR, DAN LUPA KATA SANDI
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    // 02.1. Halaman Login
    Route::get('/masuk', [AuthController::class, 'showLogin'])->name('login');

    // 02.2. Proses Login
    Route::post('/masuk', [AuthController::class, 'login'])->name('login.store');

    // 02.3. Halaman Daftar
    Route::get('/daftar', [AuthController::class, 'showRegister'])->name('register');

    // 02.4. Proses Daftar
    Route::post('/daftar', [AuthController::class, 'register'])->name('register.store');

    // 02.5. Halaman Lupa Kata Sandi
    Route::get('/lupa-kata-sandi', [PasswordRecoveryController::class, 'show'])->name('password.request');

    // 02.6. Proses Permintaan Pemulihan
    Route::post('/lupa-kata-sandi', [PasswordRecoveryController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('password.request.store');
});

/*
|--------------------------------------------------------------------------
| 03. LOGOUT
|--------------------------------------------------------------------------
*/

Route::post('/keluar', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| 04. DASHBOARD SETELAH LOGIN / DAFTAR
|--------------------------------------------------------------------------
*/

Route::get('/dashboard', function () {
    $user = Auth::user();

    // Dashboard Siswa
    if ($user->role === 'siswa') {
        return view('siswa.dashboard');
    }

    // Dashboard Guru
    if ($user->role === 'guru') {
        return view('guru.dashboard');
    }

    // Dashboard Orang Tua
    if ($user->role === 'orang_tua') {
        // Sementara mempertahankan dashboard lama
        // sampai dashboard Orang Tua selesai dibuat.
        return view('dashboard', compact('user'));
    }

    // Jika role tidak dikenali, akses ditolak.
    abort(403, 'Peran pengguna tidak dikenali.');
})
    ->middleware('auth')
    ->name('dashboard');

/*
|--------------------------------------------------------------------------
| 05. PREVIEW DASHBOARD SISWA TANPA LOGIN
|--------------------------------------------------------------------------
|
| Digunakan untuk menguji desain frontend.
| Tidak mengambil data pengguna dari database.
|
*/

Route::get('/siswa-preview', function () {
    return view('siswa.dashboard');
})->name('siswa.preview');

/*
|--------------------------------------------------------------------------
| 06. FORUM DISKUSI SISWA SALUANG
|--------------------------------------------------------------------------
|
| Forum terhubung dengan database MySQL.
|
| Fitur:
| - Menampilkan daftar diskusi
| - Membuat diskusi baru
| - Membuka detail diskusi
| - Mengirim tanggapan
|
| Semua endpoint hanya dapat diakses setelah login.
| Pemeriksaan role siswa dilakukan di DiscussionController.
|
*/

Route::middleware('auth')
    ->prefix('forum')
    ->name('forum.')
    ->group(function () {
        /*
        |--------------------------------------------------------------
        | 06.1. DAFTAR DISKUSI
        |--------------------------------------------------------------
        */

        Route::get('/discussions', [DiscussionController::class, 'index'])->name('discussions.index');

        /*
        |--------------------------------------------------------------
        | 06.2. MEMBUAT DISKUSI BARU
        |--------------------------------------------------------------
        */

        Route::post('/discussions', [DiscussionController::class, 'store'])->name('discussions.store');

        /*
        |--------------------------------------------------------------
        | 06.3. MELIHAT DETAIL DISKUSI
        |--------------------------------------------------------------
        */

        Route::get('/discussions/{discussion}', [DiscussionController::class, 'show'])->name('discussions.show');

        /*
        |--------------------------------------------------------------
        | 06.4. MENGIRIM TANGGAPAN DISKUSI
        |--------------------------------------------------------------
        */

        Route::post('/discussions/{discussion}/replies', [DiscussionController::class, 'reply'])->name('discussions.replies.store');

        /*
|--------------------------------------------------------------------------
| 06.5. MENGEDIT DISKUSI
|--------------------------------------------------------------------------
*/

        Route::patch('/discussions/{discussion}', [DiscussionController::class, 'update'])->name('discussions.update');

        /*
|--------------------------------------------------------------------------
| 06.6. MENGHAPUS DISKUSI
|--------------------------------------------------------------------------
*/

        Route::delete('/discussions/{discussion}', [DiscussionController::class, 'destroy'])->name('discussions.destroy');
    });

/*
|--------------------------------------------------------------------------
| UPLOAD FOTO PROFIL SISWA
|--------------------------------------------------------------------------
*/

Route::post('/siswa/profil/foto', [ProfileController::class, 'updatePhoto'])
    ->middleware('auth')
    ->name('siswa.profile.photo');

Route::delete('/siswa/profil/foto', [ProfileController::class, 'destroyPhoto'])
    ->middleware('auth')
    ->name('siswa.profile.photo.destroy');

/*
|--------------------------------------------------------------------------
| 07. CRUD MATERI GURU SALUANG
|--------------------------------------------------------------------------
|
| Semua endpoint wajib login.
| Hak akses Guru dan kepemilikan materi
| diperiksa kembali di MaterialController.
|
*/

Route::middleware('auth')
    ->prefix('guru')
    ->name('guru.')
    ->group(function () {
        // DAFTAR BAB
        Route::get('/chapters', [MaterialController::class, 'index'])->name('chapters.index');

        // TAMBAH BAB
        Route::post('/chapters', [MaterialController::class, 'store'])->name('chapters.store');

        // DETAIL BAB
        Route::get('/chapters/{chapter}', [MaterialController::class, 'show'])->name('chapters.show');

        // EDIT BAB
        Route::put('/chapters/{chapter}', [MaterialController::class, 'update'])->name('chapters.update');

        // HAPUS BAB
        Route::delete('/chapters/{chapter}', [MaterialController::class, 'destroy'])->name('chapters.destroy');

        // TAMBAH SUBBAB
        Route::post('/chapters/{chapter}/subchapters', [MaterialController::class, 'storeSubchapter'])->name('subchapters.store');

        // EDIT SUBBAB
        Route::put('/chapters/{chapter}/subchapters/{subchapter}', [MaterialController::class, 'updateSubchapter'])->name('subchapters.update');

        // HAPUS SUBBAB
        Route::delete('/chapters/{chapter}/subchapters/{subchapter}', [MaterialController::class, 'destroySubchapter'])->name('subchapters.destroy');
    });

/*
|--------------------------------------------------------------------------
| CRUD KELOLA SOAL KUIS GURU — SALUANG
|--------------------------------------------------------------------------
|
| Guru wajib login.
| Pemeriksaan role Guru dan kepemilikan Subbab
| dilakukan di QuizQuestionController.
|
*/

Route::middleware('auth')
    ->prefix('guru')
    ->name('guru.')
    ->group(function () {
        // 01. DATA BAB DAN SUBBAB UNTUK DROPDOWN
        Route::get('/quiz/chapters', [QuizQuestionController::class, 'chapters'])->name('quiz.chapters');

        // 02. DAFTAR SOAL BERDASARKAN SUBBAB
        Route::get('/subchapters/{subchapter}/questions', [QuizQuestionController::class, 'index'])->name('quiz.questions.index');

        // 03. TAMBAH SOAL
        Route::post('/subchapters/{subchapter}/questions', [QuizQuestionController::class, 'store'])->name('quiz.questions.store');

        // 04. DETAIL / PRATINJAU SOAL
        Route::get('/subchapters/{subchapter}/questions/{question}', [QuizQuestionController::class, 'show'])->name('quiz.questions.show');

        // 05. EDIT SOAL
        Route::put('/subchapters/{subchapter}/questions/{question}', [QuizQuestionController::class, 'update'])->name('quiz.questions.update');

        // 06. HAPUS SOAL
        Route::delete('/subchapters/{subchapter}/questions/{question}', [QuizQuestionController::class, 'destroy'])->name('quiz.questions.destroy');
    });

/*
|--------------------------------------------------------------------------
| MATERI SISWA — DATA DARI GURU
|--------------------------------------------------------------------------
| Siswa mengambil Bab dan Subbab yang dipublikasikan Guru.
*/

Route::middleware('auth')
    ->prefix('siswa')
    ->name('siswa.')
    ->group(function () {
        Route::get('/materials', [SiswaMaterialController::class, 'index'])->name('materials.index');

        Route::get('/progress', [SiswaProgressController::class, 'index'])->name('progress.index');

        Route::post('/subchapters/{subchapter}/open', [SiswaProgressController::class, 'openMaterial'])
            ->whereNumber('subchapter')
            ->name('subchapters.open');

        Route::post('/subchapters/{subchapter}/complete', [SiswaProgressController::class, 'completeMaterial'])
            ->whereNumber('subchapter')
            ->name('subchapters.complete');
    });

/*
|--------------------------------------------------------------------------
| 08. KUIS SUBBAB SISWA — SALUANG
|--------------------------------------------------------------------------
|
| Mengambil soal yang dipublikasikan Guru.
| Memeriksa jawaban di server Laravel.
| Pemeriksaan role Siswa dilakukan di QuizController.
|
*/

Route::middleware('auth')
    ->prefix('siswa')
    ->name('siswa.')
    ->group(function () {
        // 01. AMBIL SOAL BERDASARKAN BAB DAN SUBBAB
        Route::get('/quiz/subchapters/{subchapter}/questions', [QuizController::class, 'questionsForSubchapter'])
            ->whereNumber('subchapter')
            ->name('quiz.subchapters.questions');

        Route::get('/quiz/chapters/{chapterNumber}/subchapters/{subchapterNumber}/questions', [QuizController::class, 'questions'])
            ->whereNumber('chapterNumber')
            ->whereNumber('subchapterNumber')
            ->name('quiz.questions');

        // 02. PERIKSA JAWABAN SISWA
        Route::post('/quiz/questions/{question}/check', [QuizController::class, 'checkAnswer'])
            ->whereNumber('question')
            ->name('quiz.check');
    });

/*
|--------------------------------------------------------------------------
| 09. CRUD KELOLA EVALUASI GURU — SALUANG
|--------------------------------------------------------------------------
|
| Guru wajib login.
| Pemeriksaan role Guru dan kepemilikan soal
| dilakukan di EvaluationController.
|
*/

Route::middleware('auth')
    ->prefix('guru')
    ->name('guru.')
    ->group(function () {
        // 00. UPLOAD GAMBAR SOAL EVALUASI
        Route::post('/evaluations/upload-image', [EvaluationController::class, 'uploadImage'])->name('evaluations.upload-image');

        // 01. DAFTAR SOAL EVALUASI
        Route::get('/evaluations/questions', [EvaluationController::class, 'index'])->name('evaluations.questions.index');

        // 02. TAMBAH SOAL EVALUASI
        Route::post('/evaluations/questions', [EvaluationController::class, 'store'])->name('evaluations.questions.store');

        // 03. DETAIL / PRATINJAU SOAL
        Route::get('/evaluations/questions/{question}', [EvaluationController::class, 'show'])
            ->whereNumber('question')
            ->name('evaluations.questions.show');

        // 04. EDIT SOAL EVALUASI
        Route::put('/evaluations/questions/{question}', [EvaluationController::class, 'update'])
            ->whereNumber('question')
            ->name('evaluations.questions.update');

        // 05. HAPUS SOAL EVALUASI
        Route::delete('/evaluations/questions/{question}', [EvaluationController::class, 'destroy'])
            ->whereNumber('question')
            ->name('evaluations.questions.destroy');
    });

/*
|--------------------------------------------------------------------------
| FORUM DISKUSI GURU — SALUANG
|--------------------------------------------------------------------------
| Menggunakan database diskusi yang sama dengan Forum Siswa.
*/

Route::middleware('auth')
    ->prefix('guru')
    ->name('guru.')
    ->group(function () {
        // Menampilkan daftar diskusi.
        Route::get('/discussions', [GuruDiscussionController::class, 'index'])->name('discussions.index');

        // Membuat diskusi baru.
        Route::post('/discussions', [GuruDiscussionController::class, 'store'])->name('discussions.store');

        // Menampilkan detail dan semua tanggapan.
        Route::get('/discussions/{discussion}', [GuruDiscussionController::class, 'show'])
            ->whereNumber('discussion')
            ->name('discussions.show');

        // Mengirim tanggapan Guru.
        Route::post('/discussions/{discussion}/replies', [GuruDiscussionController::class, 'reply'])
            ->whereNumber('discussion')
            ->name('discussions.replies.store');

        // Mengedit diskusi milik Guru sendiri.
        Route::put('/discussions/{discussion}', [GuruDiscussionController::class, 'update'])
            ->whereNumber('discussion')
            ->name('discussions.update');

        // Menghapus diskusi milik Guru sendiri.
        Route::delete('/discussions/{discussion}', [GuruDiscussionController::class, 'destroy'])
            ->whereNumber('discussion')
            ->name('discussions.destroy');
    });

/*
|--------------------------------------------------------------------------
| NILAI SISWA GURU — SALUANG
|--------------------------------------------------------------------------
| Daftar nilai dan detail hasil belajar siswa.
*/

Route::middleware('auth')
    ->prefix('guru')
    ->name('guru.')
    ->group(function () {
        // 01. DAFTAR NILAI SISWA
        Route::get('/student-grades', [StudentGradeController::class, 'index'])->name('student-grades.index');

        // 02. DETAIL NILAI SISWA
        Route::get('/student-grades/{student}', [StudentGradeController::class, 'show'])
            ->whereNumber('student')
            ->name('student-grades.show');
    });

/*
|--------------------------------------------------------------------------
| 10. NILAI SISWA GURU — SALUANG
|--------------------------------------------------------------------------
|
| Menampilkan nilai siswa dari database.
| Mendukung detail nilai dan ekspor laporan Excel.
|
| Semua endpoint wajib login.
| Pemeriksaan role Guru dilakukan di Controller.
|
*/

Route::middleware('auth')
    ->prefix('guru')
    ->name('guru.')
    ->group(function () {
        /*
        |--------------------------------------------------------------------------
        | 10.1. DAFTAR NILAI SISWA
        |--------------------------------------------------------------------------
        */

        Route::get('/student-grades', [StudentGradeController::class, 'index'])->name('student-grades.index');

        /*
        |--------------------------------------------------------------------------
        | 10.2. UNDUH REKAP NILAI EXCEL
        |--------------------------------------------------------------------------
        */

        Route::get('/student-grades/export/excel', [StudentGradeExportController::class, 'excel'])->name('student-grades.export.excel');

        /*
        |--------------------------------------------------------------------------
        | 10.3. DETAIL NILAI SATU SISWA
        |--------------------------------------------------------------------------
        */

        Route::get('/student-grades/{student}', [StudentGradeController::class, 'show'])
            ->whereNumber('student')
            ->name('student-grades.show');

        // 10.3. UNDUH REKAP NILAI PDF
        Route::get('/student-grades/export/pdf', [StudentGradePdfController::class, 'download'])->name('student-grades.export.pdf');
    });

/*
|--------------------------------------------------------------------------
| 11. HUBUNGI ORANG TUA GURU — SALUANG
|--------------------------------------------------------------------------
|
| Mengambil narahubung Ayah / Ibu / Wali dari database.
| Hanya hubungan yang berstatus approved yang ditampilkan.
|
*/

Route::middleware('auth')
    ->prefix('guru')
    ->name('guru.')
    ->group(function () {
        // 11.1. DAFTAR KONTAK ORANG TUA
        Route::get('/parent-contacts', [ParentContactController::class, 'index'])->name('parent-contacts.index');

        // 11.2. DETAIL NARAHUBUNG ORANG TUA
        Route::get('/parent-contacts/{studentGuardian}', [ParentContactController::class, 'show'])
            ->whereNumber('studentGuardian')
            ->name('parent-contacts.show');
    });

/*
|--------------------------------------------------------------------------
| 12. PROFIL GURU — SALUANG
|--------------------------------------------------------------------------
|
| Mengelola profil akun Guru yang sedang login.
|
*/

Route::middleware('auth')
    ->prefix('guru')
    ->name('guru.')
    ->group(function () {
        // 12.1. SIMPAN INFORMASI PROFIL
        Route::patch('/profile', [GuruProfileController::class, 'update'])->name('profile.update');

        // 12.2. UNGGAH / GANTI FOTO PROFIL
        Route::post('/profile/photo', [GuruProfileController::class, 'updatePhoto'])->name('profile.photo.update');

        // 12.3. HAPUS FOTO PROFIL
        Route::delete('/profile/photo', [GuruProfileController::class, 'destroyPhoto'])->name('profile.photo.destroy');

        // 12.4. UBAH KATA SANDI
        Route::patch('/profile/password', [GuruProfileController::class, 'updatePassword'])->name('profile.password.update');
    });

/* CATATAN GURU — hanya pengguna terautentikasi, role diverifikasi controller */
Route::middleware('auth')->group(function () {
    Route::get('/guru/student-grades/{student}/feedback', [\App\Http\Controllers\Guru\StudentFeedbackController::class, 'show'])
        ->whereNumber('student')
        ->name('guru.student-feedback.show');
    Route::put('/guru/student-grades/{student}/feedback', [\App\Http\Controllers\Guru\StudentFeedbackController::class, 'update'])
        ->whereNumber('student')
        ->name('guru.student-feedback.update');
    Route::get('/siswa/teacher-notes', [\App\Http\Controllers\Siswa\TeacherNoteController::class, 'index'])->name('siswa.teacher-notes.index');
});
