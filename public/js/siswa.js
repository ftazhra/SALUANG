

/* ==========================================================

   SALUANG - DASHBOARD SISWA

   File: public/js/siswa.js



   Materi Guru ditampilkan sebagai buku digital.

   Navigasi, kuis, forum, dan profil dipertahankan.

   ========================================================== */



document.addEventListener('DOMContentLoaded', function () {

    'use strict';



    const get = id => document.getElementById(id);



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
    const homeLessonTitle = get('sdHomeLessonTitle');
    const achievementMaterialTitle = get('sdAchievementMaterialTitle');
    const homeLessonLabel = document.querySelector(
        '#homePage .sd-home-lesson .sd-pill'
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

    // 02. RENDER HTML MATERI YANG DIBATASI

    //

    // Konten Guru dapat berisi:

    // <p>, <h2>, <h3>, <strong>, <em>, <u>,

    // <ol>, <ul>, <li>, dan <br>.

    //

    // Tag lain tidak diberi izin untuk dijalankan.

    // Atribut HTML juga tidak disalin.

    //

    // PENTING:

    // Pengamanan browser ini tidak menggantikan

    // sanitasi di backend Laravel.

    // ======================================================



    const allowedMaterialTags = new Set([

        'P', 'BR', 'H2', 'H3',

        'STRONG', 'B', 'EM', 'I', 'U',

        'UL', 'OL', 'LI', 'DIV'

    ]);



    const blockedMaterialTags = new Set([

        'SCRIPT', 'STYLE', 'IFRAME',

        'OBJECT', 'EMBED', 'SVG', 'MATH',

        'FORM', 'INPUT', 'BUTTON',

        'TEXTAREA', 'SELECT', 'TEMPLATE',

        'IMG', 'VIDEO', 'AUDIO'

    ]);



    const htmlTagPattern =

        /<\/?(?:p|div|br|h2|h3|strong|b|em|i|u|ul|ol|li)\b[^>]*>/i;



    function decodeEntitiesOnce(value) {

        const helper = document.createElement('textarea');

        helper.innerHTML = value;

        return helper.value;

    }



    function normalizeMaterialSource(value) {

        let source = String(value ?? '').trim();



        if (!source) return '';



        // Menangani konten yang pernah tersimpan sebagai:

        // &lt;p&gt;Teks&lt;/p&gt;

        // atau &amp;lt;p&amp;gt;Teks...



        for (let i = 0; i < 4; i++) {

            if (!/&(?:amp;)*(?:lt|gt);|&#(?:0*60|0*62|x0*3c|x0*3e);/i.test(source)) {

                break;

            }



            const decoded = decodeEntitiesOnce(source);

            if (decoded === source) break;



            source = decoded;

        }



        // Materi lama mungkin memiliki tag HTML

        // sebagai teks di dalam sebuah paragraf.



        for (let i = 0; i < 3; i++) {

            if (!htmlTagPattern.test(source)) break;



            const parsed = new DOMParser().parseFromString(

                source,

                'text/html'

            );



            const visible = parsed.body.textContent.trim();



            if (

                htmlTagPattern.test(visible) &&

                visible !== source

            ) {

                source = visible;

                continue;

            }



            break;

        }



        return source;

    }



    function safeMaterialNode(node) {

        if (node.nodeType === Node.TEXT_NODE) {

            return document.createTextNode(

                node.nodeValue || ''

            );

        }



        if (node.nodeType !== Node.ELEMENT_NODE) {

            return null;

        }



        if (blockedMaterialTags.has(node.tagName)) {

            return null;

        }



        const tag = allowedMaterialTags.has(node.tagName)

            ? node.tagName.toLowerCase()

            : 'span';



        const result = document.createElement(tag);



        // Tidak menyalin class, style, src,

        // href, onerror, onclick, dan atribut lain.



        for (const child of node.childNodes) {

            const safe = safeMaterialNode(child);



            if (safe) result.appendChild(safe);

        }



        return result;

    }



    const learningActivityTitles = new Map([
        ['ayo berpikir', 'Ayo Berpikir!'],
        ['ayo mengamati', 'Ayo Mengamati!'],
        ['ayo berdiskusi', 'Ayo Berdiskusi!'],
        ['ayo mencoba', 'Ayo Mencoba!'],
        ['ayo refleksi', 'Ayo Refleksi!'],
        ['ayo berlatih', 'Ayo Berlatih!']
    ]);

    function learningActivityTitle(node) {
        if (node.nodeType !== Node.ELEMENT_NODE) return null;

        const match = node.textContent
            .replace(/\*\*/g, '')
            .trim()
            .match(/^Ayo\s+(Berpikir|Mengamati|Berdiskusi|Mencoba|Refleksi|Berlatih)!?(?=$|\s)/i);
        if (!match) return null;

        return learningActivityTitles.get(
            `ayo ${match[1].toLocaleLowerCase('id')}`
        ) || null;
    }

    function learningActivityRemainder(node) {
        const prefix = node.textContent.match(
            /^\s*Ayo\s+(?:Berpikir|Mengamati|Berdiskusi|Mencoba|Refleksi|Berlatih)!?/i
        );
        if (!prefix || !node.textContent.slice(prefix[0].length).trim()) {
            return null;
        }

        const remainder = node.cloneNode(true);
        let charactersToRemove = prefix[0].length;
        const walker = document.createTreeWalker(
            remainder,
            NodeFilter.SHOW_TEXT
        );
        let textNode;

        while (charactersToRemove > 0 && (textNode = walker.nextNode())) {
            const count = Math.min(
                charactersToRemove,
                textNode.textContent.length
            );
            textNode.textContent = textNode.textContent.slice(count);
            charactersToRemove -= count;
        }

        return remainder;
    }

    function isLearningActivityBoundary(node) {
        if (node.nodeType !== Node.ELEMENT_NODE) return false;

        const startsNumberedSection = /^H[1-3]$/.test(node.tagName)
            && /^\s*\d+\.\s+/.test(node.textContent);

        return startsNumberedSection
            || ['FIGURE', 'IMG', 'VIDEO', 'IFRAME', 'HR', 'TABLE'].includes(node.tagName)
            || node.matches(
                '.gm-preview-media, .gm-summary-card, .sd-media-item, .sd-summary-panel'
            )
            || Boolean(node.querySelector(
                'figure, img, video, iframe, hr, table, '
                + '.gm-preview-media, .gm-summary-card, .sd-media-item, .sd-summary-panel'
            ));
    }

    function isLearningActivityContent(node) {
        if (node.nodeType === Node.TEXT_NODE) {
            return Boolean(node.textContent.trim());
        }

        return node.nodeType === Node.ELEMENT_NODE
            && !isLearningActivityBoundary(node)
            && (/^H[1-6]$/.test(node.tagName)
                || ['P', 'OL', 'UL', 'BLOCKQUOTE', 'DIV'].includes(node.tagName));
    }

    function wrapLearningActivities(parent) {
        const nodes = [...parent.childNodes];
        const output = document.createDocumentFragment();

        for (let index = 0; index < nodes.length; index++) {
            const node = nodes[index];
            const title = learningActivityTitle(node);

            if (!title) {
                if (
                    node.nodeType === Node.ELEMENT_NODE
                    && ['DIV', 'SECTION', 'BLOCKQUOTE'].includes(node.tagName)
                ) {
                    wrapLearningActivities(node);
                }
                output.appendChild(node);
                continue;
            }

            const inlineRemainder = node.tagName === 'P'
                ? learningActivityRemainder(node)
                : null;
            const activityContent = [];
            let nextIndex = index + 1;

            while (nextIndex < nodes.length) {
                const candidate = nodes[nextIndex];
                if (
                    learningActivityTitle(candidate)
                    || isLearningActivityBoundary(candidate)
                ) {
                    break;
                }

                if (
                    candidate.nodeType === Node.TEXT_NODE
                    && !candidate.textContent.trim()
                ) {
                    activityContent.push(candidate);
                    nextIndex++;
                    continue;
                }

                if (!isLearningActivityContent(candidate)) break;
                activityContent.push(candidate);
                nextIndex++;
            }

            if (
                !inlineRemainder
                && !activityContent.some(content =>
                    content.nodeType !== Node.TEXT_NODE
                    || content.textContent.trim()
                )
            ) {
                output.appendChild(node);
                continue;
            }

            const card = el('section', 'sd-learning-activity');
            card.appendChild(el('h3', 'sd-learning-activity-title', title));
            const body = el('div', 'sd-learning-activity-body');
            if (inlineRemainder) body.appendChild(inlineRemainder);
            activityContent.forEach(content => body.appendChild(content));
            if (body.childNodes.length) card.appendChild(body);
            output.appendChild(card);
            index = nextIndex - 1;
        }

        parent.replaceChildren(output);
    }

    function renderMaterialContent(container, value, applyActivityCards = true) {

        if (!container) return;



        container.replaceChildren();



        const source = normalizeMaterialSource(value);



        if (!source) {

            container.appendChild(

                el('p', '', 'Materi Subbab belum diisi Guru.')

            );

            return;

        }



        if (htmlTagPattern.test(source)) {

            const parsed = new DOMParser().parseFromString(

                source,

                'text/html'

            );



            for (const node of parsed.body.childNodes) {
                const safe = safeMaterialNode(node);

                if (safe) container.appendChild(safe);
            }

            if (applyActivityCards) wrapLearningActivities(container);
            return;

        }



        // Kompatibilitas materi lama yang masih

        // berupa teks biasa tanpa tag HTML.



        source.split(/\r?\n\s*\r?\n/)

            .filter(Boolean)

            .forEach(paragraph => {

                const p = document.createElement('p');



                const lines = paragraph.split(/\r?\n/);



                lines.forEach((line, index) => {

                    if (index) {

                        p.appendChild(

                            document.createElement('br')

                        );

                    }



                    p.appendChild(

                        document.createTextNode(line)

                    );

                });

                container.appendChild(p);
            });

        if (applyActivityCards) wrapLearningActivities(container);
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



        showPage(2);

        renderLesson();

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



    function renderSubchapterMedia(container, entries) {

        if (!container) return false;



        container.replaceChildren();



        if (!Array.isArray(entries) || entries.length === 0) {

            return false;

        }



        entries.forEach(media => {

            const item = document.createElement('section');

            item.className = 'sd-media-item';



            if (media.title) {

                const title = document.createElement('h3');

                title.className = 'sd-media-title';

                title.textContent = media.title;

                item.appendChild(title);

            }



            if (media.url && media.media_type === 'image') {

                const image = document.createElement('img');

                image.className = 'sd-media-picture';

                image.src = media.url;

                image.alt = media.title || 'Gambar pembelajaran';

                image.loading = 'lazy';

                image.addEventListener('error', () => {

                    image.remove();

                    appendUnavailableMessage(

                        item,

                        'Gambar pembelajaran tidak tersedia.'

                    );

                }, { once: true });

                item.appendChild(image);

            } else if (

                media.source === 'youtube' &&

                typeof media.embed_url === 'string' &&

                media.embed_url.startsWith(

                    'https\://www.youtube-nocookie.com/embed/'

                )

            ) {

                const frame = document.createElement('iframe');

                frame.className = 'sd-media-youtube';

                frame.title = media.title || 'Video YouTube pembelajaran';

                frame.loading = 'lazy';

                frame.allowFullscreen = true;

                frame.referrerPolicy = 'strict-origin-when-cross-origin';

                frame.allow =

                    'accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture';

                frame.src = media.embed_url;

                item.appendChild(frame);



                const note = document.createElement('p');

                note.className = 'sd-media-note';

                note.textContent =

                    'Jika video tidak tersedia di pemutar SALUANG, coba tonton langsung di YouTube atau minta Guru mengganti tautannya. ';

                const videoId = media.embed_url.slice(

                    'https\://www.youtube-nocookie.com/embed/'.length

                );

                if (/^[A-Za-z0-9_-]{11}$/.test(videoId)) {

                    const watchLink = document.createElement('a');

                    const watchUrl = new URL('https\://www.youtube.com/watch');

                    watchUrl.searchParams.set('v', videoId);

                    watchLink.href = watchUrl.toString();

                    watchLink.target = '_blank';

                    watchLink.rel = 'noopener noreferrer';

                    watchLink.textContent = 'Tonton di YouTube';

                    note.appendChild(watchLink);

                }

                item.appendChild(note);

            } else if (

                media.url &&

                media.media_type === 'video' &&

                media.source !== 'youtube'

            ) {

                const video = document.createElement('video');

                video.className = 'sd-media-video';

                video.controls = true;

                video.preload = 'metadata';

                video.playsInline = true;



                const source = document.createElement('source');

                source.src = media.url;

                source.type = 'video/mp4';

                video.appendChild(source);

                video.addEventListener('error', () => {

                    video.remove();

                    appendUnavailableMessage(

                        item,

                        'Video tidak dapat dimuat. Periksa kembali berkas media.'

                    );

                }, { once: true });

                item.appendChild(video);

            } else {

                appendUnavailableMessage(

                    item,

                    'Berkas media tidak tersedia.'

                );

            }



            if (media.caption) {

                const caption = document.createElement('p');

                caption.className = 'sd-media-caption';

                caption.textContent = media.caption;

                item.appendChild(caption);

            }



            container.appendChild(item);

        });



        return true;

    }



    function appendUnavailableMessage(container, message) {

        const notice = document.createElement('p');

        notice.className = 'sd-media-unavailable';

        notice.setAttribute('role', 'status');

        notice.textContent = message;

        container.appendChild(notice);

    }



    // SALUANG_SISWA_PREVIEW_SYNC_V1: kartu informasi berbasis konten Guru.
    // Teks ditambahkan melalui textContent agar tidak menjalankan HTML mentah.
    const activityNames = [
        'Ayo Berpikir!', 'Ayo Mengamati!', 'Ayo Berdiskusi!',
        'Ayo Mencoba!', 'Ayo Refleksi!', 'Ayo Berlatih!'
    ];
    function isStudentActivity(title) {
        return activityNames.some(name =>
            name.toLocaleLowerCase('id') === String(title || '').trim().toLocaleLowerCase('id')
        );
    }
    function renderStudentActivity(section, payload) {
        section.classList.add('sd-content-block-activity');
        const card = el('section', 'sd-learning-activity sd-learning-activity-explicit');
        card.appendChild(el('h3', 'sd-learning-activity-title', payload.title));
        const body = el('div', 'sd-learning-activity-body');
        renderMaterialContent(body, payload.content || '');
        card.appendChild(body);
        section.appendChild(card);
    }

    function summaryContentToText(value) {
        if (!/<\/?(?:p|div|h[1-6]|ol|ul|li|br|strong|b|em|i)\b/i.test(value)) {
            return value;
        }

        const parsed = new DOMParser().parseFromString(value, 'text/html');
        const inline = node => {
            if (node.nodeType === Node.TEXT_NODE) return node.nodeValue || '';
            if (node.nodeType !== Node.ELEMENT_NODE) return '';

            const content = [...node.childNodes].map(inline).join('');
            if (node.tagName === 'BR') return '\n';
            if (['B', 'STRONG'].includes(node.tagName)) return `**${content}**`;
            if (['I', 'EM'].includes(node.tagName)) return `*${content}*`;
            return content;
        };
        const block = node => {
            if (node.nodeType === Node.TEXT_NODE) return node.nodeValue || '';
            if (node.nodeType !== Node.ELEMENT_NODE) return '';

            const content = [...node.childNodes].map(block).join('');
            switch (node.tagName) {
                case 'H1':
                case 'H2':
                    return `## ${content.trim()}\n\n`;
                case 'H3':
                case 'H4':
                case 'H5':
                case 'H6':
                    return `### ${content.trim()}\n\n`;
                case 'P':
                case 'DIV':
                    return `${content.trim()}\n\n`;
                case 'UL':
                    return [...node.children]
                        .filter(child => child.tagName === 'LI')
                        .map(child => `- ${inline(child).trim()}\n`)
                        .join('') + '\n';
                case 'OL':
                    return [...node.children]
                        .filter(child => child.tagName === 'LI')
                        .map((child, index) => `${index + 1}. ${inline(child).trim()}\n`)
                        .join('') + '\n';
                default:
                    return inline(node);
            }
        };

        return [...parsed.body.childNodes]
            .map(block)
            .join('')
            .replace(/\n{3,}/g, '\n\n')
            .trim();
    }

    // Kesimpulan Siswa: struktur dan gaya identik dengan pratinjau Guru.
    // Semua konten berasal dari database Guru; tidak mengubah data tersimpan.
    function renderStudentCallout(section, payload) {
        const card = el('section', 'gm-summary-card');
        const header = el('header', 'gm-summary-header');
        header.appendChild(el('h4', '', payload?.title || 'Informasi / Kesimpulan'));
        card.appendChild(header);

        const body = el('div', 'gm-summary-body');
        const raw = String(payload?.content || '').replace(/\r\n?/g, '\n').trim();
        const lines = summaryContentToText(raw).split('\n');
        let items = [];
        const flushItems = () => {
            if (!items.length) return;
            const grid = el('div', 'gm-summary-grid');
            items.forEach(item => grid.appendChild(item));
            body.appendChild(grid);
            items = [];
        };
        const inline = (container, value) => {
            String(value || '').split(/(\*\*[^*]+\*\*)/g).forEach(part => {
                if (part.startsWith('**') && part.endsWith('**')) {
                    container.appendChild(el('strong', '', part.slice(2, -2)));
                } else if (part) {
                    container.appendChild(document.createTextNode(part));
                }
            });
        };
        for (const source of lines) {
            const line = source.trim();
            if (!line) continue;
            const numbered = line.match(/^(?:\*\*)?(\d+)\.\s+(.+)$/);
            if (numbered) {
                const remainder = numbered[2].replace(/\*\*$/, '');
                const label = remainder.match(/^\*\*(.+?):?\*\*\s*(.*)$/);
                const heading = label ? label[1].replace(/:$/, '') : remainder.split(':')[0].replace(/\*\*/g, '').trim();
                const description = label ? label[2] : (remainder.includes(':') ? remainder.slice(remainder.indexOf(':') + 1).trim() : '');
                const item = el('article', 'gm-summary-item');
                item.appendChild(el('span', 'gm-summary-number', numbered[1]));
                const content = el('div');
                const h = el('h5');
                inline(h, heading);
                content.appendChild(h);
                if (description) {
                    const p = el('p');
                    inline(p, description);
                    content.appendChild(p);
                }
                item.appendChild(content);
                items.push(item);
                continue;
            }
            flushItems();
            const heading = line.match(/^#{2,3}\s+(.+)$/);
            const knownHeading = /^(?:RANGKUMAN KARAKTERISTIK MAKHLUK HIDUP|MAKHLUK HIDUP DI SEKITAR KITA|KESIMPULAN|AYO REFLEKSI!?|AYO MENGINGAT KEMBALI!?)$/i.test(line.replace(/\*\*/g, ''));
            if (heading || knownHeading) {
                const h = el('h4', 'gm-summary-heading');
                inline(h, heading ? heading[1] : line);
                body.appendChild(h);
            } else {
                const p = el('p', 'gm-summary-paragraph');
                inline(p, line);
                body.appendChild(p);
            }
        }
        flushItems();
        if (!body.childNodes.length) body.appendChild(el('p', '', 'Belum ada isi kesimpulan.'));
        card.appendChild(body);
        section.appendChild(card);
    }

    // Media diperlebar tanpa mengubah URL, CRUD, atau mekanisme upload.
    if (!document.getElementById('sdGuruParityStyles')) {
        const style = document.createElement('style');
        style.id = 'sdGuruParityStyles';
        style.textContent = `
          #lessonPage .sd-content-block:has(.sd-media-item) { width: 100% !important; max-width: none !important; }
          #lessonPage .sd-media-item { width: min(100%, 980px) !important; max-width: 980px !important; margin: 24px auto 32px !important; padding: 24px !important; box-sizing: border-box; }
          #lessonPage .sd-media-picture { width: 100% !important; max-width: 100% !important; max-height: none !important; height: auto !important; object-fit: contain !important; margin: 0 auto !important; }
          #lessonPage .sd-media-title { text-align: center !important; margin: 0 0 18px !important; }
          #lessonPage .sd-media-caption { text-align: center !important; margin: 24px auto 0 !important; line-height: 1.5 !important; }
          @media (max-width: 680px) { #lessonPage .sd-media-item { padding: 16px !important; } }
        `;
        document.head.appendChild(style);
    }

    function renderMaterialBlocks(container, blocks) {

        if (!container || !Array.isArray(blocks)) return false;



        container.replaceChildren();



        if (!blocks.length) {

            container.appendChild(

                el('p', '', 'Materi Subbab belum diisi Guru.')

            );

            return false;

        }



        blocks.forEach(block => {
            if (block.type === 'text') {
                const section = el('section', 'sd-content-block');
                renderMaterialContent(
                    section,
                    block.payload?.content || ''
                );
                container.appendChild(section);
                return;
            }

            const section = el('section', 'sd-content-block');

            if (block.type === 'callout') {
                if (isStudentActivity(block.payload?.title)) {
                    renderStudentActivity(section, block.payload || {});
                } else {
                    renderStudentCallout(section, block.payload || {});
                }
            } else if (
                (block.type === 'image' || block.type === 'video') &&
                block.media
            ) {
                renderSubchapterMedia(section, [{
                    ...block.media,
                    title: block.payload?.title || block.media.title,
                    caption: block.payload?.caption || block.media.caption
                }]);
            } else {
                appendUnavailableMessage(
                    section,
                    'Blok materi tidak dapat ditampilkan.'
                );
            }
            container.appendChild(section);
        });

        return true;

    }



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

            renderMaterialBlocks(

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

            renderMaterialContent(

                get('lessonContent'),

                activeSubchapter.konten

            );

            hasMedia = renderSubchapterMedia(

                mediaItems,

                activeSubchapter.media

            );

        }



        if (media) media.hidden = hasOrderedBlocks || !hasMedia;



        renderLessonNavigation();



        currentQuiz = [];

        questionIndex = 0;



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



        const url =

            '/siswa/quiz/chapters/' +

            encodeURIComponent(activeChapter.nomor_bab) +

            '/subchapters/' +

            encodeURIComponent(activeSubchapter.nomor_subbab) +

            '/questions';



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

                'Belum ada Kuis yang dipublikasikan Guru untuk Subbab ini.'

            );

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

                explanation: result.explanation || ''

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
        const chapter = publishedChapters[0] || null;
        const title = chapter
            ? textOr(chapter.judul, 'Bab Tanpa Judul')
            : 'Belum ada materi yang dipublikasikan Guru.';

        setText(homeLessonTitle, title);
        setText(achievementMaterialTitle, title);
        setText(
            homeLessonLabel,
            chapter ? `BAB ${chapter.nomor_bab}` : 'MATERI PEMBELAJARAN'
        );
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



        } catch (error) {

            console.error('SALUANG:', error);

            renderHomeMaterialSummary();
            setText(homeLessonTitle, error.message);
            setText(achievementMaterialTitle, error.message);

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