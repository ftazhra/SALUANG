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

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- =====================================================
         03. CSS DASHBOARD
    ====================================================== -->
    <link rel="stylesheet" href="{{ asset('css/siswa.css') }}?v=40">
    <link rel="stylesheet" href="{{ asset('css/siswa-beranda.css') }}?v=7">
    <link rel="stylesheet" href="{{ asset('css/siswa-materi.css') }}?v=20261111a">
    <link rel="stylesheet" href="{{ asset('css/siswa-evaluasi.css') }}?v=61">
    <link rel="stylesheet" href="{{ asset('css/siswa-forum.css') }}?v=7">
    <link rel="stylesheet" href="{{ asset('css/siswa-profil.css') }}?v=5">
    <link rel="stylesheet" href="{{ asset('css/saluang-selects.css') }}?v=3">
    <link rel="stylesheet" href="{{ asset('css/website-notifications.css') }}?v=5">
</head>

<body class="sd-page">

    <!-- =====================================================
         05. SIDEBAR DESKTOP
    ====================================================== -->
    <aside class="sd-sidebar" id="sidebar">

        <div class="sd-brand">
            <img src="{{ asset('images/saluang-logo-transparent.png') }}" alt="Logo SALUANG">
        </div>

        <nav class="sd-nav" aria-label="Navigasi Siswa">

            <button type="button" class="sd-fold" id="foldSidebar" title="Perkecil sidebar"
                aria-label="Perkecil sidebar" aria-expanded="true">
                <i class="bi bi-layout-sidebar-inset"></i>
            </button>

            <button type="button" class="sd-menu active" data-page="0">
                <i class="bi bi-house-door"></i>
                <span>Beranda</span>
            </button>
            <button type="button" class="sd-menu sd-material-menu" data-page="7" aria-expanded="false"
                aria-controls="desktopMateri">
                <i class="bi bi-book" aria-hidden="true"></i>
                <span>Materi</span>
                <i class="bi bi-chevron-down sd-material-chevron" aria-hidden="true"></i>
            </button>

            <!--
                Bab dan Subbab dibuat oleh siswa.js.
                Sumber: database Guru.
            -->
            <div id="desktopMateri" hidden>
                <div class="sd-submenu"></div>
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

    </aside>

    <!-- =====================================================
         06. SIDEBAR MOBILE
    ====================================================== -->
    <div class="offcanvas offcanvas-start sd-offcanvas" id="mobileSidebar" tabindex="-1"
        aria-labelledby="mobileSidebarTitle">
        <div class="offcanvas-header">

            <img src="{{ asset('images/saluang-logo-transparent.png') }}" alt="SALUANG" id="mobileSidebarTitle"
                width="145">

            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup menu"></button>
        </div>

        <div class="offcanvas-body d-flex flex-column">

            <nav class="sd-nav" aria-label="Navigasi Mobile">

                <button type="button" class="sd-menu" data-page="0">
                    <i class="bi bi-house-door"></i>
                    <span>Beranda</span>
                </button>
                <button type="button" class="sd-menu sd-material-menu" data-page="7" aria-expanded="false"
                    aria-controls="mobileMateri">
                    <i class="bi bi-book" aria-hidden="true"></i>
                    <span>Materi</span>
                    <i class="bi bi-chevron-down sd-material-chevron" aria-hidden="true"></i>
                </button>

                <!--
                    Sidebar mobile juga berasal dari database Guru.
                -->
                <div id="mobileMateri" hidden>
                    <div class="sd-submenu"></div>
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

                <button type="button" class="sd-icon d-lg-none" data-bs-toggle="offcanvas"
                    data-bs-target="#mobileSidebar" aria-label="Buka menu">
                    <i class="bi bi-list"></i>
                </button>

                <label class="sd-search">
                    <i class="bi bi-search"></i>
                    <input type="search" id="searchTopic" placeholder="Cari materi, topik, atau kata kunci..."
                        aria-label="Cari materi">
                </label>

            </div>

            <div class="sd-tools">

                <!-- Notifikasi -->
                <div class="dropdown">

                    <button type="button" class="sd-icon" data-bs-toggle="dropdown" aria-expanded="false"
                        aria-label="Notifikasi">
                        <i class="bi bi-bell"></i>
                        <span class="website-notification-count" data-notification-count
                            @if (($unreadNotificationCount ?? 0) < 1) hidden @endif>{{ $unreadNotificationCount ?? 0 }}</span>
                    </button>

                    <div class="dropdown-menu dropdown-menu-end p-3 sd-dropdown website-notification-menu">
                        <strong>Notifikasi</strong>
                        <div class="website-notification-list">
                        @forelse ($notifications ?? [] as $notification)
                            <article class="website-notification-item" data-notification-item
                                data-notification-unread="{{ $notification['is_read'] ? 'false' : 'true' }}">
                                <a class="website-notification-link{{ $notification['is_read'] ? '' : ' is-unread' }}"
                                    href="{{ $notification['url'] }}">
                                <strong>{{ $notification['title'] }}</strong>
                                <span>{{ $notification['message'] }}</span>
                                <small>{{ $notification['is_read'] ? 'Dibaca' : 'Belum dibaca' }} · {{ $notification['date']?->format('d M Y, H:i') ?? '-' }}</small>
                                </a>
                                <button type="button" class="website-notification-delete"
                                    data-notification-delete="{{ route('notifications.destroy', $notification['id']) }}"
                                    aria-label="Hapus notifikasi: {{ $notification['title'] }}"
                                    title="Hapus notifikasi">×</button>
                            </article>
                        @empty
                            <p class="website-notification-empty" data-notification-empty>Belum ada notifikasi</p>
                        @endforelse
                        </div>
                        <p class="website-notification-error" data-notification-error role="status" hidden></p>
                    </div>

                </div>

                <!-- Menu akun -->
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
            {{-- Halaman Beranda Siswa --}}
            @include('siswa.partials.beranda')

            <!-- =================================================
                 09. HALAMAN DAFTAR MATERI
            ================================================== -->
            {{-- Halaman Ringkasan Materi Siswa --}}
            @include('siswa.partials.ringkasan-materi')

            <!-- =================================================
                 10. HALAMAN PENGENALAN BAB
            ================================================== -->
            {{-- Halaman Bab Siswa --}}
            @include('siswa.partials.bab')

            <!-- =================================================
                 11. HALAMAN SUBBAB / BUKU DIGITAL
            ================================================== -->
            {{-- Halaman Subbab dan Materi Siswa --}}
            @include('siswa.partials.subbab-materi')

            {{-- =================================================
                 12. HALAMAN EVALUASI
            ================================================== --}}
            {{-- Halaman Evaluasi Siswa --}}
            @include('siswa.partials.evaluasi')

            {{-- =================================================
                 13. HALAMAN FORUM DISKUSI
            ================================================== --}}
            {{-- Halaman Forum Diskusi Siswa --}}
            @include('siswa.partials.forum')

            {{-- =================================================
                 14. HALAMAN PROFIL SISWA
            ================================================== --}}
            {{-- Halaman Profil Siswa --}}
            @include('siswa.partials.profil')

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
            'parentLinkGenerate' => route('siswa.parent-link.generate'),
        ];
    @endphp

    <script id="sd-assets" type="application/json">
        {!! json_encode(
            $siswaAssets,
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        ) !!}
    </script>

    {{-- =====================================================
         16. MODUL JAVASCRIPT DASHBOARD
    ====================================================== --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script src="{{ asset('js/siswa-beranda.js') }}?v=3" defer></script>

    <script src="{{ asset('js/siswa-materi.js') }}?v=2" defer></script>

    <script src="{{ asset('js/siswa.js') }}?v=20261111a" defer></script>

    <script src="{{ asset('js/siswa-evaluasi.js') }}?v=61" defer></script>

    <script src="{{ asset('js/siswa-forum.js') }}?v=4" defer></script>

    <script src="{{ asset('js/siswa-profil.js') }}?v=2" defer></script>

    <script src="{{ asset('js/siswa-logout.js') }}?v=1" defer></script>
    <script src="{{ asset('js/website-notifications.js') }}?v=3" defer></script>

    {{-- =====================================================
         17. MODAL KONFIRMASI LOGOUT
    ====================================================== --}}
    <div id="sdLogoutModal" class="sd-logout-modal" hidden>

        <div class="sd-logout-backdrop"></div>

        <div class="sd-logout-dialog" role="dialog" aria-modal="true" aria-labelledby="sdLogoutTitle"
            aria-describedby="sdLogoutDescription" tabindex="-1">

            <button type="button" class="sd-logout-close" id="sdLogoutClose" aria-label="Tutup">
                <i class="bi bi-x-lg"></i>
            </button>

            <img class="sd-logout-mascot" src="{{ asset('images/bekantan-logout-ceria.png') }}"
                alt="Bekantan SALUANG tersenyum dan melambaikan tangan">

            <h2 id="sdLogoutTitle">
                Yakin ingin keluar?
            </h2>

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

    <script src="{{ asset('js/siswa-parent-link.js') }}?v=1"></script>

</body>

</html>