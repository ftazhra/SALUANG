@extends('layouts.app')

@section('title', 'SALUANG — Belajar IPA, Jaga Lahan Basah')

@section('content')

    {{-- NAVBAR --}}
    <header class="site-header">
        <div class="nav-container">
            <a href="{{ route('beranda') }}" class="site-logo">
                <img src="{{ asset('images/saluang-logo-transparent.png') }}" alt="SALUANG">
            </a>

            <nav class="main-nav" aria-label="Navigasi utama">
                <a href="#beranda" class="active" aria-current="location">Beranda</a>
                <a href="#tentang">Tentang</a>
                <a href="#fitur">Fitur</a>
                <a href="#panduan">Panduan</a>

                <span class="nav-indicator" aria-hidden="true"></span>
            </nav>

            <div class="nav-auth">
                <a href="{{ route('login') }}" class="nav-login">Masuk</a>
                <a href="{{ route('register') }}" class="nav-register">Daftar</a>
            </div>
        </div>
    </header>

    <main>

        {{-- HERO --}}
        <section class="hero-saluang" id="beranda">
            <div class="hero-container">
                <div class="hero-copy">
                    <div class="eyebrow">
                        <span class="eyebrow-dot"></span>
                        BELAJAR DARI ALAM SEKITAR KITA
                    </div>

                    <h1>
                        Belajar IPA dari<br>
                        Lingkungan Sekitar<br>
                        <span>SALUANG</span>
                    </h1>

                    <h2>
                        Media Pembelajaran IPA Berbasis<br>
                        Konteks Lahan Basah
                    </h2>

                    <p>
                        Belajar IPA dengan cara yang lebih dekat,
                        interaktif, dan menyenangkan melalui lingkungan
                        lahan basah di sekitar kita.
                    </p>

                    <div class="hero-actions">
                        <a href="#panduan" class="btn btn-blue">Mulai Belajar</a>
                        <a href="#tentang" class="btn btn-white">Kenali SALUANG</a>
                    </div>

                    <div class="hero-note">
                        <span>✓</span>
                        Dirancang untuk mendukung pembelajaran IPA siswa SMP
                    </div>
                </div>
            </div>
        </section>

        {{-- TENTANG SALUANG --}}
        <section class="section about-section" id="tentang">
            <div class="container about-grid">
                <div class="about-visual">
                    <img src="{{ asset('images/about-wetland.png') }}"
                        alt="Lingkungan sungai dan lahan basah Kalimantan Selatan" loading="lazy">
                    <div class="about-float">
                        <div class="about-float-icon">
                            <img src="{{ asset('images/ikan-saluang.png') }}" alt="Ikan Saluang">
                        </div>

                        <div class="about-float-text">
                            <strong>Ikan Saluang</strong>
                            <small>Inspirasi nama SALUANG</small>
                        </div>
                    </div>
                </div>

                <div class="about-copy">
                    <div class="section-label">KENALAN DULU YUK</div>
                    <h2>Apa itu <span>SALUANG?</span></h2>

                    <p>
                        SALUANG merupakan media pembelajaran interaktif
                        berbasis web yang membantu siswa SMP mempelajari
                        IPA melalui konteks lingkungan lahan basah.
                    </p>

                    <div class="about-highlight">
                        <span class="highlight-icon">🌿</span>
                        <div>
                            <strong>Terinspirasi dari Ikan Saluang</strong>
                            <p>
                                Nama SALUANG terinspirasi dari ikan saluang
                                yang hidup di perairan Kalimantan.
                                Identitas ini memperkuat keterkaitan media
                                pembelajaran dengan lingkungan lahan basah
                                dan kehidupan di sekitar siswa.
                            </p>
                        </div>
                    </div>

                    <div class="about-acronym">
                        <strong>🌿 SALUANG</strong>
                        <span>Sarana Aktivitas Literasi untuk Anak tentang Lingkungan</span>
                    </div>
                </div>
            </div>
        </section>


        {{-- MENGAPA SALUANG --}}
        <section class="section benefits-section" id="manfaat">
            <div class="container">

                <div class="section-heading center">
                    <div class="section-label">
                        BELAJAR LEBIH BERMAKNA
                    </div>

                    <h2>Mengapa <span>SALUANG?</span></h2>

                    <p>
                        Belajar IPA tidak hanya dari buku.
                        SALUANG mengajakmu memahami konsep IPA
                        melalui aktivitas interaktif dan lingkungan
                        lahan basah di sekitar kita.
                    </p>
                </div>

                <div class="benefit-grid">

                    {{-- KARTU 1 --}}
                    <article class="benefit-card benefit-green">
                        <div class="benefit-image">
                            <img src="{{ asset('images/saluang-aktivitas-kuis.png') }}"
                                alt="Ilustrasi buku pembelajaran interaktif" loading="lazy">
                        </div>

                        <h3>Materi Lebih Mudah Dipahami</h3>

                        <p>
                            Materi IPA disajikan secara visual dan interaktif
                            agar lebih menarik dan mudah dipahami.
                        </p>
                    </article>

                    {{-- KARTU 2 --}}
                    <article class="benefit-card benefit-blue">
                        <div class="benefit-image">
                            <img src="{{ asset('images/saluang-peduli-lahan-basah.png') }}"
                                alt="Ilustrasi lingkungan lahan basah" loading="lazy">
                        </div>

                        <h3>Belajar dari Lingkungan Nyata</h3>

                        <p>
                            Konsep IPA dihubungkan dengan lingkungan
                            lahan basah yang dekat dengan kehidupan sehari-hari.
                        </p>
                    </article>

                    {{-- KARTU 3 --}}
                    <article class="benefit-card benefit-yellow">
                        <div class="benefit-image">
                            <img src="{{ asset('images/saluang-belajar-kontekstual.png') }}"
                                alt="Ilustrasi aktivitas dan kuis" loading="lazy">
                        </div>

                        <h3>Belajar Sambil Beraktivitas</h3>

                        <p>
                            Aktivitas dan kuis interaktif membantu siswa
                            menguji pemahaman setelah belajar.
                        </p>
                    </article>

                    {{-- KARTU 4 --}}
                    <article class="benefit-card benefit-green">
                        <div class="benefit-image">
                            <img src="{{ asset('images/saluang-materi-interaktif.png') }}"
                                alt="Ilustrasi kepedulian terhadap lingkungan" loading="lazy">
                        </div>

                        <h3>Menumbuhkan Kepedulian Lingkungan</h3>

                        <p>
                            Siswa diajak mengenal ekosistem lahan basah
                            sekaligus memahami pentingnya menjaganya.
                        </p>
                    </article>

                </div>
            </div>
        </section>


        {{-- =====================================================
     06. FITUR SALUANG
     ===================================================== --}}

        <section class="section features-section" id="fitur">
            <div class="container">

                <div class="section-heading">
                    <div class="section-label">FITUR SALUANG</div>

                    <h2>Belajar, berinteraksi, dan berkembang</h2>

                    <p>
                        Satu alur pembelajaran yang menghubungkan
                        Siswa, Guru, dan Orang Tua.
                    </p>
                </div>

                {{-- 06.1. MENU PILIHAN FITUR --}}
                <div class="feature-links" aria-label="Pilih fitur SALUANG">
                    <a href="#fitur-materi">
                        <i class="bi bi-book"></i>
                        Materi
                    </a>

                    <a href="#fitur-kuis">
                        <i class="bi bi-patch-question"></i>
                        Kuis
                    </a>

                    <a href="#fitur-evaluasi">
                        <i class="bi bi-clipboard-check"></i>
                        Evaluasi
                    </a>

                    <a href="#fitur-forum">
                        <i class="bi bi-chat-dots"></i>
                        Forum Diskusi
                    </a>

                    <a href="#fitur-progres">
                        <i class="bi bi-graph-up-arrow"></i>
                        Progres/Hasil Belajar
                    </a>
                </div>

                {{-- 06.2. PENJELASAN SETIAP FITUR --}}
                <div class="feature-list">

                    <article class="feature-row" id="fitur-materi">
                        <span class="feature-icon" aria-hidden="true">
                            <i class="bi bi-book"></i>
                        </span>

                        <div>
                            <h3>Materi</h3>
                            <strong>
                                Belajar dari Bab dan Subbab yang tersusun rapi.
                            </strong>

                            <p>
                                Siswa dapat membuka BAB 1 — Karakteristik dan
                                Klasifikasi Makhluk Hidup. Di dalamnya tersedia
                                Subbab 1.1 Makhluk Hidup atau Benda Mati?,
                                Subbab 1.2 Mengapa Makhluk Hidup Dikelompokkan?,
                                dan Subbab 1.3 Makhluk Hidup Beraneka Ragam.
                                Gunakan navigasi Previous dan Next untuk
                                berpindah materi.
                            </p>

                            <a href="#fitur">Kembali ke Fitur</a>
                        </div>
                    </article>

                    <article class="feature-row" id="fitur-kuis">
                        <span class="feature-icon" aria-hidden="true">
                            <i class="bi bi-patch-question"></i>
                        </span>

                        <div>
                            <h3>Kuis</h3>
                            <strong>
                                Uji pemahaman melalui pertanyaan pilihan ganda.
                            </strong>

                            <p>
                                Setelah mempelajari Subbab, siswa dapat
                                mengerjakan kuis untuk menguji pemahaman.
                                Pilih jawaban yang tersedia, lalu perhatikan
                                umpan balik dan pembahasan soal untuk mengetahui
                                materi yang perlu dipelajari kembali.
                            </p>

                            <a href="#fitur">Kembali ke Fitur</a>
                        </div>
                    </article>

                    <article class="feature-row" id="fitur-evaluasi">
                        <span class="feature-icon" aria-hidden="true">
                            <i class="bi bi-clipboard-check"></i>
                        </span>

                        <div>
                            <h3>Evaluasi</h3>
                            <strong>
                                Uji pemahaman setelah menyelesaikan pembelajaran.
                            </strong>

                            <p>
                                Evaluasi Akhir BAB 1 berisi 20 soal dengan waktu
                                pengerjaan 45 menit. Siswa membaca petunjuk,
                                mengerjakan soal, memeriksa jawaban, dan
                                menyelesaikan evaluasi sesuai alur yang
                                tersedia pada dashboard.
                            </p>

                            <a href="#fitur">Kembali ke Fitur</a>
                        </div>
                    </article>

                    <article class="feature-row" id="fitur-forum">
                        <span class="feature-icon" aria-hidden="true">
                            <i class="bi bi-chat-dots"></i>
                        </span>

                        <div>
                            <h3>Forum Diskusi</h3>
                            <strong>
                                Bertanya dan berdiskusi bersama pengguna lain.
                            </strong>

                            <p>
                                Siswa dapat membuat pertanyaan sesuai Bab dan
                                Subbab, mencari diskusi, menggunakan filter
                                Terbaru, Sudah Terjawab, atau Belum Terjawab,
                                serta membaca dan mengirim tanggapan.
                                Diskusi milik sendiri dapat diedit atau dihapus.
                                Data diskusi disimpan melalui Laravel dan MySQL.
                            </p>

                            <a href="#fitur">Kembali ke Fitur</a>
                        </div>
                    </article>

                    <article class="feature-row" id="fitur-progres">
                        <span class="feature-icon" aria-hidden="true">
                            <i class="bi bi-graph-up-arrow"></i>
                        </span>

                        <div>
                            <h3>Progres/Hasil Belajar</h3>
                            <strong>
                                Mengenali perkembangan pemahaman siswa.
                            </strong>

                            <p>
                                Siswa dapat menggunakan hasil dan umpan balik
                                dari evaluasi yang tersedia untuk meninjau
                                pemahamannya. Pengembangan rekap progres
                                pembelajaran yang lebih lengkap bagi Guru
                                dan Orang Tua dilakukan secara bertahap.
                            </p>

                            <a href="#fitur">Kembali ke Fitur</a>
                        </div>
                    </article>

                </div>
            </div>
        </section>


        {{-- PANDUAN --}}


        {{-- =====================================================
     07. PANDUAN PENGGUNAAN SALUANG
     ===================================================== --}}

        @php
            $panduanSaluang = [
                'siswa' => [
                    'nama' => 'Siswa',
                    'deskripsi' => 'Ikuti langkah berikut untuk menggunakan SALUANG sebagai media pembelajaran.',
                    'langkah' => [
                        [
                            'judul' => 'Masuk ke akun',
                            'poin' => [
                                'Buka website SALUANG.',
                                'Klik tombol Masuk pada bagian navigasi.',
                                'Masuk menggunakan NISN dan kata sandi siswa.',
                                'Setelah berhasil login, buka Dashboard Siswa.',
                            ],
                        ],
                        [
                            'judul' => 'Melihat materi',
                            'poin' => [
                                'Klik menu Materi pada sidebar dashboard.',
                                'Buka BAB 1 — Karakteristik dan Klasifikasi Makhluk Hidup.',
                                'Pilih Subbab 1.1, 1.2, atau 1.3 yang ingin dipelajari.',
                                'Baca penjelasan materi sampai selesai.',
                                'Amati gambar pembelajaran jika tersedia.',
                                'Gunakan tombol Previous dan Next untuk berpindah halaman.',
                            ],
                        ],
                        [
                            'judul' => 'Mengerjakan kuis',
                            'poin' => [
                                'Buka salah satu Subbab yang sudah dipelajari.',
                                'Temukan bagian kuis di dalam Subbab.',
                                'Baca pertanyaan dengan teliti.',
                                'Pilih jawaban A, B, C, atau D.',
                                'Perhatikan umpan balik dan pembahasan jawaban.',
                                'Gunakan hasil kuis untuk mengulang materi yang belum dipahami.',
                            ],
                        ],
                        [
                            'judul' => 'Mengikuti evaluasi',
                            'poin' => [
                                'Klik menu Evaluasi pada sidebar.',
                                'Baca petunjuk Evaluasi Akhir BAB 1.',
                                'Mulai evaluasi ketika sudah siap.',
                                'Kerjakan 20 soal dalam waktu 45 menit.',
                                'Gunakan navigasi soal untuk berpindah pertanyaan.',
                                'Periksa jawaban sebelum menyelesaikan evaluasi.',
                            ],
                        ],
                        [
                            'judul' => 'Forum diskusi',
                            'poin' => [
                                'Klik menu Forum Diskusi.',
                                'Pilih Bab dan Subbab sesuai topik.',
                                'Gunakan pencarian atau filter status jika diperlukan.',
                                'Klik Buat Diskusi untuk membuat pertanyaan baru.',
                                'Klik Lihat Diskusi untuk membaca pertanyaan dan tanggapan.',
                                'Kirim tanggapan melalui halaman detail diskusi.',
                                'Gunakan Edit atau Hapus untuk mengelola diskusi milik sendiri.',
                            ],
                        ],
                        [
                            'judul' => 'Melihat hasil belajar',
                            'poin' => [
                                'Selesaikan kuis atau evaluasi yang tersedia.',
                                'Perhatikan hasil dan umpan balik yang ditampilkan.',
                                'Pelajari kembali materi yang belum dipahami.',
                                'Gunakan evaluasi sebagai bahan refleksi belajar.',
                            ],
                        ],
                        [
                            'judul' => 'Profil',
                            'poin' => [
                                'Klik menu Profil pada sidebar atau dropdown akun.',
                                'Lihat nama lengkap, username, NISN, email, kelas, dan sekolah.',
                                'Klik Ubah Foto Profil untuk mengunggah foto.',
                                'Gunakan Hapus Foto jika ingin mengembalikan avatar inisial.',
                                'Klik Edit Profil untuk membuka formulir informasi pribadi.',
                                'Klik Ubah Kata Sandi untuk membuka pengaturan password.',
                                'Klik Keluar dan konfirmasi jika ingin mengakhiri sesi.',
                            ],
                        ],
                    ],
                ],

                'guru' => [
                    'nama' => 'Guru',
                    'deskripsi' => 'Panduan alur pengelolaan pembelajaran dan pendampingan siswa dalam SALUANG.',
                    'langkah' => [
                        [
                            'judul' => 'Masuk ke akun',
                            'poin' => [
                                'Buka website SALUANG.',
                                'Klik menu Masuk.',
                                'Gunakan identitas dan kata sandi akun Guru.',
                                'Masuk ke dashboard sesuai peran akun.',
                            ],
                        ],
                        [
                            'judul' => 'Mengelola Bab dan Materi',
                            'poin' => [
                                'Buka bagian pengelolaan Materi jika tersedia pada dashboard Guru.',
                                'Pilih Bab yang ingin dikelola.',
                                'Siapkan judul dan susunan Subbab pembelajaran.',
                                'Periksa isi materi serta gambar pendukung.',
                                'Publikasikan materi melalui fitur pengelolaan ketika sudah tersedia.',
                            ],
                        ],
                        [
                            'judul' => 'Mengelola Soal Kuis',
                            'poin' => [
                                'Buka bagian pengelolaan soal jika sudah tersedia.',
                                'Pilih Bab atau Subbab yang sesuai.',
                                'Susun pertanyaan pilihan ganda.',
                                'Siapkan pilihan jawaban A, B, C, dan D.',
                                'Tentukan jawaban benar dan pembahasan.',
                                'Simpan soal melalui fitur yang tersedia.',
                            ],
                        ],
                        [
                            'judul' => 'Mengelola Evaluasi',
                            'poin' => [
                                'Buka bagian pengelolaan Evaluasi jika tersedia.',
                                'Tentukan cakupan materi yang akan dievaluasi.',
                                'Siapkan pertanyaan dan kunci jawaban.',
                                'Periksa petunjuk serta aturan pengerjaan.',
                                'Publikasikan evaluasi setelah fitur pengelolaan diaktifkan.',
                            ],
                        ],
                        [
                            'judul' => 'Forum Diskusi',
                            'poin' => [
                                'Buka bagian Forum Diskusi jika tersedia untuk akun Guru.',
                                'Pilih Bab atau Subbab yang sesuai.',
                                'Baca pertanyaan yang diajukan siswa.',
                                'Berikan tanggapan untuk membantu pemahaman siswa.',
                                'Periksa kembali diskusi yang membutuhkan jawaban.',
                            ],
                        ],
                        [
                            'judul' => 'Nilai Siswa',
                            'poin' => [
                                'Buka pengelolaan hasil belajar jika fitur sudah tersedia.',
                                'Pilih siswa atau kegiatan pembelajaran.',
                                'Periksa hasil kuis dan evaluasi yang tercatat.',
                                'Gunakan hasilnya untuk merencanakan tindak lanjut.',
                                'Unduh rekap apabila fitur ekspor sudah tersedia.',
                            ],
                        ],
                        [
                            'judul' => 'Hubungi Orang Tua',
                            'poin' => [
                                'Buka fitur komunikasi Orang Tua jika sudah tersedia.',
                                'Pilih Orang Tua yang akan dihubungi.',
                                'Tulis pesan terkait pembelajaran siswa.',
                                'Kirim pesan melalui fitur komunikasi yang tersedia.',
                            ],
                        ],
                        [
                            'judul' => 'Profil',
                            'poin' => [
                                'Buka halaman Profil jika tersedia pada dashboard Guru.',
                                'Periksa informasi akun.',
                                'Gunakan pengaturan akun sesuai fitur yang sudah diaktifkan.',
                            ],
                        ],
                    ],
                ],

                'orang-tua' => [
                    'nama' => 'Orang Tua',
                    'deskripsi' => 'Panduan untuk mendampingi dan memantau perkembangan belajar anak melalui SALUANG.',
                    'langkah' => [
                        [
                            'judul' => 'Masuk ke akun',
                            'poin' => [
                                'Buka website SALUANG.',
                                'Klik tombol Masuk.',
                                'Masukkan identitas akun Orang Tua dan kata sandi.',
                                'Buka halaman dashboard sesuai peran akun.',
                            ],
                        ],
                        [
                            'judul' => 'Melihat aktivitas anak',
                            'poin' => [
                                'Buka bagian aktivitas anak jika sudah tersedia.',
                                'Pilih anak yang terhubung dengan akun Orang Tua.',
                                'Periksa kegiatan pembelajaran yang ditampilkan.',
                            ],
                        ],
                        [
                            'judul' => 'Melihat hasil belajar',
                            'poin' => [
                                'Buka bagian hasil belajar jika sudah tersedia.',
                                'Pilih hasil kuis atau evaluasi anak.',
                                'Baca nilai, umpan balik, dan informasi hasil belajar.',
                                'Gunakan hasil tersebut untuk mendampingi anak.',
                            ],
                        ],
                        [
                            'judul' => 'Melihat progres belajar',
                            'poin' => [
                                'Buka bagian progres belajar jika sudah tersedia.',
                                'Periksa perkembangan kegiatan belajar anak.',
                                'Gunakan informasi tersebut untuk memberi dukungan belajar.',
                            ],
                        ],
                        [
                            'judul' => 'Chat dengan Guru',
                            'poin' => [
                                'Buka fitur komunikasi Guru jika sudah tersedia.',
                                'Pilih Guru yang ingin dihubungi.',
                                'Baca pesan atau percakapan yang tersedia.',
                                'Kirim pertanyaan terkait perkembangan belajar anak.',
                            ],
                        ],
                        [
                            'judul' => 'Profil',
                            'poin' => [
                                'Buka halaman Profil pada dashboard Orang Tua jika tersedia.',
                                'Periksa informasi akun.',
                                'Gunakan pengaturan profil sesuai fitur yang tersedia.',
                            ],
                        ],
                    ],
                ],
            ];
        @endphp

        <section class="section guide-section" id="panduan">
            <div class="container">

                {{-- 07.1. HALAMAN PILIH PERAN --}}
                <div id="saluangGuideHome">

                    <div class="section-heading">
                        <div class="section-label">PANDUAN PENGGUNAAN</div>

                        <h2>Mulai dengan langkah yang tepat</h2>

                        <p>
                            Pilih panduan sesuai peran Anda.
                            Kenali alur SALUANG, langkah demi langkah.
                        </p>
                    </div>

                    <div class="guide-grid">

                        <article class="guide-card">
                            <span class="guide-icon">🎓</span>
                            <h3>Siswa</h3>
                            <strong>Panduan penggunaan</strong>

                            <p>
                                Belajar materi, mencoba kuis dan evaluasi,
                                serta berdiskusi melalui Forum Diskusi.
                            </p>

                            <button type="button" class="saluang-guide-open" data-guide-open="siswa">
                                Lihat Panduan
                            </button>
                        </article>

                        <article class="guide-card">
                            <span class="guide-icon">📗</span>
                            <h3>Guru</h3>
                            <strong>Panduan penggunaan</strong>

                            <p>
                                Kenali alur pengelolaan pembelajaran,
                                evaluasi, dan pendampingan siswa.
                            </p>

                            <button type="button" class="saluang-guide-open" data-guide-open="guru">
                                Lihat Panduan
                            </button>
                        </article>

                        <article class="guide-card">
                            <span class="guide-icon">👨‍👩‍👧</span>
                            <h3>Orang Tua</h3>
                            <strong>Panduan penggunaan</strong>

                            <p>
                                Kenali alur pemantauan aktivitas, hasil belajar,
                                dan komunikasi dengan Guru.
                            </p>

                            <button type="button" class="saluang-guide-open" data-guide-open="orang-tua">
                                Lihat Panduan
                            </button>
                        </article>

                    </div>
                </div>

                {{-- 07.2. HALAMAN PANDUAN LENGKAP --}}
                @foreach ($panduanSaluang as $kodePeran => $panduan)
                    <div class="saluang-guide-page" id="saluang-guide-{{ $kodePeran }}"
                        data-guide-page="{{ $kodePeran }}" hidden>

                        <button type="button" class="saluang-guide-back" data-guide-back>
                            Kembali
                        </button>

                        <div class="section-heading">
                            <div class="section-label">
                                PANDUAN PENGGUNAAN
                            </div>

                            <h2>Panduan {{ $panduan['nama'] }}</h2>

                            <p>{{ $panduan['deskripsi'] }}</p>
                        </div>

                        <div class="saluang-guide-steps">

                            @foreach ($panduan['langkah'] as $index => $langkah)
                                <article class="saluang-guide-step">

                                    <span class="saluang-guide-number">
                                        {{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}
                                    </span>

                                    <div class="saluang-guide-step-content">
                                        <h3>{{ $langkah['judul'] }}</h3>

                                        <ul>
                                            @foreach ($langkah['poin'] as $poin)
                                                <li>{{ $poin }}</li>
                                            @endforeach
                                        </ul>
                                    </div>

                                </article>
                            @endforeach

                        </div>

                        <button type="button" class="saluang-guide-back saluang-guide-back-bottom" data-guide-back>
                            Kembali
                        </button>

                    </div>
                @endforeach

            </div>
        </section>

        {{-- BANNER AJAKAN BELAJAR --}}
        <section class="cta-section">
            <div class="container">
                <div class="cta-banner">
                    <div class="cta-illustration">
                        <img src="{{ asset('images/saluang-hero-characters-new.png') }}"
                            alt="Ilustrasi siswa dan guru SALUANG" loading="lazy">
                    </div>

                    <div class="cta-content">
                        <span>MARI MULAI MENJELAJAH!</span>
                        <h2>Siap belajar IPA<br>bersama SALUANG?</h2>
                        <p>
                            Jelajahi materi IPA dan kenali lingkungan
                            lahan basah di sekitar kita.
                        </p>
                        <a href="#panduan" class="btn btn-yellow">Mulai Belajar</a>
                    </div>
                </div>
            </div>
        </section>

    </main>

    {{-- FOOTER --}}
    <footer class="site-footer">
        <div class="container">
            <div class="footer-top">
                <a href="{{ route('beranda') }}">
                    <img src="{{ asset('images/saluang-logo-footer.png') }}" alt="SALUANG">
                </a>

                <nav aria-label="Navigasi footer">
                    <a href="#beranda">Beranda</a>
                    <a href="#tentang">Tentang</a>
                    <a href="#fitur">Fitur</a>
                    <a href="#panduan">Panduan</a>
                </nav>
            </div>

            <div class="footer-bottom">
                <span>Media Pembelajaran IPA Berbasis Konteks Lahan Basah untuk Siswa SMP</span>
                <span>© {{ date('Y') }} SALUANG</span>
            </div>
        </div>
    </footer>

@endsection
