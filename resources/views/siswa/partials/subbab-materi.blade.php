            <section id="lessonPage" hidden>

                <div class="sd-breadcrumb" id="lessonBreadcrumb"></div>

                <span class="sd-pill" id="lessonBadge"></span>

                <h1 class="sd-title" id="lessonTitle"></h1>

                <!-- Navigasi atas subbab -->
                <div class="sd-navigation" data-lesson-navigation></div>

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
                            <div class="sd-copy" id="lessonContent"></div>

                            <!-- Fakta, tampil hanya bila diisi -->
                            <div class="sd-fact" hidden>
                                <strong>Tahukah Kamu?</strong>

                                <p class="sd-copy" id="lessonFact"></p>
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
                        Kerjakan setiap soal sesuai petunjuknya. Aktivitas formatif tidak menambah nilai angka Kuis.
                    </p>

                    <div class="sd-question-count" id="questionCount"></div>

                    <p id="questionText" class="fw-semibold mb-3"></p>

                    <!-- Jawaban dibuat JS -->
                    <div class="sd-choices" id="answerChoices"></div>

                    <!-- Feedback kuis -->
                    <div id="answerFeedback" class="sd-answer-feedback" role="status" aria-live="polite" hidden>
                    </div>

                    <!-- Navigasi soal -->
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

                <!-- Navigasi bawah subbab -->
                <div class="sd-navigation" data-lesson-navigation></div>

            </section>
