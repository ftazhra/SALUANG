
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

    <link rel="stylesheet" href="{{ asset('css/siswa-beranda.css') }}?v=1">
    <link rel="stylesheet" href="{{ asset('css/orang-tua-dashboard.css') }}?v=17">
    <link rel="stylesheet" href="{{ asset('css/guru-profil.css') }}?v=3">
    <link rel="stylesheet" href="{{ asset('css/orang-tua-aktivitas.css') }}?v=2">
    <link rel="stylesheet" href="{{ asset('css/orang-tua-hasil-belajar.css') }}?v=3">
    <link rel="stylesheet" href="{{ asset('css/orang-tua-progres.css') }}?v=2">
    <link rel="stylesheet" href="{{ asset('css/orang-tua-chat.css') }}?v=2">

    <style>
        /* CATATAN GURU — DESAIN BARU */
        .parent-teacher-notes {
            margin-top: 22px;
            padding: 26px;
        }

        .parent-teacher-notes-header {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 22px;
        }

        .parent-teacher-notes-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            flex: 0 0 44px;
            background: #e8f5ed;
            color: #27865a;
            border-radius: 12px;
            font-size: 20px;
        }

        .parent-teacher-notes-header h2 {
            margin: 0 0 5px;
            color: #075477;
            font-size: 19px;
            font-weight: 700;
            line-height: 1.4;
        }

        .parent-teacher-notes-header p {
            margin: 0;
            color: #718d96;
            font-size: 12px;
            line-height: 1.6;
        }

        .parent-teacher-notes-list {
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: 12px;
        }

        .parent-teacher-note-item {
            display: block;
            width: 100%;
            min-height: 0;
            box-sizing: border-box;
            padding: 17px 20px;
            margin: 0;
            border: 1px solid #dce9e5;
            border-radius: 12px;
            background: #f8fbfa;
            text-align: left;
        }

        .parent-teacher-note-body {
            display: block;
            margin: 0;
            padding: 0;
            color: #244b59;
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            font-weight: 400;
            line-height: 1.75;
            text-align: left;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .parent-teacher-note-date {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 7px;
            margin-top: 10px;
            padding: 0;
            color: #81958d;
            font-size: 11px;
            line-height: 1.5;
            text-align: left;
        }

        .parent-teacher-note-date i {
            color: #6f9d83;
            font-size: 12px;
        }

        .parent-teacher-notes-empty {
            padding: 28px 16px;
            text-align: center;
            color: #78919a;
            font-size: 12px;
        }

        .parent-teacher-notes-empty i {
            display: block;
            margin-bottom: 10px;
            color: #9cbbb0;
            font-size: 26px;
        }

        .parent-teacher-notes-empty p {
            margin: 0;
        }

        @media (max-width: 600px) {
            .parent-teacher-notes {
                padding: 18px;
            }

            .parent-teacher-notes-header {
                gap: 11px;
            }

            .parent-teacher-notes-header h2 {
                font-size: 17px;
            }

            .parent-teacher-note-item {
                padding: 15px;
            }
        }
    </style>
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

        <aside class="parent-sidebar" id="parentSidebar">

            <div class="parent-brand">
                <img src="{{ asset('images/saluang-logo-transparent.png') }}"
                     alt="Logo SALUANG">
            </div>

            <button type="button"
                    class="parent-sidebar-toggle"
                    id="parentSidebarToggle"
                    aria-label="Perkecil sidebar"
                    aria-expanded="true">
                <i class="bi bi-layout-sidebar-inset"></i>
            </button>

            <nav class="parent-navigation" aria-label="Navigasi Orang Tua">

                @foreach ($menu as $item)
                    <button type="button"
                            class="parent-menu {{ $item['key'] === 'beranda' ? 'active' : '' }}"
                            data-parent-page="{{ $item['key'] }}"
                            title="{{ $item['title'] }}">
                        <i class="bi {{ $item['icon'] }}"></i>
                        <span>{{ $item['title'] }}</span>
                    </button>
                @endforeach

            </nav>

            <div class="parent-sidebar-footer">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf

                    <button type="submit" class="parent-exit">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Keluar</span>
                    </button>
                </form>
            </div>

        </aside>

        <div class="parent-main" id="parentMain">

            <header class="parent-topbar">

                <div class="parent-topbar-title" id="parentPageTitle">
                    Dashboard Orang Tua
                </div>

                <div class="parent-topbar-tools">

                    <div class="parent-dropdown">
                        <button type="button"
                                class="parent-icon-button"
                                id="parentNotifButton"
                                aria-label="Notifikasi"
                                aria-expanded="false"
                                aria-controls="parentNotifMenu">
                            <i class="bi bi-bell"></i>
                        </button>
                        <div class="parent-dropdown-menu parent-notification-menu"
                             id="parentNotifMenu"
                             hidden>
                            <strong>Notifikasi</strong>
                            <p>Belum ada notifikasi baru.</p>
                        </div>
                    </div>

                    <div class="parent-dropdown">
                        <button type="button"
                                class="parent-user parent-user-button"
                                id="parentProfileButton"
                                aria-label="Menu profil"
                                aria-expanded="false"
                                aria-controls="parentProfileMenu">
                            <span class="parent-avatar">
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
                        <div class="parent-dropdown-menu parent-profile-menu"
                             id="parentProfileMenu"
                             hidden>
                            <button type="button"
                                    class="parent-dropdown-item"
                                    data-parent-page="profil">
                                <i class="bi bi-person"></i>
                                Profil
                            </button>
                            <hr class="parent-dropdown-divider">
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit"
                                        class="parent-dropdown-item parent-dropdown-item-danger">
                                    <i class="bi bi-box-arrow-right"></i>
                                    Keluar
                                </button>
                            </form>
                        </div>
                    </div>

                </div>

            </header>

            <main class="parent-content">

                {{-- BERANDA --}}
                <section class="parent-section" data-parent-section="beranda">

                    <div class="sd-hero parent-hero">

                        <div class="parent-hero-copy">
                            <h1>Halo, {{ $firstName }}! 👋</h1>
                            <p>
                                Yuk, dampingi perjalanan belajar anak Anda dan
                                pantau perkembangan belajarnya bersama SALUANG!
                            </p>
                        </div>

                        <div class="sd-quote">
                            “Dukungan orang tua membantu setiap langkah anak
                            dalam belajar dan mengenal lingkungan lahan basah.”
                        </div>

                    </div>

                    <article class="parent-card parent-child-card">

                        <div class="parent-card-heading">
                            <h2>Informasi Anak</h2>

                            <button type="button"
                                    class="parent-outline-button"
                                    data-parent-page="profil">
                                Lihat Detail
                            </button>
                        </div>

                        @if ($child)

                            <div class="parent-child-information">

                                <div>
                                    <small>Nama</small>
                                    <strong>{{ $childName }}</strong>
                                </div>

                                <div>
                                    <small>Kelas</small>
                                    <strong>{{ $className }}</strong>
                                </div>

                                <div>
                                    <small>Sekolah</small>
                                    <strong>{{ $schoolName }}</strong>
                                </div>

                                <div>
                                    <small>Status</small>
                                    <strong class="parent-green">
                                        Aktif belajar
                                    </strong>
                                </div>

                            </div>

                        @else

                            <div class="parent-empty" id="parentLinkSection">

                                <div style="margin-bottom: 18px;">
                                    <i class="bi bi-link-45deg"
                                       style="font-size: 28px; color: #21865b;"></i>

                                    <h3 style="margin: 8px 0; font-size: 17px;">
                                        Hubungkan Akun Anak
                                    </h3>

                                    <p style="margin: 0; line-height: 1.7;">
                                        Minta anak membuat kode penghubung
                                        melalui menu Profil pada akun Siswa SALUANG.
                                        Masukkan kode enam digit tersebut di bawah ini.
                                    </p>
                                </div>

                                @if (session('status'))
                                    <p role="status" style="color: #21865b;">
                                        {{ session('status') }}
                                    </p>
                                @endif

                                @if ($errors->has('code'))
                                    <p role="alert" style="color: #c0392b;">
                                        {{ $errors->first('code') }}
                                    </p>
                                @endif

                                <form method="POST"
                                      action="{{ route('orang-tua.parent-link.verify') }}"
                                      style="display: flex; flex-wrap: wrap; gap: 12px; align-items: end;">
                                    @csrf

                                    <div style="flex: 1; min-width: 190px;">

                                        <label for="parentLinkCode"
                                               style="display: block; margin-bottom: 8px; font-weight: 600;">
                                            Kode Penghubung
                                        </label>

                                        <input type="text"
                                               id="parentLinkCode"
                                               name="code"
                                               inputmode="numeric"
                                               pattern="[0-9]{6}"
                                               maxlength="6"
                                               minlength="6"
                                               placeholder="Masukkan 6 digit kode"
                                               autocomplete="off"
                                               value="{{ old('code') }}"
                                               required
                                               style="
                                                   width: 100%;
                                                   padding: 12px 14px;
                                                   border: 1px solid #d7e2dc;
                                                   border-radius: 10px;
                                                   font-family: inherit;
                                                   font-size: 15px;
                                                   box-sizing: border-box;
                                               ">
                                    </div>

                                    <button type="submit"
                                            class="parent-outline-button"
                                            style="min-height: 46px;">
                                        <i class="bi bi-check-circle"></i>
                                        Hubungkan Akun
                                    </button>

                                </form>

                                <p style="margin-top: 14px; font-size: 12px;">
                                    Kode hanya berlaku 10 menit dan dapat digunakan
                                    satu kali. Jangan bagikan kode kepada orang lain.
                                </p>

                            </div>

                        @endif

                    </article>

                    <div class="parent-statistics">

                        <article class="parent-card parent-stat-card">
                            <small>Materi Dipelajari</small>
                            <strong>{{ $stats['materials'] ?? 0 }}</strong>
                        </article>

                        <article class="parent-card parent-stat-card">
                            <small>Kuis Dikerjakan</small>
                            <strong>{{ $stats['quizzes'] ?? 0 }}</strong>
                        </article>

                        <article class="parent-card parent-stat-card">
                            <small>Rata-rata Nilai</small>
                            <strong>
                                {{ number_format((float) ($stats['average'] ?? 0), 1, ',', '.') }}
                            </strong>
                        </article>

                        <article class="parent-card parent-stat-card">
                            <small>Ketuntasan Materi</small>
                            <strong>{{ $stats['completion'] ?? 0 }}%</strong>
                        </article>

                    </div>

                    <div class="parent-bottom-grid">

                        <article class="parent-card parent-panel">

                            <div class="parent-card-heading">
                                <h2>Aktivitas Terbaru</h2>

                                <button type="button"
                                        class="parent-outline-button"
                                        data-parent-page="aktivitas">
                                    Lihat Semua Aktivitas
                                </button>
                            </div>

                            @forelse ($activities as $activity)

                                <div class="parent-activity-row">

                                    <span class="parent-activity-dot"></span>

                                    <span>
                                        {{ $activity['title'] ?? 'Aktivitas belajar' }}
                                    </span>

                                    <small>
                                        @if (!empty($activity['date']))
                                            {{ \Illuminate\Support\Carbon::parse($activity['date'])->format('d M Y') }}
                                        @endif
                                    </small>

                                </div>

                            @empty

                                <div class="parent-empty">
                                    Belum ada aktivitas belajar yang tercatat.
                                </div>

                            @endforelse

                        </article>

                        <article class="parent-card parent-panel">

                            <div class="parent-card-heading">

                                <h2>Progres Belajar</h2>

                                <button type="button"
                                        class="parent-outline-button"
                                        data-parent-page="progres">
                                    Lihat Progres
                                </button>

                            </div>

                            <p class="parent-progress-caption">
                                {{ $stats['materials'] ?? 0 }} materi selesai ·
                                {{ $stats['completion'] ?? 0 }}%
                            </p>

                            @include('orang-tua.partials.progress-cards')

                            <button type="button"
                                    class="parent-outline-button parent-bottom-button"
                                    data-parent-page="hasil">
                                Lihat Hasil Belajar
                            </button>

                        </article>

                    </div>

                </section>

                {{-- AKTIVITAS ANAK --}}
                <section class="parent-section"
                         data-parent-section="aktivitas"
                         hidden>
                    @include('orang-tua.partials.aktivitas-anak')
                </section>

                {{-- HASIL BELAJAR --}}
                <section class="parent-section"
                         data-parent-section="hasil"
                         hidden>

                    @include('orang-tua.partials.hasil-belajar')

                    {{-- CATATAN GURU --}}
                    <article class="parent-card parent-panel parent-teacher-notes">

                        <div class="parent-teacher-notes-header">

                            <div class="parent-teacher-notes-icon">
                                <i class="bi bi-journal-text"></i>
                            </div>

                            <div>
                                <h2>Catatan Guru</h2>

                                <p>
                                    Informasi perkembangan belajar anak dari Guru.
                                </p>
                            </div>

                        </div>

                        <div class="parent-teacher-notes-list">

                            @forelse (($teacherNotes ?? []) as $note)

                                <div class="parent-teacher-note-item">

                                    <div class="parent-teacher-note-body">{{ $note['body'] }}</div>

                                    <div class="parent-teacher-note-date">
                                        <i class="bi bi-calendar3"></i>

                                        <span>
                                            Dikirim pada
                                            {{ \Illuminate\Support\Carbon::parse($note['date'])->format('d M Y, H:i') }}
                                        </span>
                                    </div>

                                </div>

                            @empty

                                <div class="parent-teacher-notes-empty">
                                    <i class="bi bi-journal-text"></i>

                                    <p>
                                        Belum ada catatan yang dikirim oleh Guru.
                                    </p>
                                </div>

                            @endforelse

                        </div>

                    </article>

                </section>

                {{-- PROGRES BELAJAR --}}
                <section class="parent-section"
                         data-parent-section="progres"
                         hidden>
                    @include('orang-tua.partials.progres-belajar')
                </section>

                {{-- CHAT GURU --}}
                <section class="parent-section"
                         data-parent-section="chat"
                         hidden>
                    @include('orang-tua.partials.chat-guru')
                </section>

                {{-- PROFIL --}}
                <section class="parent-section gp-profile-page"
                         data-parent-section="profil"
                         hidden>

                    <div class="gp-profile-container">
                        <div class="gp-profile-heading">
                            <h1>Profil Orang Tua</h1>
                            <p>Informasi akun Orang Tua/Wali dan anak yang terhubung.</p>
                        </div>

                        <article class="gp-profile-card">
                            <div class="gp-profile-card-header">
                                <h2>Informasi Pribadi</h2>
                            </div>

                            <div class="gp-profile-info-grid">
                                <div class="gp-profile-info-item">
                                    <span>Nama Lengkap</span>
                                    <strong>{{ $parentName }}</strong>
                                </div>
                                <div class="gp-profile-info-item">
                                    <span>Email</span>
                                    <strong>{{ $parent?->email ?? '-' }}</strong>
                                </div>
                                <div class="gp-profile-info-item">
                                    <span>Peran</span>
                                    <strong>Orang Tua/Wali</strong>
                                </div>
                                <div class="gp-profile-info-item">
                                    <span>Nama Anak</span>
                                    <strong>{{ $childName ?? 'Belum terhubung' }}</strong>
                                </div>
                                <div class="gp-profile-info-item">
                                    <span>Kelas Anak</span>
                                    <strong>{{ $child ? $className : '-' }}</strong>
                                </div>
                                <div class="gp-profile-info-item">
                                    <span>Sekolah Anak</span>
                                    <strong>{{ $child ? $schoolName : '-' }}</strong>
                                </div>
                            </div>
                        </article>
                    </div>

                </section>

            </main>

        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const layout = document.getElementById('parentLayout');
            const toggle = document.getElementById('parentSidebarToggle');
            const title = document.getElementById('parentPageTitle');

            const sections = document.querySelectorAll(
                '[data-parent-section]'
            );

            const pageTriggers = document.querySelectorAll(
                '[data-parent-page]'
            );
            const menuButtons = document.querySelectorAll(
                '.parent-navigation .parent-menu'
            );
            const notificationButton = document.getElementById('parentNotifButton');
            const notificationMenu = document.getElementById('parentNotifMenu');
            const profileButton = document.getElementById('parentProfileButton');
            const profileMenu = document.getElementById('parentProfileMenu');

            const names = {
                beranda: 'Dashboard Orang Tua',
                aktivitas: 'Aktivitas Anak',
                hasil: 'Hasil Belajar',
                progres: 'Progres Belajar',
                chat: 'Chat Guru',
                profil: 'Profil Orang Tua'
            };

            function closeParentDropdowns() {
                if (notificationMenu) notificationMenu.hidden = true;
                if (profileMenu) profileMenu.hidden = true;
                notificationButton?.setAttribute('aria-expanded', 'false');
                profileButton?.setAttribute('aria-expanded', 'false');
            }

            function toggleParentDropdown(button, menu) {
                if (!button || !menu) return;
                button.addEventListener('click', event => {
                    event.stopPropagation();
                    const shouldOpen = menu.hidden;
                    closeParentDropdowns();
                    menu.hidden = !shouldOpen;
                    button.setAttribute('aria-expanded', String(shouldOpen));
                });
            }

            toggleParentDropdown(notificationButton, notificationMenu);
            toggleParentDropdown(profileButton, profileMenu);

            function showPage(page) {
                if (!Object.prototype.hasOwnProperty.call(names, page)) {
                    return;
                }

                sections.forEach(function(section) {
                    section.hidden =
                        section.dataset.parentSection !== page;
                });

                menuButtons.forEach(function(button) {
                    button.classList.toggle(
                        'active',
                        button.dataset.parentPage === page
                    );
                    if (button.dataset.parentPage === page) {
                        button.setAttribute('aria-current', 'page');
                    } else {
                        button.removeAttribute('aria-current');
                    }
                });

                closeParentDropdowns();

                if (title) {
                    title.textContent = names[page];
                }

                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            }

            pageTriggers.forEach(function(button) {
                button.addEventListener('click', function() {
                    showPage(button.dataset.parentPage);
                });
            });

            document.addEventListener('click', event => {
                if (!event.target.closest('.parent-dropdown')) {
                    closeParentDropdowns();
                }
            });

            document.addEventListener('keydown', event => {
                if (event.key === 'Escape') closeParentDropdowns();
            });

            if (toggle && layout) {
                toggle.addEventListener('click', function() {
                    const collapsed =
                        layout.classList.toggle('sidebar-collapsed');

                    toggle.setAttribute(
                        'aria-expanded',
                        collapsed ? 'false' : 'true'
                    );

                    toggle.setAttribute(
                        'aria-label',
                        collapsed
                            ? 'Perbesar sidebar'
                            : 'Perkecil sidebar'
                    );
                });
            }
        });
    </script>

    <script src="{{ asset('js/orang-tua-aktivitas.js') }}?v=1" defer></script>
    <script src="{{ asset('js/orang-tua-hasil-belajar.js') }}?v=2" defer></script>
    <script src="{{ asset('js/orang-tua-progres.js') }}?v=1" defer></script>
    <script src="{{ asset('js/orang-tua-chat.js') }}?v=2" defer></script>

</body>
</html>