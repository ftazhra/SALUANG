

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

    let questionIndex = 0;

    let quizVersion = 0;

    let quizAnswerPending = false;



    const savedAnswers = new Map();
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

        questionIndex = 0;
        savedAnswers.clear();
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

            const response = await fetch(url, {

                credentials: 'same-origin',

                headers: {

                    'Accept': 'application/json'

                }

            });



            if (!response.ok) {

                throw new Error(

                    'Gagal mengambil Kuis dari Guru.'

                );

            }



            const result = await response.json();



            if (

                version !== quizVersion ||

                currentPage !== 2 ||

                Number(activeChapter?.id) !== chapterId ||

                Number(activeSubchapter?.id) !== subchapterId

            ) {

                return;

            }



            currentQuiz = (

                Array.isArray(result.questions)

                    ? result.questions

                    : []

            ).map(item => ({

                id: item.id,

                question: item.pertanyaan,

                options: [

                    item.pilihan_a,

                    item.pilihan_b,

                    item.pilihan_c,

                    item.pilihan_d

                ]
            }));

            // Jawaban tersimpan di server untuk histori/progres,
            // tetapi tidak dikunci pada latihan baru.
            savedAnswers.clear();

            questionIndex = 0;

            renderQuestion();



        } catch (error) {

            if (

                version === quizVersion &&

                currentPage === 2

            ) {

                showQuizMessage(error.message);

            }

        }

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



        setText(

            get('questionCount'),

            'Soal ' + (questionIndex + 1) +

            ' dari ' + currentQuiz.length

        );



        setText(

            get('questionText'),

            question.question || ''

        );



        const container = get('answerChoices');

        if (!container) return;



        container.replaceChildren();



        const saved = getAnswer(question.id);



        question.options.forEach((option, index) => {

            const button = el(

                'button',

                'sd-choice'

            );



            button.type = 'button';

            button.disabled = Boolean(saved);



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



        if (saved) {

            applyFeedback(saved);

        }



        if (get('previousQuestion')) {

            get('previousQuestion').disabled =

                questionIndex === 0;

        }



        if (get('nextQuestion')) {

            get('nextQuestion').hidden =

                questionIndex >= currentQuiz.length - 1;

        }

    }



    async function checkStudentAnswer(

        question,

        selected

    ) {

        if (

            quizAnswerPending ||

            !activeSubchapter ||

            getAnswer(question.id)

        ) {

            return;

        }



        quizAnswerPending = true;



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
                throw new Error('Materi belum berhasil tercatat sebagai dipelajari.');
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

                        jawaban: String.fromCharCode(

                            65 + selected

                        )

                    })

                }

            );



            const result = await response.json();



            if (!response.ok) {

                throw new Error(

                    result.message ||

                    'Jawaban gagal diperiksa.'

                );

            }



            const answer = {

                selected,

                correct: Boolean(result.correct),

                correctAnswer: result.correct_answer,

                explanation: result.explanation || '',
                completed: Boolean(result.completed)
            };



            savedAnswers.set(

                subchapterId + ':' + questionId,

                answer

            );



            if (

                currentPage === 2 &&

                Number(activeSubchapter?.id) ===

                subchapterId &&

                Number(

                    currentQuiz[questionIndex]?.id

                ) === questionId

            ) {

                renderQuestion();
            }

            if (result.completed) {
                // Perbarui progres tanpa menampilkan skor, teks selesai,
                // atau tombol khusus. Buka kembali Subbab untuk latihan baru.
                await refreshDashboardProgress();
            }

        } catch (error) {

            if (

                currentPage === 2 &&

                Number(activeSubchapter?.id) ===

                subchapterId

            ) {

                const feedback = get('answerFeedback');



                if (feedback) {

                    feedback.className =

                        'sd-answer-feedback is-wrong';



                    feedback.textContent = error.message;

                    feedback.hidden = false;

                }



                buttons.forEach(button => {

                    button.disabled = false;

                });

            }



        } finally {

            quizAnswerPending = false;

        }

    }



    function applyFeedback(answer) {

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



        feedback.replaceChildren(

            el(

                'strong',

                '',

                answer.correct

                    ? '✓ Jawaban benar!'

                    : '✕ Jawaban belum tepat.'

            ),

            el(

                'p',

                'mb-0 mt-1',

                answer.explanation ||

                'Tidak ada pembahasan.'

            )
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



            if (!getAnswer(question.id)) {

                const feedback = get(

                    'answerFeedback'

                );



                if (feedback) {

                    feedback.className =

                        'sd-answer-feedback is-wrong';



                    feedback.textContent =

                        'Pilih jawaban terlebih dahulu.';



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

                'BAB ' + chapter.nomor_bab

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