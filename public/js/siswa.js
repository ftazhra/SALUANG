

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



    function renderMaterialContent(container, value) {

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

                chapter.pengantar ||

                chapter.deskripsi,

                'Pengantar Bab belum diisi Guru.'

            )

        );



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

                    'Pertanyaan pemantik belum diisi Guru.';

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

                'Materi ' + (index + 1) +

                ' dari ' + materialSteps.length

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
    function renderStudentCallout(section, payload) {
        section.classList.add('sd-content-block-callout', 'sd-summary-panel');
        const title = el('h3', 'sd-summary-main-title', payload?.title || 'Informasi');
        section.appendChild(title);

        const body = el('div', 'sd-summary-body');
        const text = String(payload?.content || '').replace(/\r\n?/g, '\n').trim();
        const lines = text.split('\n');
        const pending = [];
        let grid = null;
        function flush() {
            if (pending.length) {
                body.appendChild(el('p', 'sd-summary-paragraph', pending.join(' ').trim()));
                pending.length = 0;
            }
        }
        for (const sourceLine of lines) {
            const line = sourceLine.trim();
            if (!line) { flush(); continue; }
            const heading = line.match(/^#{2,3}\s+(.+)$/);
            if (heading) {
                flush();
                body.appendChild(el('h4', 'sd-summary-subtitle', heading[1].replace(/\*\*/g, '')));
                continue;
            }
            const numbered = line.match(/^([1-8])\.\s+\*\*(.+?)\*\*\s*(.*)$/);
            if (numbered) {
                flush();
                if (!grid) {
                    grid = el('div', 'sd-summary-grid');
                    body.appendChild(grid);
                }
                const item = el('article', 'sd-summary-item');
                item.appendChild(el('span', 'sd-summary-number', numbered[1]));
                const content = el('div', 'sd-summary-item-content');
                content.appendChild(el('strong', '', numbered[2].replace(/:$/, '')));
                if (numbered[3]) content.appendChild(el('p', '', numbered[3]));
                item.appendChild(content);
                grid.appendChild(item);
                continue;
            }
            const genericNumber = line.match(/^([1-8])\.\s+(.+?):\s*(.*)$/);
            if (genericNumber) {
                flush();
                if (!grid) { grid = el('div', 'sd-summary-grid'); body.appendChild(grid); }
                const item = el('article', 'sd-summary-item');
                item.appendChild(el('span', 'sd-summary-number', genericNumber[1]));
                const content = el('div', 'sd-summary-item-content');
                content.appendChild(el('strong', '', genericNumber[2]));
                if (genericNumber[3]) content.appendChild(el('p', '', genericNumber[3]));
                item.appendChild(content); grid.appendChild(item);
                continue;
            }
            if (/^\*\*(Kesimpulan|Ayo Refleksi!?|Ayo Mengingat Kembali!?)\*\*$/i.test(line)) {
                flush();
                body.appendChild(el('h4', 'sd-summary-subtitle', line.replace(/\*\*/g, '')));
                continue;
            }
            pending.push(line.replace(/\*\*/g, ''));
        }
        flush();
        section.appendChild(body);
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

            const section = document.createElement('section');

            section.className = 'sd-content-block';



            if (block.type === 'text') {

                renderMaterialContent(

                    section,

                    block.payload?.content || ''

                );

            } else if (block.type === 'callout') {

renderStudentCallout(section, block.payload || {});

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



            buildMaterialSteps();

            renderSidebar(desktopMenu);

            renderSidebar(mobileMenu);

            renderMaterialCards();

            updateActiveMenu();



        } catch (error) {

            console.error('SALUANG:', error);



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