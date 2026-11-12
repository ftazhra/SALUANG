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
                        <img src="{{ asset('images/lesson-interaksi-makhluk-hidup.png') }}"
                            alt="Ilustrasi kehidupan lahan basah" loading="lazy">
                        <figcaption class="sd-chapter-introduction-caption" id="sdChapterIntroductionCaption" hidden>
                            <strong id="sdChapterIntroductionTitle" hidden></strong>
                        </figcaption>
                    </figure>

                </div>

                <!-- Navigasi atas -->
                <div class="sd-navigation">

                    <button type="button" class="sd-nav-btn" data-page="7">
                        <i class="bi bi-chevron-left"></i>
                        Previous
                    </button>

                    <button type="button" class="sd-nav-btn next" data-page="2">
                        Next
                        <i class="bi bi-chevron-right"></i>
                    </button>

                </div>

                <!-- Tujuan Pembelajaran -->
                <article class="sd-card sd-objectives-card mt-3">

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
                <article class="sd-card sd-prompt-card mt-3">

                    <h2 class="sd-heading">
                        <i class="bi bi-lightbulb"></i>
                        Yuk, Pikirkan!
                    </h2>

                    <div class="sd-prompt">
                        <div class="sd-copy mb-0">
                            Memuat pertanyaan...
                        </div>

                    </div>

                    <img src="{{ asset('images/lesson-ekosistem-lahan-basah.png') }}"
                        alt="Ilustrasi ekosistem sungai" class="sd-prompt-image" loading="lazy"
                        onerror="this.hidden=true">

                </article>

                <!-- Navigasi bawah -->
                <div class="sd-navigation">

                    <button type="button" class="sd-nav-btn" data-page="7">
                        <i class="bi bi-chevron-left"></i>
                        Previous
                    </button>

                    <button type="button" class="sd-nav-btn next" data-page="2">
                        Next
                        <i class="bi bi-chevron-right"></i>
                    </button>

                </div>

            </section>
