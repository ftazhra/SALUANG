<!DOCTYPE html>
<html lang="id">

<head>
    <!-- =====================================================
        01. PENGATURAN DASAR
        ===================================================== -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard Siswa — SALUANG</title>

    <!-- =====================================================
        02. FONT DAN BOOTSTRAP
        ===================================================== -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet">

    <!-- =====================================================
        03. CSS DASHBOARD SISWA
        ===================================================== -->
    <link rel="stylesheet" href="{{ asset('css/siswa.css') }}?v=24">

    <!-- =====================================================
        04. CSS KHUSUS EVALUASI
        ===================================================== -->
    <link rel="stylesheet" href="{{ asset('css/siswa-evaluasi.css') }}?v=49">
    <link rel="stylesheet" href="{{ asset('css/siswa-profil.css') }}?v=2">
</head>

<body class="sd-page">

    <!-- 04. Daftar subbab -->
    @php
        $subbab = [
            '1.1 Makhluk Hidup atau Benda Mati?',
            '1.2 Mengapa Makhluk Hidup Dikelompokkan?',
            '1.3 Makhluk Hidup Beraneka Ragam',
        ];
    @endphp

    <!-- =========================================
        05. SIDEBAR DESKTOP
        ========================================= -->
    <aside class="sd-sidebar" id="sidebar">

        <div class="sd-brand">
            <img src="{{ asset('images/saluang-logo-transparent.png') }}" alt="Logo SALUANG">
        </div>

        <nav class="sd-nav" aria-label="Navigasi Siswa">

            <!-- 05.1. Tombol lipat sidebar -->
            <button type="button" class="sd-fold" id="foldSidebar" title="Perkecil sidebar"
                aria-label="Perkecil sidebar" aria-expanded="true">
                <i class="bi bi-layout-sidebar-inset" aria-hidden="true"></i>
            </button>

            <!-- 05.2. Beranda -->
            <button type="button" class="sd-menu active" data-page="0">
                <i class="bi bi-house-door"></i>
                <span>Beranda</span>
            </button>

            <!-- 05.3. Menu Materi -->
            <button type="button" class="sd-menu" data-page="7">
                <i class="bi bi-book"></i>
                <span>Materi</span>
            </button>

            <!-- Daftar Bab 1 tetap terlihat, bukan dropdown -->
            <div id="desktopMateri">
                <div class="sd-submenu">

                    <button type="button" class="sd-chapter-button" data-page="1">
                        <strong>BAB 1</strong>
                        <span>Karakteristik dan Klasifikasi Makhluk Hidup</span>
                    </button>

                    @foreach ($subbab as $index => $judul)
                        <button type="button" class="sd-topic" data-page="{{ $index + 2 }}">
                            {{ $judul }}
                        </button>
                    @endforeach

                </div>
            </div>

            <!-- 05.4. Evaluasi -->
            <button type="button" class="sd-menu" data-page="5">
                <i class="bi bi-clipboard-check"></i>
                <span>Evaluasi</span>
            </button>

            <!-- 05.5. Forum Diskusi -->
            <button type="button" class="sd-menu" data-page="6">
                <i class="bi bi-chat-dots"></i>
                <span>Forum Diskusi</span>
            </button>

            <!-- 05.6. Profil -->
            <button type="button" class="sd-menu" data-page="8">
                <i class="bi bi-person"></i>
                <span>Profil</span>
            </button>
        </nav>

        <!-- 05.7. Tombol keluar -->
        <div class="sd-footer">
            @auth
                <form action="{{ route('logout') }}" method="POST" data-sd-logout-form>
                    @csrf
                    <button type="submit" class="sd-exit">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Keluar</span>
                    </button>
                </form>
            @else
                <a href="{{ route('beranda') }}" class="sd-exit">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Keluar dari Pratinjau</span>
                </a>
            @endauth
        </div>

    </aside>

    <!-- =========================================
        06. SIDEBAR MOBILE — BOOTSTRAP OFFCANVAS
        ========================================= -->
    <div class="offcanvas offcanvas-start sd-offcanvas" id="mobileSidebar" tabindex="-1"
        aria-labelledby="mobileSidebarTitle">
        <div class="offcanvas-header">
            <img src="{{ asset('images/saluang-logo-transparent.png') }}" alt="SALUANG" id="mobileSidebarTitle"
                width="145">
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"
                aria-label="Tutup menu"></button>
        </div>

        <div class="offcanvas-body d-flex flex-column">
            <nav class="sd-nav">

                <button type="button" class="sd-menu" data-page="0">
                    <i class="bi bi-house-door"></i>
                    <span>Beranda</span>
                </button>

                <!-- 06. Menu Materi Mobile -->
                <button type="button" class="sd-menu" data-page="7">
                    <i class="bi bi-book"></i>
                    <span>Materi</span>
                </button>

                <div id="mobileMateri">
                    <div class="sd-submenu">

                        <button type="button" class="sd-chapter-button" data-page="1">
                            <strong>BAB 1</strong>
                            <span>Karakteristik dan Klasifikasi Makhluk Hidup</span>
                        </button>

                        @foreach ($subbab as $index => $judul)
                            <button type="button" class="sd-topic" data-page="{{ $index + 2 }}">
                                {{ $judul }}
                            </button>
                        @endforeach

                    </div>
                </div>

                <button type="button" class="sd-menu" data-page="5">
                    <i class="bi bi-clipboard-check"></i>
                    <span>Evaluasi</span>
                </button>

                <button type="button" class="sd-menu" data-page="6">
                    <i class="bi bi-chat-dots"></i>
                    <span>Forum Diskusi</span>
                </button>

                <button type="button" class="sd-menu" data-page="8">
                    <i class="bi bi-person"></i>
                    <span>Profil</span>
                </button>
            </nav>

            <div class="sd-footer">
                @auth
                    <form action="{{ route('logout') }}" method="POST" data-sd-logout-form>
                        @csrf
                        <button type="submit" class="sd-exit">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>Keluar</span>
                        </button>
                    </form>
                @else
                    <a href="{{ route('beranda') }}" class="sd-exit">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Keluar dari Pratinjau</span>
                    </a>
                @endauth
            </div>
        </div>
    </div>

    <!-- =========================================
        07. KONTEN UTAMA
        ========================================= -->
    <div class="sd-main" id="main">

        <!-- 07.1. Topbar -->
        <header class="sd-topbar">
            <div class="d-flex align-items-center gap-2 flex-grow-1">

                <button type="button" class="sd-icon d-lg-none" data-bs-toggle="offcanvas"
                    data-bs-target="#mobileSidebar" aria-label="Buka menu">
                    <i class="bi bi-list"></i>
                </button>

                <label class="sd-search">
                    <i class="bi bi-search"></i>
                    <input type="search" id="searchTopic"
                        placeholder="Cari materi, topik, atau kata kunci..." aria-label="Cari materi">
                </label>
            </div>

            <div class="sd-tools">

                <!-- 07.2. Notifikasi -->
                <div class="dropdown">
                    <button type="button" class="sd-icon" data-bs-toggle="dropdown" aria-expanded="false"
                        aria-label="Notifikasi">
                        <i class="bi bi-bell"></i>
                    </button>

                    <div class="dropdown-menu dropdown-menu-end p-3 sd-dropdown">
                        <strong>Notifikasi</strong>
                        <p class="mt-2 mb-0 text-secondary">
                            Belum ada notifikasi baru.
                        </p>
                    </div>
                </div>

                <!-- 07.3. Profil dan dropdown -->
                <div class="dropdown">
                    <button type="button" class="sd-user" data-bs-toggle="dropdown" aria-expanded="false"
                        aria-label="Menu profil">
                        <span class="sd-avatar">
                            @if (auth()->user()?->profile_photo_path)
                                <img src="{{ asset('storage/' . auth()->user()->profile_photo_path) }}"
                                    alt="Foto profil" class="sd-topbar-avatar-img">
                            @else
                                {{ strtoupper(substr(auth()->user()?->name ?? 'Siswa', 0, 1)) }}
                            @endif
                        </span>

                        <span class="sd-user-meta">
                            <strong>{{ auth()->user()?->name ?? 'Pengguna SALUANG' }}</strong>
                            <small>
                                Siswa · Kelas {{ auth()->user()?->kelas ?: 'belum diisi' }}
                            </small>
                        </span>

                        <i class="bi bi-chevron-down"></i>
                    </button>

                    <ul class="dropdown-menu dropdown-menu-end sd-dropdown">
                        <li>
                            <button type="button" class="dropdown-item" data-page="8">
                                <i class="bi bi-person me-2"></i>
                                Profil Saya
                            </button>
                        </li>

                        <li>
                            <button type="button" class="dropdown-item" data-profile-settings>
                                <i class="bi bi-gear me-2"></i>
                                Pengaturan
                            </button>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>

                        <li>
                            @auth
                                <form action="{{ route('logout') }}" method="POST" data-sd-logout-form>
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="bi bi-box-arrow-right me-2"></i>
                                        Keluar
                                    </button>
                                </form>
                            @else
                                <a href="{{ route('beranda') }}" class="dropdown-item text-danger">
                                    <i class="bi bi-box-arrow-right me-2"></i>
                                    Keluar
                                </a>
                            @endauth
                        </li>
                    </ul>
                </div>

            </div>
        </header>

        <main class="sd-content">

            <!-- =========================================
                08. HALAMAN BERANDA
                ========================================= -->
            <section id="homePage">

                <!-- 08.1. Banner -->
                <div class="sd-hero">
                    <div>
                        <h1>Halo, {{ auth()->user()?->name ?? 'Siswa SALUANG' }}! 👋</h1>
                        <p>
                            Siap melanjutkan petualangan belajarmu
                            tentang kehidupan lahan basah hari ini?
                        </p>
                    </div>

                    <div class="sd-quote">
                        <i class="bi bi-leaf"></i>
                        “Lahan basah bukan hanya rumah bagi makhluk hidup,
                        tapi juga masa depan kita.”
                    </div>
                </div>

                <!-- 08.2. Statistik -->
                <div class="row g-3 mt-1">

                    <div class="col-12 col-md-4">
                        <div class="sd-card sd-stat">
                            <img src="{{ asset('images/progress-assets/progress-1.png') }}"
                                alt="Ilustrasi progres belajar">
                            <div>
                                <small>Progres Belajar</small>
                                <strong>0%</strong>
                                <p>0 dari 3 materi selesai</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="sd-card sd-stat">
                            <img src="{{ asset('images/progress-assets/progress-2.png') }}"
                                alt="Ilustrasi kuis">
                            <div>
                                <small>Kuis Selesai</small>
                                <strong>1</strong>
                                <p>Hasil tersimpan pada akunmu</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="sd-card sd-stat">
                            <img src="{{ asset('images/progress-assets/progress-3.png') }}"
                                alt="Ilustrasi evaluasi">
                            <div>
                                <small>Evaluasi Pembelajaran</small>
                                <strong>1</strong>
                                <p>Evaluasi lintas Bab dan Subbab</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 08.3. Lanjutkan Belajar dan Aktivitas -->
                <div class="row g-3 mt-1">

                    <div class="col-12 col-xl-8">
                        <article class="sd-card h-100">
                            <h2 class="sd-heading">
                                <i class="bi bi-bar-chart-line"></i>
                                Lanjutkan Belajar
                            </h2>

                            <div class="sd-home-lesson">
                                <img src="{{ asset('images/lesson-interaksi-makhluk-hidup.png') }}"
                                    alt="Ekosistem lahan basah">

                                <div>
                                    <span class="sd-pill">MATERI PEMBELAJARAN</span>

                                    <h3>
                                        Karakteristik dan Klasifikasi Makhluk Hidup
                                    </h3>

                                    <span class="sd-muted">1 Bab · 3 Subbab</span>
                                    <div class="sd-progress"></div>

                                    <button type="button" class="sd-primary" data-page="1">
                                        Lihat Materi
                                    </button>
                                </div>
                            </div>
                        </article>
                    </div>

                    <div class="col-12 col-xl-4">
                        <article class="sd-card h-100">
                            <h2 class="sd-heading">
                                <i class="bi bi-bar-chart-line"></i>
                                Aktivitas Terbaru
                            </h2>

                            <div class="sd-empty">
                                <img src="{{ asset('images/saluang-materi-interaktif.png') }}"
                                    alt="Ilustrasi SALUANG">
                                <span>Aktivitas belajarmu akan muncul di sini.</span>
                            </div>
                        </article>
                    </div>
                </div>

                <!-- 08.4. Pencapaian Belajar -->
                <article class="sd-card mt-3">
                    <h2 class="sd-achievement-heading">
                        Pencapaian Belajar
                    </h2>

                    <p class="sd-muted">
                        Progres dihitung dari materi yang telah selesai.
                    </p>

                    <div class="d-flex justify-content-between align-items-end mt-4 gap-3">
                        <div>
                            <span class="sd-muted">BAB 1</span>

                            <h3 class="sd-achievement-title">
                                Karakteristik dan Klasifikasi Makhluk Hidup
                            </h3>

                            <span class="sd-muted">Materi 0/3</span>
                        </div>

                        <strong class="sd-status">Belum dimulai</strong>
                    </div>

                    <div class="sd-progress"></div>
                </article>

                <!-- 08.5. Navigasi Beranda: hanya tombol Next -->
                <div class="sd-navigation sd-navigation-home">
                    <button type="button" class="sd-nav-btn next" data-page="7">
                        Next
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>

            </section>

            <!-- =========================================
    08.6. HALAMAN PENGANTAR MATERI
    ========================================= -->
            <section id="materialOverviewPage" hidden>

                <!-- 08.6.1. NAVIGASI ATAS -->
                <div class="sd-navigation">

                    <button type="button" class="sd-nav-btn" data-page="0">
                        <i class="bi bi-chevron-left"></i>
                        Previous
                    </button>

                    <button type="button" class="sd-nav-btn next" data-page="1">
                        Next
                        <i class="bi bi-chevron-right"></i>
                    </button>

                </div>

                <!-- 08.6.2. KARTU UTAMA MATERI -->
                <article class="sd-card sd-material-overview">

                    <div class="sd-material-overview-header">
                        <div>
                            <h1 class="sd-material-overview-title">
                                Materi Pembelajaran
                            </h1>

                            <p class="sd-material-overview-subtitle">
                                Pilih bab untuk melihat subbab dan materi di dalamnya.
                            </p>
                        </div>
                    </div>

                    <!-- 08.6.3. KARTU BAB 1 -->
                    <div class="sd-material-overview-grid">

                        <article class="sd-material-chapter-card">

                            <span class="sd-material-chapter-label">
                                BAB 1
                            </span>

                            <h2 class="sd-material-chapter-title">
                                Karakteristik dan Klasifikasi Makhluk Hidup
                            </h2>

                            <div class="sd-material-chapter-topics">

                                <button type="button" class="sd-material-topic-button" data-page="2">
                                    1.1 Makhluk Hidup atau Benda Mati?
                                </button>

                                <button type="button" class="sd-material-topic-button" data-page="3">
                                    1.2 Mengapa Makhluk Hidup Dikelompokkan?
                                </button>

                                <button type="button" class="sd-material-topic-button" data-page="4">
                                    1.3 Makhluk Hidup Beraneka Ragam
                                </button>

                            </div>

                            <button type="button" class="sd-material-chapter-open" data-page="1">
                                Lihat Selengkapnya
                            </button>

                        </article>

                    </div>

                </article>

                <!-- 08.6.4. NAVIGASI BAWAH HALAMAN MATERI -->
                <div class="sd-navigation">

                    <button type="button" class="sd-nav-btn" data-page="0">
                        <i class="bi bi-chevron-left"></i>
                        Previous
                    </button>

                    <button type="button" class="sd-nav-btn next" data-page="1">
                        Next
                        <i class="bi bi-chevron-right"></i>
                    </button>

                </div>

            </section>

            <!-- =========================================
                09. HALAMAN PENGENALAN BAB 1
                ========================================= -->
            <section id="chapterPage" hidden>

                <!-- 09.1. Pengantar Bab -->
                <div class="sd-breadcrumb">
                    Beranda › Materi › Bab 1
                </div>

                <div class="sd-chapter-hero">
                    <div>
                        <span class="sd-pill">BAB 1 · PENGENALAN</span>

                        <h1 class="sd-title">
                            Karakteristik dan Klasifikasi Makhluk Hidup
                        </h1>

                        <p class="sd-copy">
                            Di sekitar sungai, rawa, dan permukiman,
                            kita menemukan beragam hewan, tumbuhan,
                            serta benda mati. Pada bab ini, kita akan
                            belajar mengenali ciri makhluk hidup dan
                            memahami cara mengelompokkannya.
                        </p>
                    </div>

                    <img src="{{ asset('images/lesson-interaksi-makhluk-hidup.png') }}"
                        alt="Ilustrasi kehidupan lahan basah">
                </div>

                <!-- 09.2. Navigasi Atas -->
                <div class="sd-navigation">
                    <button type="button" class="sd-nav-btn" data-page="7">
                        <i class="bi bi-chevron-left"></i>
                        Previous
                    </button>

                    <button type="button" class="sd-nav-btn next" data-page="2">
                        Next
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>

                <!-- 09.3. Tujuan Pembelajaran -->
                <article class="sd-card mt-3">
                    <h2 class="sd-heading">
                        <i class="bi bi-bullseye"></i>
                        Tujuan Pembelajaran
                    </h2>

                    <p class="sd-copy">
                        Setelah mempelajari bab ini, siswa diharapkan mampu:
                    </p>

                    <div class="sd-objectives">
                        <div class="sd-objective">
                            <i class="bi bi-search"></i>
                            <strong>Mengidentifikasi</strong>
                            <p>
                                Membedakan makhluk hidup dan benda mati
                                berdasarkan ciri-cirinya.
                            </p>
                        </div>

                        <div class="sd-objective">
                            <i class="bi bi-diagram-3"></i>
                            <strong>Mengelompokkan</strong>
                            <p>
                                Menjelaskan alasan dan manfaat
                                pengelompokan makhluk hidup.
                            </p>
                        </div>

                        <div class="sd-objective">
                            <i class="bi bi-tree"></i>
                            <strong>Mengenali Keragaman</strong>
                            <p>
                                Mengamati keanekaragaman makhluk hidup
                                di lingkungan lahan basah.
                            </p>
                        </div>
                    </div>
                </article>

                <!-- 09.4. Pertanyaan Pemantik -->
                <article class="sd-card mt-3">
                    <h2 class="sd-heading">
                        <i class="bi bi-lightbulb"></i>
                        Pertanyaan Pemantik
                    </h2>

                    <div class="sd-prompt">
                        <p class="sd-copy mb-0">
                            Air sungai dapat bergerak mengalir.
                            Apakah air termasuk makhluk hidup?
                            Apa perbedaannya dengan ikan saluang
                            yang berenang di sungai?
                        </p>
                    </div>

                    <img src="{{ asset('images/lesson-ekosistem-lahan-basah.png') }}" alt="Ekosistem sungai"
                        class="sd-prompt-image">
                </article>

                <!-- 09.5. Navigasi Bawah -->
                <div class="sd-navigation">
                    <button type="button" class="sd-nav-btn" data-page="7">
                        <i class="bi bi-chevron-left"></i>
                        Previous
                    </button>

                    <button type="button" class="sd-nav-btn next" data-page="2">
                        Next
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>

            </section>

            <!-- =========================================
                10. HALAMAN SUBBAB 1.1, 1.2, 1.3
                ========================================= -->
            <section id="lessonPage" hidden>

                <!-- 10.1. Judul dan penanda -->
                <div class="sd-breadcrumb" id="lessonBreadcrumb"></div>
                <span class="sd-pill" id="lessonBadge"></span>
                <h1 class="sd-title" id="lessonTitle"></h1>

                <!-- 10.2. Previous/Next Atas -->
                <div class="sd-navigation" data-lesson-navigation></div>

                <!-- 10.3. SATU Card Materi dan Gambar -->
                <article class="sd-card sd-material-card">
                    <div class="sd-material-grid">

                        <!-- Kiri: penjelasan -->
                        <div class="sd-material-text">
                            <h2 class="sd-material-heading">
                                <i class="bi bi-book-half"></i>
                                Materi Pembelajaran
                            </h2>

                            <div class="sd-copy" id="lessonContent"></div>

                            <div class="sd-fact">
                                <strong>Tahukah Kamu?</strong>
                                <p class="sd-copy" id="lessonFact"></p>
                            </div>
                        </div>

                        <!-- Kanan: gambar dan caption -->
                        <div class="sd-material-media">
                            <img id="lessonImage" class="sd-media-picture" alt="Ilustrasi materi SALUANG">

                            <p class="sd-media-caption" id="mediaCaption"></p>
                        </div>

                    </div>
                </article>

                <!-- 10.4. Kuis Subbab -->
                <article class="sd-card mt-3">
                    <h2 class="sd-quiz-title">Kuis Subbab</h2>

                    <p class="sd-muted mt-2">
                        Pilih satu jawaban. Setelah diklik, kamu langsung
                        mendapat penjelasan benar atau salah.
                    </p>

                    <div class="sd-question-count" id="questionCount"></div>

                    <p id="questionText" class="fw-semibold mb-3"></p>

                    <!-- Pilihan jawaban dibuat JavaScript -->
                    <div class="sd-choices" id="answerChoices"></div>

                    <!-- Penjelasan jawaban langsung -->
                    <div id="answerFeedback" class="sd-answer-feedback" role="status" aria-live="polite"
                        hidden>
                    </div>

                    <!-- Navigasi antarsoal -->
                    <div class="sd-quiz-controls">

                        <button type="button" class="sd-nav-btn" id="previousQuestion">
                            <i class="bi bi-chevron-left"></i>
                            Previous
                        </button>

                        <button type="button" class="sd-nav-btn next" id="nextQuestion">
                            Next
                            <i class="bi bi-chevron-right"></i>
                        </button>

                    </div>
                </article>

                <!-- 10.5. Previous/Next Bawah -->
                <div class="sd-navigation" data-lesson-navigation></div>

            </section>

            {{-- =====================================================
    11. HALAMAN EVALUASI PEMBELAJARAN
    ===================================================== --}}
            <section id="sd-section-evaluasi" hidden>

                {{-- 11.1. HALAMAN AWAL / PENGANTAR EVALUASI --}}
                <div class="sd-eval-screen is-active" data-eval-screen="overview">

                    {{-- 11.1.0. NAVIGASI ATAS EVALUASI --}}
                    <div class="sd-navigation">

                        <button type="button" class="sd-nav-btn" data-page="4">
                            <i class="bi bi-chevron-left"></i>
                            Previous
                        </button>

                        <button type="button" class="sd-nav-btn next" data-page="6">
                            Next
                            <i class="bi bi-chevron-right"></i>
                        </button>

                    </div>

                    <article class="sd-card sd-eval-card sd-eval-intro">

                        {{-- 11.1.1. JUDUL PENGANTAR --}}
                        <div class="sd-eval-overview-header">
                            <div class="sd-eval-intro-heading">
                                <span class="sd-eval-intro-icon">
                                    <i class="bi bi-journal-check"></i>
                                </span>

                                <div>
                                    <h1 class="sd-eval-intro-title">
                                        Evaluasi Akhir
                                    </h1>

                                    <p class="sd-eval-intro-subtitle">
                                        Uji pemahamanmu tentang makhluk hidup,
                                        klasifikasi organisme, interaksi, dan lingkungan lahan basah!
                                    </p>
                                </div>
                            </div>
                        </div>

                        {{-- 11.1.2. INFORMASI EVALUASI --}}
                        <div class="sd-eval-intro-layout">

                            <div class="sd-eval-intro-main">

                                <section class="sd-eval-intro-panel">
                                    <h2 class="sd-eval-intro-panel-title">
                                        <i class="bi bi-info-circle"></i>
                                        Informasi Evaluasi
                                    </h2>

                                    <div class="sd-eval-info-grid">

                                        {{-- KARTU JUMLAH SOAL --}}
                                        <div class="sd-eval-info-card sd-eval-info-card--blue">
                                            <div class="sd-eval-info-image">
                                                <img src="{{ asset('images/evaluasi/eval-jumlah-soal.png') }}"
                                                    alt="Siswa sedang mengerjakan soal">
                                            </div>

                                            <div>
                                                <span>Jumlah Soal</span>
                                                <strong>20 Soal</strong>
                                            </div>
                                        </div>

                                        {{-- KARTU DURASI --}}
                                        <div class="sd-eval-info-card sd-eval-info-card--green">
                                            <div class="sd-eval-info-image">
                                                <img src="{{ asset('images/evaluasi/eval-durasi.png') }}"
                                                    alt="Siswa sedang memperhatikan waktu">
                                            </div>

                                            <div>
                                                <span>Durasi Pengerjaan</span>
                                                <strong>45 Menit</strong>
                                            </div>
                                        </div>

                                        {{-- KARTU KETUNTASAN --}}
                                        <div class="sd-eval-info-card sd-eval-info-card--yellow">
                                            <div class="sd-eval-info-image">
                                                <img src="{{ asset('images/evaluasi/eval-ketuntasan.png') }}"
                                                    alt="Siswa merayakan keberhasilan">
                                            </div>

                                            <div>
                                                <span>Nilai Ketuntasan</span>
                                                <strong>≥ 70</strong>
                                            </div>
                                        </div>

                                    </div>

                                    <div class="sd-eval-intro-material">
                                        <div class="sd-eval-intro-material-icon">
                                            <i class="bi bi-journal-check"></i>
                                        </div>

                                        <div>
                                            <h3>Materi yang Diujikan</h3>
                                            <p>
                                                Karakteristik dan klasifikasi makhluk hidup,
                                                komponen biotik dan abiotik,
                                                keanekaragaman organisme,
                                                serta interaksi dalam lingkungan lahan basah.
                                            </p>
                                        </div>
                                    </div>
                                </section>

                                {{-- 11.1.3. PETUNJUK PENGERJAAN --}}
                                <section class="sd-eval-intro-panel">
                                    <h2 class="sd-eval-intro-panel-title">
                                        <i class="bi bi-list-check"></i>
                                        Petunjuk Pengerjaan
                                    </h2>

                                    <ol class="sd-eval-instructions">
                                        <li>
                                            Bacalah setiap pertanyaan dengan teliti sebelum menentukan jawaban.
                                        </li>
                                        <li>
                                            Evaluasi terdiri dari 20 soal: pilihan ganda A–D,
                                            drag-and-drop gambar, menghubungkan pasangan,
                                            dan memilih gambar yang tepat.
                                        </li>
                                        <li>
                                            Untuk soal drag-and-drop, seret setiap gambar
                                            ke kelompok atau kotak jawaban yang sesuai.
                                        </li>
                                        <li>
                                            Untuk soal menghubungkan, seret bullet hijau
                                            menuju bullet pasangan yang benar hingga
                                            terbentuk garis penghubung.
                                        </li>
                                        <li>
                                            Gunakan tombol Previous, Next, atau daftar nomor soal
                                            untuk berpindah soal. Jawaban dapat diperiksa kembali
                                            selama waktu masih tersedia.
                                        </li>
                                        <li>
                                            Waktu pengerjaan adalah 45 menit.
                                            Periksa seluruh jawaban sebelum menekan tombol
                                            Selesai Evaluasi.
                                        </li>
                                    </ol>

                                </section>

                            </div>

                            {{-- 11.1.4. TUJUAN DAN MOTIVASI --}}
                            <aside class="sd-eval-intro-aside">

                                <section class="sd-eval-intro-panel">
                                    <h2 class="sd-eval-intro-panel-title">
                                        <i class="bi bi-bullseye"></i>
                                        Tujuan Evaluasi
                                    </h2>

                                    <ol class="sd-eval-instructions">
                                        <li>
                                            Mengukur pemahaman siswa tentang ciri-ciri
                                            makhluk hidup dan benda mati.
                                        </li>

                                        <li>
                                            Menilai kemampuan siswa dalam mengelompokkan
                                            makhluk hidup berdasarkan persamaan dan
                                            perbedaan karakteristiknya.
                                        </li>

                                        <li>
                                            Menguji pemahaman siswa tentang keanekaragaman
                                            makhluk hidup dan komponen lingkungan lahan basah.
                                        </li>
                                    </ol>
                                </section>

                                <div class="sd-eval-intro-quote">
                                    <i class="bi bi-lightbulb"></i>
                                    <p>
                                        Belajar dari alam, tumbuh bersama pengetahuan,
                                        dan jadilah penjaga lahan basah!
                                    </p>
                                </div>

                                <div class="sd-eval-intro-start">
                                    <div class="sd-eval-intro-start">
                                        <button type="button" class="sd-eval-primary" data-eval-open="quiz">
                                            Mulai Evaluasi
                                        </button>
                                    </div>
                                </div>

                            </aside>

                        </div>

                    </article>

                </div>

                {{-- =====================================================
    11.1.5. HALAMAN PENGERJAAN EVALUASI AKHIR
    20 Soal — 45 Menit
    ===================================================== --}}

                <div class="sd-eval-screen" data-eval-screen="quiz">

                    {{-- 11.1.5.1. Judul --}}
                    <div class="sd-eval-exam-header">
                        <div class="sd-eval-exam-title-group">
                            <span class="sd-eval-exam-icon">
                                <i class="bi bi-clipboard2-check"></i>
                            </span>

                            <div>
                                <h1>Evaluasi Akhir</h1>
                                <p>Jawablah setiap soal dengan teliti dan jujur!</p>
                            </div>
                        </div>

                        <button type="button" class="sd-eval-outline sd-eval-exam-back"
                            id="sd-eval-quiz-exit">
                            Kembali ke Evaluasi
                        </button>
                    </div>

                    {{-- 11.1.5.2. Area soal dan panel kanan --}}
                    <div class="sd-eval-exam-layout">

                        {{-- Kolom kiri: isi soal --}}
                        <div class="sd-eval-exam-main">

                            <article class="sd-eval-question-card">

                                <div class="sd-eval-question-meta">
                                    <span id="sd-eval-current-number">
                                        Soal 1 dari 20
                                    </span>

                                    <span id="sd-eval-current-type">
                                        Pilihan Ganda
                                    </span>
                                </div>

                                <div class="sd-eval-exam-question-content" id="sd-eval-question-content">

                                    <div class="sd-eval-question-placeholder">
                                        <i class="bi bi-journal-text"></i>

                                        <h2>Soal Nomor 1</h2>

                                        <p>
                                            Kerangka halaman pengerjaan sudah siap.
                                            Isi soal akan dipasang pada tahap berikutnya.
                                        </p>
                                    </div>

                                </div>

                            </article>

                            {{-- Navigasi bawah soal --}}
                            <div class="sd-eval-exam-navigation">

                                <button type="button" class="sd-nav-btn" id="sd-eval-quiz-prev" hidden>
                                    <i class="bi bi-chevron-left"></i>
                                    Previous
                                </button>

                                <button type="button" class="sd-nav-btn next" id="sd-eval-quiz-next">
                                    Next
                                    <i class="bi bi-chevron-right"></i>
                                </button>

                                <span class="sd-eval-quiz-last-note" id="sd-eval-quiz-last-note" hidden>
                                    Kamu sudah berada di nomor terakhir.
                                    Tombol pengumpulan jawaban akan ditambahkan
                                    setelah seluruh soal selesai dibuat.
                                </span>

                            </div>

                        </div>

                        {{-- Kolom kanan: timer dan nomor soal --}}
                        <aside class="sd-eval-exam-sidebar">

                            <div class="sd-eval-exam-timer-panel">

                                <div class="sd-eval-timer-header">
                                    <i class="bi bi-clock-history"></i>

                                    <div>
                                        <span>Waktu Tersisa</span>

                                        <strong id="sd-eval-timer" role="timer"
                                            aria-label="Sisa waktu evaluasi">
                                            45:00
                                        </strong>
                                    </div>
                                </div>

                                <div class="sd-eval-exam-number-panel">

                                    <h2>
                                        <i class="bi bi-grid-3x3-gap"></i>
                                        Daftar Soal
                                    </h2>

                                    <div class="sd-eval-number-grid" id="sd-eval-number-grid"
                                        aria-label="Pilih nomor soal">
                                        {{-- Nomor 1–20 dibuat oleh JavaScript --}}
                                    </div>

                                    <div class="sd-eval-number-legend">
                                        <span>
                                            <i class="sd-eval-legend-dot is-active"></i>
                                            Aktif
                                        </span>

                                        <span>
                                            <i class="sd-eval-legend-dot is-unanswered"></i>
                                            Belum dijawab
                                        </span>

                                        <span>
                                            <i class="sd-eval-legend-dot is-answered"></i>
                                            Terjawab
                                        </span>
                                    </div>

                                </div>

                                <p class="sd-eval-timer-notice" id="sd-eval-timer-notice" role="status"
                                    aria-live="polite"></p>

                            </div>

                        </aside>

                    </div>

                </div>

                {{-- =====================================================
    11.1.6. MODAL KONFIRMASI EVALUASI
    ===================================================== --}}

                <div class="sd-eval-confirm" id="sd-eval-confirm" hidden>

                    <div class="sd-eval-confirm-backdrop"></div>

                    <div class="sd-eval-confirm-dialog" role="dialog" aria-modal="true"
                        aria-labelledby="sd-eval-confirm-title" aria-describedby="sd-eval-confirm-description"
                        tabindex="-1">

                        {{-- Tombol tutup --}}
                        <button type="button" class="sd-eval-confirm-close" id="sd-eval-confirm-close"
                            aria-label="Tutup konfirmasi">
                            <i class="bi bi-x-lg"></i>
                        </button>

                        {{-- GAMBAR BEKANTAN BERTANYA --}}
                        {{-- 11.1.6. GAMBAR MODAL KONFIRMASI --}}
                        <div class="sd-eval-confirm-icon">
                            <img id="sd-eval-confirm-icon"
                                src="{{ asset('images/evaluasi/bekantan-bertanya.png') }}"
                                alt="Bekantan sedang berpikir" width="170" height="155">
                        </div>

                        {{-- Judul konfirmasi --}}
                        <h2 class="sd-eval-confirm-title" id="sd-eval-confirm-title">
                            Yakin Ingin Memulai Evaluasi?
                        </h2>

                        {{-- Keterangan --}}
                        <p class="sd-eval-confirm-description" id="sd-eval-confirm-description">
                            Kamu akan mengerjakan 20 soal selama 45 menit.
                            Kerjakan dengan teliti dan jujur!
                        </p>

                        {{-- Informasi timer --}}
                        <div class="sd-eval-confirm-info" id="sd-eval-confirm-info">

                            <i class="bi bi-clock-history"></i>

                            <span>
                                Setelah dimulai, waktu akan terus berjalan
                                meskipun kamu keluar dari halaman.
                            </span>

                        </div>

                        {{-- Tombol konfirmasi --}}
                        <div class="sd-eval-confirm-actions">

                            <button type="button" class="sd-eval-confirm-cancel"
                                id="sd-eval-confirm-cancel">
                                Batal
                            </button>

                            <button type="button" class="sd-eval-confirm-accept"
                                id="sd-eval-confirm-accept">

                                <span id="sd-eval-confirm-accept-text">
                                    Ya, Mulai
                                </span>
                            </button>

                        </div>

                    </div>

                </div>

                {{-- 11.2. SOAL 1 — DRAG AND DROP GAMBAR --}}
                <div class="sd-eval-screen" data-eval-screen="q1">

                    <div class="sd-eval-time">
                        <i class="bi bi-clock"></i>
                        Waktu pengerjaan: ± 30 menit
                    </div>

                    <article class="sd-card sd-eval-card">

                        <div class="sd-eval-overview-header">
                            <div>
                                <h1 class="sd-eval-heading">
                                    Evaluasi — Karakteristik dan Klasifikasi Makhluk Hidup
                                </h1>
                                <p class="sd-eval-description">
                                    BAB 1 — Karakteristik dan Klasifikasi Makhluk Hidup
                                </p>
                            </div>

                            <button type="button" class="sd-eval-outline" data-eval-open="overview">
                                Kembali ke Evaluasi
                            </button>
                        </div>

                        <div class="sd-eval-topic">
                            1.1 Makhluk Hidup atau Benda Mati?
                        </div>

                        <p class="sd-eval-number">Soal 1 dari 2</p>

                        <h2 class="sd-eval-question">
                            Kelompokkan gambar komponen lahan basah ke target yang benar.
                        </h2>

                        <p class="sd-eval-description">
                            Seret gambar ke Biotik atau Abiotik.
                            Arahkan kursor ke gambar untuk melihat namanya.
                            Pada layar sentuh, ketuk gambar lalu ketuk target.
                        </p>

                        <div class="sd-eval-classification sd-eval-q1-layout">

                            {{-- SUMBER GAMBAR --}}
                            <div class="sd-eval-items sd-eval-image-items">

                                <button type="button" class="sd-eval-item sd-eval-image-item" data-eval-item
                                    data-question="q1" data-item="ikan_saluang"
                                    aria-label="Ikan saluang. Pilih untuk dikelompokkan."
                                    title="Ikan saluang">

                                    <span class="sd-eval-emoji">
                                        <img src="{{ asset('images/evaluasi/saluang.png') }}"
                                            alt="Ikan saluang" draggable="false">
                                    </span>
                                </button>

                                <button type="button" class="sd-eval-item sd-eval-image-item" data-eval-item
                                    data-question="q1" data-item="air_sungai"
                                    aria-label="Air sungai. Pilih untuk dikelompokkan." title="Air sungai">

                                    <span class="sd-eval-emoji">
                                        <img src="{{ asset('images/evaluasi/air-sungai.png') }}"
                                            alt="Air sungai" draggable="false">
                                    </span>
                                </button>

                                <button type="button" class="sd-eval-item sd-eval-image-item" data-eval-item
                                    data-question="q1" data-item="tumbuhan_purun"
                                    aria-label="Tumbuhan purun. Pilih untuk dikelompokkan."
                                    title="Tumbuhan purun">

                                    <span class="sd-eval-emoji">
                                        <img src="{{ asset('images/evaluasi/purun.png') }}"
                                            alt="Tumbuhan purun" draggable="false">
                                    </span>
                                </button>

                            </div>

                            {{-- TARGET DROP --}}
                            <div class="sd-eval-targets">

                                <button type="button" class="sd-eval-target" data-eval-target
                                    data-question="q1" data-target="biotik">

                                    <strong>Biotik</strong>
                                    <small>Komponen makhluk hidup</small>

                                    <span class="sd-eval-chips" data-target-box="q1:biotik"></span>
                                </button>

                                <button type="button" class="sd-eval-target" data-eval-target
                                    data-question="q1" data-target="abiotik">

                                    <strong>Abiotik</strong>
                                    <small>Komponen benda tak hidup</small>

                                    <span class="sd-eval-chips" data-target-box="q1:abiotik"></span>
                                </button>

                            </div>

                        </div>

                        <div class="sd-eval-feedback" data-eval-feedback="q1" role="status"
                            aria-live="polite" hidden></div>

                        <div class="sd-eval-navigation">
                            <button type="button" class="sd-nav-btn" data-eval-open="overview">
                                <i class="bi bi-chevron-left"></i>
                                Previous
                            </button>

                            <button type="button" class="sd-nav-btn next" data-eval-open="q2"
                                data-next-for="q1" hidden>
                                Next
                                <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>

                    </article>
                </div>

                {{-- 11.3. SOAL 2 — MENGHUBUNGKAN GAMBAR DENGAN BULLET --}}
                <div class="sd-eval-screen" data-eval-screen="q2">

                    <div class="sd-eval-time">
                        <i class="bi bi-clock"></i>
                        Waktu pengerjaan: ± 30 menit
                    </div>

                    <article class="sd-card sd-eval-card">

                        <div class="sd-eval-overview-header">
                            <div>
                                <h1 class="sd-eval-heading">
                                    Evaluasi — Karakteristik dan Klasifikasi Makhluk Hidup
                                </h1>

                                <p class="sd-eval-description">
                                    BAB 1 — Karakteristik dan Klasifikasi Makhluk Hidup
                                </p>
                            </div>

                            <button type="button" class="sd-eval-outline" data-eval-open="overview">
                                Kembali ke Evaluasi
                            </button>
                        </div>

                        <div class="sd-eval-topic">
                            1.2 Mengapa Makhluk Hidup Dikelompokkan?
                        </div>

                        <p class="sd-eval-number">Soal 2 dari 2</p>

                        <h2 class="sd-eval-question">
                            Hubungkan organisme lahan basah dengan kelompoknya:
                            hewan atau tumbuhan.
                        </h2>

                        <p class="sd-eval-description">
                            Klik bullet hijau pada gambar, lalu klik bullet
                            pada kelompok Hewan atau Tumbuhan.
                            Garis akan terbentuk otomatis.
                        </p>

                        <div class="sd-eval-classification sd-eval-match-layout" id="sdMatchQ2">

                            <svg class="sd-eval-lines" id="sdMatchLinesQ2" aria-hidden="true"></svg>

                            {{-- GAMBAR DAN BULLET SUMBER --}}
                            <div class="sd-eval-items sd-eval-match-items">

                                <div class="sd-eval-match-row" data-match-row="ikan">
                                    <img src="{{ asset('images/evaluasi/saluang.png') }}" alt="Ikan"
                                        title="Ikan" class="sd-eval-match-image">

                                    <button type="button" class="sd-eval-bullet sd-eval-bullet-source"
                                        data-eval-item data-question="q2" data-item="ikan"
                                        aria-label="Hubungkan ikan" title="Ikan"></button>
                                </div>

                                <div class="sd-eval-match-row" data-match-row="burung">
                                    <img src="{{ asset('images/evaluasi/burung-rangkong.png') }}"
                                        alt="Burung rangkong" title="Burung rangkong"
                                        class="sd-eval-match-image">

                                    <button type="button" class="sd-eval-bullet sd-eval-bullet-source"
                                        data-eval-item data-question="q2" data-item="burung"
                                        aria-label="Hubungkan burung" title="Burung"></button>
                                </div>

                                <div class="sd-eval-match-row" data-match-row="katak">
                                    <img src="{{ asset('images/evaluasi/katak.png') }}" alt="Katak"
                                        title="Katak" class="sd-eval-match-image">

                                    <button type="button" class="sd-eval-bullet sd-eval-bullet-source"
                                        data-eval-item data-question="q2" data-item="katak"
                                        aria-label="Hubungkan katak" title="Katak"></button>
                                </div>

                                <div class="sd-eval-match-row" data-match-row="teratai">
                                    <img src="{{ asset('images/evaluasi/teratai.png') }}" alt="Teratai"
                                        title="Teratai" class="sd-eval-match-image">

                                    <button type="button" class="sd-eval-bullet sd-eval-bullet-source"
                                        data-eval-item data-question="q2" data-item="teratai"
                                        aria-label="Hubungkan teratai" title="Teratai"></button>
                                </div>

                                <div class="sd-eval-match-row" data-match-row="purun">
                                    <img src="{{ asset('images/evaluasi/purun.png') }}" alt="Purun"
                                        title="Purun" class="sd-eval-match-image">

                                    <button type="button" class="sd-eval-bullet sd-eval-bullet-source"
                                        data-eval-item data-question="q2" data-item="purun"
                                        aria-label="Hubungkan purun" title="Purun"></button>
                                </div>

                            </div>

                            {{-- BULLET TARGET --}}
                            <div class="sd-eval-targets sd-eval-match-targets">

                                <div class="sd-eval-match-target">
                                    <button type="button" class="sd-eval-bullet sd-eval-bullet-target"
                                        data-eval-target data-question="q2" data-target="hewan"
                                        aria-label="Hubungkan ke Hewan" title="Hewan"></button>

                                    <div class="sd-eval-match-target-label">
                                        <strong>Hewan</strong>
                                        <small>Kelompok organisme hewan</small>
                                    </div>
                                </div>

                                <div class="sd-eval-match-target">
                                    <button type="button" class="sd-eval-bullet sd-eval-bullet-target"
                                        data-eval-target data-question="q2" data-target="tumbuhan"
                                        aria-label="Hubungkan ke Tumbuhan" title="Tumbuhan"></button>

                                    <div class="sd-eval-match-target-label">
                                        <strong>Tumbuhan</strong>
                                        <small>Kelompok organisme tumbuhan</small>
                                    </div>
                                </div>

                            </div>

                        </div>

                        <div class="sd-eval-feedback" data-eval-feedback="q2" role="status"
                            aria-live="polite" hidden></div>

                        <div class="sd-eval-navigation">

                            <button type="button" class="sd-nav-btn" data-eval-open="q1">
                                <i class="bi bi-chevron-left"></i>
                                Previous
                            </button>

                            <button type="button" class="sd-eval-primary" id="sd-eval-finish" hidden>
                                Selesai Evaluasi
                                <i class="bi bi-check2-circle"></i>
                            </button>

                        </div>

                    </article>
                </div>

                {{-- 11.4. HASIL EVALUASI --}}
                <div class="sd-eval-screen" data-eval-screen="result">

                    <article class="sd-card sd-eval-card">

                        <div class="sd-eval-overview-header">
                            <h1 class="sd-eval-heading">
                                Evaluasi Bab 1 Selesai!
                            </h1>

                            <button type="button" class="sd-eval-outline" data-eval-open="overview">
                                Kembali ke Evaluasi
                            </button>
                        </div>

                        <div class="sd-eval-result">

                            <div class="sd-eval-result-text">
                                <span class="sd-pill">HASIL EVALUASI</span>

                                <h2 id="sd-eval-result-heading">
                                    Selamat sudah menyelesaikan evaluasi!
                                </h2>

                                <div class="sd-eval-score">
                                    <span>Nilai kamu</span>
                                    <strong id="sd-eval-final-score">0</strong>
                                    <small id="sd-eval-final-summary">
                                        0 benar · 0 salah
                                    </small>
                                </div>

                                <p id="sd-eval-final-message">
                                    Terus semangat belajar bersama SALUANG!
                                </p>

                                <div class="sd-eval-result-actions">

                                    <button type="button" class="sd-eval-outline" id="sd-eval-retry">
                                        Coba Lagi
                                    </button>

                                    <button type="button" class="sd-eval-primary" data-page="6">
                                        Lanjut ke Forum Diskusi
                                        <i class="bi bi-arrow-right"></i>
                                    </button>

                                </div>
                            </div>

                            <div class="sd-eval-result-illustration">
                                <img src="{{ asset('images/evaluasi/hasil-evaluasi.png') }}"
                                    alt="Ilustrasi keberhasilan evaluasi">

                                <p>
                                    Terus jelajahi keanekaragaman hayati
                                    lahan basah bersama SALUANG!
                                </p>
                            </div>

                        </div>

                    </article>
                </div>

                {{-- 11.5. NAVIGASI ANTARHALAMAN --}}
                <div class="sd-navigation sd-eval-outer-navigation">

                    <button type="button" class="sd-nav-btn" data-page="4">
                        <i class="bi bi-chevron-left"></i>
                        Previous
                    </button>

                    <button type="button" class="sd-nav-btn next" data-page="6">
                        Next
                        <i class="bi bi-chevron-right"></i>
                    </button>

                </div>

            </section>

            {{-- =====================================================
                12. HALAMAN FORUM DISKUSI
                ===================================================== --}}
            <section id="finalPage" hidden>

                {{-- 12.1. NAVIGASI ATAS --}}
                <div class="sd-navigation">
                    <button type="button" class="sd-nav-btn" data-page="5">
                        <i class="bi bi-chevron-left"></i>
                        Previous
                    </button>

                    <button type="button" class="sd-nav-btn next" data-page="0">
                        Next
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>

                {{-- 12.2. KONTEN UTAMA FORUM --}}
                <article class="sd-card sd-forum-card">

                    {{-- 12.2.1. HEADER --}}
                    <div class="sd-forum-header">
                        <div>
                            <h1 class="sd-forum-title">Forum Diskusi</h1>

                            <p class="sd-forum-subtitle">
                                Diskusikan pembelajaran dengan konteks Bab
                                dan Subbab yang sama.
                            </p>
                        </div>

                        <button type="button" class="sd-forum-primary" id="forumCreateButton">
                            <i class="bi bi-plus-lg"></i>
                            Buat Diskusi
                        </button>
                    </div>

                    {{-- 12.3. HALAMAN DAFTAR DISKUSI --}}
                    <div id="forumListScreen">

                        {{-- 12.3.1. FILTER BAB DAN SUBBAB --}}
                        <div class="sd-forum-filters">

                            <div class="sd-forum-field">
                                <label for="forumChapterFilter">Bab</label>

                                <select id="forumChapterFilter">
                                    <option value="">Semua Bab</option>
                                    <option value="bab-1">
                                        BAB 1 – Karakteristik dan Klasifikasi
                                        Makhluk Hidup
                                    </option>
                                </select>
                            </div>

                            <div class="sd-forum-field">
                                <label for="forumSubchapterFilter">Subbab</label>

                                <select id="forumSubchapterFilter">
                                    <option value="">Semua Subbab</option>

                                    <option value="1.1">
                                        1.1 Makhluk Hidup atau Benda Mati?
                                    </option>

                                    <option value="1.2">
                                        1.2 Mengapa Makhluk Hidup Dikelompokkan?
                                    </option>

                                    <option value="1.3">
                                        1.3 Makhluk Hidup Beraneka Ragam
                                    </option>
                                </select>
                            </div>
                        </div>

                        {{-- 12.3.2. PENCARIAN --}}
                        <div class="sd-forum-field sd-forum-search">
                            <label for="forumSearchInput">Cari diskusi</label>

                            <input type="search" id="forumSearchInput"
                                placeholder="Cari pertanyaan atau judul diskusi">
                        </div>

                        {{-- 12.3.3. FILTER STATUS --}}
                        <div class="sd-forum-status-filters">
                            <button type="button" class="sd-forum-filter-btn active"
                                data-forum-status="all">
                                Terbaru
                            </button>

                            <button type="button" class="sd-forum-filter-btn" data-forum-status="answered">
                                Sudah Terjawab
                            </button>

                            <button type="button" class="sd-forum-filter-btn"
                                data-forum-status="unanswered">
                                Belum Terjawab
                            </button>
                        </div>

                        {{-- 12.3.4. PESAN STATUS --}}
                        <p id="forumListMessage" class="sd-forum-message" role="status" aria-live="polite">
                        </p>

                        {{-- 12.3.5. DAFTAR DARI DATABASE --}}
                        <div id="forumDiscussionList"></div>

                        {{-- 12.3.6. PAGINASI --}}
                        <div class="sd-forum-pagination" id="forumPagination">
                        </div>

                    </div>

                    {{-- 12.4. FORM BUAT DISKUSI --}}
                    <div id="forumCreateScreen" hidden>

                        <div class="sd-forum-screen-header">
                            <h2>Buat Diskusi Baru</h2>

                            <button type="button" class="sd-forum-back" data-forum-back>
                                Kembali ke Daftar
                            </button>
                        </div>

                        <form id="forumCreateForm">

                            <div class="sd-forum-field">
                                <label for="forumCreateChapter">Bab</label>

                                <select id="forumCreateChapter" required>
                                    <option value="bab-1">
                                        BAB 1 – Karakteristik dan Klasifikasi
                                        Makhluk Hidup
                                    </option>
                                </select>
                            </div>

                            <div class="sd-forum-field">
                                <label for="forumCreateSubchapter">
                                    Subbab
                                </label>

                                <select id="forumCreateSubchapter" required>
                                    <option value="">Pilih Subbab</option>
                                    <option value="1.1">
                                        1.1 Makhluk Hidup atau Benda Mati?
                                    </option>
                                    <option value="1.2">
                                        1.2 Mengapa Makhluk Hidup Dikelompokkan?
                                    </option>
                                    <option value="1.3">
                                        1.3 Makhluk Hidup Beraneka Ragam
                                    </option>
                                </select>
                            </div>

                            <div class="sd-forum-field">
                                <label for="forumCreateTitle">
                                    Judul Diskusi
                                </label>

                                <input type="text" id="forumCreateTitle" minlength="5" maxlength="200"
                                    placeholder="Tulis judul pertanyaan" required>
                            </div>

                            <div class="sd-forum-field">
                                <label for="forumCreateBody">
                                    Pertanyaan
                                </label>

                                <textarea id="forumCreateBody" minlength="10" maxlength="5000" rows="6"
                                    placeholder="Jelaskan pertanyaanmu di sini..." required></textarea>
                            </div>

                            <p id="forumCreateMessage" class="sd-forum-message" role="status"
                                aria-live="polite">
                            </p>

                            <button type="submit" class="sd-forum-primary" id="forumCreateSubmit">
                                <i class="bi bi-send"></i>
                                Kirim Diskusi
                            </button>

                        </form>
                    </div>

                    {{-- 12.5. DETAIL DISKUSI DAN TANGGAPAN --}}
                    <div id="forumDetailScreen" hidden>

                        <div class="sd-forum-screen-header">
                            <h2>Detail Diskusi</h2>

                            <button type="button" class="sd-forum-back" data-forum-back>
                                Kembali ke Daftar
                            </button>
                        </div>

                        {{-- Pertanyaan dari database --}}
                        <div id="forumDetailContent"></div>

                        <h3 class="sd-forum-replies-heading">
                            Tanggapan Diskusi
                        </h3>

                        {{-- Balasan dari database --}}
                        <div id="forumRepliesList"></div>

                        {{-- Form tanggapan --}}
                        <form id="forumReplyForm">

                            <div class="sd-forum-field">
                                <label for="forumReplyBody">
                                    Tulis Tanggapan
                                </label>

                                <textarea id="forumReplyBody" minlength="2" maxlength="5000" rows="4" placeholder="Tuliskan tanggapanmu..."
                                    required></textarea>
                            </div>

                            <p id="forumReplyMessage" class="sd-forum-message" role="status"
                                aria-live="polite">
                            </p>

                            <button type="submit" class="sd-forum-primary" id="forumReplySubmit">
                                <i class="bi bi-send"></i>
                                Kirim Tanggapan
                            </button>

                        </form>
                    </div>

                </article>

                {{-- =====================================================
12.5.1. MODAL KONFIRMASI HAPUS DISKUSI
===================================================== --}}

                <div class="sd-forum-confirm" id="forumDeleteModal" hidden>

                    <div class="sd-forum-confirm-backdrop" id="forumDeleteBackdrop"></div>

                    <div class="sd-forum-confirm-dialog" role="dialog" aria-modal="true"
                        aria-labelledby="forumDeleteTitle" aria-describedby="forumDeleteDescription"
                        tabindex="-1">

                        {{-- Tombol tutup --}}
                        <button type="button" class="sd-forum-confirm-close" id="forumDeleteClose"
                            aria-label="Tutup konfirmasi">
                            <i class="bi bi-x-lg"></i>
                        </button>

                        {{-- Ikon tempat sampah --}}
                        <div class="sd-forum-confirm-icon">
                            <i class="bi bi-trash3"></i>
                        </div>

                        {{-- Judul --}}
                        <h2 class="sd-forum-confirm-title" id="forumDeleteTitle">
                            Yakin Ingin Menghapus Diskusi?
                        </h2>

                        {{-- Penjelasan --}}
                        <p class="sd-forum-confirm-description" id="forumDeleteDescription">
                            Diskusi ini beserta seluruh tanggapannya
                            akan dihapus secara permanen.
                        </p>

                        {{-- Judul diskusi yang akan dihapus --}}
                        <div class="sd-forum-confirm-info">
                            <span>Diskusi yang akan dihapus:</span>

                            <strong id="forumDeleteDiscussionTitle"></strong>
                        </div>

                        {{-- Peringatan --}}
                        <p class="sd-forum-confirm-warning">
                            Tindakan ini tidak dapat dibatalkan.
                        </p>

                        {{-- Tombol aksi --}}
                        <div class="sd-forum-confirm-actions">

                            <button type="button" class="sd-forum-confirm-cancel" id="forumDeleteCancel">
                                Batal
                            </button>

                            <button type="button" class="sd-forum-confirm-danger" id="forumDeleteAccept">
                                Ya, Hapus
                            </button>

                        </div>

                        <p class="sd-forum-confirm-error" id="forumDeleteError" role="alert" hidden></p>

                    </div>
                </div>

                {{-- 12.6. NAVIGASI BAWAH --}}
                <div class="sd-navigation">
                    <button type="button" class="sd-nav-btn" data-page="5">
                        <i class="bi bi-chevron-left"></i>
                        Previous
                    </button>

                    <button type="button" class="sd-nav-btn next" data-page="0">
                        Next
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>

            </section>

            {{-- =====================================================
12.7. HALAMAN PROFIL SISWA SALUANG
===================================================== --}}

            <section id="profilePage" hidden>

                {{-- NAVIGASI ATAS PROFIL --}}
                <div class="sd-navigation">
                    <button type="button" class="sd-nav-btn" data-page="6">
                        <i class="bi bi-chevron-left"></i>
                        Previous
                    </button>
                </div>

                <div class="sd-profile-container">

                    {{-- 12.7.1. JUDUL --}}
                    <div class="sd-profile-heading">
                        <h1>Profil Saya</h1>
                        <p>Kelola informasi pribadi dan pengaturan akunmu.</p>
                    </div>

                    {{-- 12.7.2. KARTU IDENTITAS --}}
                    <article class="sd-profile-card sd-profile-identity">

                        <div class="sd-profile-avatar" id="profileAvatar">
                            @if (auth()->user()?->profile_photo_path)
                                <img id="profileAvatarImage"
                                    src="{{ asset('storage/' . auth()->user()->profile_photo_path) }}"
                                    alt="Foto profil siswa">
                            @else
                                <span id="profileAvatarInitial">
                                    {{ strtoupper(substr(auth()->user()?->name ?? 'S', 0, 1)) }}
                                </span>
                            @endif
                        </div>

                        <div class="sd-profile-identity-info">
                            <h2 id="profileDisplayName">
                                {{ auth()->user()?->name ?? 'Pengguna SALUANG' }}
                            </h2>

                            <p>
                                {{ auth()->user()?->sekolah ?? 'Sekolah belum diisi' }}
                                ·
                                {{ auth()->user()?->kelas ?? 'Kelas belum diisi' }}
                            </p>

                            <div class="sd-profile-photo-actions">
                                <button type="button" class="sd-profile-outline" id="profilePhotoButton">
                                    Ubah Foto Profil
                                </button>

                                <input type="file" id="profilePhotoInput"
                                    accept="image/png,image/jpeg,image/webp" hidden>

                                <button type="button" class="sd-profile-delete-photo"
                                    id="profileDeletePhotoButton"
                                    @if (!auth()->user()?->profile_photo_path) hidden @endif>
                                    Hapus Foto
                                </button>

                                <span id="profilePhotoFilename">
                                    Belum ada file dipilih
                                </span>
                            </div>
                        </div>
                    </article>

                    {{-- 12.7.3. INFORMASI PRIBADI --}}
                    <article class="sd-profile-card">

                        <div class="sd-profile-card-header">
                            <h2>Informasi Pribadi</h2>

                            <button type="button" class="sd-profile-outline" id="profileEditButton">
                                Edit Profil
                            </button>
                        </div>

                        <div id="profileInformation">
                            <div class="sd-profile-info-grid">

                                <div class="sd-profile-info-item">
                                    <span>Nama Lengkap</span>
                                    <strong>{{ auth()->user()?->name ?? 'Belum diisi' }}</strong>
                                </div>

                                <div class="sd-profile-info-item">
                                    <span>Username</span>
                                    <strong>{{ auth()->user()?->username ?? 'Belum diisi' }}</strong>
                                </div>

                                <div class="sd-profile-info-item">
                                    <span>Email</span>
                                    <strong>{{ auth()->user()?->email ?? 'Belum diisi' }}</strong>
                                </div>

                                <div class="sd-profile-info-item">
                                    <span>NISN</span>
                                    <strong>{{ auth()->user()?->nisn ?? 'Belum diisi' }}</strong>
                                </div>

                                <div class="sd-profile-info-item">
                                    <span>Kelas</span>
                                    <strong>{{ auth()->user()?->kelas ?? 'Belum diisi' }}</strong>
                                </div>

                                <div class="sd-profile-info-item">
                                    <span>Sekolah</span>
                                    <strong>{{ auth()->user()?->sekolah ?? 'Belum diisi' }}</strong>
                                </div>

                            </div>
                        </div>

                        {{-- Form Edit Profil --}}
                        <form id="profileEditForm" class="sd-profile-form" hidden>
                            <div class="sd-profile-form-grid">

                                <label>
                                    Nama Lengkap
                                    <input type="text" id="profileEditName"
                                        value="{{ auth()->user()?->name ?? '' }}" maxlength="255" required>
                                </label>

                                <label>
                                    Username
                                    <input type="text" id="profileEditUsername"
                                        value="{{ auth()->user()?->username ?? '' }}" maxlength="50"
                                        required>
                                </label>

                                <label>
                                    Email
                                    <input type="email" id="profileEditEmail"
                                        value="{{ auth()->user()?->email ?? '' }}"
                                        placeholder="Masukkan email">
                                </label>

                                <label>
                                    NISN
                                    <input type="text" value="{{ auth()->user()?->nisn ?? '' }}" readonly>
                                </label>

                                <label>
                                    Kelas
                                    <input type="text" id="profileEditClass"
                                        value="{{ auth()->user()?->kelas ?? '' }}"
                                        placeholder="Contoh: VII A">
                                </label>

                                <label>
                                    Sekolah
                                    <input type="text" id="profileEditSchool"
                                        value="{{ auth()->user()?->sekolah ?? '' }}"
                                        placeholder="Nama sekolah">
                                </label>
                            </div>

                            <div class="sd-profile-form-actions">
                                <button type="button" class="sd-profile-outline" id="profileCancelEdit">
                                    Kembali ke Profil
                                </button>

                                <button type="submit" class="sd-profile-primary">
                                    Simpan Perubahan
                                </button>
                            </div>

                            <p id="profileEditMessage" class="sd-profile-message" role="status"></p>
                        </form>
                    </article>

                    {{-- 12.7.4. PENGATURAN AKUN --}}
                    <article class="sd-profile-card" id="profileSettingsCard">

                        <div class="sd-profile-card-header">
                            <h2>Pengaturan Akun</h2>
                        </div>

                        <div class="sd-profile-password-summary">
                            <div>
                                <span>Kata Sandi</span>
                                <strong>••••••••••</strong>
                            </div>

                            <button type="button" class="sd-profile-outline" id="profilePasswordButton">
                                Ubah Kata Sandi
                            </button>
                        </div>

                        {{-- Form muncul di dalam kartu saat tombol diklik --}}
                        <form id="profilePasswordForm" class="sd-profile-form" hidden>

                            <div class="sd-profile-form-grid">

                                <button type="button" class="sd-profile-outline sd-profile-back"
                                    id="profilePasswordBack">
                                    Kembali ke Profil
                                </button>

                                <label>
                                    Password Saat Ini
                                    <input type="password" id="profileCurrentPassword"
                                        autocomplete="current-password" placeholder="Password Saat Ini"
                                        required>
                                </label>

                                <label>
                                    Password Baru
                                    <input type="password" id="profileNewPassword"
                                        autocomplete="new-password" minlength="8"
                                        placeholder="Password Baru" required>
                                </label>

                                <label>
                                    Konfirmasi Password Baru
                                    <input type="password" id="profileConfirmPassword"
                                        autocomplete="new-password" minlength="8"
                                        placeholder="Konfirmasi Password Baru" required>
                                </label>

                            </div>

                            <div class="sd-profile-password-actions">
                                <button type="submit" class="sd-profile-primary">
                                    Simpan Password
                                </button>
                            </div>

                            <p id="profilePasswordMessage" class="sd-profile-message" role="status"></p>

                        </form>

                    </article>

                    {{-- 12.7.5. KELUAR DARI AKUN --}}
                    @auth
                        <form method="POST" action="{{ route('logout') }}" data-sd-logout-form>
                            @csrf

                            <button type="submit" class="sd-profile-logout">
                                Keluar dari Akun
                            </button>
                        </form>
                    @endauth

                    {{-- 12.7.6. KEMBALI KE BERANDA --}}
                    <button type="button" class="sd-profile-home" data-page="0">
                        Kembali ke Beranda
                    </button>

                </div>

                {{-- NAVIGASI BAWAH PROFIL --}}
                <div class="sd-navigation">
                    <button type="button" class="sd-nav-btn" data-page="6">
                        <i class="bi bi-chevron-left"></i>
                        Previous
                    </button>
                </div>

                {{-- KONFIRMASI HAPUS FOTO PROFIL --}}
                <div class="sd-profile-photo-modal" id="profileDeletePhotoModal" hidden>
                    <div class="sd-profile-photo-backdrop"></div>

                    <div class="sd-profile-photo-dialog" role="dialog" aria-modal="true"
                        aria-labelledby="profileDeletePhotoTitle">

                        <div class="sd-profile-photo-icon">
                            <i class="bi bi-trash3"></i>
                        </div>

                        <h2 id="profileDeletePhotoTitle">
                            Yakin Ingin Menghapus Foto?
                        </h2>

                        <p>
                            Foto profilmu akan dihapus dan diganti
                            dengan avatar inisial nama.
                        </p>

                        <p id="profileDeletePhotoError" class="sd-profile-message is-error" role="alert"
                            hidden></p>

                        <div class="sd-profile-photo-actions">
                            <button type="button" id="profileDeletePhotoCancel"
                                class="sd-profile-photo-cancel">
                                Batal
                            </button>

                            <button type="button" id="profileDeletePhotoConfirm"
                                class="sd-profile-photo-danger">
                                Ya, Hapus
                            </button>
                        </div>
                    </div>
                </div>

            </section>

        </main>
    </div>

    {{-- =====================================================
        13. URL GAMBAR DARI LARAVEL
        ===================================================== --}}
    @php
        $siswaAssets = [
            // 13.1. Gambar materi yang sudah ada
            'living' => asset('images/lesson-interaksi-makhluk-hidup.png'),
            'ecosystem' => asset('images/lesson-ekosistem-lahan-basah.png'),
            'conservation' => asset('images/lesson-pelestarian-lingkungan.png'),
            'evaluation' => asset('images/progress-assets/progress-3.png'),
            'discussion' => asset('images/saluang-materi-interaktif.png'),

            // 13.2. Gambar khusus Evaluasi
            'ikanSaluang' => asset('images/evaluasi/saluang.png'),
            'airSungai' => asset('images/evaluasi/air-sungai.png'),
            'katak' => asset('images/evaluasi/katak.png'),
            'burungRangkong' => asset('images/evaluasi/burung-rangkong.png'),
            'teratai' => asset('images/evaluasi/teratai.png'),
            'purun' => asset('images/evaluasi/purun.png'),
            'hasilEvaluasi' => asset('images/evaluasi/hasil-evaluasi.png'),
        ];
    @endphp

    <script id="sd-assets" type="application/json">
        {!! json_encode($siswaAssets, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
    </script>

    {{-- =====================================================
        14. FILE JAVASCRIPT TERPISAH
        ===================================================== --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/siswa.js') }}?v=28" defer></script>
    <script src="{{ asset('js/siswa-evaluasi.js') }}?v=50" defer></script>
    <script src="{{ asset('js/siswa-forum.js') }}?v=3" defer></script>
    <script src="{{ asset('js/siswa-profil.js') }}?v=2" defer></script>
    <script src="{{ asset('js/siswa-logout.js') }}?v=1" defer></script>
    {{-- =====================================================
MODAL KONFIRMASI LOGOUT SALUANG
===================================================== --}}

    <div id="sdLogoutModal" class="sd-logout-modal" hidden>
        <div class="sd-logout-backdrop"></div>

        <div class="sd-logout-dialog" role="dialog" aria-modal="true" aria-labelledby="sdLogoutTitle"
            aria-describedby="sdLogoutDescription" tabindex="-1">

            <button type="button" class="sd-logout-close" id="sdLogoutClose" aria-label="Tutup">
                <i class="bi bi-x-lg"></i>
            </button>

            <img class="sd-logout-mascot" src="{{ asset('images/bekantan-logout-ceria.png') }}"
                alt="Bekantan SALUANG tersenyum dan melambaikan tangan">

            <h2 id="sdLogoutTitle">Yakin ingin keluar?</h2>

            <p id="sdLogoutDescription">
                Kamu akan keluar dari akun SALUANG.
                Pastikan semua aktivitas telah disimpan
                sebelum melanjutkan.
            </p>

            <div class="sd-logout-actions">
                <button type="button" class="sd-logout-cancel" id="sdLogoutCancel">
                    Batal
                </button>

                <button type="button" class="sd-logout-confirm" id="sdLogoutConfirm">
                    Ya, Keluar
                </button>
            </div>
        </div>
    </div>

</body>
</html>