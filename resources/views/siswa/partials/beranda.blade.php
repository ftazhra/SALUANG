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

                            <img src="{{ asset('images/progress-assets/progress-1.png') }}"
                                alt="Ilustrasi progres belajar">

                            <div>
                                <small>Progres Belajar</small>
                                <strong id="sdProgressPercentage">0%</strong>
                                <p id="sdCompletedMaterials">0 dari 0 materi selesai</p>
                            </div>

                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="sd-card sd-stat">

                            <img src="{{ asset('images/progress-assets/progress-2.png') }}" alt="Ilustrasi kuis">

                            <div>
                                <small>Kuis Selesai</small>
                                <strong id="sdCompletedQuizzes">0</strong>
                                <p>Setiap kuis yang selesai adalah langkah maju!</p>
                            </div>

                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="sd-card sd-stat">

                            <img src="{{ asset('images/progress-assets/progress-3.png') }}" alt="Ilustrasi evaluasi">

                            <div>
                                <small>Evaluasi Pembelajaran</small>
                                <strong id="sdCompletedEvaluations">0</strong>
                                <p>Lihat sejauh mana usahamu membuahkan hasil!</p>
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

                                <img src="{{ asset('images/lesson-interaksi-makhluk-hidup.png') }}"
                                    alt="Ekosistem lahan basah">

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

                                    <div class="sd-progress" role="progressbar" aria-label="Progres belajar"
                                        aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><span></span></div>

                                    <button type="button" class="sd-primary" data-page="7">
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

                            <div class="sd-empty" id="sdActivityEmpty">

                                <img src="{{ asset('images/saluang-materi-interaktif.png') }}" alt="Ilustrasi SALUANG">

                                <span>
                                    Aktivitas belajarmu akan muncul di sini.
                                </span>

                            </div>
                            <ul class="sd-activity-list" id="sdActivityList" aria-live="polite"></ul>

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

                    <div class="d-flex justify-content-between align-items-end mt-4 gap-3">
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

                    <div class="sd-progress" role="progressbar" aria-label="Pencapaian belajar" aria-valuemin="0"
                        aria-valuemax="100" aria-valuenow="0"><span></span></div>

                    <div class="sd-final-grade-summary" id="sdFinalGradeSummary" hidden>
                        <h4>Nilai Akhir</h4>
                        <p>Kuis Subbab dan Evaluasi Akhir berbobot 50:50; KKM 70.</p>
                        <ul id="sdFinalGradeList" aria-live="polite"></ul>
                    </div>
                </article>

                <!-- Navigasi -->
                <div class="sd-navigation sd-navigation-home">

                    <button type="button" class="sd-nav-btn next" data-page="7">
                        Next
                        <i class="bi bi-chevron-right"></i>
                    </button>

                </div>

            </section>
