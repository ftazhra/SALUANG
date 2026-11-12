            <section id="sd-section-evaluasi" hidden>

                {{-- 12.1 PENGANTAR EVALUASI --}}
                <div class="sd-eval-screen is-active" data-eval-screen="overview">

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

                        <div class="sd-eval-overview-header">

                            <div class="sd-eval-intro-heading">

                                <span class="sd-eval-intro-icon">
                                    <i class="bi bi-journal-check"></i>
                                </span>

                                <div>
                                    <h1 class="sd-eval-intro-title">
                                        Evaluasi Akhir
                                    </h1>

                                    <p class="sd-eval-intro-subtitle" id="sd-eval-intro-subtitle">
                                        Kerjakan 15 soal pilihan ganda A–D dalam 45 menit.
                                    </p>
                                </div>

                            </div>

                        </div>

                        <p id="sd-eval-bank-status" class="sd-eval-bank-status" role="status"
                            aria-live="polite" hidden></p>

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
                                                <img src="{{ asset('images/evaluasi/eval-jumlah-soal.png') }}"
                                                    alt="Jumlah soal evaluasi">
                                            </div>

                                            <div>
                                                <span>Jumlah Soal</span>
                                                <strong>15 Soal</strong>
                                            </div>

                                        </div>

                                        <div class="sd-eval-info-card sd-eval-info-card--green">

                                            <div class="sd-eval-info-image">
                                                <img src="{{ asset('images/evaluasi/eval-durasi.png') }}"
                                                    alt="Durasi evaluasi">
                                            </div>

                                            <div>
                                                <span>Durasi Pengerjaan</span>
                                                <strong>45 Menit</strong>
                                            </div>

                                        </div>

                                        <div class="sd-eval-info-card sd-eval-info-card--yellow">

                                            <div class="sd-eval-info-image">
                                                <img src="{{ asset('images/evaluasi/eval-ketuntasan.png') }}"
                                                    alt="Ketuntasan belajar">
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
                                                Evaluasi Akhir memuat soal
                                                yang dipublikasikan oleh Guru.
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
                                            Evaluasi terdiri dari 15 soal pilihan ganda
                                            dengan pilihan A, B, C, dan D.
                                        </li>

                                        <li>
                                            Pilih satu jawaban pada setiap soal.
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

                                <div id="sd-eval-locked-message" class="se-locked-note" hidden>
                                    <i class="bi bi-shield-check"></i>
                                    <div><strong>Evaluasi sudah tuntas</strong>
                                        <p>Kamu telah mencapai KKM. Kamu bisa melihat hasil, tetapi tidak dapat
                                            mengerjakan ulang.</p>
                                    </div>
                                </div>

                                <div class="sd-eval-intro-start">

                                    <button type="button" class="sd-eval-primary" data-eval-open="quiz">
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
                <div class="sd-eval-screen" data-eval-screen="quiz">

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

                        <button type="button" class="sd-eval-outline sd-eval-exam-back" id="sd-eval-quiz-exit">
                            Kembali ke Evaluasi
                        </button>

                    </div>

                    <div class="sd-eval-exam-layout">

                        <!-- Kolom soal -->
                        <div class="sd-eval-exam-main">

                            <article class="sd-eval-question-card">

                                <div class="sd-eval-question-meta">

                                    <span id="sd-eval-current-number">
                                        Soal 1 dari 15
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
                                            Soal akan dimuat oleh
                                            JavaScript evaluasi.
                                        </p>

                                    </div>
                                </div>

                            </article>

                            <div class="sd-eval-exam-navigation">

                                <button type="button" class="sd-nav-btn" id="sd-eval-quiz-prev" disabled>
                                    <i class="bi bi-chevron-left"></i>
                                    Previous
                                </button>

                                <button type="button" class="sd-nav-btn next" id="sd-eval-quiz-next">
                                    Next
                                    <i class="bi bi-chevron-right"></i>
                                </button>

                                <span class="sd-eval-quiz-last-note" id="sd-eval-quiz-last-note" hidden>
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

                                        <strong id="sd-eval-timer" role="timer" aria-label="Sisa waktu evaluasi">
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
                                        aria-label="Pilih nomor soal"></div>

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

                {{-- =================================================
                     12.3 MODAL KONFIRMASI EVALUASI
                ================================================== --}}
                <div class="sd-eval-confirm" id="sd-eval-confirm" hidden>
                    <div class="sd-eval-confirm-backdrop"></div>

                    <div class="sd-eval-confirm-dialog" role="dialog" aria-modal="true"
                        aria-labelledby="sd-eval-confirm-title" aria-describedby="sd-eval-confirm-description"
                        tabindex="-1">

                        <button type="button" class="sd-eval-confirm-close" id="sd-eval-confirm-close"
                            aria-label="Tutup konfirmasi">
                            <i class="bi bi-x-lg"></i>
                        </button>

                        <div class="sd-eval-confirm-icon">

                            <img id="sd-eval-confirm-icon" src="{{ asset('images/evaluasi/bekantan-bertanya.png') }}"
                                alt="Bekantan sedang berpikir" width="170" height="155">

                        </div>

                        <h2 class="sd-eval-confirm-title" id="sd-eval-confirm-title">
                            Yakin Ingin Memulai Evaluasi?
                        </h2>

                        <p class="sd-eval-confirm-description" id="sd-eval-confirm-description">
                            Kamu akan mengerjakan 15 soal
                            selama 45 menit.
                        </p>

                        <div class="sd-eval-confirm-info" id="sd-eval-confirm-info">
                            <i class="bi bi-clock-history"></i>

                            <span>
                                Setelah dimulai, waktu akan
                                terus berjalan meskipun
                                kamu keluar dari halaman.
                            </span>
                        </div>

                        <div class="sd-eval-confirm-actions">

                            <button type="button" class="sd-eval-confirm-cancel" id="sd-eval-confirm-cancel">
                                Batal
                            </button>

                            <button type="button" class="sd-eval-confirm-accept" id="sd-eval-confirm-accept">
                                <span id="sd-eval-confirm-accept-text">
                                    Ya, Mulai
                                </span>
                            </button>

                        </div>

                    </div>
                </div>

                {{-- =================================================
                     12.6 HASIL EVALUASI
                ================================================== --}}
                <div class="sd-eval-screen" data-eval-screen="result">

                    <article class="sd-card sd-eval-card">

                        <div class="sd-eval-overview-header">

                            <h1 class="sd-eval-heading">
                                <span id="sd-eval-result-page-title">Hasil Evaluasi</span>
                            </h1>

                        </div>

                        <div class="sd-eval-result se-results-v2" id="sd-eval-result-panel">
                            <div class="se-results-hero">
                                <div class="se-results-copy">
                                    <span class="se-results-state" id="sd-eval-result-status">HASIL EVALUASI</span>
                                    <h2 id="sd-eval-result-heading">Evaluasi selesai!</h2>
                                    <p id="sd-eval-final-message">Hasil belajarmu telah tersimpan.</p>
                                    <div class="se-results-score">
                                        <span>Nilai Akhir</span>
                                        <div><strong id="sd-eval-final-score">0</strong><small>/ 100</small></div>
                                        <small id="sd-eval-final-summary">0 benar · 0 salah</small>
                                    </div>
                                </div>
                                <div class="se-results-visual">
                                    <img src="{{ asset('images/evaluasi/bekantan-juara-transparan.png') }}"
                                        alt="Bekantan SALUANG mengangkat piala emas bersama buku, tumbuhan, dan papan evaluasi">
                                </div>
                            </div>
                            <div class="se-results-stats" aria-label="Ringkasan nilai evaluasi">
                                <div class="se-stat"><span>Skor</span><strong
                                        id="sd-eval-summary-score">0</strong><small>dari 100</small></div>
                                <div class="se-stat se-stat-good"><span>Jawaban Benar</span><strong
                                        id="sd-eval-summary-correct">0</strong><small>dari 15 soal</small></div>
                                <div class="se-stat se-stat-wrong"><span>Jawaban Salah</span><strong
                                        id="sd-eval-summary-wrong">0</strong><small>dari 15 soal</small></div>
                                <div class="se-stat"><span>KKM</span><strong
                                        id="sd-eval-result-kkm">70</strong><small>nilai minimum tuntas</small></div>
                            </div>
                            <div class="sd-eval-result-actions se-results-actions">
                                <button type="button" class="sd-eval-outline" id="sd-eval-retry">Coba Lagi</button>
                                <button type="button" class="sd-eval-primary" data-page="6">Lanjut ke Forum
                                    Diskusi</button>
                            </div>

                        </div>

                    </article>

                </div>

                {{-- Navigasi evaluasi --}}
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
