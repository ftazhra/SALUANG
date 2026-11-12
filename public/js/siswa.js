/* ==========================================================
   SALUANG - DASHBOARD SISWA
   File: public/js/siswa.js
   Materi Guru ditampilkan sebagai buku digital.
   Navigasi, kuis, forum, dan profil dipertahankan.
   ========================================================== */
document.addEventListener('DOMContentLoaded', function () {
    'use strict';
    const get = id => document.getElementById(id);
    const studentMaterial = window.SaluangStudentMaterial;
    const studentHome = window.SaluangStudentHome;
    if (!studentMaterial || !studentHome) {
        throw new Error('Modul Dashboard Siswa belum dimuat.');
    }
    const sections = {
        0: get('homePage'),
        1: get('chapterPage'),
        2: get('lessonPage'),
        5: get('sd-section-evaluasi'),
        6: get('finalPage'),
        7: get('materialOverviewPage'),
        8: get('profilePage')
    };
    const desktopMenu = document.querySelector(
        '#desktopMateri .sd-submenu'
    );
    const mobileMenu = document.querySelector(
        '#mobileMateri .sd-submenu'
    );
    // Satu tombol: klik Materi membuka halaman daftar Materi sekaligus
    // menampilkan/menyembunyikan daftar Bab/Subbab di sidebar.
    document.querySelectorAll('.sd-material-menu[aria-controls]').forEach(button => {
        const target = document.getElementById(button.getAttribute('aria-controls'));
        if (!target) return;
        target.hidden = true;
        button.setAttribute('aria-expanded', 'false');
        button.addEventListener('click', () => {
            const expanded = button.getAttribute('aria-expanded') === 'true';
            target.hidden = expanded;
            button.setAttribute('aria-expanded', String(!expanded));
        });
    });
    const materialGrid = document.querySelector(
        '.sd-material-overview-grid'
    );
    let images = {};
    const assets = get('sd-assets');
    if (assets) {
        try {
            images = JSON.parse(assets.textContent);
        } catch (error) {
            console.warn('Asset gambar tidak terbaca.', error);
        }
    }
    let currentPage = 0;
    let publishedChapters = [];
    let materialSteps = [];
    let activeChapter = null;
    let activeSubchapter = null;
    let currentQuiz = [];
    let currentQuizContentCount = 0;
    let currentQuizAttemptId = null;
    let quizAttemptCompleted = false;
    let questionIndex = 0;
    let quizVersion = 0;
    let quizAnswerPending = false;
    const navigationEntry = performance.getEntriesByType?.('navigation')?.[0];
    let restartQuizOnFirstOpen = navigationEntry
        ? navigationEntry.type === 'reload'
        : performance.navigation?.type === 1;
    const savedAnswers = new Map();
    const activityAnswers = new Map();
    const matchingLineUpdaters = new Set();
    window.addEventListener('resize', () => {
        matchingLineUpdaters.forEach(update => update());
    });
    window.addEventListener('scroll', () => {
        matchingLineUpdaters.forEach(update => update());
    }, { passive: true });
    let dashboardProgress = null;
    let activeMaterialOpen = Promise.resolve();
    // ======================================================
    // 01. UTILITAS
    // ======================================================
    function el(tag, className = '', text = null) {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== null && text !== undefined) {
            node.textContent = String(text);
        }
        return node;
    }
    studentMaterial.configure(el);
    function setText(element, value) {
        if (element) {
            element.textContent = String(value ?? '');
        }
    }
    function textOr(value, fallback = 'Belum diisi Guru.') {
        return String(value ?? '').trim() || fallback;
    }
    function subchaptersOf(chapter) {
        return Array.isArray(chapter?.subchapters)
            ? chapter.subchapters
            : [];
    }
    function closeMobileSidebar() {
        const mobile = get('mobileSidebar');
        if (mobile && window.bootstrap?.Offcanvas) {
            window.bootstrap.Offcanvas
                .getInstance(mobile)?.hide();
        }
    }
    function scrollTop() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }
    // ======================================================
    // 03. URUTAN PEMBELAJARAN
    // ======================================================
    function buildMaterialSteps() {
        materialSteps = [];
        publishedChapters.forEach(chapter => {
            materialSteps.push({
                type: 'chapter',
                chapter,
                subchapter: null
            });
            subchaptersOf(chapter).forEach(subchapter => {
                materialSteps.push({
                    type: 'subchapter',
                    chapter,
                    subchapter
                });
            });
        });
    }
    function findStepIndex() {
        if (!activeChapter) return -1;
        return materialSteps.findIndex(step => {
            if (
                Number(step.chapter.id) !==
                Number(activeChapter.id)
            ) {
                return false;
            }
            if (currentPage === 1) {
                return step.type === 'chapter';
            }
            if (
                currentPage === 2 &&
                activeSubchapter
            ) {
                return (
                    step.type === 'subchapter' &&
                    Number(step.subchapter.id) ===
                    Number(activeSubchapter.id)
                );
            }
            return false;
        });
    }
    function openStep(step) {
        if (!step) return;
        if (step.type === 'chapter') {
            openChapter(step.chapter);
        } else {
            openSubchapter(
                step.chapter,
                step.subchapter
            );
        }
    }
    function firstMaterial() {
        if (materialSteps.length) {
            openStep(materialSteps[0]);
        } else {
            showPage(7);
        }
    }
    function lastMaterial() {
        if (materialSteps.length) {
            openStep(
                materialSteps[materialSteps.length - 1]
            );
        } else {
            showPage(7);
        }
    }
    function navigateMaterial(direction) {
        const index = findStepIndex();
        if (index === -1) {
            showPage(7);
            return;
        }
        const targetIndex = index +
            (direction === 'next' ? 1 : -1);
        if (targetIndex < 0) {
            showPage(7);
            return;
        }
        if (targetIndex >= materialSteps.length) {
            showPage(5);
            return;
        }
        openStep(materialSteps[targetIndex]);
    }
    // ======================================================
    // 05. MENU AKTIF
    // ======================================================
    function updateActiveMenu() {
        document.querySelectorAll(
            '.sd-menu[data-page]'
        ).forEach(button => {
            const page = Number(button.dataset.page);
            const active = page === 7
                ? [1, 2, 7].includes(currentPage)
                : page === currentPage;
            button.classList.toggle('active', active);
            if (active) {
                button.setAttribute(
                    'aria-current',
                    'page'
                );
            } else {
                button.removeAttribute('aria-current');
            }
        });
        document.querySelectorAll(
            '.sd-chapter-button'
        ).forEach(button => {
            const active =
                currentPage === 1 &&
                activeChapter !== null &&
                Number(button.dataset.chapterId) ===
                Number(activeChapter.id);
            button.classList.toggle('active', active);
            if (active) {
                button.setAttribute(
                    'aria-current',
                    'page'
                );
            } else {
                button.removeAttribute('aria-current');
            }
        });
        document.querySelectorAll(
            '.sd-topic'
        ).forEach(button => {
            const active =
                currentPage === 2 &&
                activeSubchapter !== null &&
                Number(button.dataset.subchapterId) ===
                Number(activeSubchapter.id);
            button.classList.toggle('active', active);
            if (active) {
                button.setAttribute(
                    'aria-current',
                    'page'
                );
            } else {
                button.removeAttribute('aria-current');
            }
        });
    }
    // ======================================================
    // 06. PERPINDAHAN HALAMAN
    // ======================================================
    function showPage(page) {
        if (!Object.prototype.hasOwnProperty.call(
            sections,
            page
        )) {
            return;
        }
        if (currentPage === 2 && page !== 2) {
            quizVersion++;
        }
        currentPage = page;
        Object.entries(sections).forEach(
            ([key, section]) => {
                if (section) {
                    section.hidden =
                        Number(key) !== page;
                }
            }
        );
        if (
            page === 6 &&
            typeof window.saluangForumLoad === 'function'
        ) {
            window.saluangForumLoad();
        }
        updateActiveMenu();
        closeMobileSidebar();
        scrollTop();
        if (page === 0) {
            requestAnimationFrame(() => studentHome.fitActivityList());
        }
    }
    // ======================================================
    // 07. PENGENALAN BAB
    // ======================================================
    function openChapter(chapter) {
        if (!chapter) return;
        activeChapter = chapter;
        activeSubchapter = null;
        setText(
            document.querySelector(
                '#chapterPage .sd-breadcrumb'
            ),
            'Beranda › Materi › Bab ' +
            chapter.nomor_bab
        );
        setText(
            document.querySelector(
                '#chapterPage .sd-chapter-hero .sd-pill'
            ),
            'BAB ' + chapter.nomor_bab +
            ' · PENGENALAN'
        );
        setText(
            document.querySelector(
                '#chapterPage .sd-chapter-hero .sd-title'
            ),
            textOr(
                chapter.judul,
                'Bab Tanpa Judul'
            )
        );
        setText(
            document.querySelector(
                '#chapterPage .sd-chapter-hero .sd-copy'
            ),
            textOr(
                chapter.deskripsi,
                'Bab ini belum memiliki deskripsi.'
            )
        );
        const chapterImage = document.querySelector(
            '#chapterPage .sd-chapter-hero img'
        );
        if (chapterImage) {
            const introductionFigure = chapterImage.closest(
                '.sd-chapter-introduction-figure'
            );
            const uploadedImage = chapter.gambar_pengenalan_url;
            const legacyImage = Number(chapter.nomor_bab) === 1
                ? images.living
                : null;
            const imageUrl = uploadedImage || legacyImage;
            const introductionTitle = document.querySelector(
                '#sdChapterIntroductionTitle'
            );
            const introductionCaption = document.querySelector(
                '#sdChapterIntroductionCaption'
            );
            chapterImage.hidden = !imageUrl;
            if (introductionFigure) {
                introductionFigure.hidden = !imageUrl;
            }
            chapterImage.alt = `Gambar Pengenalan BAB ${chapter.nomor_bab}`;
            chapterImage.onerror = () => {
                chapterImage.hidden = true;
                if (introductionFigure) {
                    introductionFigure.hidden = true;
                }
                if (introductionCaption) {
                    introductionCaption.hidden = true;
                }
            };
            if (imageUrl) {
                chapterImage.src = imageUrl;
            } else {
                chapterImage.removeAttribute('src');
            }
            const title = String(
                chapter.gambar_pengenalan_judul || ''
            ).trim();
            if (introductionTitle && introductionCaption) {
                introductionTitle.textContent = title;
                introductionTitle.hidden = !title;
                introductionCaption.hidden = !imageUrl || !title;
            }
        }
        renderObjectives(chapter);
        renderPrompt(chapter);
        showPage(1);
    }
    // ======================================================
    // 08. TUJUAN PEMBELAJARAN BERNOMOR
    // ======================================================
    function renderObjectives(chapter) {
        const container = document.querySelector(
            '#chapterPage .sd-objectives'
        );
        if (!container) return;
        container.replaceChildren();
        let objectives = chapter.tujuan_pembelajaran;
        if (typeof objectives === 'string') {
            try {
                objectives = JSON.parse(objectives);
            } catch {
                objectives = [objectives];
            }
        }
        if (!Array.isArray(objectives)) {
            objectives = [];
        }
        objectives = objectives.filter(item =>
            String(item ?? '').trim()
        );
        if (!objectives.length) {
            container.appendChild(
                el(
                    'p',
                    'sd-copy',
                    'Tujuan pembelajaran belum diisi Guru.'
                )
            );
            return;
        }
        objectives.forEach((objective, index) => {
            const card = el(
                'div',
                'sd-objective'
            );
            const number = el(
                'span',
                'sd-objective-number',
                index + 1
            );
            const body = el(
                'div',
                'sd-objective-body'
            );
            body.appendChild(
                el('p', '', objective)
            );
            card.append(number, body);
            container.appendChild(card);
        });
    }
    // ======================================================
    // 09. PERTANYAAN PEMANTIK TERPISAH
    // ======================================================
    function renderPrompt(chapter) {
        const container = document.querySelector(
            '#chapterPage .sd-prompt .sd-copy'
        );
        if (container) {
            container.replaceChildren();
            const raw = String(
                chapter.pertanyaan_pemantik || ''
            ).trim();
            if (!raw) {
                container.textContent =
                    'Belum ada pertanyaan untuk dipikirkan.';
            } else {
                const firstQuestion = raw
                    .split(/\r?\n/)
                    .map(text => text.trim())
                    .find(Boolean) || '';
                const cleaned = firstQuestion
                    .replace(
                        /^\s*(?:pertanyaan\s*)?\d+\s*[.):\-]?\s*/i,
                        ''
                    )
                    .replace(
                        /^\s*pertanyaan\s*[:\-]\s*/i,
                        ''
                    );
                container.textContent = cleaned;
            }
        }
        // Gambar rusak tidak ditampilkan.
        const picture = document.querySelector(
            '#chapterPage .sd-prompt-image'
        );
        if (picture) {
            picture.onerror = function () {
                this.hidden = true;
            };
            if (!picture.getAttribute('src')) {
                picture.hidden = true;
            }
        }
    }
    // ======================================================
    // 10. BUKA SUBBAB
    // ======================================================
    function openSubchapter(chapter, subchapter) {
        if (!chapter || !subchapter) return;
        activeChapter = chapter;
        activeSubchapter = subchapter;
        activeMaterialOpen = markMaterialOpened(subchapter.id);
        showPage(2);
        renderLesson();
    }
    async function markMaterialOpened(subchapterId) {
        try {
            const response = await fetch(
                `/siswa/subchapters/${encodeURIComponent(subchapterId)}/open`,
                {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                }
            );
            if (!response.ok) {
                throw new Error('Status materi gagal disimpan.');
            }
            await refreshDashboardProgress();
        } catch (error) {
            console.error('SALUANG:', error);
            const feedback = get('answerFeedback');
            if (feedback) {
                feedback.textContent = error.message;
                feedback.hidden = false;
            }
            return false;
        }
        return true;
    }
    async function completeMaterial() {
        const subchapterId = Number(activeSubchapter?.id);
        if (!subchapterId) return;
        const button = get('answerChoices')?.querySelector('button');
        if (button) button.disabled = true;
        try {
            const opened = await activeMaterialOpen;
            if (!opened) {
                throw new Error('Materi belum berhasil tercatat sebagai dipelajari.');
            }
            const response = await fetch(
                `/siswa/subchapters/${encodeURIComponent(subchapterId)}/complete`,
                {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                }
            );
            const result = await response.json();
            if (!response.ok) {
                throw new Error(result.message || 'Penyelesaian materi gagal disimpan.');
            }
            showQuizMessage('Materi berhasil diselesaikan dan progres telah tersimpan.');
            await refreshDashboardProgress();
        } catch (error) {
            const feedback = get('answerFeedback');
            if (feedback) {
                feedback.textContent = error.message;
                feedback.hidden = false;
            }
            if (button) button.disabled = false;
        }
    }
    function renderLessonNavigation() {
        const index = findStepIndex();
        document.querySelectorAll(
            '[data-lesson-navigation]'
        ).forEach(container => {
            container.replaceChildren();
            if (index < 0) return;
            const previous = el(
                'button',
                'sd-nav-btn'
            );
            previous.type = 'button';
            previous.dataset.materialNav = 'previous';
            previous.setAttribute('aria-label', 'Previous');
            previous.append(
                el('i', 'bi bi-chevron-left'),
                document.createTextNode(' Previous')
            );
            const counter = el(
                'span',
                'sd-counter',
                'Materi ' + (
                    materialSteps
                        .slice(0, index + 1)
                        .filter(step => step.type === 'subchapter')
                        .length
                ) + ' dari ' + (
                    materialSteps
                        .filter(step => step.type === 'subchapter')
                        .length
                )
            );
            const next = el(
                'button',
                'sd-nav-btn next'
            );
            next.type = 'button';
            next.dataset.materialNav = 'next';
            next.setAttribute('aria-label', 'Next');
            next.append(
                document.createTextNode('Next '),
                el('i', 'bi bi-chevron-right')
            );
            container.append(
                previous,
                counter,
                next
            );
        });
    }
    // ======================================================
    // 11. TAMPILKAN MATERI SEPERTI BUKU DIGITAL
    // ======================================================
    function renderLesson() {
        if (!activeChapter || !activeSubchapter) {
            return;
        }
        const chapterNumber = activeChapter.nomor_bab;
        const subNumber = activeSubchapter.nomor_subbab;
        const code = chapterNumber + '.' + subNumber;
        const title = code + ' ' + textOr(
            activeSubchapter.judul,
            'Subbab Tanpa Judul'
        );
        setText(
            get('lessonBreadcrumb'),
            'Beranda › Materi › Bab ' +
            chapterNumber + ' › ' + title
        );
        setText(
            get('lessonBadge'),
            'BAB ' + chapterNumber +
            ' · SUBBAB ' + code
        );
        setText(
            get('lessonTitle'),
            title
        );
        // PERBAIKAN PENTING:
        // Jangan gunakan textContent untuk HTML Guru.
        // Render hanya elemen pemformatan yang diizinkan.
        const hasOrderedBlocks = Array.isArray(
            activeSubchapter.content_blocks
        );
        if (hasOrderedBlocks) {
            studentMaterial.renderMaterialBlocks(
                get('lessonContent'),
                activeSubchapter.content_blocks
            );
        }
        const fact = document.querySelector(
            '#lessonPage .sd-fact'
        );
        if (fact) {
            fact.hidden = true;
        }
        const media = document.querySelector(
            '#lessonPage .sd-material-media'
        );
        const mediaItems = get('lessonMediaItems');
        let hasMedia = false;
        if (hasOrderedBlocks) {
            if (mediaItems) mediaItems.replaceChildren();
        } else {
            studentMaterial.renderMaterialContent(
                get('lessonContent'),
                activeSubchapter.konten
            );
            hasMedia = studentMaterial.renderSubchapterMedia(
                mediaItems,
                activeSubchapter.media
            );
        }
        if (media) media.hidden = hasOrderedBlocks || !hasMedia;
        renderLessonNavigation();
        currentQuiz = [];
        currentQuizContentCount = 0;
        currentQuizAttemptId = null;
        quizAttemptCompleted = false;
        questionIndex = 0;
        savedAnswers.clear();
        activityAnswers.clear();
        quizAnswerPending = false;
        showQuizMessage(
            'Memuat soal kuis dari Guru...'
        );
        loadPublishedQuiz();
    }
    // ======================================================
    // 12. KUIS DARI DATABASE
    // ======================================================
    async function loadPublishedQuiz() {
        if (!activeChapter || !activeSubchapter) return;
        const version = ++quizVersion;
        const chapterId = Number(activeChapter.id);
        const subchapterId = Number(activeSubchapter.id);
        const url = '/siswa/quiz/subchapters/' +
            encodeURIComponent(activeSubchapter.id) + '/questions';
        try {
            let response = await fetch(url, {
                credentials: 'same-origin',
                signal: AbortSignal.timeout(15000),
                headers: {
                    'Accept': 'application/json'
                }
            });
            if (!response.ok) {
                throw new Error(
                    'Gagal mengambil Kuis dari Guru.'
                );
            }
            let result = await response.json();
            if (
                version !== quizVersion ||
                currentPage !== 2 ||
                Number(activeChapter?.id) !== chapterId ||
                Number(activeSubchapter?.id) !== subchapterId
            ) {
                return;
            }
            let quizQuestions = (
                Array.isArray(result.questions)
                    ? result.questions
                    : []
            );
            savedAnswers.clear();
            activityAnswers.clear();
            if (!result.attempt_storage_available) {
                showQuizMessage(
                    'Kuis tersedia, tetapi penyimpanan attempt belum dipasang pada database aplikasi. Hubungi administrator sebelum mulai atau mengulang Kuis.'
                );
                return;
            }
            const restart = restartQuizOnFirstOpen;
            if (quizQuestions.length) restartQuizOnFirstOpen = false;
            let attemptId = result.active_attempt?.id || null;
            if (quizQuestions.length && (restart || !attemptId)) {
                attemptId = await createQuizAttempt(
                    version,
                    subchapterId,
                    restart
                );
                if (!attemptId) return;
                response = await fetch(
                    `${url}?attempt_id=${encodeURIComponent(attemptId)}`,
                    {
                        credentials: 'same-origin',
                        signal: AbortSignal.timeout(15000),
                        headers: {
                            'Accept': 'application/json'
                        }
                    }
                );
                if (!response.ok) {
                    throw new Error('Gagal memuat ulang soal Kuis yang aktif.');
                }
                const attemptResult = await response.json();
                if (
                    version !== quizVersion ||
                    currentPage !== 2 ||
                    Number(activeChapter?.id) !== chapterId ||
                    Number(activeSubchapter?.id) !== subchapterId
                ) {
                    return;
                }
                quizQuestions.splice(
                    0,
                    quizQuestions.length,
                    ...(Array.isArray(attemptResult.questions)
                        ? attemptResult.questions
                        : [])
                );
                result.active_attempt = attemptResult.active_attempt;
            }
            currentQuiz = quizQuestions
                .sort((a, b) => Number(a.nomor_soal) - Number(b.nomor_soal))
                .map((item, index) => {
                    const displayNumber = index + 1;
                    if (item.activity_type === 'multiple_choice') {
                        if (item.student_answer) {
                            savedAnswers.set(
                                subchapterId + ':' + item.id,
                                {
                                    selected: 'ABCD'.indexOf(item.student_answer.answer),
                                    correct: item.answer_feedback?.correct,
                                    correctAnswer: item.answer_feedback?.correct_answer,
                                    explanation: item.answer_feedback?.explanation
                                }
                            );
                        }
                        return {
                            id: item.id,
                            activity_type: 'multiple_choice',
                            type: 'multiple_choice',
                            question: item.pertanyaan,
                            options: [
                                item.pilihan_a,
                                item.pilihan_b,
                                item.pilihan_c,
                                item.pilihan_d
                            ],
                            display_number: displayNumber
                        };
                    }

                    activityState({
                        ...item,
                        display_number: displayNumber
                    }, item.student_answer, item.activity_feedback || null);
                    return {
                        ...item,
                        type: item.activity_type,
                        question: item.pertanyaan,
                        display_number: displayNumber
                    };
                });
            currentQuizContentCount = currentQuiz.length;
            questionIndex = 0;
            currentQuizAttemptId = attemptId || result.active_attempt?.id || null;
            quizAttemptCompleted = false;
            if (!currentQuiz.length) {
                renderQuestion();
                return;
            }
            renderQuestion();
        } catch (error) {
            if (
                version === quizVersion &&
                currentPage === 2
            ) {
                showQuizMessage(
                    error.name === 'TimeoutError'
                        ? 'Koneksi ke server terlalu lama. Buka kembali subbab untuk mencoba lagi.'
                        : error.message
                );
            }
        }
        async function createQuizAttempt(version, subchapterId, restart) {
            const opened = await activeMaterialOpen;
            if (
                version !== quizVersion
                || currentPage !== 2
                || Number(activeSubchapter?.id) !== subchapterId
            ) {
                return null;
            }
            if (!opened) {
                throw new Error('Materi belum berhasil tercatat sebagai dipelajari.');
            }
            const response = await fetch(
                `/siswa/quiz/subchapters/${encodeURIComponent(subchapterId)}/attempts`,
                {
                    method: 'POST',
                    credentials: 'same-origin',
                    signal: AbortSignal.timeout(15000),
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector(
                            'meta[name="csrf-token"]'
                        )?.content || ''
                    },
                    body: JSON.stringify({ restart })
                }
            );
            const result = await response.json();
            if (!response.ok) {
                throw new Error(result.message || 'Sesi Kuis baru gagal dimulai.');
            }
            const newAttemptId = Number(result.attempt?.id);
            if (!Number.isSafeInteger(newAttemptId) || newAttemptId <= 0) {
                throw new Error('Sesi kuis belum berhasil dibuat. Silakan buka ulang subbab ini.');
            }
            return newAttemptId;
        }
    }
    function activityState(activity, savedAnswer = null, savedFeedback = null) {
        if (!activityAnswers.has(activity.id)) {
            const rightOrder = (activity.right_items || []).map(item => item.id);
            let answers = savedAnswer || (
                activity.activity_type === 'matching' ? [] : {}
            );
            if (
                activity.activity_type === 'matching'
                && !Array.isArray(answers)
            ) {
                answers = Object.entries(answers).flatMap(([leftId, rightId]) =>
                    (Array.isArray(rightId) ? rightId : [rightId]).map(value => ({
                        left_id: leftId,
                        right_id: value
                    }))
                );
            }
            activityAnswers.set(activity.id, {
                answers,
                selected: null,
                error: '',
                pending: false,
                saving: false,
                feedback: savedFeedback,
                saveChain: Promise.resolve(),
                version: 0,
                savedVersion: savedAnswer === null ? -1 : 0,
                rightOrder,
                suppressClickUntil: 0
            });
        }
        return activityAnswers.get(activity.id);
    }
    function bindTouchDrag(card, itemId, state, onDrop) {
        let startX = 0;
        let startY = 0;
        let dragging = false;
        let activeZone = null;
        let moveHandler;
        let upHandler;

        card.addEventListener('pointerdown', event => {
            if (event.pointerType !== 'touch' || state.pending) return;
            startX = event.clientX;
            startY = event.clientY;
            dragging = false;
            activeZone = null;
            moveHandler = moveEvent => {
                if (!dragging && Math.hypot(
                    moveEvent.clientX - startX,
                    moveEvent.clientY - startY
                ) > 8) {
                    dragging = true;
                    card.classList.add('is-dragging');
                }
                if (!dragging) return;
                moveEvent.preventDefault();
                const nextZone = document.elementFromPoint(
                    moveEvent.clientX,
                    moveEvent.clientY
                )?.closest('[data-activity-dropzone]');
                if (activeZone && activeZone !== nextZone) {
                    activeZone.classList.remove('is-drop-target');
                }
                activeZone = nextZone;
                activeZone?.classList.add('is-drop-target');
            };
            upHandler = upEvent => {
                document.removeEventListener('pointermove', moveHandler);
                document.removeEventListener('pointerup', upHandler);
                document.removeEventListener('pointercancel', upHandler);
                card.classList.remove('is-dragging');
                activeZone?.classList.remove('is-drop-target');
                if (upEvent.type === 'pointercancel') return;
                if (!dragging) return;
                upEvent.preventDefault();
                state.suppressClickUntil = Date.now() + 400;
                const zone = document.elementFromPoint(
                    upEvent.clientX,
                    upEvent.clientY
                )?.closest('[data-activity-dropzone]');
                if (zone) onDrop(itemId, zone);
            };
            document.addEventListener('pointermove', moveHandler, { passive: false });
            document.addEventListener('pointerup', upHandler, { once: true });
            document.addEventListener('pointercancel', upHandler, { once: true });
        });
    }
    function highlightDropZone(zone) {
        zone.addEventListener('dragenter', event => {
            event.preventDefault();
            zone.classList.add('is-drop-target');
        });
        zone.addEventListener('dragover', event => {
            event.preventDefault();
            if (event.dataTransfer) event.dataTransfer.dropEffect = 'move';
            zone.classList.add('is-drop-target');
        });
        zone.addEventListener('dragleave', event => {
            if (!zone.contains(event.relatedTarget)) {
                zone.classList.remove('is-drop-target');
            }
        });
        zone.addEventListener('drop', () => zone.classList.remove('is-drop-target'));
    }
    function addActivityImage(parent, item) {
        if (!item.image_url) {
            parent.classList.add('is-text-only');
            return;
        }
        parent.classList.add('has-image');
        const frame = el('span', 'sd-activity-image-frame');
        const image = el('img', 'sd-activity-image');
        image.src = item.image_url;
        image.alt = item.label || 'Gambar aktivitas';
        image.loading = 'lazy';
        image.draggable = false;
        image.onerror = () => {
            frame.replaceChildren(el('span', 'sd-activity-image-fallback', 'Gambar tidak tersedia'));
        };
        frame.appendChild(image);
        parent.appendChild(frame);
    }
    function updateActivityAnswer(activity, state, itemId, targetId) {
        if (quizAttemptCompleted || !currentQuizAttemptId) return;
        if (targetId === undefined) {
            delete state.answers[itemId];
        } else {
            state.answers[itemId] = targetId;
        }
        state.selected = null;
        state.error = '';
        state.feedback = null;
        state.version++;
        renderQuestion();
        saveActivityAnswer(activity, state);
    }
    function saveActivityAnswer(activity, state) {
        if (!activeSubchapter || !currentQuizAttemptId || quizAttemptCompleted) return;
        const subchapterId = Number(activeSubchapter.id);
        const attemptId = currentQuizAttemptId;
        const requestVersion = quizVersion;
        const version = state.version;
        const answers = JSON.parse(JSON.stringify(state.answers));
        state.saving = true;
        state.error = '';
        renderQuestion();
        const send = async () => {
            try {
                if (
                    currentQuizAttemptId !== attemptId
                    || quizVersion !== requestVersion
                    || quizAttemptCompleted
                ) {
                    return;
                }
                const opened = await activeMaterialOpen;
                if (!opened) {
                    throw new Error('Materi belum siap disimpan.');
                }
                if (
                    currentQuizAttemptId !== attemptId
                    || quizVersion !== requestVersion
                    || quizAttemptCompleted
                ) {
                    return;
                }
                const response = await fetch(
                    `/siswa/quiz/activities/${encodeURIComponent(activity.id)}/answers`,
                    {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector(
                                'meta[name="csrf-token"]'
                            )?.content || ''
                        },
                        body: JSON.stringify({
                            attempt_id: attemptId,
                            answers
                        })
                    }
                );
                const result = await response.json();
                if (!response.ok) {
                    if (
                        response.status === 409
                        && currentQuizAttemptId === attemptId
                        && quizVersion === requestVersion
                    ) {
                        quizAttemptCompleted = true;
                        state.saving = false;
                        state.error = '';
                        renderQuestion();
                        try {
                            await refreshDashboardProgress();
                        } catch (progressError) {
                            console.error('Quiz progress refresh failed:', progressError);
                        }
                        return;
                    }
                    throw new Error(
                        'Progres aktivitas belum tersimpan.'
                    );
                }
                if (
                    currentQuizAttemptId === attemptId
                    && quizVersion === requestVersion
                    && state.version === version
                ) {
                    state.savedVersion = version;
                    state.saving = false;
                    state.error = '';
                    state.feedback = result.activity_feedback || null;
                    if (
                        currentPage === 2
                        && Number(activeSubchapter?.id) === subchapterId
                    ) {
                        renderQuestion();
                    }
                }
                if (
                    result.completed
                    && currentQuizAttemptId === attemptId
                    && quizVersion === requestVersion
                    && currentPage === 2
                    && Number(activeSubchapter?.id) === subchapterId
                ) {
                    quizAttemptCompleted = true;
                    renderQuestion();
                    try {
                        await refreshDashboardProgress();
                    } catch (progressError) {
                        console.error('Quiz progress refresh failed:', progressError);
                    }
                }
            } catch (error) {
                if (
                    currentQuizAttemptId === attemptId
                    && quizVersion === requestVersion
                    && state.version === version
                ) {
                    state.saving = false;
                    state.error = 'Progres belum tersimpan. Periksa koneksi, lalu coba lagi.';
                    console.error('Quiz activity autosave failed:', error);
                    if (
                        currentPage === 2
                        && Number(activeSubchapter?.id) === subchapterId
                    ) {
                        renderQuestion();
                    }
                }
            }
        };
        state.saveChain = state.saveChain.then(send, send);
    }
    function toggleMatchingPair(activity, state, leftId, rightId) {
        if (quizAttemptCompleted || !currentQuizAttemptId) return;
        const existingIndex = state.answers.findIndex(pair =>
            pair.left_id === leftId && pair.right_id === rightId
        );
        if (existingIndex >= 0) {
            state.answers.splice(existingIndex, 1);
        } else {
            state.answers.push({ left_id: leftId, right_id: rightId });
        }
        state.selected = null;
        state.error = '';
        state.feedback = null;
        state.version++;
        renderQuestion();
        saveActivityAnswer(activity, state);
    }
    function renderActivityFeedback(activity, feedback) {
        if (!feedback) return null;
        const panel = el('section', 'sd-activity-feedback');
        panel.setAttribute('aria-live', 'polite');
        if (activity.activity_type === 'drag_drop') {
            const results = feedback.items || [];
            results.forEach(item => {
                const row = el('p', `sd-activity-feedback-row ${item.is_correct ? 'is-correct' : 'is-incorrect'}`);
                const name = item.item || 'Objek';
                const category = item.student_category;
                row.textContent = item.is_correct
                    ? `${name} sudah tepat di kategori ${category}.`
                    : category
                        ? `${name} belum tepat di kategori ${category}.`
                        : `${name} belum ditempatkan.`;
                panel.appendChild(row);
            });
            // Pola sama dengan Menghubungkan: koreksi hanya muncul bila ada jawaban keliru.
            if (results.some(item => !item.is_correct)) {
                panel.appendChild(el('p', 'sd-activity-feedback-correction', 'Kategori yang tepat:'));
                results.forEach(item => {
                    const name = item.item || 'Objek';
                    const correctCategory = item.correct_category || 'belum tersedia';
                    panel.appendChild(el('p', 'sd-activity-feedback-row is-correct', `${name} di kategori ${correctCategory}.`));
                });
            }
            return panel;
        }

        (feedback.student_pairs || []).forEach(pair => {
            const row = el('p', `sd-activity-feedback-row ${pair.is_correct ? 'is-correct' : 'is-incorrect'}`);
            row.textContent = pair.is_correct
                ? `Pasangan ${pair.left} dan ${pair.right} sudah tepat.`
                : `Pasangan ${pair.left} dan ${pair.right} belum tepat.`;
            panel.appendChild(row);
        });
        if ((feedback.student_pairs || []).some(pair => !pair.is_correct)) {
            panel.appendChild(el('p', 'sd-activity-feedback-correction', 'Pasangan yang tepat:'));
            (feedback.correct_pairs || []).forEach(pair => {
                panel.appendChild(el('p', 'sd-activity-feedback-row is-correct', `${pair.left} dengan ${pair.right}.`));
            });
        }
        return panel;
    }
    function renderDragDropActivity(container, activity, state) {
        const board = el('div', 'sd-drag-drop-board');
        const items = el('div', 'sd-activity-token-list');
        items.classList.add('sd-activity-source-items');
        const visibleObjectCount = (activity.items || []).filter(
            item => state.answers[item.id] === undefined
        ).length;
        items.style.setProperty('--sd-object-columns', String(Math.max(1, Math.min(visibleObjectCount, 4))));
        (activity.items || [])
            .filter(item => state.answers[item.id] === undefined)
            .forEach(item => {
                const button = el('button', 'sd-activity-token is-drag-card');
                button.type = 'button';
                button.draggable = !state.pending;
                button.setAttribute('aria-label', `Seret gambar ${item.label} atau pilih objek`);
                button.setAttribute('aria-pressed', String(state.selected === item.id));
                if (state.selected === item.id) button.classList.add('is-selected');
                addActivityImage(button, item);
                button.appendChild(el('span', 'sd-activity-caption', item.label));
                button.addEventListener('click', () => {
                    if (state.pending) return;
                    if (state.suppressClickUntil > Date.now()) {
                        state.suppressClickUntil = 0;
                        return;
                    }
                    state.selected = item.id;
                    renderQuestion();
                });
                button.addEventListener('dragstart', event => {
                    button.classList.add('is-dragging');
                    event.dataTransfer?.setData('text/plain', item.id);
                    if (event.dataTransfer) {
                        event.dataTransfer.effectAllowed = 'move';
                        const rect = button.getBoundingClientRect();
                        event.dataTransfer.setDragImage(
                            button,
                            rect.width / 2,
                            rect.height / 2
                        );
                    }
                });
                button.addEventListener('dragend', () => button.classList.remove('is-dragging'));
                bindTouchDrag(button, item.id, state, (draggedId, zone) => {
                    updateActivityAnswer(
                        activity,
                        state,
                        draggedId,
                        zone.dataset.categoryId || undefined
                    );
                });
                items.appendChild(button);
            });
        const source = el('section', 'sd-activity-source');
        source.dataset.activityDropzone = 'source';
        source.setAttribute('aria-label', 'Objek yang belum dimasukkan kategori');
        source.appendChild(el('strong', 'sd-activity-source-title', 'Objek'));
        source.appendChild(items);
        source.addEventListener('click', event => {
            if (event.target.closest('.sd-activity-token')) return;
            if (state.pending || !state.selected) return;
            updateActivityAnswer(activity, state, state.selected, undefined);
        });
        source.addEventListener('dragover', event => event.preventDefault());
        highlightDropZone(source);
        source.addEventListener('drop', event => {
            event.preventDefault();
            if (state.pending) return;
            const itemId = event.dataTransfer?.getData('text/plain');
            if ((activity.items || []).some(item => item.id === itemId)) {
                updateActivityAnswer(activity, state, itemId, undefined);
            }
        });
        board.appendChild(source);
        const categories = el('div', 'sd-activity-target-grid');
        (activity.categories || []).forEach(category => {
            const target = el('section', 'sd-activity-target');
            target.dataset.activityDropzone = 'category';
            target.dataset.categoryId = category.id;
            target.setAttribute('role', 'group');
            target.setAttribute('aria-label', `Kategori ${category.label}`);
            target.appendChild(el('strong', '', category.label));
            const assignedItems = (activity.items || []).filter(
                item => state.answers[item.id] === category.id
            );
            const assignedList = el('div', 'sd-activity-assigned-list');
            assignedItems.forEach(item => {
                const assigned = el('div', 'sd-activity-assigned');
                assigned.draggable = !state.pending;
                assigned.tabIndex = state.pending ? -1 : 0;
                assigned.setAttribute('role', 'button');
                assigned.setAttribute(
                    'aria-label',
                    `Objek ${item.label}, berada di kategori ${category.label}. Pilih atau seret untuk memindahkan.`
                );
                assigned.setAttribute(
                    'aria-pressed',
                    String(state.selected === item.id)
                );
                if (state.selected === item.id) assigned.classList.add('is-selected');
                addActivityImage(assigned, item);
                assigned.appendChild(el('span', 'sd-activity-caption', item.label));
                assigned.addEventListener('click', event => {
                    event.stopPropagation();
                    if (state.pending) return;
                    if (state.suppressClickUntil > Date.now()) {
                        state.suppressClickUntil = 0;
                        return;
                    }
                    if (state.selected && state.selected !== item.id) {
                        updateActivityAnswer(
                            activity,
                            state,
                            state.selected,
                            category.id
                        );
                        return;
                    }
                    state.selected = item.id;
                    renderQuestion();
                });
                assigned.addEventListener('keydown', event => {
                    if (!['Enter', ' '].includes(event.key) || state.pending) return;
                    event.preventDefault();
                    state.selected = item.id;
                    renderQuestion();
                });
                assigned.addEventListener('dragstart', event => {
                    event.stopPropagation();
                    assigned.classList.add('is-dragging');
                    event.dataTransfer?.setData('text/plain', item.id);
                    if (event.dataTransfer) {
                        event.dataTransfer.effectAllowed = 'move';
                        const rect = assigned.getBoundingClientRect();
                        event.dataTransfer.setDragImage(
                            assigned,
                            rect.width / 2,
                            rect.height / 2
                        );
                    }
                });
                assigned.addEventListener('dragend', () =>
                    assigned.classList.remove('is-dragging')
                );
                bindTouchDrag(assigned, item.id, state, (draggedId, zone) => {
                    updateActivityAnswer(
                        activity,
                        state,
                        draggedId,
                        zone.dataset.categoryId || undefined
                    );
                });
                assignedList.appendChild(assigned);
            });
            target.appendChild(assignedList);
            target.addEventListener('click', () => {
                if (state.pending || !state.selected) return;
                updateActivityAnswer(
                    activity,
                    state,
                    state.selected,
                    category.id
                );
            });
            target.addEventListener('dragover', event => event.preventDefault());
            highlightDropZone(target);
            target.addEventListener('drop', event => {
                event.preventDefault();
                if (state.pending) return;
                const itemId = event.dataTransfer?.getData('text/plain');
                if ((activity.items || []).some(item => item.id === itemId)) {
                    updateActivityAnswer(activity, state, itemId, category.id);
                }
            });
            categories.appendChild(target);
        });
        board.appendChild(categories);
        container.appendChild(board);
    }
    function renderMatchingActivity(container, activity, state) {
        const columns = el('div', 'sd-matching-columns');
        const lines = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        lines.classList.add('sd-matching-lines');
        lines.setAttribute('aria-hidden', 'true');
        const leftColumn = el('div', 'sd-activity-token-list sd-matching-left');
        (activity.left_items || []).forEach(item => {
            const row = el('div', 'sd-matching-item-row is-left');
            row.dataset.leftItem = item.id;
            const button = el('div', 'sd-matching-card');
            button.setAttribute('aria-label', `Kartu ${item.label}`);
            addActivityImage(button, item);
            button.appendChild(el('span', 'sd-activity-caption', item.label));
            const dot = el('span', 'sd-matching-dot sd-matching-left-dot');
            if (state.selected === item.id) dot.classList.add('is-selected');
            dot.setAttribute('role', 'button');
            dot.tabIndex = 0;
            dot.setAttribute('aria-label', `Titik penghubung ${item.label}`);
            row.append(button, dot);
            const selectLeft = () => {
                state.selected = item.id;
                renderQuestion();
            };
            dot.addEventListener('click', event => {
                event.stopPropagation();
                selectLeft();
            });
            dot.addEventListener('keydown', event => {
                if (event.key !== 'Enter' && event.key !== ' ') return;
                event.preventDefault();
                selectLeft();
            });
            dot.addEventListener('pointerdown', event => {
                if (state.pending) return;
                event.stopPropagation();
                state.selected = item.id;
                dot.classList.add('is-selected');
                const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                line.classList.add('sd-matching-line', 'is-temporary');
                lines.appendChild(line);
                const updateTemporary = pointerEvent => {
                    const rect = columns.getBoundingClientRect();
                    const dotRect = dot.getBoundingClientRect();
                    line.setAttribute('x1', String(dotRect.left + dotRect.width / 2 - rect.left));
                    line.setAttribute('y1', String(dotRect.top + dotRect.height / 2 - rect.top));
                    line.setAttribute('x2', String(pointerEvent.clientX - rect.left));
                    line.setAttribute('y2', String(pointerEvent.clientY - rect.top));
                };
                const onPointerMove = pointerEvent => updateTemporary(pointerEvent);
                const onPointerUp = pointerEvent => {
                    document.removeEventListener('pointermove', onPointerMove);
                    document.removeEventListener('pointerup', onPointerUp);
                    document.removeEventListener('pointercancel', onPointerUp);
                    line.remove();
                    if (pointerEvent.type === 'pointercancel') {
                        return;
                    }
                    const targetDot = document.elementFromPoint(
                        pointerEvent.clientX,
                        pointerEvent.clientY
                    )?.closest('.sd-matching-right-dot');
                    const target = targetDot?.closest('[data-right-item]');
                    if (target && !state.pending) {
                        toggleMatchingPair(
                            activity,
                            state,
                            item.id,
                            target.dataset.rightItem
                        );
                    } else {
                        updateLines();
                    }
                };
                document.addEventListener('pointermove', onPointerMove);
                document.addEventListener('pointerup', onPointerUp, { once: true });
                document.addEventListener('pointercancel', onPointerUp, { once: true });
            });
            leftColumn.appendChild(row);
        });
        const rightColumn = el('div', 'sd-activity-token-list sd-matching-right');
        const rightItemsById = new Map(
            (activity.right_items || []).map(item => [item.id, item])
        );
        state.rightOrder.forEach(id => {
            const item = rightItemsById.get(id);
            if (!item) return;
            const row = el('div', 'sd-matching-item-row is-right');
            row.dataset.rightItem = item.id;
            const button = el('div', 'sd-matching-card');
            button.setAttribute('aria-label', `Kartu pasangan ${item.label}.`);
            const dot = el('span', 'sd-matching-dot sd-matching-right-dot');
            if (state.selected) dot.classList.add('is-target-ready');
            dot.setAttribute('role', 'button');
            dot.tabIndex = 0;
            dot.setAttribute('aria-label', `Titik pasangan ${item.label}`);
            addActivityImage(button, item);
            button.appendChild(el('span', 'sd-activity-caption', item.label));
            row.append(dot, button);
            const selectRight = () => {
                if (!state.selected) return;
                toggleMatchingPair(
                    activity,
                    state,
                    state.selected,
                    item.id
                );
            };
            dot.addEventListener('click', event => {
                event.stopPropagation();
                selectRight();
            });
            dot.addEventListener('keydown', event => {
                if (event.key !== 'Enter' && event.key !== ' ') return;
                event.preventDefault();
                selectRight();
            });
            rightColumn.appendChild(row);
        });
        columns.append(lines, leftColumn, rightColumn);
        container.appendChild(columns);
        const updateLines = () => {
            const rect = columns.getBoundingClientRect();
            if (!rect.width || !rect.height) return;
            lines.setAttribute('width', String(rect.width));
            lines.setAttribute('height', String(rect.height));
            lines.setAttribute('viewBox', `0 0 ${rect.width} ${rect.height}`);
            lines.replaceChildren();
            state.answers.forEach(({ left_id: leftId, right_id: rightId }) => {
                const leftDot = columns.querySelector(
                    `[data-left-item="${CSS.escape(leftId)}"] .sd-matching-left-dot`
                );
                const rightDot = columns.querySelector(
                    `[data-right-item="${CSS.escape(rightId)}"] .sd-matching-right-dot`
                );
                if (!leftDot || !rightDot) return;
                const leftRect = leftDot.getBoundingClientRect();
                const rightRect = rightDot.getBoundingClientRect();
                const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                line.classList.add('sd-matching-line');
                line.setAttribute('x1', String(leftRect.left + leftRect.width / 2 - rect.left));
                line.setAttribute('y1', String(leftRect.top + leftRect.height / 2 - rect.top));
                line.setAttribute('x2', String(rightRect.left + rightRect.width / 2 - rect.left));
                line.setAttribute('y2', String(rightRect.top + rightRect.height / 2 - rect.top));
                lines.appendChild(line);
            });
        };
        matchingLineUpdaters.add(updateLines);
        requestAnimationFrame(updateLines);
    }
    function showQuizMessage(message) {
        setText(get('questionCount'), '');
        setText(get('questionText'), message);
        get('answerChoices')?.replaceChildren();
        const feedback = get('answerFeedback');
        if (feedback) {
            feedback.hidden = true;
            feedback.replaceChildren();
        }
        if (get('previousQuestion')) {
            get('previousQuestion').disabled = true;
        }
        if (get('nextQuestion')) {
            get('nextQuestion').hidden = true;
        }
    }
    function getAnswer(questionId) {
        if (!activeSubchapter) return null;
        return savedAnswers.get(
            activeSubchapter.id + ':' + questionId
        ) || null;
    }
    function activityIsComplete(activity, state) {
        if (activity.activity_type === 'drag_drop') {
            return (activity.items || []).every(item =>
                state.answers[item.id] !== undefined
                && (activity.categories || []).some(category =>
                    category.id === state.answers[item.id]
                )
            );
        }
        return state.answers.length >= Number(activity.required_answer_count)
            && (activity.left_items || []).every(item =>
                state.answers.some(pair => pair.left_id === item.id)
            );
    }
    function questionIsAnswered(question) {
        if (question.activity_type === 'multiple_choice') {
            return Boolean(getAnswer(question.id));
        }
        const state = activityAnswers.get(question.id);
        return Boolean(
            state
            && activityIsComplete(question, state)
            && !state.saving
            && !state.error
            && state.savedVersion === state.version
        );
    }
    function renderQuestion() {
        if (!currentQuiz.length) {
            showQuizMessage(
                'Subbab ini tidak memiliki kuis wajib. Selesaikan materi setelah membacanya.'
            );
            const completeButton = el('button', 'sd-primary', 'Tandai materi selesai');
            completeButton.type = 'button';
            completeButton.addEventListener('click', completeMaterial);
            get('answerChoices')?.appendChild(completeButton);
            return;
        }
        const question = currentQuiz[questionIndex];
        if (!question) return;
        const previousButton = get('previousQuestion');
        const nextButton = get('nextQuestion');
        if (previousButton) previousButton.hidden = false;
        if (nextButton) nextButton.hidden = false;
        if (previousButton) {
            previousButton.setAttribute('aria-label', 'Previous');
            previousButton.replaceChildren(
                el('i', 'bi bi-chevron-left'),
                document.createTextNode(' Previous')
            );
        }
        if (nextButton) {
            nextButton.setAttribute('aria-label', 'Next');
            nextButton.replaceChildren(
                document.createTextNode('Next '),
                el('i', 'bi bi-chevron-right')
            );
        }
        const typeLabel = question.activity_type === 'multiple_choice'
            ? ''
            : question.activity_type === 'drag_drop'
                ? ' (Drag & Drop)'
                : ' (Menghubungkan)';
        setText(
            get('questionCount'),
            quizAttemptCompleted
                ? 'Kuis selesai · Jawaban dan progres tersimpan'
                : 'Soal ' + (question.display_number || questionIndex + 1) +
                ' dari ' + (currentQuizContentCount || currentQuiz.length) +
                typeLabel
        );
        setText(
            get('questionText'),
            question.question || ''
        );
        const container = get('answerChoices');
        if (!container) return;
        container.replaceChildren();
        if (question.activity_type !== 'multiple_choice') {
            matchingLineUpdaters.clear();
            const state = activityState(question);
            const activityContent = el('div', 'sd-formative-content');
            const activityCard = el('section', 'sd-formative-card');
            const normalizePrompt = value => String(value || '')
                .trim()
                .replace(/\s+/g, ' ')
                .toLocaleLowerCase('id');
            const questionPrompt = normalizePrompt(question.question);
            const instruction = String(question.instruction || '').trim();
            if (instruction && normalizePrompt(instruction) !== questionPrompt) {
                activityContent.appendChild(el(
                    'p',
                    'sd-formative-instruction',
                    instruction
                ));
            } else if (!instruction) {
                activityContent.appendChild(el(
                    'p',
                    'sd-formative-hint',
                    question.activity_type === 'drag_drop'
                        ? 'Seret setiap objek ke kategorinya.'
                        : 'Pilih satu kartu, lalu hubungkan dengan satu atau beberapa pasangan.'
                ));
            }
            if (question.activity_type === 'drag_drop') {
                renderDragDropActivity(activityCard, question, state);
            } else {
                renderMatchingActivity(activityCard, question, state);
            }
            if (quizAttemptCompleted) {
                activityCard.classList.add('is-locked');
                activityCard.setAttribute('aria-disabled', 'true');
            }
            activityContent.appendChild(activityCard);
            const status = el('p', 'sd-activity-save-status');
            status.setAttribute('role', 'status');
            status.setAttribute('aria-live', 'polite');
            if (state.error) {
                status.classList.add('is-error');
                status.textContent = `Progres belum tersimpan: ${state.error}`;
            } else if (state.saving) {
                status.textContent = 'Menyimpan progres jawaban...';
            } else if (
                activityIsComplete(question, state)
                && state.savedVersion === state.version
            ) {
                status.classList.add('is-saved');
                status.textContent = 'Jawaban tersimpan.';
            } else {
                status.hidden = true;
            }
            activityContent.appendChild(status);
            const feedback = renderActivityFeedback(question, state.feedback);
            if (feedback) activityContent.appendChild(feedback);
            container.appendChild(activityContent);
            if (get('answerFeedback')) get('answerFeedback').hidden = true;
            if (get('previousQuestion')) {
                get('previousQuestion').disabled =
                    questionIndex === 0 || state.saving;
            }
            if (get('nextQuestion')) {
                get('nextQuestion').hidden =
                    questionIndex >= currentQuiz.length - 1;
                get('nextQuestion').disabled =
                    !questionIsAnswered(question);
            }
            return;
        }
        const saved = getAnswer(question.id);
        question.options.forEach((option, index) => {
            const button = el(
                'button',
                'sd-choice'
            );
            button.type = 'button';
            button.disabled = Boolean(saved);
            if (saved && saved.selected === index) {
                button.classList.add('is-selected');
            }
            button.append(
                el(
                    'span',
                    'sd-letter',
                    String.fromCharCode(65 + index)
                ),
                el('span', '', option || '')
            );
            button.addEventListener('click', () => {
                checkStudentAnswer(question, index);
            });
            container.appendChild(button);
        });
        const feedback = get('answerFeedback');
        if (feedback) {
            feedback.hidden = true;
            feedback.className = 'sd-answer-feedback';
            feedback.replaceChildren();
        }
        if (saved && typeof saved.correct === 'boolean') {
            applyFeedback(saved);
        } else if (saved) {
            const feedback = get('answerFeedback');
            if (feedback) {
                feedback.replaceChildren(
                    el('strong', '', 'Jawaban tersimpan.'),
                    el('p', 'mb-0 mt-1', 'Kunci jawaban akan ditampilkan setelah Kuis selesai.')
                );
                feedback.className = 'sd-answer-feedback is-saved';
                feedback.hidden = false;
            }
        }
        if (get('previousQuestion')) {
            get('previousQuestion').disabled =
                questionIndex === 0;
        }
        if (get('nextQuestion')) {
            get('nextQuestion').hidden =
                questionIndex >= currentQuiz.length - 1;
            get('nextQuestion').disabled = !saved;
        }
    }
    async function checkStudentAnswer(
        question,
        selected
    ) {
        if (
            quizAnswerPending ||
            !activeSubchapter ||
            !currentQuizAttemptId ||
            quizAttemptCompleted ||
            getAnswer(question.id)
        ) {
            return;
        }
        quizAnswerPending = true;
        const attemptId = currentQuizAttemptId;
        const requestVersion = quizVersion;
        const subchapterId = Number(
            activeSubchapter.id
        );
        const questionId = Number(
            question.id
        );
        const buttons = document.querySelectorAll(
            '#answerChoices .sd-choice'
        );
        buttons.forEach(button => {
            button.disabled = true;
        });
        try {
            const opened = await activeMaterialOpen;
            if (!opened) {
                throw new Error('Materi belum siap disimpan.');
            }
            if (
                currentQuizAttemptId !== attemptId
                || quizVersion !== requestVersion
                || quizAttemptCompleted
            ) {
                return;
            }
            const response = await fetch(
                '/siswa/quiz/questions/' +
                encodeURIComponent(question.id) +
                '/check',
                {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type':
                            'application/json',
                        'Accept':
                            'application/json',
                        'X-CSRF-TOKEN':
                            document.querySelector(
                                'meta[name="csrf-token"]'
                            )?.content || ''
                    },
                    body: JSON.stringify({
                        attempt_id: attemptId,
                        jawaban: String.fromCharCode(
                            65 + selected
                        )
                    })
                }
            );
            const result = await response.json();
            if (!response.ok) {
                if (
                    response.status === 409
                    && currentQuizAttemptId === attemptId
                    && quizVersion === requestVersion
                ) {
                    quizAttemptCompleted = true;
                    renderQuestion();
                    await refreshDashboardProgress();
                    return;
                }
                throw new Error('Jawaban belum tersimpan.');
            }
            if (
                currentQuizAttemptId !== attemptId
                || quizVersion !== requestVersion
            ) {
                return;
            }
            const feedback = result.answer_feedback;
            savedAnswers.set(
                subchapterId + ':' + questionId,
                {
                    selected,
                    correct: feedback?.correct,
                    correctAnswer: feedback?.correct_answer,
                    explanation: feedback?.explanation
                }
            );
            if (result.completed) {
                if (
                    currentQuizAttemptId === attemptId
                    && quizVersion === requestVersion
                    &&
                    currentPage === 2
                    && Number(activeSubchapter?.id) === subchapterId
                    && Number(currentQuiz[questionIndex]?.id) === questionId
                ) {
                    quizAttemptCompleted = true;
                    renderQuestion();
                }
                await refreshDashboardProgress();
                return;
            }
            if (
                currentQuizAttemptId === attemptId
                && quizVersion === requestVersion
                &&
                currentPage === 2 &&
                Number(activeSubchapter?.id) ===
                subchapterId &&
                Number(
                    currentQuiz[questionIndex]?.id
                ) === questionId
            ) {
                renderQuestion();
            }
        } catch (error) {
            if (
                currentQuizAttemptId === attemptId
                && quizVersion === requestVersion
                &&
                currentPage === 2 &&
                Number(activeSubchapter?.id) ===
                subchapterId
            ) {
                const feedback = get('answerFeedback');
                if (feedback) {
                    feedback.className =
                        'sd-answer-feedback is-wrong';
                    feedback.textContent = 'Jawaban belum tersimpan. Periksa koneksi, lalu coba lagi.';
                    feedback.hidden = false;
                }
                console.error('Quiz answer save failed:', error);
                buttons.forEach(button => {
                    button.disabled = false;
                });
            }
        } finally {
            if (quizVersion === requestVersion) {
                quizAnswerPending = false;
            }
        }
    }
    function applyFeedback(answer) {
        if (!answer || typeof answer.correct !== 'boolean') return;
        const correctIndex = 'ABCD'.indexOf(
            answer.correctAnswer
        );
        get('answerChoices')
            ?.querySelectorAll('.sd-choice')
            .forEach((button, index) => {
                button.classList.toggle(
                    'correct',
                    index === correctIndex
                );
                button.classList.toggle(
                    'wrong',
                    index === answer.selected &&
                    !answer.correct
                );
                button.disabled = true;
            });
        const feedback = get('answerFeedback');
        if (!feedback) return;
        const explanation = el(
            'p',
            'mb-0 mt-1',
            answer.explanation || 'Tidak ada pembahasan.'
        );
        const correctChoice = currentQuiz[questionIndex]?.options?.[correctIndex];
        const correctAnswer = !answer.correct
            ? el(
                'p',
                'mb-0 mt-1',
                `Jawaban yang benar adalah ${answer.correctAnswer}${correctChoice ? `, yaitu ${correctChoice}` : ''}.`
            )
            : null;
        feedback.replaceChildren(
            el(
                'strong',
                '',
                answer.correct
                    ? 'Jawaban kamu benar.'
                    : 'Jawaban kamu belum tepat.'
            ),
            ...(correctAnswer ? [correctAnswer] : []),
            explanation
        );
        feedback.className =
            'sd-answer-feedback ' +
            (
                answer.correct
                    ? 'is-correct'
                    : 'is-wrong'
            );
        feedback.hidden = false;
    }
    get('previousQuestion')?.addEventListener(
        'click',
        function () {
            if (questionIndex > 0) {
                questionIndex--;
                renderQuestion();
            }
        }
    );
    get('nextQuestion')?.addEventListener(
        'click',
        function () {
            if (!currentQuiz.length) return;
            if (
                questionIndex >=
                currentQuiz.length - 1
            ) {
                return;
            }
            const question =
                currentQuiz[questionIndex];
            if (!questionIsAnswered(question)) {
                const feedback = get(
                    'answerFeedback'
                );
                if (feedback) {
                    feedback.className =
                        'sd-answer-feedback is-wrong';
                    feedback.textContent =
                        'Selesaikan soal ini terlebih dahulu.';
                    feedback.hidden = false;
                }
                return;
            }
            questionIndex++;
            renderQuestion();
        }
    );
    // ======================================================
    // 13. SIDEBAR BAB DAN SUBBAB
    // ======================================================
    function renderSidebar(container) {
        if (!container) return;
        container.replaceChildren();
        publishedChapters.forEach(chapter => {
            const chapterButton = el(
                'button',
                'sd-chapter-button'
            );
            chapterButton.type = 'button';
            chapterButton.dataset.chapterId =
                chapter.id;
            chapterButton.append(
                el(
                    'strong',
                    '',
                    'BAB ' + chapter.nomor_bab
                ),
                el(
                    'span',
                    '',
                    chapter.judul
                )
            );
            chapterButton.addEventListener(
                'click',
                () => {
                    openChapter(chapter);
                }
            );
            container.appendChild(chapterButton);
            subchaptersOf(chapter).forEach(
                subchapter => {
                    const button = el(
                        'button',
                        'sd-topic',
                        chapter.nomor_bab + '.' +
                        subchapter.nomor_subbab +
                        ' ' + subchapter.judul
                    );
                    button.type = 'button';
                    button.dataset.subchapterId =
                        subchapter.id;
                    button.addEventListener(
                        'click',
                        () => {
                            openSubchapter(
                                chapter,
                                subchapter
                            );
                        }
                    );
                    container.appendChild(button);
                }
            );
        });
        if (!publishedChapters.length) {
            container.appendChild(
                el(
                    'p',
                    'sd-muted',
                    'Belum ada Bab yang dipublikasikan Guru.'
                )
            );
        }
    }
    // ======================================================
    // 14. KARTU DAFTAR MATERI
    // ======================================================
    function renderMaterialCards() {
        if (!materialGrid) return;
        materialGrid.replaceChildren();
        publishedChapters.forEach(chapter => {
            const card = el(
                'article',
                'sd-material-chapter-card'
            );
            const label = el(
                'span',
                'sd-material-chapter-label',
                `BAB ${chapter.nomor_bab} · Guru ${chapter.guru?.name || 'SALUANG'}`
            );
            const heading = el(
                'h2',
                'sd-material-chapter-title',
                chapter.judul
            );
            const topics = el(
                'div',
                'sd-material-chapter-topics'
            );
            subchaptersOf(chapter).forEach(
                subchapter => {
                    const button = el(
                        'button',
                        'sd-material-topic-button',
                        chapter.nomor_bab + '.' +
                        subchapter.nomor_subbab + ' ' +
                        subchapter.judul
                    );
                    button.type = 'button';
                    button.addEventListener(
                        'click',
                        () => {
                            openSubchapter(
                                chapter,
                                subchapter
                            );
                        }
                    );
                    topics.appendChild(button);
                }
            );
            if (!subchaptersOf(chapter).length) {
                topics.appendChild(
                    el(
                        'p',
                        'sd-muted',
                        'Belum ada Subbab terpublikasi.'
                    )
                );
            }
            const openButton = el(
                'button',
                'sd-material-chapter-open',
                'Lihat Selengkapnya'
            );
            openButton.type = 'button';
            openButton.addEventListener(
                'click',
                () => {
                    openChapter(chapter);
                }
            );
            card.append(
                label,
                heading,
                topics,
                openButton
            );
            materialGrid.appendChild(card);
        });
        if (!publishedChapters.length) {
            materialGrid.appendChild(
                el(
                    'p',
                    'sd-muted',
                    'Belum ada materi yang dipublikasikan Guru.'
                )
            );
        }
    }
    function renderHomeMaterialSummary() {
        studentHome.renderSummary({
            publishedChapters,
            dashboardProgress,
        });
    }
    async function refreshDashboardProgress() {
        dashboardProgress = await studentHome.refreshProgress();
        renderHomeMaterialSummary();
    }
    // ======================================================
    // 15. AMBIL MATERI DARI LARAVEL
    // ======================================================
    async function loadMaterials() {
        try {
            const response = await fetch(
                '/siswa/materials',
                {
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json'
                    }
                }
            );
            if (!response.ok) {
                throw new Error(
                    'Gagal mengambil materi dari database Guru.'
                );
            }
            const result = await response.json();
            publishedChapters =
                Array.isArray(result.chapters)
                    ? result.chapters
                    : [];
            publishedChapters.sort(
                (a, b) =>
                    Number(a.nomor_bab) -
                    Number(b.nomor_bab)
            );
            publishedChapters.forEach(chapter => {
                chapter.subchapters =
                    subchaptersOf(chapter);
                chapter.subchapters.sort(
                    (a, b) =>
                        Number(a.nomor_subbab) -
                        Number(b.nomor_subbab)
                );
            });
            renderHomeMaterialSummary();
            buildMaterialSteps();
            renderSidebar(desktopMenu);
            renderSidebar(mobileMenu);
            renderMaterialCards();
            updateActiveMenu();
            openNotificationTarget();
            refreshDashboardProgress().catch(error => {
                console.error('SALUANG:', error);
                const emptyState = get('sdActivityEmpty');
                if (emptyState) {
                    const message = emptyState.querySelector('span');
                    if (message) message.textContent = error.message;
                    emptyState.hidden = false;
                }
            });
        } catch (error) {
            console.error('SALUANG:', error);
            renderHomeMaterialSummary();
            studentHome.showMaterialError(error.message);
            openNotificationTarget();
            [
                desktopMenu,
                mobileMenu,
                materialGrid
            ].forEach(container => {
                if (!container) return;
                container.replaceChildren(
                    el(
                        'p',
                        'sd-muted',
                        error.message
                    )
                );
            });
        }
        function openNotificationTarget() {
            const parameters = new URLSearchParams(window.location.search);
            const section = parameters.get('section');
            if (section === 'evaluasi') {
                showPage(5);
                return;
            }
            if (section === 'material') {
                const chapterId = Number(parameters.get('chapter_id'));
                const chapter = publishedChapters.find(
                    item => Number(item.id) === chapterId
                );
                if (chapter) {
                    openChapter(chapter);
                } else {
                    showPage(7);
                }
                return;
            }
            if (section === 'quiz') {
                const subchapterId = Number(parameters.get('subchapter_id'));
                for (const chapter of publishedChapters) {
                    const subchapter = subchaptersOf(chapter)
                        .find(item => Number(item.id) === subchapterId);
                    if (subchapter) {
                        openSubchapter(chapter, subchapter);
                        return;
                    }
                }
                showPage(7);
            }
        }
    }
    // ======================================================
    // 16. NAVIGASI HALAMAN
    // ======================================================
    function navigateSection(direction) {
        if (currentPage === 0) {
            if (direction === 'next') {
                showPage(7);
            }
            return;
        }
        if (currentPage === 7) {
            if (direction === 'next') {
                firstMaterial();
            } else {
                showPage(0);
            }
            return;
        }
        if (
            currentPage === 1 ||
            currentPage === 2
        ) {
            navigateMaterial(direction);
            return;
        }
        if (currentPage === 5) {
            if (direction === 'next') {
                showPage(6);
            } else {
                lastMaterial();
            }
            return;
        }
        if (currentPage === 6) {
            if (direction === 'next') {
                showPage(8);
            } else {
                showPage(5);
            }
            return;
        }
        if (currentPage === 8) {
            if (direction === 'previous') {
                showPage(6);
            }
        }
    }
    // ======================================================
    // 17. SATU PENGENDALI KLIK
    // ======================================================
    document.addEventListener(
        'click',
        function (event) {
            const target = event.target;
            if (!(target instanceof Element)) {
                return;
            }
            const button = target.closest(
                'button, a'
            );
            if (!button || button.disabled) {
                return;
            }
            const navigation = button.closest(
                '.sd-navigation'
            );
            if (
                navigation &&
                button.classList.contains('sd-nav-btn') &&
                (
                    navigation.closest('#chapterPage') ||
                    navigation.closest('#lessonPage') ||
                    navigation.closest('#materialOverviewPage') ||
                    navigation.closest('#sd-section-evaluasi') ||
                    navigation.closest('#finalPage') ||
                    navigation.closest('#profilePage') ||
                    navigation.closest('#homePage')
                )
            ) {
                event.preventDefault();
                const direction =
                    button.classList.contains('next') ||
                        button.dataset.materialNav === 'next'
                        ? 'next'
                        : 'previous';
                navigateSection(direction);
                return;
            }
            const pageButton = target.closest(
                '[data-page]'
            );
            if (pageButton) {
                event.preventDefault();
                const targetPage = Number(
                    pageButton.dataset.page
                );
                if (targetPage === 1) {
                    firstMaterial();
                    return;
                }
                if (
                    targetPage >= 2 &&
                    targetPage <= 4
                ) {
                    const subchapterSteps =
                        materialSteps.filter(
                            step =>
                                step.type === 'subchapter'
                        );
                    const step = subchapterSteps[
                        targetPage - 2
                    ];
                    if (step) {
                        openStep(step);
                    } else {
                        showPage(7);
                    }
                    return;
                }
                showPage(targetPage);
                return;
            }
            if (
                target.closest(
                    '[data-profile-settings]'
                )
            ) {
                showPage(8);
                const card = get(
                    'profileSettingsCard'
                );
                if (card) {
                    setTimeout(() => {
                        card.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }, 300);
                }
                return;
            }
            const extra = target.closest(
                '[data-extra]'
            );
            if (extra) {
                alert(
                    extra.dataset.extra +
                    ' akan dibuat pada tahap berikutnya.'
                );
            }
        }
    );
    // ======================================================
    // 18. PENCARIAN MATERI
    // ======================================================
    get('searchTopic')?.addEventListener(
        'keydown',
        function (event) {
            if (event.key !== 'Enter') {
                return;
            }
            event.preventDefault();
            const keyword = this.value
                .trim()
                .toLowerCase();
            if (!keyword) {
                showPage(7);
                return;
            }
            const chapter = publishedChapters.find(
                item =>
                    String(item.judul || '')
                        .toLowerCase()
                        .includes(keyword) ||
                    (
                        'bab ' + item.nomor_bab
                    ).includes(keyword)
            );
            if (chapter) {
                openChapter(chapter);
                return;
            }
            const step = materialSteps.find(item => {
                if (
                    item.type !== 'subchapter'
                ) {
                    return false;
                }
                const title = String(
                    item.subchapter.judul || ''
                ).toLowerCase();
                const number =
                    item.chapter.nomor_bab + '.' +
                    item.subchapter.nomor_subbab;
                return (
                    title.includes(keyword) ||
                    number.includes(keyword)
                );
            });
            if (step) {
                openStep(step);
            } else {
                alert('Materi tidak ditemukan.');
            }
        }
    );
    // ======================================================
    // 19. SIDEBAR LIPAT
    // ======================================================
    get('foldSidebar')?.addEventListener(
        'click',
        function () {
            const sidebar = get('sidebar');
            const main = get('main');
            if (!sidebar || !main) return;
            const collapsed =
                sidebar.classList.toggle(
                    'folded'
                );
            main.classList.toggle(
                'expanded',
                collapsed
            );
            this.setAttribute(
                'aria-expanded',
                String(!collapsed)
            );
            this.setAttribute(
                'aria-label',
                collapsed
                    ? 'Perluas sidebar'
                    : 'Perkecil sidebar'
            );
            this.title = collapsed
                ? 'Perluas sidebar'
                : 'Perkecil sidebar';
        }
    );
    // ======================================================
    // 20. INISIALISASI
    // ======================================================
    showPage(0);
    loadMaterials();
});