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
    <link rel="stylesheet" href="{{ asset('css/orang-tua-dashboard.css') }}?v=15">
    <link rel="stylesheet" href="{{ asset('css/orang-tua-aktivitas.css') }}?v=1">
    <link rel="stylesheet" href="{{ asset('css/orang-tua-hasil-belajar.css') }}?v=1">
    <link rel="stylesheet" href="{{ asset('css/orang-tua-progres.css') }}?v=1">
    <link rel="stylesheet" href="{{ asset('css/orang-tua-chat.css') }}?v=1">
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

                <div class="parent-user">

                    <div class="parent-avatar">
                        @if ($parent?->profile_photo_path)
                            <img src="{{ asset('storage/' . $parent->profile_photo_path) }}"
                                alt="Foto profil Orang Tua">
                        @else
                            {{ strtoupper(mb_substr($parentName, 0, 1)) }}
                        @endif
                    </div>

                    <div class="parent-user-info">
                        <strong>{{ $parentName }}</strong>
                        <small>Orang Tua/Wali</small>
                    </div>

                </div>

            </header>

            <main class="parent-content">

                {{-- BERANDA --}}
                <section class="parent-section" data-parent-section="beranda">

                    <div class="sd-hero parent-hero">
                        <div class="parent-hero-copy">
                            <h1>Halo, {{ $firstName }}! 👋</h1>
                            <p>Yuk, dampingi perjalanan belajar anak Anda dan pantau perkembangan belajarnya bersama
                                SALUANG!</p>
                        </div>
                        <div class="sd-quote">
                            “Dukungan orang tua membantu setiap langkah anak dalam belajar dan mengenal lingkungan lahan
                            basah.”
                        </div>
                    </div>

                    <article class="parent-card parent-child-card">

                        <div class="parent-card-heading">

                            <h2>Informasi Anak</h2>

                            <button type="button" class="parent-outline-button" data-parent-page="profil">
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
                            <div class="parent-empty">
                                Belum ada akun anak dengan hubungan yang
                                telah disetujui. Setelah hubungan akun
                                diverifikasi, informasi anak dan hasil
                                belajarnya akan ditampilkan di sini.
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

                                <button type="button" class="parent-outline-button" data-parent-page="aktivitas">
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

                                <button type="button" class="parent-outline-button" data-parent-page="progres">
                                    Lihat Progres
                                </button>

                            </div>

                            <p class="parent-progress-caption">
                                {{ $stats['materials'] ?? 0 }} materi selesai ·
                                {{ $stats['completion'] ?? 0 }}%
                            </p>

                            @include('orang-tua.partials.progress-cards')

                            <button type="button" class="parent-outline-button parent-bottom-button"
                                data-parent-page="hasil">
                                Lihat Hasil Belajar
                            </button>

                        </article>

                    </div>

                </section>

                {{-- AKTIVITAS ANAK — FILTER BAB, SUBBAB, DAN JENIS AKTIVITAS --}}
                <section class="parent-section" data-parent-section="aktivitas" hidden>
                    @include('orang-tua.partials.aktivitas-anak')
                </section>

                {{-- HASIL BELAJAR --}}
                <section class="parent-section" data-parent-section="hasil" hidden>
                    @include('orang-tua.partials.hasil-belajar')
                </section>

                {{-- PROGRES BELAJAR --}}
                <section class="parent-section" data-parent-section="progres" hidden>
                    @include('orang-tua.partials.progres-belajar')
                </section>

                {{-- CHAT GURU — MODUL PESAN TERHUBUNG KE ANAK --}}
                <section class="parent-section" data-parent-section="chat" hidden>
                    @include('orang-tua.partials.chat-guru')
                </section>

                {{-- PROFIL --}}
                <section class="parent-section" data-parent-section="profil" hidden>

                    <article class="parent-card parent-panel">

                        <h2>Profil Orang Tua</h2>

                        <div class="parent-profile-grid">

                            <div>
                                <small>Nama Lengkap</small>
                                <strong>{{ $parentName }}</strong>
                            </div>

                            <div>
                                <small>Email</small>
                                <strong>{{ $parent?->email ?? '-' }}</strong>
                            </div>

                            <div>
                                <small>Peran</small>
                                <strong>Orang Tua/Wali</strong>
                            </div>

                            <div>
                                <small>Nama Anak</small>
                                <strong>{{ $childName ?? 'Belum terhubung' }}</strong>
                            </div>

                            <div>
                                <small>Kelas Anak</small>
                                <strong>{{ $child ? $className : '-' }}</strong>
                            </div>

                            <div>
                                <small>Sekolah Anak</small>
                                <strong>{{ $child ? $schoolName : '-' }}</strong>
                            </div>

                        </div>

                    </article>

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

            const buttons = document.querySelectorAll(
                '[data-parent-page]'
            );

            const names = {
                beranda: 'Dashboard Orang Tua',
                aktivitas: 'Aktivitas Anak',
                hasil: 'Hasil Belajar',
                progres: 'Progres Belajar',
                chat: 'Chat Guru',
                profil: 'Profil Orang Tua'
            };

            function showPage(page) {
                if (!Object.prototype.hasOwnProperty.call(names, page)) {
                    return;
                }

                sections.forEach(function(section) {
                    section.hidden = section.dataset.parentSection !== page;
                });

                buttons.forEach(function(button) {
                    button.classList.toggle(
                        'active',
                        button.dataset.parentPage === page
                    );
                });

                if (title) {
                    title.textContent = names[page];
                }

                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            }

            buttons.forEach(function(button) {
                button.addEventListener('click', function() {
                    showPage(button.dataset.parentPage);
                });
            });

            if (toggle && layout) {
                toggle.addEventListener('click', function() {
                    const collapsed = layout.classList.toggle('sidebar-collapsed');

                    toggle.setAttribute(
                        'aria-expanded',
                        collapsed ? 'false' : 'true'
                    );

                    toggle.setAttribute(
                        'aria-label',
                        collapsed ? 'Perbesar sidebar' : 'Perkecil sidebar'
                    );
                });
            }
        });
    </script>

    <script src="{{ asset('js/orang-tua-aktivitas.js') }}?v=1" defer></script>
    <script src="{{ asset('js/orang-tua-hasil-belajar.js') }}?v=1" defer></script>
    <script src="{{ asset('js/orang-tua-progres.js') }}?v=1" defer></script>
    <script src="{{ asset('js/orang-tua-chat.js') }}?v=1" defer></script>
</body>

</html>
