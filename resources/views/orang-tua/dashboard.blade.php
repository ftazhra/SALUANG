<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Dashboard Orang Tua — SALUANG</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('css/guru.css') }}?v=10">
    <link rel="stylesheet" href="{{ asset('css/siswa-beranda.css') }}?v=4">
    <link rel="stylesheet" href="{{ asset('css/orang-tua-dashboard.css') }}?v=33">
    <link rel="stylesheet" href="{{ asset('css/guru-profil.css') }}?v=6">
    <link rel="stylesheet" href="{{ asset('css/orang-tua-aktivitas.css') }}?v=4">
    <link rel="stylesheet" href="{{ asset('css/orang-tua-hasil-belajar.css') }}?v=13">
    <link rel="stylesheet" href="{{ asset('css/orang-tua-progres.css') }}?v=6">
    <link rel="stylesheet" href="{{ asset('css/orang-tua-chat.css') }}?v=6">
    <link rel="stylesheet" href="{{ asset('css/website-notifications.css') }}?v=5">

</head>

<body class="parent-page">

    @php
        $parent = $parent ?? auth()->user();
        $child = $child ?? null;

        $parentName = $parent?->name ?? 'Orang Tua';
        $firstName = explode(' ', trim($parentName))[0] ?: 'Orang Tua';

        $childName = $child?->name ?? null;
        $className = $child?->kelas ?? ($child?->class ?? '-');
        $schoolName = $child?->sekolah ?? ($child?->school ?? 'SMP Negeri 21 Banjarmasin');

        $stats = $stats ?? [
            'materials' => 0,
            'quizzes' => 0,
            'average' => 0,
            'completion' => 0,
            'completed_activities' => 0,
            'total_activities' => 0,
        ];

        $chapters = $chapters ?? [];
        $activities = $activities ?? [];
        $notes = $notes ?? ($teacherNotes ?? []);

        $menu = [
            ['key' => 'beranda', 'icon' => 'bi-house-door', 'title' => 'Beranda'],
            ['key' => 'aktivitas', 'icon' => 'bi-calendar3', 'title' => 'Aktivitas Anak'],
            ['key' => 'hasil', 'icon' => 'bi-clipboard-check', 'title' => 'Hasil Belajar'],
            ['key' => 'progres', 'icon' => 'bi-bar-chart-line', 'title' => 'Progres Belajar'],
            ['key' => 'chat', 'icon' => 'bi-chat-dots', 'title' => 'Chat Guru'],
            ['key' => 'profil', 'icon' => 'bi-person', 'title' => 'Profil'],
        ];
    @endphp

    <div class="parent-layout" id="parentLayout">

        {{-- =====================================================
             SIDEBAR ORANG TUA
             ===================================================== --}}

        <aside class="parent-sidebar" id="parentSidebar">

            <div class="parent-brand">
                <img src="{{ asset('images/saluang-logo-transparent.png') }}" alt="Logo SALUANG">
            </div>

            <button type="button" class="parent-sidebar-toggle" id="parentSidebarToggle" aria-label="Perkecil sidebar"
                aria-expanded="true">
                <i class="bi bi-layout-sidebar-inset"></i>
            </button>

            <nav class="parent-navigation" aria-label="Navigasi Orang Tua">

                @foreach ($menu as $item)
                    <button type="button" class="parent-menu {{ $item['key'] === 'beranda' ? 'active' : '' }}"
                        data-parent-page="{{ $item['key'] }}" title="{{ $item['title'] }}">
                        <i class="bi {{ $item['icon'] }}"></i>
                        <span>{{ $item['title'] }}</span>
                    </button>
                @endforeach

            </nav>

            <div class="parent-sidebar-footer">
                <form action="{{ route('logout') }}" method="POST" data-gd-logout-form>
                    @csrf

                    <button type="submit" class="parent-exit" data-gd-logout-trigger>
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Keluar</span>
                    </button>
                </form>
            </div>

        </aside>

        {{-- =====================================================
             KONTEN UTAMA
             ===================================================== --}}

        <div class="parent-main" id="parentMain">

            <header class="parent-topbar">

                <div class="parent-topbar-title" id="parentPageTitle">
                    Dashboard Orang Tua
                </div>

                <div class="parent-topbar-tools">

                    {{-- NOTIFIKASI --}}
                    <div class="parent-dropdown">

                        <button type="button" class="parent-icon-button" id="parentNotifButton" aria-label="Notifikasi"
                            aria-expanded="false" aria-controls="parentNotifMenu">
                            <i class="bi bi-bell"></i>
                            <span class="website-notification-count" data-notification-count
                                @if (($unreadNotificationCount ?? 0) < 1) hidden @endif>{{ $unreadNotificationCount ?? 0 }}</span>
                        </button>

                        <div class="parent-dropdown-menu parent-notification-menu website-notification-menu"
                            id="parentNotifMenu" hidden>

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

                    {{-- DROPDOWN PROFIL --}}
                    <div class="parent-dropdown">

                        <button type="button" class="parent-user parent-user-button" id="parentProfileButton"
                            aria-label="Menu profil" aria-expanded="false" aria-controls="parentProfileMenu">

                            <span class="parent-avatar" id="parentTopbarAvatar"
                                data-initial="{{ strtoupper(mb_substr($parentName, 0, 1)) }}">

                                @if ($parent?->profile_photo_path)
                                    <img src="{{ asset('storage/' . $parent->profile_photo_path) }}"
                                        alt="Foto profil Orang Tua">
                                @else
                                    {{ strtoupper(mb_substr($parentName, 0, 1)) }}
                                @endif

                            </span>

                            <span class="parent-user-info">
                                <strong>{{ $parentName }}</strong>
                                <small>Orang Tua/Wali</small>
                            </span>

                            <i class="bi bi-chevron-down parent-user-chevron"></i>

                        </button>

                        <div class="parent-dropdown-menu parent-profile-menu" id="parentProfileMenu" hidden>

                            <button type="button" class="parent-dropdown-item" data-parent-page="profil">
                                <i class="bi bi-person"></i>
                                Profil Saya
                            </button>

                            <button type="button" class="parent-dropdown-item" data-parent-page="profil"
                                data-parent-settings>
                                <i class="bi bi-gear"></i>
                                Pengaturan
                            </button>

                            <hr class="parent-dropdown-divider">

                            <form action="{{ route('logout') }}" method="POST" data-gd-logout-form>
                                @csrf

                                <button type="submit" class="parent-dropdown-item parent-dropdown-item-danger"
                                    data-gd-logout-trigger>
                                    <i class="bi bi-box-arrow-right"></i>
                                    Keluar
                                </button>
                            </form>

                        </div>

                    </div>

                </div>

            </header>

            <main class="parent-content">

                {{-- =====================================================
                     HALAMAN 1: BERANDA
                     ===================================================== --}}

                {{-- Halaman Beranda Orang Tua --}}
                @include('orang-tua.partials.beranda')

                {{-- =====================================================
                     HALAMAN 2: AKTIVITAS ANAK
                     ===================================================== --}}

                <section class="parent-section" data-parent-section="aktivitas" hidden>

                    @include('orang-tua.partials.aktivitas-anak')

                </section>

                {{-- =====================================================
                     HALAMAN 3: HASIL BELAJAR
                     ===================================================== --}}

                <section class="parent-section" data-parent-section="hasil" hidden>

                    @include('orang-tua.partials.hasil-belajar')

                </section>

                {{-- =====================================================
                     HALAMAN 4: PROGRES BELAJAR
                     ===================================================== --}}

                <section class="parent-section" data-parent-section="progres" hidden>

                    @include('orang-tua.partials.progres-belajar')

                </section>

                {{-- =====================================================
                     HALAMAN 5: CHAT GURU
                     ===================================================== --}}

                <section class="parent-section" data-parent-section="chat" hidden>

                    @include('orang-tua.partials.chat-guru')

                </section>

                {{-- =====================================================
                     HALAMAN 6: PROFIL
                     ===================================================== --}}

                {{-- Halaman Profil Orang Tua --}}
                @include('orang-tua.partials.profil')

            </main>

        </div>

    </div>

    {{-- =====================================================
         MODAL LOGOUT
         ===================================================== --}}

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

            <p id="gdLogoutDescription">
                Kamu akan keluar dari akun SALUANG.
                Pastikan semua aktivitas telah disimpan
                sebelum melanjutkan.
            </p>

            <div class="gd-logout-actions">

                <button type="button" class="gd-logout-cancel" data-gd-logout-cancel>
                    Batal
                </button>

                <button type="button" class="gd-logout-confirm" id="gdLogoutConfirm">
                    Ya, Keluar
                </button>

            </div>

        </div>

    </div>

    {{-- =====================================================
         JAVASCRIPT DASHBOARD ORANG TUA
         ===================================================== --}}

    <script src="{{ asset('js/orang-tua-dashboard.js') }}?v=1"></script>
    <script src="{{ asset('js/website-notifications.js') }}?v=3" defer></script>

    {{-- =====================================================
         SCRIPT FITUR ORANG TUA
         ===================================================== --}}

    <script src="{{ asset('js/orang-tua-aktivitas.js') }}?v=2" defer></script>
    <script src="{{ asset('js/orang-tua-hasil-belajar.js') }}?v=7" defer></script>
    <script src="{{ asset('js/orang-tua-progres.js') }}?v=1" defer></script>
    <script src="{{ asset('js/orang-tua-chat.js') }}?v=3" defer></script>
    <script src="{{ asset('js/orang-tua-profile.js') }}?v=3" defer></script>
    <script src="{{ asset('js/guru-logout.js') }}?v=1" defer></script>

</body>

</html>
