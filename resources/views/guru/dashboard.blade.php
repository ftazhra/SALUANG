<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Dashboard Guru — SALUANG</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('css/guru.css') }}?v=10">

    <link rel="stylesheet" href="{{ asset('css/guru-materi.css') }}?v=28">
    <script src="{{ asset('js/guru-materi.js') }}?v=19" defer></script>

    <link rel="stylesheet" href="{{ asset('css/guru-kuis.css') }}?v=20261111b">
    <script src="{{ asset('js/guru-matching-preview.js') }}?v=20261111b" defer></script>
    <script src="{{ asset('js/guru-kuis.js') }}?v=20261111b" defer></script>

    <link rel="stylesheet" href="{{ asset('css/guru-evaluasi.css') }}?v=20261110a">
    <script src="{{ asset('js/guru-evaluasi.js') }}?v=9" defer></script>

    <link rel="stylesheet" href="{{ asset('css/guru-forum.css') }}?v=7">
    <script src="{{ asset('js/guru-forum.js') }}?v=4" defer></script>

    <link rel="stylesheet" href="{{ asset('css/guru-nilai.css') }}?v=4">
    <script src="{{ asset('js/guru-nilai.js') }}?v=6" defer></script>

    <link rel="stylesheet" href="{{ asset('css/guru-siswa.css') }}?v=5">
    <script src="{{ asset('js/guru-siswa.js') }}?v=6" defer></script>

    <link rel="stylesheet" href="{{ asset('css/guru-orang-tua.css') }}?v=5">
    <script src="{{ asset('js/guru-orang-tua.js') }}?v=4" defer></script>

    <link rel="stylesheet" href="{{ asset('css/guru-profil.css') }}?v=6">
    <script src="{{ asset('js/guru-profil.js') }}?v=4" defer></script>

    <link rel="stylesheet" href="{{ asset('css/saluang-selects.css') }}?v=3">
    <script src="{{ asset('js/guru-logout.js') }}?v=1" defer></script>
    <link rel="stylesheet" href="{{ asset('css/website-notifications.css') }}?v=5">
</head>

<body class="gd-page">

    {{-- =====================================================
         01. SIDEBAR GURU
         ===================================================== --}}
    <aside class="gd-sidebar" id="guruSidebar">
        <div class="gd-brand">
            <img src="{{ asset('images/saluang-logo-transparent.png') }}" alt="Logo SALUANG">
        </div>
        <nav class="gd-nav" aria-label="Navigasi Guru">

            {{-- TOMBOL LIPAT SIDEBAR --}}
            <button type="button" class="gd-fold" id="guruFold" title="Perkecil sidebar" aria-label="Perkecil sidebar"
                aria-expanded="true">
                <i class="bi bi-layout-sidebar-inset"></i>
            </button>

            {{-- BERANDA --}}
            <button type="button" class="gd-menu active" data-guru-page="beranda">
                <i class="bi bi-house-door"></i>
                <span>Beranda</span>
            </button>

            {{-- KELOLA MATERI --}}
            <button type="button" class="gd-menu" data-guru-page="materi">
                <i class="bi bi-book"></i>
                <span>Kelola Materi</span>
            </button>

            {{-- KELOLA SOAL KUIS --}}
            <button type="button" class="gd-menu" data-guru-page="kuis">
                <i class="bi bi-clipboard-check"></i>
                <span>Kelola Soal Kuis</span>
            </button>

            {{-- KELOLA EVALUASI --}}
            <button type="button" class="gd-menu" data-guru-page="evaluasi">
                <i class="bi bi-check2-circle"></i>
                <span>Kelola Evaluasi</span>
            </button>

            {{-- FORUM DISKUSI --}}
            <button type="button" class="gd-menu" data-guru-page="forum">
                <i class="bi bi-chat-dots"></i>
                <span>Forum Diskusi</span>
            </button>

            {{-- NILAI SISWA --}}
            <button type="button" class="gd-menu" data-guru-page="nilai">
                <i class="bi bi-bar-chart"></i>
                <span>Nilai Siswa</span>
            </button>

            <button type="button" class="gd-menu" data-guru-page="students">
                <i class="bi bi-people"></i>
                <span>Daftar Siswa</span>
            </button>

            {{-- HUBUNGI ORANG TUA --}}
            <button type="button" class="gd-menu" data-guru-page="orang-tua">
                <i class="bi bi-chat-left-text"></i>
                <span>Hubungi Orang Tua</span>
            </button>

            {{-- PROFIL --}}
            <button type="button" class="gd-menu" data-guru-page="profil">
                <i class="bi bi-person"></i>
                <span>Profil</span>
            </button>
        </nav>

        {{-- LOGOUT --}}
        <div class="gd-footer">
            @auth
                <form action="{{ route('logout') }}" method="POST" data-gd-logout-form>
                    @csrf
                    <button type="submit" class="gd-exit">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Keluar</span>
                    </button>
                </form>
            @endauth
        </div>
    </aside>

    {{-- =====================================================
         02. KONTEN UTAMA
         ===================================================== --}}
    <div class="gd-main" id="guruMain">

        {{-- =================================================
             03. TOPBAR
             ================================================= --}}
        <header class="gd-topbar">
            <strong>Dashboard Guru</strong>
            <div class="gd-topbar-tools">

                {{-- 03.1 NOTIFIKASI --}}
                <div class="gd-dropdown">
                    <button type="button" class="gd-icon-btn" id="guruNotifButton" aria-label="Notifikasi"
                        aria-expanded="false" aria-controls="guruNotifMenu">
                        <i class="bi bi-bell"></i>
                        <span class="website-notification-count" data-notification-count
                            @if (($unreadNotificationCount ?? 0) < 1) hidden @endif>{{ $unreadNotificationCount ?? 0 }}</span>
                    </button>
                    <div class="gd-dropdown-menu gd-notif-menu website-notification-menu" id="guruNotifMenu" hidden>
                        <strong>Notifikasi</strong>
                        <div class="website-notification-list">
                            @forelse ($notifications ?? [] as $notification)
                                <article class="website-notification-item" data-notification-item
                                    data-notification-unread="{{ $notification['is_read'] ? 'false' : 'true' }}">
                                    <a class="website-notification-link{{ $notification['is_read'] ? '' : ' is-unread' }}"
                                        href="{{ $notification['url'] }}">
                                        <strong>{{ $notification['title'] }}</strong>
                                        <span>{{ $notification['message'] }}</span>
                                        <small>{{ $notification['is_read'] ? 'Dibaca' : 'Belum dibaca' }} ·
                                            {{ $notification['date']?->format('d M Y, H:i') ?? '-' }}</small>
                                    </a>
                                    <button type="button" class="website-notification-delete"
                                        data-notification-delete="{{ route('notifications.destroy', $notification['id']) }}"
                                        aria-label="Hapus notifikasi: {{ $notification['title'] }}"
                                        title="Hapus notifikasi">×</button>
                                </article>
                            @empty
                                <p class="website-notification-empty" data-notification-empty>Belum ada notifikasi
                                    baru.</p>
                            @endforelse
                        </div>
                        <p class="website-notification-error" data-notification-error role="status" hidden></p>
                    </div>
                </div>

                {{-- 03.2 DROPDOWN PROFIL --}}
                <div class="gd-dropdown">
                    <button type="button" class="gd-user gd-user-button" id="guruProfileButton"
                        aria-label="Menu profil" aria-expanded="false" aria-controls="guruProfileMenu">
                        <span class="gd-avatar" id="gdTopbarAvatar"
                            data-initial="{{ mb_strtoupper(mb_substr(auth()->user()?->name ?? 'G', 0, 1)) }}">
                            @if (auth()->user()?->profile_photo_path)
                                <img src="{{ asset('storage/' . auth()->user()->profile_photo_path) }}"
                                    alt="Foto profil Guru">
                            @else
                                {{ mb_strtoupper(mb_substr(auth()->user()?->name ?? 'G', 0, 1)) }}
                            @endif
                        </span>
                        <span class="gd-user-info">
                            <strong>
                                {{ auth()->user()?->name ?? 'Guru SALUANG' }}
                            </strong>
                            <small>Guru IPA</small>
                        </span>
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <div class="gd-dropdown-menu gd-profile-menu" id="guruProfileMenu" hidden>
                        <button type="button" class="gd-dropdown-item" data-guru-page="profil">
                            <i class="bi bi-person"></i>
                            Profil Saya
                        </button>
                        <button type="button" class="gd-dropdown-item" data-guru-page="profil"
                            data-guru-scroll-settings>
                            <i class="bi bi-gear"></i>
                            Pengaturan
                        </button>
                        <div class="gd-dropdown-divider"></div>
                        <form action="{{ route('logout') }}" method="POST" data-gd-logout-form>
                            @csrf
                            <button type="submit" class="gd-dropdown-item gd-dropdown-danger">
                                <i class="bi bi-box-arrow-right"></i>
                                Keluar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        {{-- =================================================
             04. HALAMAN-HALAMAN GURU
             ================================================= --}}
        <main class="gd-content">

            {{-- =================================================
                 05. BERANDA GURU
                 ================================================= --}}
            <section class="gd-home" data-guru-section="beranda">

                {{-- 05.1 BANNER --}}
                <div class="gd-hero">
                    <span>RUANG GURU · SALUANG</span>
                    <h1>
                        Halo, {{ auth()->user()?->name ?? 'Guru' }}! 👋
                    </h1>
                    <p>
                        Kelola materi pembelajaran, soal evaluasi,
                        dan pantau perkembangan siswa Anda.
                    </p>
                </div>

                {{-- 05.2 KARTU STATISTIK --}}
                @php
                    // Hitung dari database setiap kali dashboard dimuat, bukan angka tetap.
                    $guruId = auth()->id();
                    $quizModel = new \App\Models\QuizQuestion();
                    $evaluationModel = new \App\Models\EvaluationQuestion();
                    $quizTable = $quizModel->getTable();
                    $evaluationTable = $evaluationModel->getTable();
                    $chapterIds = \App\Models\Chapter::query()->where('user_id', $guruId)->select('id');
                    $quizCount = \Illuminate\Support\Facades\Schema::hasTable($quizTable)
                        ? \App\Models\QuizQuestion::query()
                            ->whereHas('subchapter', fn($query) => $query->whereIn('chapter_id', $chapterIds))
                            ->count()
                        : 0;
                    $evaluationCount = \Illuminate\Support\Facades\Schema::hasTable($evaluationTable)
                        ? \App\Models\EvaluationQuestion::query()->where('user_id', $guruId)->count()
                        : 0;
                    $materialCount = \Illuminate\Support\Facades\Schema::hasTable('chapters')
                        ? \App\Models\Chapter::query()->where('user_id', $guruId)->count()
                        : 0;
                    $registeredStudentCount = \App\Models\User::query()->where('role', 'siswa')->count();
                @endphp
                <div class="gd-stats">
                    <button type="button" class="gd-card gd-stat gd-stat-link" data-guru-page="materi"
                        aria-label="Buka Kelola Materi">
                        <i class="bi bi-book"></i>
                        <small>Total Materi (Bab tersimpan)</small>
                        <strong id="gdTotalMaterials">{{ $materialCount }}</strong>
                    </button>
                    <button type="button" class="gd-card gd-stat gd-stat-link" data-guru-page="kuis"
                        aria-label="Buka Kelola Soal Kuis">
                        <i class="bi bi-clipboard-check"></i>
                        <small>Total Soal Kuis</small>
                        <strong id="gdTotalQuizQuestions">{{ $quizCount }}</strong>
                    </button>
                    <button type="button" class="gd-card gd-stat gd-stat-link" data-guru-page="students"
                        aria-label="Buka Daftar Siswa">
                        <i class="bi bi-mortarboard"></i>
                        <small>Total Siswa Terdaftar</small>
                        <strong id="gdTotalStudents">{{ $registeredStudentCount }}</strong>
                    </button>
                    <button type="button" class="gd-card gd-stat gd-stat-link" data-guru-page="evaluasi"
                        aria-label="Buka Kelola Evaluasi">
                        <i class="bi bi-check2-circle"></i>
                        <small>Total Soal Evaluasi</small>
                        <strong id="gdTotalEvaluations">{{ $evaluationCount }}</strong>
                    </button>
                </div>

                {{-- 05.3 MATERI PEMBELAJARAN --}}
                <article class="gd-card gd-material">
                    <h2>Materi Pembelajaran</h2>
                    <div id="gdHomeMaterialList" aria-live="polite">
                        <p class="gd-muted">Memuat materi dari database...</p>
                    </div>
                    <button type="button" class="gd-primary" data-guru-page="materi">
                        Kelola Materi
                    </button>
                </article>

                {{-- =================================================
                     05.4 NAVIGASI BAWAH BERANDA
                     HANYA NEXT, TANPA PREVIOUS
                     ================================================= --}}
                <div class="gd-page-navigation gd-navigation-home">
                    <button type="button" class="gd-nav-btn gd-nav-btn-next" data-guru-next>
                        Next
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            </section>

            {{-- =================================================
                 06. HALAMAN GURU LAINNYA
                 ================================================= --}}

            {{-- HALAMAN KELOLA MATERI GURU --}}
            @include('guru.partials.materi')

            {{-- HALAMAN KELOLA SOAL KUIS GURU --}}
            @include('guru.partials.kuis')

            {{-- HALAMAN KELOLA EVALUASI GURU --}}
            @include('guru.partials.evaluasi')

            {{-- HALAMAN FORUM DISKUSI GURU --}}
            @include('guru.partials.forum')

            {{-- HALAMAN NILAI SISWA GURU --}}
            @include('guru.partials.nilai')

            @include('guru.partials.students')

            {{-- HALAMAN HUBUNGI ORANG TUA GURU --}}
            @include('guru.partials.orang-tua')

            {{-- HALAMAN PROFIL GURU --}}
            @include('guru.partials.profil')
        </main>
    </div>

    {{-- =====================================================
         07. JAVASCRIPT DASHBOARD GURU
         ===================================================== --}}
    <script src="{{ asset('js/guru-dashboard.js') }}?v=2"></script>
    <script src="{{ asset('js/website-notifications.js') }}?v=3" defer></script>

    {{-- Modal konfirmasi Keluar Guru (berlaku untuk sidebar dan dropdown profil) --}}
    <div id="gdLogoutModal" class="gd-logout-modal" hidden>
        <div class="gd-logout-backdrop" data-gd-logout-cancel></div>
        <div class="gd-logout-dialog" role="dialog" aria-modal="true" aria-labelledby="gdLogoutTitle"
            aria-describedby="gdLogoutDescription" tabindex="-1">
            <button type="button" class="gd-logout-close" aria-label="Tutup" data-gd-logout-cancel>
                <i class="bi bi-x-lg"></i>
            </button>
            <img class="gd-logout-mascot" src="{{ asset('images/bekantan-logout-ceria.png') }}"
                alt="Bekantan SALUANG melambaikan tangan">
            <h2 id="gdLogoutTitle">Yakin ingin keluar?</h2>
            <p id="gdLogoutDescription">Kamu akan keluar dari akun SALUANG. Pastikan semua aktivitas telah disimpan
                sebelum melanjutkan.</p>
            <div class="gd-logout-actions">
                <button type="button" class="gd-logout-cancel" data-gd-logout-cancel>Batal</button>
                <button type="button" class="gd-logout-confirm" id="gdLogoutConfirm">Ya, Keluar</button>
            </div>
        </div>
    </div>
</body>

</html>
