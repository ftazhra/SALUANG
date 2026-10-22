<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Dashboard Guru — SALUANG</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">


    <link rel="stylesheet" href="{{ asset('css/guru.css') }}?v=6">

    <link rel="stylesheet" href="{{ asset('css/guru-materi.css') }}?v=9">
    <script src="{{ asset('js/guru-materi.js') }}?v=8" defer></script>

    <link rel="stylesheet" href="{{ asset('css/guru-kuis.css') }}?v=1">
    <script src="{{ asset('js/guru-kuis.js') }}?v=2" defer></script>

    <link rel="stylesheet" href="{{ asset('css/guru-evaluasi.css') }}?v=4">
    <script src="{{ asset('js/guru-evaluasi.js') }}?v=5" defer></script>

    <link rel="stylesheet" href="{{ asset('css/guru-forum.css') }}?v=1">
    <script src="{{ asset('js/guru-forum.js') }}?v=1" defer></script>

    <link rel="stylesheet" href="{{ asset('css/guru-nilai.css') }}?v=1">
    <script src="{{ asset('js/guru-nilai.js') }}?v=2" defer></script>

    <link rel="stylesheet" href="{{ asset('css/guru-orang-tua.css') }}?v=2">
    <script src="{{ asset('js/guru-orang-tua.js') }}?v=2" defer></script>

    <link rel="stylesheet" href="{{ asset('css/guru-profil.css') }}?v=1">
    <script src="{{ asset('js/guru-profil.js') }}?v=1" defer></script>


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
                <form action="{{ route('logout') }}" method="POST">
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
                    </button>

                    <div class="gd-dropdown-menu gd-notif-menu" id="guruNotifMenu" hidden>

                        <strong>Notifikasi</strong>
                        <p>Belum ada notifikasi baru.</p>
                    </div>

                </div>

                {{-- 03.2 DROPDOWN PROFIL --}}
                <div class="gd-dropdown">

                    <button type="button" class="gd-user gd-user-button" id="guruProfileButton"
                        aria-label="Menu profil" aria-expanded="false" aria-controls="guruProfileMenu">

                        <span class="gd-avatar">
                            {{ strtoupper(substr(auth()->user()?->name ?? 'G', 0, 1)) }}
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

                        <button type="button" class="gd-dropdown-item" data-guru-page="profil">

                            <i class="bi bi-gear"></i>
                            Pengaturan
                        </button>

                        <div class="gd-dropdown-divider"></div>

                        <form action="{{ route('logout') }}" method="POST">
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
                <div class="gd-stats">

                    <article class="gd-card gd-stat">
                        <i class="bi bi-book"></i>
                        <small>Total Materi</small>
                        <strong>1</strong>
                    </article>

                    <article class="gd-card gd-stat">
                        <i class="bi bi-clipboard-check"></i>
                        <small>Total Soal Kuis</small>
                        <strong>0</strong>
                    </article>

                    <article class="gd-card gd-stat">
                        <i class="bi bi-mortarboard"></i>
                        <small>Total Siswa Aktif</small>
                        <strong>—</strong>
                    </article>

                    <article class="gd-card gd-stat">
                        <i class="bi bi-check2-circle"></i>
                        <small>Total Evaluasi</small>
                        <strong>0</strong>
                    </article>

                </div>


                {{-- 05.3 MATERI PEMBELAJARAN --}}
                <article class="gd-card gd-material">

                    <h2>Materi Pembelajaran</h2>

                    <div class="gd-chapter">

                        <small>BAB 1</small>

                        <h3>
                            Karakteristik dan Klasifikasi Makhluk Hidup
                        </h3>

                        <div class="gd-topic">
                            1.1 Makhluk Hidup atau Benda Mati?
                        </div>

                        <div class="gd-topic">
                            1.2 Mengapa Makhluk Hidup Dikelompokkan?
                        </div>

                        <div class="gd-topic">
                            1.3 Makhluk Hidup Beraneka Ragam
                        </div>

                        <button type="button" class="gd-primary" data-guru-page="materi">

                            Kelola Materi
                        </button>

                    </div>

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

            {{-- HALAMAN HUBUNGI ORANG TUA GURU --}}
            @include('guru.partials.orang-tua')

            {{-- HALAMAN PROFIL GURU --}}
            @include('guru.partials.profil')

        </main>

    </div>


    {{-- =====================================================
         07. JAVASCRIPT DASHBOARD GURU
         ===================================================== --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {

            /* =============================================
               07.1 SIDEBAR LIPAT
               ============================================= */
            const sidebar = document.getElementById('guruSidebar');
            const main = document.getElementById('guruMain');
            const fold = document.getElementById('guruFold');

            if (sidebar && main && fold) {

                fold.addEventListener('click', () => {

                    const collapsed = sidebar.classList.toggle('collapsed');

                    main.classList.toggle('expanded', collapsed);

                    fold.setAttribute(
                        'aria-expanded',
                        String(!collapsed)
                    );

                    fold.setAttribute(
                        'aria-label',
                        collapsed ?
                        'Perbesar sidebar' :
                        'Perkecil sidebar'
                    );

                    fold.setAttribute(
                        'title',
                        collapsed ?
                        'Perbesar sidebar' :
                        'Perkecil sidebar'
                    );

                });
            }


            /* =============================================
               07.2 URUTAN HALAMAN
               ============================================= */
            const pageOrder = [
                'beranda',
                'materi',
                'kuis',
                'evaluasi',
                'forum',
                'nilai',
                'orang-tua',
                'profil'
            ];

            const pageButtons = document.querySelectorAll(
                '[data-guru-page]'
            );

            const sections = document.querySelectorAll(
                '[data-guru-section]'
            );

            let currentPage = 'beranda';


            /* =============================================
               07.3 NOTIFIKASI DAN DROPDOWN PROFIL
               ============================================= */
            const notifButton = document.getElementById(
                'guruNotifButton'
            );

            const notifMenu = document.getElementById(
                'guruNotifMenu'
            );

            const profileButton = document.getElementById(
                'guruProfileButton'
            );

            const profileMenu = document.getElementById(
                'guruProfileMenu'
            );


            function closeGuruDropdowns() {

                if (notifMenu) {
                    notifMenu.hidden = true;
                }

                if (profileMenu) {
                    profileMenu.hidden = true;
                }

                notifButton?.setAttribute(
                    'aria-expanded',
                    'false'
                );

                profileButton?.setAttribute(
                    'aria-expanded',
                    'false'
                );
            }


            function toggleGuruDropdown(button, menu) {

                if (!button || !menu) return;

                button.addEventListener('click', (event) => {

                    event.stopPropagation();

                    const shouldOpen = menu.hidden;

                    closeGuruDropdowns();

                    menu.hidden = !shouldOpen;

                    button.setAttribute(
                        'aria-expanded',
                        String(shouldOpen)
                    );
                });
            }


            toggleGuruDropdown(
                notifButton,
                notifMenu
            );

            toggleGuruDropdown(
                profileButton,
                profileMenu
            );


            /* =============================================
               07.4 TAMPILKAN HALAMAN
               ============================================= */
            function showGuruPage(page) {

                if (!pageOrder.includes(page)) {
                    return;
                }

                currentPage = page;

                // Sembunyikan halaman lain
                sections.forEach((section) => {

                    section.hidden =
                        section.dataset.guruSection !== page;

                });

                // Sesuaikan menu aktif di sidebar
                document.querySelectorAll(
                    '.gd-nav .gd-menu'
                ).forEach((menu) => {

                    const active =
                        menu.dataset.guruPage === page;

                    menu.classList.toggle(
                        'active',
                        active
                    );

                    if (active) {
                        menu.setAttribute(
                            'aria-current',
                            'page'
                        );
                    } else {
                        menu.removeAttribute(
                            'aria-current'
                        );
                    }

                });

                // Tutup dropdown setelah berpindah
                closeGuruDropdowns();

                // Kembali ke bagian atas
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            }


            /* =============================================
               07.5 KLIK MENU SIDEBAR / PROFIL
               ============================================= */
            pageButtons.forEach((button) => {

                button.addEventListener('click', () => {

                    showGuruPage(
                        button.dataset.guruPage
                    );

                });
            });


            /* =============================================
               07.6 PREVIOUS
               BERLAKU UNTUK ATAS DAN BAWAH
               ============================================= */
            document.querySelectorAll(
                '[data-guru-previous]'
            ).forEach((button) => {

                button.addEventListener('click', () => {

                    const index = pageOrder.indexOf(
                        currentPage
                    );

                    if (index > 0) {

                        showGuruPage(
                            pageOrder[index - 1]
                        );

                    }

                });

            });


            /* =============================================
               07.7 NEXT
               BERLAKU UNTUK ATAS DAN BAWAH
               ============================================= */
            document.querySelectorAll(
                '[data-guru-next]'
            ).forEach((button) => {

                button.addEventListener('click', () => {

                    const index = pageOrder.indexOf(
                        currentPage
                    );

                    if (index < pageOrder.length - 1) {

                        showGuruPage(
                            pageOrder[index + 1]
                        );

                    }

                });

            });


            /* =============================================
               07.8 TUTUP DROPDOWN KETIKA KLIK DI LUAR
               ============================================= */
            document.addEventListener('click', (event) => {

                if (!event.target.closest('.gd-dropdown')) {
                    closeGuruDropdowns();
                }

            });


            /* =============================================
               07.9 TUTUP DROPDOWN DENGAN ESCAPE
               ============================================= */
            document.addEventListener('keydown', (event) => {

                if (event.key === 'Escape') {
                    closeGuruDropdowns();
                }

            });


            /* =============================================
               07.10 HALAMAN PERTAMA
               ============================================= */
            showGuruPage('beranda');

        });
    </script>

</body>

</html>
