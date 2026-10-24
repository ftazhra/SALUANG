
<!DOCTYPE html>
<html lang="id">

<head>
    <!-- =====================================================
         01. PENGATURAN DASAR SALUANG
    ====================================================== -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Dashboard Siswa — SALUANG</title>

    <!-- =====================================================
         02. FONT DAN LIBRARY
    ====================================================== -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <!-- =====================================================
         03. CSS DASHBOARD
    ====================================================== -->
    <link
        rel="stylesheet"
        href="{{ asset('css/siswa.css') }}?v=34"
    >

    <link
        rel="stylesheet"
        href="{{ asset('css/siswa-evaluasi.css') }}?v=49"
    >

    <link
        rel="stylesheet"
        href="{{ asset('css/siswa-profil.css') }}?v=3"
    >

    <!-- =====================================================
         04. STYLE KHUSUS BUKU DIGITAL
         Tidak mengubah CSS kuis, evaluasi, forum, profil.
    ====================================================== -->
    <style>
        #lessonPage .sd-material-card {
            width: 100%;
            padding: 12px;
            overflow: hidden;
            border-radius: 14px;
        }

        #lessonPage .sd-material-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 12px;
            width: 100%;
            min-width: 0;
        }

        #lessonPage .sd-material-text {
            width: 100%;
            max-width: none;
            min-width: 0;
        }

        #lessonPage .sd-title {
            margin: 8px 0 10px;
            font-size: 18px;
            line-height: 1.4;
        }

        #lessonPage .sd-material-heading {
            margin-bottom: 8px;
            font-size: 15px;
            line-height: 1.4;
        }

        #lessonPage #lessonContent {
            display: block;
            width: 100%;
            min-width: 0;
            color: #294f57;
            font-family: inherit;
            font-size: 12px;
            font-weight: 400;
            line-height: 1.55;
            overflow-wrap: anywhere;
            white-space: normal;
        }

        #lessonPage #lessonContent p {
            margin: 0 0 7px;
            font-size: inherit;
            line-height: inherit;
        }

        #lessonPage #lessonContent h2 {
            margin: 12px 0 6px;
            color: #075b70;
            font-size: 15px;
            font-weight: 700;
            line-height: 1.5;
        }

        #lessonPage #lessonContent h3 {
            margin: 10px 0 5px;
            color: #126779;
            font-size: 13px;
            font-weight: 700;
            line-height: 1.55;
        }

        #lessonPage #lessonContent strong,
        #lessonPage #lessonContent b {
            font-weight: 700;
        }

        #lessonPage #lessonContent em,
        #lessonPage #lessonContent i {
            font-style: italic;
        }

        #lessonPage #lessonContent u {
            text-decoration: underline;
        }

        #lessonPage #lessonContent ol {
            list-style-type: decimal;
            list-style-position: outside;
        }

        #lessonPage #lessonContent ul {
            list-style-type: disc;
            list-style-position: outside;
        }

        #lessonPage #lessonContent ol,
        #lessonPage #lessonContent ul {
            margin: 6px 0 8px;
            padding-left: 20px;
        }

        #lessonPage #lessonContent li {
            display: list-item;
            margin-bottom: 3px;
            padding-left: 2px;
            font-size: inherit;
            line-height: 1.55;
        }

        #lessonPage .sd-material-media {
            width: 100%;
            max-width: 350px;
            margin: 0 auto;
            text-align: center;
        }

        #lessonPage .sd-media-picture {
            display: block;
            width: 100%;
            max-width: 350px;
            height: auto;
            object-fit: contain;
            margin-inline: auto;
            border-radius: 10px;
        }

        #lessonPage .sd-media-caption {
            margin: 10px 0 0;
            color: #69828b;
            font-size: 13px;
            line-height: 1.6;
        }

        #lessonPage .sd-media-item + .sd-media-item {
            margin-top: 16px;
            padding-top: 14px;
            border-top: 1px solid #e2ece7;
        }

        #lessonPage .sd-media-title {
            margin: 0 0 7px;
            color: #19566a;
            font-size: 14px;
            font-weight: 700;
        }

        #lessonPage .sd-media-video,
        #lessonPage .sd-media-youtube {
            display: block;
            width: 100%;
            max-width: 350px;
            aspect-ratio: 16 / 9;
            margin-inline: auto;
            border: 0;
            border-radius: 10px;
            background: #10252b;
        }

        #lessonPage .sd-media-video {
            height: auto;
            object-fit: contain;
        }

        #lessonPage .sd-media-unavailable,
        #lessonPage .sd-media-note {
            margin: 8px 0 0;
            color: #69828b;
            font-size: 12px;
            line-height: 1.5;
        }

        #lessonPage .sd-content-block + .sd-content-block {
            margin-top: 12px;
        }

        #lessonPage .sd-content-block-callout {
            padding: 11px 12px;
            border-left: 3px solid #4f9d69;
            border-radius: 8px;
            background: #f0f8f2;
        }

        #lessonPage .sd-content-block-callout > strong {
            display: block;
            margin-bottom: 5px;
            color: #19566a;
        }

        #chapterPage .sd-objectives {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 6px;
        }

        #chapterPage > .sd-card {
            padding: 12px;
        }

        #chapterPage > .sd-card.mt-3 {
            margin-top: 10px !important;
        }

        #chapterPage .sd-heading {
            margin-bottom: 8px;
            font-size: 13px;
            line-height: 1.4;
        }

        #chapterPage > .sd-card > .sd-copy {
            margin-bottom: 8px;
            font-size: 12px;
            line-height: 1.5;
        }

        #chapterPage .sd-objective {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            min-width: 0;
            padding: 9px 10px;
            border-radius: 10px;
            line-height: 1.5;
        }

        #chapterPage .sd-objective-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 25px;
            width: 25px;
            height: 25px;
            border-radius: 7px;
            background: #dff2e7;
            color: #176447;
            font-size: 12px;
            font-weight: 700;
        }

        #chapterPage .sd-objective-body {
            min-width: 0;
            flex: 1;
        }

        #chapterPage .sd-objective-body p {
            margin: 2px 0 0;
            font-size: 12px;
            line-height: 1.5;
        }

        #chapterPage .sd-prompt-image {
            display: block;
            width: min(100%, 350px);
            height: auto;
            object-fit: contain;
            margin: 10px auto 0;
            border-radius: 10px;
        }

        #lessonPage [hidden],
        #chapterPage [hidden] {
            display: none !important;
        }

        #chapterPage .sd-prompt {
            padding: 0;
            border: 0;
            border-radius: 0;
            background: transparent;
            font-size: 12px;
        }

        #chapterPage .sd-prompt .sd-copy {
            margin: 0;
            color: #294f57;
            font-size: 12px;
            line-height: 1.55;
        }

        @media (max-width: 767.98px) {
            #lessonPage .sd-material-grid {
                grid-template-columns: minmax(0, 1fr);
                gap: 10px;
            }

            #lessonPage .sd-material-card {
                padding: 10px;
            }

            #lessonPage #lessonContent {
                font-size: 13px;
                line-height: 1.55;
            }

            #lessonPage #lessonContent p {
                margin-bottom: 7px;
            }

            #lessonPage #lessonContent h2 {
                font-size: 15px;
            }

            #lessonPage #lessonContent h3 {
                font-size: 13px;
            }

            #lessonPage #lessonContent ol,
            #lessonPage #lessonContent ul {
                padding-left: 24px;
            }

            #chapterPage .sd-objective {
                padding: 8px 9px;
                gap: 7px;
            }

            #chapterPage .sd-objective-body p {
                font-size: 13px;
            }

            #chapterPage .sd-prompt .sd-copy {
                font-size: 13px;
            }

            #chapterPage .sd-prompt {
                font-size: 13px;
            }
        }
    </style>
</head>

<body class="sd-page">

    <!-- =====================================================
         05. SIDEBAR DESKTOP
    ====================================================== -->
    <aside class="sd-sidebar" id="sidebar">

        <div class="sd-brand">
            <img
                src="{{ asset('images/saluang-logo-transparent.png') }}"
                alt="Logo SALUANG"
            >
        </div>

        <nav class="sd-nav" aria-label="Navigasi Siswa">

            <button
                type="button"
                class="sd-fold"
                id="foldSidebar"
                title="Perkecil sidebar"
                aria-label="Perkecil sidebar"
                aria-expanded="true"
            >
                <i class="bi bi-layout-sidebar-inset"></i>
            </button>

            <button
                type="button"
                class="sd-menu active"
                data-page="0"
            >
                <i class="bi bi-house-door"></i>
                <span>Beranda</span>
            </button>

            <button
                type="button"
                class="sd-menu"
                data-page="7"
            >
                <i class="bi bi-book"></i>
                <span>Materi</span>
            </button>

            <!--
                Bab dan Subbab dibuat oleh siswa.js.
                Sumber: database Guru.
            -->
            <div id="desktopMateri">
                <div class="sd-submenu"></div>
            </div>

            <button
                type="button"
                class="sd-menu"
                data-page="5"
            >
                <i class="bi bi-clipboard-check"></i>
                <span>Evaluasi</span>
            </button>

            <button
                type="button"
                class="sd-menu"
                data-page="6"
            >
                <i class="bi bi-chat-dots"></i>
                <span>Forum Diskusi</span>
            </button>

            <button
                type="button"
                class="sd-menu"
                data-page="8"
            >
                <i class="bi bi-person"></i>
                <span>Profil</span>
            </button>

        </nav>

        <div class="sd-footer">
            @auth
                <form
                    action="{{ route('logout') }}"
                    method="POST"
                    data-sd-logout-form
                >
                    @csrf

                    <button type="submit" class="sd-exit">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Keluar</span>
                    </button>
                </form>
            @else
                <a
                    href="{{ route('beranda') }}"
                    class="sd-exit"
                >
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Keluar dari Pratinjau</span>
                </a>
            @endauth
        </div>

    </aside>

    <!-- =====================================================
         06. SIDEBAR MOBILE
    ====================================================== -->
    <div
        class="offcanvas offcanvas-start sd-offcanvas"
        id="mobileSidebar"
        tabindex="-1"
        aria-labelledby="mobileSidebarTitle"
    >
        <div class="offcanvas-header">

            <img
                src="{{ asset('images/saluang-logo-transparent.png') }}"
                alt="SALUANG"
                id="mobileSidebarTitle"
                width="145"
            >

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="offcanvas"
                aria-label="Tutup menu"
            ></button>
        </div>

        <div class="offcanvas-body d-flex flex-column">

            <nav class="sd-nav" aria-label="Navigasi Mobile">

                <button
                    type="button"
                    class="sd-menu"
                    data-page="0"
                >
                    <i class="bi bi-house-door"></i>
                    <span>Beranda</span>
                </button>

                <button
                    type="button"
                    class="sd-menu"
                    data-page="7"
                >
                    <i class="bi bi-book"></i>
                    <span>Materi</span>
                </button>

                <!--
                    Sidebar mobile juga berasal dari database Guru.
                -->
                <div id="mobileMateri">
                    <div class="sd-submenu"></div>
                </div>

                <button
                    type="button"
                    class="sd-menu"
                    data-page="5"
                >
                    <i class="bi bi-clipboard-check"></i>
                    <span>Evaluasi</span>
                </button>

                <button
                    type="button"
                    class="sd-menu"
                    data-page="6"
                >
                    <i class="bi bi-chat-dots"></i>
                    <span>Forum Diskusi</span>
                </button>

                <button
                    type="button"
                    class="sd-menu"
                    data-page="8"
                >
                    <i class="bi bi-person"></i>
                    <span>Profil</span>
                </button>

            </nav>

            <div class="sd-footer">
                @auth
                    <form
                        action="{{ route('logout') }}"
                        method="POST"
                        data-sd-logout-form
                    >
                        @csrf
                        <button type="submit" class="sd-exit">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>Keluar</span>
                        </button>
                    </form>
                @else
                    <a
                        href="{{ route('beranda') }}"
                        class="sd-exit"
                    >
                        Keluar dari Pratinjau
                    </a>
                @endauth
            </div>

        </div>
    </div>

    <!-- =====================================================
         07. KONTEN UTAMA
    ====================================================== -->
    <div class="sd-main" id="main">

        <!-- =================================================
             07.1 TOPBAR
        ================================================== -->
        <header class="sd-topbar">

            <div class="d-flex align-items-center gap-2 flex-grow-1">

                <button
                    type="button"
                    class="sd-icon d-lg-none"
                    data-bs-toggle="offcanvas"
                    data-bs-target="#mobileSidebar"
                    aria-label="Buka menu"
                >
                    <i class="bi bi-list"></i>
                </button>

                <label class="sd-search">
                    <i class="bi bi-search"></i>
                    <input
                        type="search"
                        id="searchTopic"
                        placeholder="Cari materi, topik, atau kata kunci..."
                        aria-label="Cari materi"
                    >
                </label>

            </div>

            <div class="sd-tools">

                <!-- Notifikasi -->
                <div class="dropdown">

                    <button
                        type="button"
                        class="sd-icon"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                        aria-label="Notifikasi"
                    >
                        <i class="bi bi-bell"></i>
                    </button>

                    <div class="dropdown-menu dropdown-menu-end p-3 sd-dropdown">
                        <strong>Notifikasi</strong>
                        <p class="mt-2 mb-0 text-secondary">
                            Belum ada notifikasi baru.
                        </p>
                    </div>

                </div>

                <!-- Menu akun -->
                <div class="dropdown">

                    <button
                        type="button"
                        class="sd-user"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                        aria-label="Menu profil"
                    >
                        <span class="sd-avatar">
                            @if (auth()->user()?->profile_photo_path)
                                <img
                                    src="{{ asset('storage/' . auth()->user()->profile_photo_path) }}"
                                    alt="Foto profil"
                                    class="sd-topbar-avatar-img"
                                >
                            @else
                                {{ strtoupper(substr(auth()->user()?->name ?? 'Siswa', 0, 1)) }}
                            @endif
                        </span>

                        <span class="sd-user-meta">
                            <strong>
                                {{ auth()->user()?->name ?? 'Pengguna SALUANG' }}
                            </strong>

                            <small>
                                Siswa · Kelas
                                {{ auth()->user()?->kelas ?: 'belum diisi' }}
                            </small>
                        </span>

                        <i class="bi bi-chevron-down"></i>
                    </button>

                    <ul class="dropdown-menu dropdown-menu-end sd-dropdown">

                        <li>
                            <button
                                type="button"
                                class="dropdown-item"
                                data-page="8"
                            >
                                <i class="bi bi-person me-2"></i>
                                Profil Saya
                            </button>
                        </li>

                        <li>
                            <button
                                type="button"
                                class="dropdown-item"
                                data-profile-settings
                            >
                                <i class="bi bi-gear me-2"></i>
                                Pengaturan
                            </button>
                        </li>

                        <li>
                            <hr class="dropdown-divider">
                        </li>

                        <li>
                            @auth
                                <form
                                    action="{{ route('logout') }}"
                                    method="POST"
                                    data-sd-logout-form
                                >
                                    @csrf

                                    <button
                                        type="submit"
                                        class="dropdown-item text-danger"
                                    >
                                        <i class="bi bi-box-arrow-right me-2"></i>
                                        Keluar
                                    </button>
                                </form>
                            @else
                                <a
                                    href="{{ route('beranda') }}"
                                    class="dropdown-item text-danger"
                                >
                                    Keluar
                                </a>
                            @endauth
                        </li>

                    </ul>

                </div>

            </div>

        </header>

        <main class="sd-content">

            <!-- =================================================
                 08. HALAMAN BERANDA
            ================================================== -->
            <section id="homePage">

                <!-- Banner -->
                <div class="sd-hero">
                    <div>
                        <h1>
                            Halo, {{ auth()->user()?->name ?? 'Siswa SALUANG' }}! 👋
                        </h1>

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

                <!-- Statistik -->
                <div class="row g-3 mt-1">

                    <div class="col-12 col-md-4">
                        <div class="sd-card sd-stat">

                            <img
                                src="{{ asset('images/progress-assets/progress-1.png') }}"
                                alt="Ilustrasi progres belajar"
                            >

                            <div>
                                <small>Progres Belajar</small>
                                <strong>0%</strong>
                                <p>0 dari 3 materi selesai</p>
                            </div>

                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="sd-card sd-stat">

                            <img
                                src="{{ asset('images/progress-assets/progress-2.png') }}"
                                alt="Ilustrasi kuis"
                            >

                            <div>
                                <small>Kuis Selesai</small>
                                <strong>1</strong>
                                <p>Hasil tersimpan pada akunmu</p>
                            </div>

                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="sd-card sd-stat">

                            <img
                                src="{{ asset('images/progress-assets/progress-3.png') }}"
                                alt="Ilustrasi evaluasi"
                            >

                            <div>
                                <small>Evaluasi Pembelajaran</small>
                                <strong>1</strong>
                                <p>Evaluasi lintas Bab dan Subbab</p>
                            </div>

                        </div>
                    </div>

                </div>

                <!-- Lanjutkan Belajar -->
                <div class="row g-3 mt-1">

                    <div class="col-12 col-xl-8">

                        <article class="sd-card h-100">

                            <h2 class="sd-heading">
                                <i class="bi bi-bar-chart-line"></i>
                                Lanjutkan Belajar
                            </h2>

                            <div class="sd-home-lesson">

                                <img
                                    src="{{ asset('images/lesson-interaksi-makhluk-hidup.png') }}"
                                    alt="Ekosistem lahan basah"
                                >

                                <div>
                                    <span class="sd-pill">
                                        MATERI PEMBELAJARAN
                                    </span>

                                    <h3 id="sdHomeLessonTitle">
                                        Memuat materi...
                                    </h3>

                                    <span class="sd-muted">
                                        Materi IPA Kelas VII
                                    </span>

                                    <div class="sd-progress"></div>

                                    <button
                                        type="button"
                                        class="sd-primary"
                                        data-page="1"
                                    >
                                        Lihat Materi
                                    </button>
                                </div>

                            </div>

                        </article>

                    </div>

                    <!-- Aktivitas -->
                    <div class="col-12 col-xl-4">

                        <article class="sd-card h-100">

                            <h2 class="sd-heading">
                                <i class="bi bi-bar-chart-line"></i>
                                Aktivitas Terbaru
                            </h2>

                            <div class="sd-empty">

                                <img
                                    src="{{ asset('images/saluang-materi-interaktif.png') }}"
                                    alt="Ilustrasi SALUANG"
                                >

                                <span>
                                    Aktivitas belajarmu akan muncul di sini.
                                </span>

                            </div>

                        </article>

                    </div>

                </div>

                <!-- Pencapaian -->
                <article class="sd-card mt-3">

                    <h2 class="sd-achievement-heading">
                        Pencapaian Belajar
                    </h2>

                    <p class="sd-muted">
                        Progres dihitung dari materi yang telah selesai.
                    </p>

                    <div
                        class="d-flex justify-content-between align-items-end mt-4 gap-3"
                    >
                        <div>
                            <span class="sd-muted">
                                IPA Kelas VII
                            </span>

                            <h3 class="sd-achievement-title" id="sdAchievementMaterialTitle">
                                Memuat materi...
                            </h3>

                            <span class="sd-muted">
                                Progres pembelajaran
                            </span>
                        </div>

                        <strong class="sd-status">
                            Belum dimulai
                        </strong>
                    </div>

                    <div class="sd-progress"></div>

                </article>

                <!-- Navigasi -->
                <div class="sd-navigation sd-navigation-home">

                    <button
                        type="button"
                        class="sd-nav-btn next"
                        data-page="7"
                    >
                        Next
                        <i class="bi bi-chevron-right"></i>
                    </button>

                </div>

            </section>

            <!-- =================================================
                 09. HALAMAN DAFTAR MATERI
            ================================================== -->
            <section id="materialOverviewPage" hidden>

                <!-- Navigasi atas -->
                <div class="sd-navigation">

                    <button
                        type="button"
                        class="sd-nav-btn"
                        data-page="0"
                    >
                        <i class="bi bi-chevron-left"></i>
                        Previous
                    </button>

                    <button
                        type="button"
                        class="sd-nav-btn next"
                        data-page="1"
                    >
                        Next
                        <i class="bi bi-chevron-right"></i>
                    </button>

                </div>

                <article class="sd-card sd-material-overview">

                    <div class="sd-material-overview-header">
                        <div>

                            <h1 class="sd-material-overview-title">
                                Materi Pembelajaran
                            </h1>

                            <p class="sd-material-overview-subtitle">
                                Pilih bab untuk melihat subbab dan
                                materi pembelajaran di dalamnya.
                            </p>

                        </div>
                    </div>

                    <!--
                        PENTING:
                        Daftar Bab dibuat otomatis oleh siswa.js
                        dari endpoint /siswa/materials.
                        Jangan menambahkan kartu contoh statis.
                    -->
                    <div class="sd-material-overview-grid"></div>

                </article>

                <!-- Navigasi bawah -->
                <div class="sd-navigation">

                    <button
                        type="button"
                        class="sd-nav-btn"
                        data-page="0"
                    >
                        <i class="bi bi-chevron-left"></i>
                        Previous
                    </button>

                    <button
                        type="button"
                        class="sd-nav-btn next"
                        data-page="1"
                    >
                        Next
                        <i class="bi bi-chevron-right"></i>
                    </button>

                </div>

            </section>

            <!-- =================================================
                 10. HALAMAN PENGENALAN BAB
            ================================================== -->
            <section id="chapterPage" hidden>

                <div class="sd-breadcrumb">
                    Beranda › Materi › Pengenalan Bab
                </div>

                <!-- Hero Bab -->
                <div class="sd-chapter-hero">

                    <div>
                        <span class="sd-pill">
                            PENGENALAN BAB
                        </span>

                        <h1 class="sd-title">
                            Materi Pembelajaran
                        </h1>

                        <p class="sd-copy">
                            Memuat informasi Bab...
                        </p>
                    </div>

                    <figure class="sd-chapter-introduction-figure">
                        <img
                            src="{{ asset('images/lesson-interaksi-makhluk-hidup.png') }}"
                            alt="Ilustrasi kehidupan lahan basah"
                            loading="lazy"
                        >
                        <figcaption class="sd-chapter-introduction-caption" id="sdChapterIntroductionCaption" hidden>
                            <strong id="sdChapterIntroductionTitle" hidden></strong>
                        </figcaption>
                    </figure>

                </div>

                <!-- Navigasi atas -->
                <div class="sd-navigation">

                    <button
                        type="button"
                        class="sd-nav-btn"
                        data-page="7"
                    >
                        <i class="bi bi-chevron-left"></i>
                        Previous
                    </button>

                    <button
                        type="button"
                        class="sd-nav-btn next"
                        data-page="2"
                    >
                        Next
                        <i class="bi bi-chevron-right"></i>
                    </button>

                </div>

                <!-- Tujuan Pembelajaran -->
                <article class="sd-card mt-3">

                    <h2 class="sd-heading">
                        <i class="bi bi-bullseye"></i>
                        Tujuan Pembelajaran
                    </h2>

                    <p class="sd-copy">
                        Setelah mempelajari bab ini,
                        siswa diharapkan mampu:
                    </p>

                    <!--
                        Diisi dari tujuan_pembelajaran
                        melalui renderObjectives().
                    -->
                    <div class="sd-objectives"></div>

                </article>

                <!-- Yuk, Pikirkan -->
                <article class="sd-card mt-3">

                    <h2 class="sd-heading">
                        <i class="bi bi-lightbulb"></i>
                        Yuk, Pikirkan!
                    </h2>

                    <div class="sd-prompt">
                        <div class="sd-copy mb-0">
                            Memuat pertanyaan...
                        </div>

                    </div>

                    <img
                        src="{{ asset('images/lesson-ekosistem-lahan-basah.png') }}"
                        alt="Ilustrasi ekosistem sungai"
                        class="sd-prompt-image"
                        loading="lazy"
                        onerror="this.hidden=true"
                    >

                </article>

                <!-- Navigasi bawah -->
                <div class="sd-navigation">

                    <button
                        type="button"
                        class="sd-nav-btn"
                        data-page="7"
                    >
                        <i class="bi bi-chevron-left"></i>
                        Previous
                    </button>

                    <button
                        type="button"
                        class="sd-nav-btn next"
                        data-page="2"
                    >
                        Next
                        <i class="bi bi-chevron-right"></i>
                    </button>

                </div>

            </section>

            <!-- =================================================
                 11. HALAMAN SUBBAB / BUKU DIGITAL
            ================================================== -->
            <section id="lessonPage" hidden>

                <div
                    class="sd-breadcrumb"
                    id="lessonBreadcrumb"
                ></div>

                <span
                    class="sd-pill"
                    id="lessonBadge"
                ></span>

                <h1
                    class="sd-title"
                    id="lessonTitle"
                ></h1>

                <!-- Navigasi atas subbab -->
                <div
                    class="sd-navigation"
                    data-lesson-navigation
                ></div>

                <!-- Materi Pembelajaran -->
                <article class="sd-card sd-material-card">

                    <div class="sd-material-grid">

                        <div class="sd-material-text">

                            <h2 class="sd-material-heading">
                                <i class="bi bi-book-half"></i>
                                Materi Pembelajaran
                            </h2>

                            <!--
                                KONTEN UTAMA:
                                Isi dari database Guru.
                                Dirender melalui siswa.js.

                                Mendukung paragraf, heading,
                                bold, italic, underline,
                                numbering, dan bullet.
                            -->
                            <div
                                class="sd-copy"
                                id="lessonContent"
                            ></div>

                            <!-- Fakta, tampil hanya bila diisi -->
                            <div class="sd-fact" hidden>
                                <strong>Tahukah Kamu?</strong>

                                <p
                                    class="sd-copy"
                                    id="lessonFact"
                                ></p>
                            </div>

                        </div>

                        <!-- Media Subbab dari Guru -->
                        <div class="sd-material-media" id="lessonMedia" hidden>
                            <div id="lessonMediaItems"></div>
                        </div>

                    </div>

                </article>

                <!-- =================================================
                     11.1 KUIS SUBBAB
                ================================================== -->
                <article class="sd-card mt-3">

                    <h2 class="sd-quiz-title">
                        Kuis Subbab
                    </h2>

                    <p class="sd-muted mt-2">
                        Pilih satu jawaban. Setelah diklik,
                        kamu langsung mendapat penjelasan benar atau salah.
                    </p>

                    <div
                        class="sd-question-count"
                        id="questionCount"
                    ></div>

                    <p
                        id="questionText"
                        class="fw-semibold mb-3"
                    ></p>

                    <!-- Jawaban dibuat JS -->
                    <div
                        class="sd-choices"
                        id="answerChoices"
                    ></div>

                    <!-- Feedback kuis -->
                    <div
                        id="answerFeedback"
                        class="sd-answer-feedback"
                        role="status"
                        aria-live="polite"
                        hidden
                    ></div>

                    <!-- Navigasi soal -->
                    <div class="sd-quiz-controls">

                        <button
                            type="button"
                            class="sd-nav-btn"
                            id="previousQuestion"
                        >
                            <i class="bi bi-chevron-left"></i>
                            Previous
                        </button>

                        <button
                            type="button"
                            class="sd-nav-btn next"
                            id="nextQuestion"
                        >
                            Next
                            <i class="bi bi-chevron-right"></i>
                        </button>

                    </div>

                </article>

                <!-- Navigasi bawah subbab -->
                <div
                    class="sd-navigation"
                    data-lesson-navigation
                ></div>

            </section>

            {{-- =================================================
                 12. HALAMAN EVALUASI
            ================================================== --}}
            <section id="sd-section-evaluasi" hidden>

                {{-- 12.1 PENGANTAR EVALUASI --}}
                <div
                    class="sd-eval-screen is-active"
                    data-eval-screen="overview"
                >

                    <div class="sd-navigation">

                        <button
                            type="button"
                            class="sd-nav-btn"
                            data-page="4"
                        >
                            <i class="bi bi-chevron-left"></i>
                            Previous
                        </button>

                        <button
                            type="button"
                            class="sd-nav-btn next"
                            data-page="6"
                        >
                            Next
                            <i class="bi bi-chevron-right"></i>
                        </button>

                    </div>

                    <article class="sd-card sd-eval-card sd-eval-intro">

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
                                        klasifikasi organisme, interaksi,
                                        dan lingkungan lahan basah!
                                    </p>
                                </div>

                            </div>

                        </div>

                        <div class="sd-eval-intro-layout">

                            <div class="sd-eval-intro-main">

                                <!-- Informasi -->
                                <section class="sd-eval-intro-panel">

                                    <h2 class="sd-eval-intro-panel-title">
                                        <i class="bi bi-info-circle"></i>
                                        Informasi Evaluasi
                                    </h2>

                                    <div class="sd-eval-info-grid">

                                        <div class="sd-eval-info-card sd-eval-info-card--blue">

                                            <div class="sd-eval-info-image">
                                                <img
                                                    src="{{ asset('images/evaluasi/eval-jumlah-soal.png') }}"
                                                    alt="Jumlah soal evaluasi"
                                                >
                                            </div>

                                            <div>
                                                <span>Jumlah Soal</span>
                                                <strong>20 Soal</strong>
                                            </div>

                                        </div>

                                        <div class="sd-eval-info-card sd-eval-info-card--green">

                                            <div class="sd-eval-info-image">
                                                <img
                                                    src="{{ asset('images/evaluasi/eval-durasi.png') }}"
                                                    alt="Durasi evaluasi"
                                                >
                                            </div>

                                            <div>
                                                <span>Durasi Pengerjaan</span>
                                                <strong>45 Menit</strong>
                                            </div>

                                        </div>

                                        <div class="sd-eval-info-card sd-eval-info-card--yellow">

                                            <div class="sd-eval-info-image">
                                                <img
                                                    src="{{ asset('images/evaluasi/eval-ketuntasan.png') }}"
                                                    alt="Ketuntasan belajar"
                                                >
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
                                                Karakteristik dan klasifikasi
                                                makhluk hidup, komponen biotik
                                                dan abiotik, keanekaragaman
                                                organisme, serta interaksi
                                                dalam lingkungan lahan basah.
                                            </p>
                                        </div>

                                    </div>

                                </section>

                                <!-- Petunjuk -->
                                <section class="sd-eval-intro-panel">

                                    <h2 class="sd-eval-intro-panel-title">
                                        <i class="bi bi-list-check"></i>
                                        Petunjuk Pengerjaan
                                    </h2>

                                    <ol class="sd-eval-instructions">
                                        <li>
                                            Bacalah setiap pertanyaan
                                            dengan teliti.
                                        </li>

                                        <li>
                                            Evaluasi terdiri dari 20 soal:
                                            pilihan ganda, drag-and-drop,
                                            menghubungkan pasangan,
                                            dan memilih gambar.
                                        </li>

                                        <li>
                                            Seret gambar ke kelompok
                                            yang sesuai.
                                        </li>

                                        <li>
                                            Untuk soal pasangan,
                                            hubungkan bullet sumber
                                            dengan bullet jawaban.
                                        </li>

                                        <li>
                                            Gunakan Previous dan Next
                                            untuk berpindah soal.
                                        </li>

                                        <li>
                                            Waktu pengerjaan 45 menit.
                                            Periksa jawaban sebelum selesai.
                                        </li>
                                    </ol>

                                </section>

                            </div>

                            <!-- Panel samping -->
                            <aside class="sd-eval-intro-aside">

                                <section class="sd-eval-intro-panel">

                                    <h2 class="sd-eval-intro-panel-title">
                                        <i class="bi bi-bullseye"></i>
                                        Tujuan Evaluasi
                                    </h2>

                                    <ol class="sd-eval-instructions">
                                        <li>
                                            Mengukur pemahaman tentang
                                            makhluk hidup dan benda mati.
                                        </li>

                                        <li>
                                            Menilai kemampuan
                                            mengelompokkan organisme.
                                        </li>

                                        <li>
                                            Menguji pemahaman tentang
                                            keanekaragaman hayati
                                            lahan basah.
                                        </li>
                                    </ol>

                                </section>

                                <div class="sd-eval-intro-quote">
                                    <i class="bi bi-lightbulb"></i>

                                    <p>
                                        Belajar dari alam,
                                        tumbuh bersama pengetahuan,
                                        dan jadilah penjaga lahan basah!
                                    </p>
                                </div>

                                <div class="sd-eval-intro-start">

                                    <button
                                        type="button"
                                        class="sd-eval-primary"
                                        data-eval-open="quiz"
                                    >
                                        Mulai Evaluasi
                                    </button>

                                </div>

                            </aside>

                        </div>

                    </article>

                </div>

                {{-- =================================================
                     12.2 LAYAR PENGERJAAN EVALUASI
                ================================================== --}}
                <div
                    class="sd-eval-screen"
                    data-eval-screen="quiz"
                >

                    <div class="sd-eval-exam-header">

                        <div class="sd-eval-exam-title-group">

                            <span class="sd-eval-exam-icon">
                                <i class="bi bi-clipboard2-check"></i>
                            </span>

                            <div>
                                <h1>Evaluasi Akhir</h1>

                                <p>
                                    Jawablah setiap soal dengan teliti dan jujur!
                                </p>
                            </div>

                        </div>

                        <button
                            type="button"
                            class="sd-eval-outline sd-eval-exam-back"
                            id="sd-eval-quiz-exit"
                        >
                            Kembali ke Evaluasi
                        </button>

                    </div>

                    <div class="sd-eval-exam-layout">

                        <!-- Kolom soal -->
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

                                <div
                                    class="sd-eval-exam-question-content"
                                    id="sd-eval-question-content"
                                >
                                    <div class="sd-eval-question-placeholder">

                                        <i class="bi bi-journal-text"></i>

                                        <h2>Soal Nomor 1</h2>

                                        <p>
                                            Soal akan dimuat oleh
                                            JavaScript evaluasi.
                                        </p>

                                    </div>
                                </div>

                            </article>

                            <div class="sd-eval-exam-navigation">

                                <button
                                    type="button"
                                    class="sd-nav-btn"
                                    id="sd-eval-quiz-prev"
                                    hidden
                                >
                                    <i class="bi bi-chevron-left"></i>
                                    Previous
                                </button>

                                <button
                                    type="button"
                                    class="sd-nav-btn next"
                                    id="sd-eval-quiz-next"
                                >
                                    Next
                                    <i class="bi bi-chevron-right"></i>
                                </button>

                                <span
                                    class="sd-eval-quiz-last-note"
                                    id="sd-eval-quiz-last-note"
                                    hidden
                                >
                                    Kamu sudah berada di nomor terakhir.
                                </span>

                            </div>

                        </div>

                        <!-- Panel timer -->
                        <aside class="sd-eval-exam-sidebar">

                            <div class="sd-eval-exam-timer-panel">

                                <div class="sd-eval-timer-header">

                                    <i class="bi bi-clock-history"></i>

                                    <div>
                                        <span>Waktu Tersisa</span>

                                        <strong
                                            id="sd-eval-timer"
                                            role="timer"
                                            aria-label="Sisa waktu evaluasi"
                                        >
                                            45:00
                                        </strong>
                                    </div>

                                </div>

                                <div class="sd-eval-exam-number-panel">

                                    <h2>
                                        <i class="bi bi-grid-3x3-gap"></i>
                                        Daftar Soal
                                    </h2>

                                    <div
                                        class="sd-eval-number-grid"
                                        id="sd-eval-number-grid"
                                        aria-label="Pilih nomor soal"
                                    ></div>

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

                                <p
                                    class="sd-eval-timer-notice"
                                    id="sd-eval-timer-notice"
                                    role="status"
                                    aria-live="polite"
                                ></p>

                            </div>

                        </aside>

                    </div>

                </div>

                {{-- =================================================
                     12.3 MODAL KONFIRMASI EVALUASI
                ================================================== --}}
                <div
                    class="sd-eval-confirm"
                    id="sd-eval-confirm"
                    hidden
                >
                    <div class="sd-eval-confirm-backdrop"></div>

                    <div
                        class="sd-eval-confirm-dialog"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="sd-eval-confirm-title"
                        aria-describedby="sd-eval-confirm-description"
                        tabindex="-1"
                    >

                        <button
                            type="button"
                            class="sd-eval-confirm-close"
                            id="sd-eval-confirm-close"
                            aria-label="Tutup konfirmasi"
                        >
                            <i class="bi bi-x-lg"></i>
                        </button>

                        <div class="sd-eval-confirm-icon">

                            <img
                                id="sd-eval-confirm-icon"
                                src="{{ asset('images/evaluasi/bekantan-bertanya.png') }}"
                                alt="Bekantan sedang berpikir"
                                width="170"
                                height="155"
                            >

                        </div>

                        <h2
                            class="sd-eval-confirm-title"
                            id="sd-eval-confirm-title"
                        >
                            Yakin Ingin Memulai Evaluasi?
                        </h2>

                        <p
                            class="sd-eval-confirm-description"
                            id="sd-eval-confirm-description"
                        >
                            Kamu akan mengerjakan 20 soal
                            selama 45 menit.
                        </p>

                        <div
                            class="sd-eval-confirm-info"
                            id="sd-eval-confirm-info"
                        >
                            <i class="bi bi-clock-history"></i>

                            <span>
                                Setelah dimulai, waktu akan
                                terus berjalan meskipun
                                kamu keluar dari halaman.
                            </span>
                        </div>

                        <div class="sd-eval-confirm-actions">

                            <button
                                type="button"
                                class="sd-eval-confirm-cancel"
                                id="sd-eval-confirm-cancel"
                            >
                                Batal
                            </button>

                            <button
                                type="button"
                                class="sd-eval-confirm-accept"
                                id="sd-eval-confirm-accept"
                            >
                                <span id="sd-eval-confirm-accept-text">
                                    Ya, Mulai
                                </span>
                            </button>

                        </div>

                    </div>
                </div>

                {{-- =================================================
                     12.4 EVALUASI INTERAKTIF SOAL 1
                     KLASIFIKASI BIOTIK DAN ABIOTIK
                ================================================== --}}
                <div
                    class="sd-eval-screen"
                    data-eval-screen="q1"
                >

                    <div class="sd-eval-time">
                        <i class="bi bi-clock"></i>
                        Waktu pengerjaan: ± 30 menit
                    </div>

                    <article class="sd-card sd-eval-card">

                        <div class="sd-eval-overview-header">

                            <div>
                                <h1 class="sd-eval-heading">
                                    Evaluasi — Karakteristik dan
                                    Klasifikasi Makhluk Hidup
                                </h1>

                                <p class="sd-eval-description">
                                    BAB 1 — Karakteristik dan Klasifikasi
                                    Makhluk Hidup
                                </p>
                            </div>

                            <button
                                type="button"
                                class="sd-eval-outline"
                                data-eval-open="overview"
                            >
                                Kembali ke Evaluasi
                            </button>

                        </div>

                        <div class="sd-eval-topic">
                            1.1 Makhluk Hidup atau Benda Mati?
                        </div>

                        <p class="sd-eval-number">
                            Soal 1 dari 2
                        </p>

                        <h2 class="sd-eval-question">
                            Kelompokkan gambar komponen lahan basah
                            ke target yang benar.
                        </h2>

                        <p class="sd-eval-description">
                            Seret gambar ke Biotik atau Abiotik.
                            Pada layar sentuh, ketuk gambar
                            lalu ketuk target.
                        </p>

                        <div class="sd-eval-classification sd-eval-q1-layout">

                            <!-- Sumber gambar -->
                            <div class="sd-eval-items sd-eval-image-items">

                                <button
                                    type="button"
                                    class="sd-eval-item sd-eval-image-item"
                                    data-eval-item
                                    data-question="q1"
                                    data-item="ikan_saluang"
                                    aria-label="Ikan saluang"
                                    title="Ikan saluang"
                                >
                                    <span class="sd-eval-emoji">
                                        <img
                                            src="{{ asset('images/evaluasi/saluang.png') }}"
                                            alt="Ikan saluang"
                                            draggable="false"
                                        >
                                    </span>
                                </button>

                                <button
                                    type="button"
                                    class="sd-eval-item sd-eval-image-item"
                                    data-eval-item
                                    data-question="q1"
                                    data-item="air_sungai"
                                    aria-label="Air sungai"
                                    title="Air sungai"
                                >
                                    <span class="sd-eval-emoji">
                                        <img
                                            src="{{ asset('images/evaluasi/air-sungai.png') }}"
                                            alt="Air sungai"
                                            draggable="false"
                                        >
                                    </span>
                                </button>

                                <button
                                    type="button"
                                    class="sd-eval-item sd-eval-image-item"
                                    data-eval-item
                                    data-question="q1"
                                    data-item="tumbuhan_purun"
                                    aria-label="Tumbuhan purun"
                                    title="Tumbuhan purun"
                                >
                                    <span class="sd-eval-emoji">
                                        <img
                                            src="{{ asset('images/evaluasi/purun.png') }}"
                                            alt="Tumbuhan purun"
                                            draggable="false"
                                        >
                                    </span>
                                </button>

                            </div>

                            <!-- Target -->
                            <div class="sd-eval-targets">

                                <button
                                    type="button"
                                    class="sd-eval-target"
                                    data-eval-target
                                    data-question="q1"
                                    data-target="biotik"
                                >
                                    <strong>Biotik</strong>
                                    <small>Komponen makhluk hidup</small>

                                    <span
                                        class="sd-eval-chips"
                                        data-target-box="q1:biotik"
                                    ></span>
                                </button>

                                <button
                                    type="button"
                                    class="sd-eval-target"
                                    data-eval-target
                                    data-question="q1"
                                    data-target="abiotik"
                                >
                                    <strong>Abiotik</strong>
                                    <small>Komponen benda tak hidup</small>

                                    <span
                                        class="sd-eval-chips"
                                        data-target-box="q1:abiotik"
                                    ></span>
                                </button>

                            </div>

                        </div>

                        <div
                            class="sd-eval-feedback"
                            data-eval-feedback="q1"
                            role="status"
                            aria-live="polite"
                            hidden
                        ></div>

                        <div class="sd-eval-navigation">

                            <button
                                type="button"
                                class="sd-nav-btn"
                                data-eval-open="overview"
                            >
                                <i class="bi bi-chevron-left"></i>
                                Previous
                            </button>

                            <button
                                type="button"
                                class="sd-nav-btn next"
                                data-eval-open="q2"
                                data-next-for="q1"
                                hidden
                            >
                                Next
                                <i class="bi bi-chevron-right"></i>
                            </button>

                        </div>

                    </article>

                </div>

                {{-- =================================================
                     12.5 EVALUASI INTERAKTIF SOAL 2
                     MENGHUBUNGKAN PASANGAN
                ================================================== --}}
                <div
                    class="sd-eval-screen"
                    data-eval-screen="q2"
                >

                    <div class="sd-eval-time">
                        <i class="bi bi-clock"></i>
                        Waktu pengerjaan: ± 30 menit
                    </div>

                    <article class="sd-card sd-eval-card">

                        <div class="sd-eval-overview-header">

                            <div>
                                <h1 class="sd-eval-heading">
                                    Evaluasi — Karakteristik dan
                                    Klasifikasi Makhluk Hidup
                                </h1>

                                <p class="sd-eval-description">
                                    BAB 1 — Karakteristik dan Klasifikasi
                                    Makhluk Hidup
                                </p>
                            </div>

                            <button
                                type="button"
                                class="sd-eval-outline"
                                data-eval-open="overview"
                            >
                                Kembali ke Evaluasi
                            </button>

                        </div>

                        <div class="sd-eval-topic">
                            1.2 Mengapa Makhluk Hidup Dikelompokkan?
                        </div>

                        <p class="sd-eval-number">
                            Soal 2 dari 2
                        </p>

                        <h2 class="sd-eval-question">
                            Hubungkan organisme lahan basah
                            dengan kelompoknya: hewan atau tumbuhan.
                        </h2>

                        <p class="sd-eval-description">
                            Klik bullet hijau pada gambar,
                            lalu klik bullet pada kelompok
                            Hewan atau Tumbuhan.
                        </p>

                        <div
                            class="sd-eval-classification sd-eval-match-layout"
                            id="sdMatchQ2"
                        >

                            <svg
                                class="sd-eval-lines"
                                id="sdMatchLinesQ2"
                                aria-hidden="true"
                            ></svg>

                            <!-- Sumber -->
                            <div class="sd-eval-items sd-eval-match-items">

                                <div
                                    class="sd-eval-match-row"
                                    data-match-row="ikan"
                                >
                                    <img
                                        src="{{ asset('images/evaluasi/saluang.png') }}"
                                        alt="Ikan"
                                        title="Ikan"
                                        class="sd-eval-match-image"
                                    >

                                    <button
                                        type="button"
                                        class="sd-eval-bullet sd-eval-bullet-source"
                                        data-eval-item
                                        data-question="q2"
                                        data-item="ikan"
                                        aria-label="Hubungkan ikan"
                                        title="Ikan"
                                    ></button>
                                </div>

                                <div
                                    class="sd-eval-match-row"
                                    data-match-row="burung"
                                >
                                    <img
                                        src="{{ asset('images/evaluasi/burung-rangkong.png') }}"
                                        alt="Burung rangkong"
                                        title="Burung rangkong"
                                        class="sd-eval-match-image"
                                    >

                                    <button
                                        type="button"
                                        class="sd-eval-bullet sd-eval-bullet-source"
                                        data-eval-item
                                        data-question="q2"
                                        data-item="burung"
                                        aria-label="Hubungkan burung"
                                        title="Burung"
                                    ></button>
                                </div>

                                <div
                                    class="sd-eval-match-row"
                                    data-match-row="katak"
                                >
                                    <img
                                        src="{{ asset('images/evaluasi/katak.png') }}"
                                        alt="Katak"
                                        title="Katak"
                                        class="sd-eval-match-image"
                                    >

                                    <button
                                        type="button"
                                        class="sd-eval-bullet sd-eval-bullet-source"
                                        data-eval-item
                                        data-question="q2"
                                        data-item="katak"
                                        aria-label="Hubungkan katak"
                                        title="Katak"
                                    ></button>
                                </div>

                                <div
                                    class="sd-eval-match-row"
                                    data-match-row="teratai"
                                >
                                    <img
                                        src="{{ asset('images/evaluasi/teratai.png') }}"
                                        alt="Teratai"
                                        title="Teratai"
                                        class="sd-eval-match-image"
                                    >

                                    <button
                                        type="button"
                                        class="sd-eval-bullet sd-eval-bullet-source"
                                        data-eval-item
                                        data-question="q2"
                                        data-item="teratai"
                                        aria-label="Hubungkan teratai"
                                        title="Teratai"
                                    ></button>
                                </div>

                                <div
                                    class="sd-eval-match-row"
                                    data-match-row="purun"
                                >
                                    <img
                                        src="{{ asset('images/evaluasi/purun.png') }}"
                                        alt="Purun"
                                        title="Purun"
                                        class="sd-eval-match-image"
                                    >

                                    <button
                                        type="button"
                                        class="sd-eval-bullet sd-eval-bullet-source"
                                        data-eval-item
                                        data-question="q2"
                                        data-item="purun"
                                        aria-label="Hubungkan purun"
                                        title="Purun"
                                    ></button>
                                </div>

                            </div>

                            <!-- Target -->
                            <div class="sd-eval-targets sd-eval-match-targets">

                                <div class="sd-eval-match-target">

                                    <button
                                        type="button"
                                        class="sd-eval-bullet sd-eval-bullet-target"
                                        data-eval-target
                                        data-question="q2"
                                        data-target="hewan"
                                        aria-label="Hubungkan ke Hewan"
                                        title="Hewan"
                                    ></button>

                                    <div class="sd-eval-match-target-label">
                                        <strong>Hewan</strong>
                                        <small>Kelompok organisme hewan</small>
                                    </div>

                                </div>

                                <div class="sd-eval-match-target">

                                    <button
                                        type="button"
                                        class="sd-eval-bullet sd-eval-bullet-target"
                                        data-eval-target
                                        data-question="q2"
                                        data-target="tumbuhan"
                                        aria-label="Hubungkan ke Tumbuhan"
                                        title="Tumbuhan"
                                    ></button>

                                    <div class="sd-eval-match-target-label">
                                        <strong>Tumbuhan</strong>
                                        <small>Kelompok organisme tumbuhan</small>
                                    </div>

                                </div>

                            </div>

                        </div>

                        <div
                            class="sd-eval-feedback"
                            data-eval-feedback="q2"
                            role="status"
                            aria-live="polite"
                            hidden
                        ></div>

                        <div class="sd-eval-navigation">

                            <button
                                type="button"
                                class="sd-nav-btn"
                                data-eval-open="q1"
                            >
                                <i class="bi bi-chevron-left"></i>
                                Previous
                            </button>

                            <button
                                type="button"
                                class="sd-eval-primary"
                                id="sd-eval-finish"
                                hidden
                            >
                                Selesai Evaluasi
                                <i class="bi bi-check2-circle"></i>
                            </button>

                        </div>

                    </article>

                </div>

                {{-- =================================================
                     12.6 HASIL EVALUASI
                ================================================== --}}
                <div
                    class="sd-eval-screen"
                    data-eval-screen="result"
                >

                    <article class="sd-card sd-eval-card">

                        <div class="sd-eval-overview-header">

                            <h1 class="sd-eval-heading">
                                Evaluasi Bab 1 Selesai!
                            </h1>

                            <button
                                type="button"
                                class="sd-eval-outline"
                                data-eval-open="overview"
                            >
                                Kembali ke Evaluasi
                            </button>

                        </div>

                        <div class="sd-eval-result">

                            <div class="sd-eval-result-text">

                                <span class="sd-pill">
                                    HASIL EVALUASI
                                </span>

                                <h2 id="sd-eval-result-heading">
                                    Selamat sudah menyelesaikan evaluasi!
                                </h2>

                                <div class="sd-eval-score">

                                    <span>Nilai kamu</span>

                                    <strong id="sd-eval-final-score">
                                        0
                                    </strong>

                                    <small id="sd-eval-final-summary">
                                        0 benar · 0 salah
                                    </small>

                                </div>

                                <p id="sd-eval-final-message">
                                    Terus semangat belajar bersama SALUANG!
                                </p>

                                <div class="sd-eval-result-actions">

                                    <button
                                        type="button"
                                        class="sd-eval-outline"
                                        id="sd-eval-retry"
                                    >
                                        Coba Lagi
                                    </button>

                                    <button
                                        type="button"
                                        class="sd-eval-primary"
                                        data-page="6"
                                    >
                                        Lanjut ke Forum Diskusi
                                        <i class="bi bi-arrow-right"></i>
                                    </button>

                                </div>

                            </div>

                            <div class="sd-eval-result-illustration">

                                <img
                                    src="{{ asset('images/evaluasi/hasil-evaluasi.png') }}"
                                    alt="Ilustrasi keberhasilan evaluasi"
                                >

                                <p>
                                    Terus jelajahi keanekaragaman hayati
                                    lahan basah bersama SALUANG!
                                </p>

                            </div>

                        </div>

                    </article>

                </div>

                {{-- Navigasi evaluasi --}}
                <div class="sd-navigation sd-eval-outer-navigation">

                    <button
                        type="button"
                        class="sd-nav-btn"
                        data-page="4"
                    >
                        <i class="bi bi-chevron-left"></i>
                        Previous
                    </button>

                    <button
                        type="button"
                        class="sd-nav-btn next"
                        data-page="6"
                    >
                        Next
                        <i class="bi bi-chevron-right"></i>
                    </button>

                </div>

            </section>

            {{-- =================================================
                 13. HALAMAN FORUM DISKUSI
            ================================================== --}}
            <section id="finalPage" hidden>

                <!-- Navigasi atas -->
                <div class="sd-navigation">

                    <button
                        type="button"
                        class="sd-nav-btn"
                        data-page="5"
                    >
                        <i class="bi bi-chevron-left"></i>
                        Previous
                    </button>

                    <button
                        type="button"
                        class="sd-nav-btn next"
                        data-page="8"
                    >
                        Next
                        <i class="bi bi-chevron-right"></i>
                    </button>

                </div>

                <article class="sd-card sd-forum-card">

                    <!-- Header -->
                    <div class="sd-forum-header">

                        <div>
                            <h1 class="sd-forum-title">
                                Forum Diskusi
                            </h1>

                            <p class="sd-forum-subtitle">
                                Diskusikan pembelajaran dengan konteks
                                Bab dan Subbab yang sama.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="sd-forum-primary"
                            id="forumCreateButton"
                        >
                            <i class="bi bi-plus-lg"></i>
                            Buat Diskusi
                        </button>

                    </div>

                    <!-- =================================================
                         13.1 DAFTAR DISKUSI
                    ================================================== -->
                    <div id="forumListScreen">

                        <div class="sd-forum-filters">

                            <div class="sd-forum-field">

                                <label for="forumChapterFilter">
                                    Bab
                                </label>

                                <select id="forumChapterFilter">
                                    <option value="">
                                        Semua Bab
                                    </option>

                                    <option value="bab-1">
                                        BAB 1 – Karakteristik dan Klasifikasi
                                        Makhluk Hidup
                                    </option>
                                </select>

                            </div>

                            <div class="sd-forum-field">

                                <label for="forumSubchapterFilter">
                                    Subbab
                                </label>

                                <select id="forumSubchapterFilter">

                                    <option value="">
                                        Semua Subbab
                                    </option>

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

                        <!-- Pencarian -->
                        <div class="sd-forum-field sd-forum-search">

                            <label for="forumSearchInput">
                                Cari diskusi
                            </label>

                            <input
                                type="search"
                                id="forumSearchInput"
                                placeholder="Cari pertanyaan atau judul diskusi"
                            >

                        </div>

                        <!-- Filter status -->
                        <div class="sd-forum-status-filters">

                            <button
                                type="button"
                                class="sd-forum-filter-btn active"
                                data-forum-status="all"
                            >
                                Terbaru
                            </button>

                            <button
                                type="button"
                                class="sd-forum-filter-btn"
                                data-forum-status="answered"
                            >
                                Sudah Terjawab
                            </button>

                            <button
                                type="button"
                                class="sd-forum-filter-btn"
                                data-forum-status="unanswered"
                            >
                                Belum Terjawab
                            </button>

                        </div>

                        <p
                            id="forumListMessage"
                            class="sd-forum-message"
                            role="status"
                            aria-live="polite"
                        ></p>

                        <!-- Daftar dari database -->
                        <div id="forumDiscussionList"></div>

                        <!-- Paginasi -->
                        <div
                            class="sd-forum-pagination"
                            id="forumPagination"
                        ></div>

                    </div>

                    <!-- =================================================
                         13.2 BUAT DISKUSI
                    ================================================== -->
                    <div id="forumCreateScreen" hidden>

                        <div class="sd-forum-screen-header">

                            <h2>Buat Diskusi Baru</h2>

                            <button
                                type="button"
                                class="sd-forum-back"
                                data-forum-back
                            >
                                Kembali ke Daftar
                            </button>

                        </div>

                        <form id="forumCreateForm">

                            <div class="sd-forum-field">

                                <label for="forumCreateChapter">
                                    Bab
                                </label>

                                <select
                                    id="forumCreateChapter"
                                    required
                                >
                                    <option value="bab-1">
                                        BAB 1 – Karakteristik dan
                                        Klasifikasi Makhluk Hidup
                                    </option>
                                </select>

                            </div>

                            <div class="sd-forum-field">

                                <label for="forumCreateSubchapter">
                                    Subbab
                                </label>

                                <select
                                    id="forumCreateSubchapter"
                                    required
                                >
                                    <option value="">
                                        Pilih Subbab
                                    </option>

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

                                <input
                                    type="text"
                                    id="forumCreateTitle"
                                    minlength="5"
                                    maxlength="200"
                                    placeholder="Tulis judul pertanyaan"
                                    required
                                >

                            </div>

                            <div class="sd-forum-field">

                                <label for="forumCreateBody">
                                    Pertanyaan
                                </label>

                                <textarea
                                    id="forumCreateBody"
                                    minlength="10"
                                    maxlength="5000"
                                    rows="6"
                                    placeholder="Jelaskan pertanyaanmu di sini..."
                                    required
                                ></textarea>

                            </div>

                            <p
                                id="forumCreateMessage"
                                class="sd-forum-message"
                                role="status"
                                aria-live="polite"
                            ></p>

                            <button
                                type="submit"
                                class="sd-forum-primary"
                                id="forumCreateSubmit"
                            >
                                <i class="bi bi-send"></i>
                                Kirim Diskusi
                            </button>

                        </form>

                    </div>

                    <!-- =================================================
                         13.3 DETAIL DISKUSI
                    ================================================== -->
                    <div id="forumDetailScreen" hidden>

                        <div class="sd-forum-screen-header">

                            <h2>Detail Diskusi</h2>

                            <button
                                type="button"
                                class="sd-forum-back"
                                data-forum-back
                            >
                                Kembali ke Daftar
                            </button>

                        </div>

                        <!-- Pertanyaan dari database -->
                        <div id="forumDetailContent"></div>

                        <h3 class="sd-forum-replies-heading">
                            Tanggapan Diskusi
                        </h3>

                        <!-- Balasan dari database -->
                        <div id="forumRepliesList"></div>

                        <form id="forumReplyForm">

                            <div class="sd-forum-field">

                                <label for="forumReplyBody">
                                    Tulis Tanggapan
                                </label>

                                <textarea
                                    id="forumReplyBody"
                                    minlength="2"
                                    maxlength="5000"
                                    rows="4"
                                    placeholder="Tuliskan tanggapanmu..."
                                    required
                                ></textarea>

                            </div>

                            <p
                                id="forumReplyMessage"
                                class="sd-forum-message"
                                role="status"
                                aria-live="polite"
                            ></p>

                            <button
                                type="submit"
                                class="sd-forum-primary"
                                id="forumReplySubmit"
                            >
                                <i class="bi bi-send"></i>
                                Kirim Tanggapan
                            </button>

                        </form>

                    </div>

                </article>

                <!-- =================================================
                     13.4 MODAL HAPUS DISKUSI
                ================================================== -->
                <div
                    class="sd-forum-confirm"
                    id="forumDeleteModal"
                    hidden
                >

                    <div
                        class="sd-forum-confirm-backdrop"
                        id="forumDeleteBackdrop"
                    ></div>

                    <div
                        class="sd-forum-confirm-dialog"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="forumDeleteTitle"
                        aria-describedby="forumDeleteDescription"
                        tabindex="-1"
                    >

                        <button
                            type="button"
                            class="sd-forum-confirm-close"
                            id="forumDeleteClose"
                            aria-label="Tutup konfirmasi"
                        >
                            <i class="bi bi-x-lg"></i>
                        </button>

                        <div class="sd-forum-confirm-icon">
                            <i class="bi bi-trash3"></i>
                        </div>

                        <h2
                            class="sd-forum-confirm-title"
                            id="forumDeleteTitle"
                        >
                            Yakin Ingin Menghapus Diskusi?
                        </h2>

                        <p
                            class="sd-forum-confirm-description"
                            id="forumDeleteDescription"
                        >
                            Diskusi ini beserta seluruh tanggapannya
                            akan dihapus secara permanen.
                        </p>

                        <div class="sd-forum-confirm-info">

                            <span>Diskusi yang akan dihapus:</span>

                            <strong id="forumDeleteDiscussionTitle"></strong>

                        </div>

                        <p class="sd-forum-confirm-warning">
                            Tindakan ini tidak dapat dibatalkan.
                        </p>

                        <div class="sd-forum-confirm-actions">

                            <button
                                type="button"
                                class="sd-forum-confirm-cancel"
                                id="forumDeleteCancel"
                            >
                                Batal
                            </button>

                            <button
                                type="button"
                                class="sd-forum-confirm-danger"
                                id="forumDeleteAccept"
                            >
                                Ya, Hapus
                            </button>

                        </div>

                        <p
                            class="sd-forum-confirm-error"
                            id="forumDeleteError"
                            role="alert"
                            hidden
                        ></p>

                    </div>

                </div>

                <!-- Navigasi bawah forum -->
                <div class="sd-navigation">

                    <button
                        type="button"
                        class="sd-nav-btn"
                        data-page="5"
                    >
                        <i class="bi bi-chevron-left"></i>
                        Previous
                    </button>

                    <button
                        type="button"
                        class="sd-nav-btn next"
                        data-page="8"
                    >
                        Next
                        <i class="bi bi-chevron-right"></i>
                    </button>

                </div>

            </section>

            {{-- =================================================
                 14. HALAMAN PROFIL SISWA
            ================================================== --}}
            <section id="profilePage" hidden>

                <!-- Navigasi atas -->
                <div class="sd-navigation">

                    <button
                        type="button"
                        class="sd-nav-btn"
                        data-page="6"
                    >
                        <i class="bi bi-chevron-left"></i>
                        Previous
                    </button>

                </div>

                <div class="sd-profile-container">

                    <!-- Judul -->
                    <div class="sd-profile-heading">
                        <h1>Profil Saya</h1>
                        <p>
                            Kelola informasi pribadi
                            dan pengaturan akunmu.
                        </p>
                    </div>

                    <!-- =================================================
                         14.1 IDENTITAS SISWA
                    ================================================== -->
                    <article class="sd-profile-card sd-profile-identity">

                        <div
                            class="sd-profile-avatar"
                            id="profileAvatar"
                        >

                            @if (auth()->user()?->profile_photo_path)

                                <img
                                    id="profileAvatarImage"
                                    src="{{ asset('storage/' . auth()->user()->profile_photo_path) }}"
                                    alt="Foto profil siswa"
                                >

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

                                <button
                                    type="button"
                                    class="sd-profile-outline"
                                    id="profilePhotoButton"
                                >
                                    Ubah Foto Profil
                                </button>

                                <input
                                    type="file"
                                    id="profilePhotoInput"
                                    accept="image/png,image/jpeg,image/webp"
                                    hidden
                                >

                                <button
                                    type="button"
                                    class="sd-profile-delete-photo"
                                    id="profileDeletePhotoButton"
                                    @if (!auth()->user()?->profile_photo_path) hidden @endif
                                >
                                    Hapus Foto
                                </button>

                                <span id="profilePhotoFilename">
                                    Belum ada file dipilih
                                </span>

                            </div>

                        </div>

                    </article>

                    <!-- =================================================
                         14.2 INFORMASI PRIBADI
                    ================================================== -->
                    <article class="sd-profile-card">

                        <div class="sd-profile-card-header">

                            <h2>Informasi Pribadi</h2>

                            <button
                                type="button"
                                class="sd-profile-outline"
                                id="profileEditButton"
                            >
                                Edit Profil
                            </button>

                        </div>

                        <div id="profileInformation">

                            <div class="sd-profile-info-grid">

                                <div class="sd-profile-info-item">
                                    <span>Nama Lengkap</span>

                                    <strong>
                                        {{ auth()->user()?->name ?? 'Belum diisi' }}
                                    </strong>
                                </div>

                                <div class="sd-profile-info-item">
                                    <span>Username</span>

                                    <strong>
                                        {{ auth()->user()?->username ?? 'Belum diisi' }}
                                    </strong>
                                </div>

                                <div class="sd-profile-info-item">
                                    <span>Email</span>

                                    <strong>
                                        {{ auth()->user()?->email ?? 'Belum diisi' }}
                                    </strong>
                                </div>

                                <div class="sd-profile-info-item">
                                    <span>NISN</span>

                                    <strong>
                                        {{ auth()->user()?->nisn ?? 'Belum diisi' }}
                                    </strong>
                                </div>

                                <div class="sd-profile-info-item">
                                    <span>Kelas</span>

                                    <strong>
                                        {{ auth()->user()?->kelas ?? 'Belum diisi' }}
                                    </strong>
                                </div>

                                <div class="sd-profile-info-item">
                                    <span>Sekolah</span>

                                    <strong>
                                        {{ auth()->user()?->sekolah ?? 'Belum diisi' }}
                                    </strong>
                                </div>

                            </div>

                        </div>

                        <!-- Form Edit Profil -->
                        <form
                            id="profileEditForm"
                            class="sd-profile-form"
                            hidden
                        >

                            <div class="sd-profile-form-grid">

                                <label>
                                    Nama Lengkap

                                    <input
                                        type="text"
                                        id="profileEditName"
                                        value="{{ auth()->user()?->name ?? '' }}"
                                        maxlength="255"
                                        required
                                    >
                                </label>

                                <label>
                                    Username

                                    <input
                                        type="text"
                                        id="profileEditUsername"
                                        value="{{ auth()->user()?->username ?? '' }}"
                                        maxlength="50"
                                        required
                                    >
                                </label>

                                <label>
                                    Email

                                    <input
                                        type="email"
                                        id="profileEditEmail"
                                        value="{{ auth()->user()?->email ?? '' }}"
                                        placeholder="Masukkan email"
                                    >
                                </label>

                                <label>
                                    NISN

                                    <input
                                        type="text"
                                        value="{{ auth()->user()?->nisn ?? '' }}"
                                        readonly
                                    >
                                </label>

                                <label>
                                    Kelas

                                    <input
                                        type="text"
                                        id="profileEditClass"
                                        value="{{ auth()->user()?->kelas ?? '' }}"
                                        placeholder="Contoh: VII A"
                                    >
                                </label>

                                <label>
                                    Sekolah

                                    <input
                                        type="text"
                                        id="profileEditSchool"
                                        value="{{ auth()->user()?->sekolah ?? '' }}"
                                        placeholder="Nama sekolah"
                                    >
                                </label>

                            </div>

                            <div class="sd-profile-form-actions">

                                <button
                                    type="button"
                                    class="sd-profile-outline"
                                    id="profileCancelEdit"
                                >
                                    Kembali ke Profil
                                </button>

                                <button
                                    type="submit"
                                    class="sd-profile-primary"
                                >
                                    Simpan Perubahan
                                </button>

                            </div>

                            <p
                                id="profileEditMessage"
                                class="sd-profile-message"
                                role="status"
                            ></p>

                        </form>

                    </article>

                    <!-- =================================================
                         14.3 PENGATURAN AKUN
                    ================================================== -->
                    <article
                        class="sd-profile-card"
                        id="profileSettingsCard"
                    >

                        <div class="sd-profile-card-header">
                            <h2>Pengaturan Akun</h2>
                        </div>

                        <div class="sd-profile-password-summary">

                            <div>
                                <span>Kata Sandi</span>
                                <strong>••••••••••</strong>
                            </div>

                            <button
                                type="button"
                                class="sd-profile-outline"
                                id="profilePasswordButton"
                            >
                                Ubah Kata Sandi
                            </button>

                        </div>

                        <form
                            id="profilePasswordForm"
                            class="sd-profile-form"
                            hidden
                        >

                            <div class="sd-profile-form-grid">

                                <button
                                    type="button"
                                    class="sd-profile-outline sd-profile-back"
                                    id="profilePasswordBack"
                                >
                                    Kembali ke Profil
                                </button>

                                <label>
                                    Password Saat Ini

                                    <input
                                        type="password"
                                        id="profileCurrentPassword"
                                        autocomplete="current-password"
                                        placeholder="Password Saat Ini"
                                        required
                                    >
                                </label>

                                <label>
                                    Password Baru

                                    <input
                                        type="password"
                                        id="profileNewPassword"
                                        autocomplete="new-password"
                                        minlength="8"
                                        placeholder="Password Baru"
                                        required
                                    >
                                </label>

                                <label>
                                    Konfirmasi Password Baru

                                    <input
                                        type="password"
                                        id="profileConfirmPassword"
                                        autocomplete="new-password"
                                        minlength="8"
                                        placeholder="Konfirmasi Password Baru"
                                        required
                                    >
                                </label>

                            </div>

                            <div class="sd-profile-password-actions">

                                <button
                                    type="submit"
                                    class="sd-profile-primary"
                                >
                                    Simpan Password
                                </button>

                            </div>

                            <p
                                id="profilePasswordMessage"
                                class="sd-profile-message"
                                role="status"
                            ></p>

                        </form>

                    </article>

                    <!-- Keluar dari akun -->
                    @auth
                        <form
                            method="POST"
                            action="{{ route('logout') }}"
                            data-sd-logout-form
                        >
                            @csrf

                            <button
                                type="submit"
                                class="sd-profile-logout"
                            >
                                Keluar dari Akun
                            </button>
                        </form>
                    @endauth

                    <!-- Kembali Beranda -->
                    <button
                        type="button"
                        class="sd-profile-home"
                        data-page="0"
                    >
                        Kembali ke Beranda
                    </button>

                </div>

                <!-- Navigasi bawah -->
                <div class="sd-navigation">

                    <button
                        type="button"
                        class="sd-nav-btn"
                        data-page="6"
                    >
                        <i class="bi bi-chevron-left"></i>
                        Previous
                    </button>

                </div>

                <!-- =================================================
                     14.4 MODAL HAPUS FOTO PROFIL
                ================================================== -->
                <div
                    class="sd-profile-photo-modal"
                    id="profileDeletePhotoModal"
                    hidden
                >

                    <div class="sd-profile-photo-backdrop"></div>

                    <div
                        class="sd-profile-photo-dialog"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="profileDeletePhotoTitle"
                    >

                        <div class="sd-profile-photo-icon">
                            <i class="bi bi-trash3"></i>
                        </div>

                        <h2 id="profileDeletePhotoTitle">
                            Yakin Ingin Menghapus Foto?
                        </h2>

                        <p>
                            Foto profilmu akan dihapus
                            dan diganti dengan avatar inisial nama.
                        </p>

                        <p
                            id="profileDeletePhotoError"
                            class="sd-profile-message is-error"
                            role="alert"
                            hidden
                        ></p>

                        <div class="sd-profile-photo-actions">

                            <button
                                type="button"
                                id="profileDeletePhotoCancel"
                                class="sd-profile-photo-cancel"
                            >
                                Batal
                            </button>

                            <button
                                type="button"
                                id="profileDeletePhotoConfirm"
                                class="sd-profile-photo-danger"
                            >
                                Ya, Hapus
                            </button>

                        </div>

                    </div>

                </div>

            </section>

        </main>

    </div>

    {{-- =====================================================
         15. URL GAMBAR DARI LARAVEL
    ====================================================== --}}
    @php
        $siswaAssets = [
            'living' => asset('images/lesson-interaksi-makhluk-hidup.png'),
            'ecosystem' => asset('images/lesson-ekosistem-lahan-basah.png'),
            'conservation' => asset('images/lesson-pelestarian-lingkungan.png'),

            'evaluation' => asset('images/progress-assets/progress-3.png'),
            'discussion' => asset('images/saluang-materi-interaktif.png'),

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
        {!! json_encode(
            $siswaAssets,
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        ) !!}
    </script>

    {{-- =====================================================
         16. JAVASCRIPT DASHBOARD
    ====================================================== --}}
    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    ></script>

    <script
        src="{{ asset('js/siswa.js') }}?v=39"
        defer
    ></script>

    <script
        src="{{ asset('js/siswa-evaluasi.js') }}?v=50"
        defer
    ></script>

    <script
        src="{{ asset('js/siswa-forum.js') }}?v=3"
        defer
    ></script>

    <script
        src="{{ asset('js/siswa-profil.js') }}?v=2"
        defer
    ></script>

    <script
        src="{{ asset('js/siswa-logout.js') }}?v=1"
        defer
    ></script>

    {{-- =====================================================
         17. MODAL KONFIRMASI LOGOUT
    ====================================================== --}}
    <div
        id="sdLogoutModal"
        class="sd-logout-modal"
        hidden
    >

        <div class="sd-logout-backdrop"></div>

        <div
            class="sd-logout-dialog"
            role="dialog"
            aria-modal="true"
            aria-labelledby="sdLogoutTitle"
            aria-describedby="sdLogoutDescription"
            tabindex="-1"
        >

            <button
                type="button"
                class="sd-logout-close"
                id="sdLogoutClose"
                aria-label="Tutup"
            >
                <i class="bi bi-x-lg"></i>
            </button>

            <img
                class="sd-logout-mascot"
                src="{{ asset('images/bekantan-logout-ceria.png') }}"
                alt="Bekantan SALUANG tersenyum dan melambaikan tangan"
            >

            <h2 id="sdLogoutTitle">
                Yakin ingin keluar?
            </h2>

            <p id="sdLogoutDescription">
                Kamu akan keluar dari akun SALUANG.
                Pastikan semua aktivitas telah disimpan
                sebelum melanjutkan.
            </p>

            <div class="sd-logout-actions">

                <button
                    type="button"
                    class="sd-logout-cancel"
                    id="sdLogoutCancel"
                >
                    Batal
                </button>

                <button
                    type="button"
                    class="sd-logout-confirm"
                    id="sdLogoutConfirm"
                >
                    Ya, Keluar
                </button>

            </div>

        </div>

    </div>

</body>
</html>