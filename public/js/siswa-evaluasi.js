/* =====================================================
   SALUANG — JAVASCRIPT EVALUASI PEMBELAJARAN
   01. Elemen utama
   02. Data jawaban dan state
   03. Helper
   04. Perpindahan layar
   05. Memilih gambar
   06. Drag-and-drop gambar
   07. Target pengelompokan
   08. Drag bullet penghubung
   09. Garis penghubung
   10. Jawaban dan feedback
   11. Hasil evaluasi lama
   12. Reset soal lama
   13. Sistem 20 soal dan timer
   14. Modal konfirmasi SALUANG
   15. Inisialisasi
   ===================================================== */
document.addEventListener('DOMContentLoaded', () => {
    'use strict';
    /* =====================================================
       01. ELEMEN UTAMA
       ===================================================== */
    const root = document.getElementById('sd-section-evaluasi');
    if (!root) return;
    const $ = (selector) => root.querySelector(selector);
    const $$ = (selector) => Array.from(root.querySelectorAll(selector));
    const screens = $$('[data-eval-screen]');
    const outerNavigation = $('.sd-eval-outer-navigation');
    const q1Feedback = $('[data-eval-feedback="q1"]');
    const q2Feedback = $('[data-eval-feedback="q2"]');
    const q1Next = $('[data-next-for="q1"]');
    const finishButton = document.getElementById('sd-eval-finish');
    const retryButton = document.getElementById('sd-eval-retry');
    const finalScore = document.getElementById('sd-eval-final-score');
    const finalSummary = document.getElementById('sd-eval-final-summary');
    const finalHeading = document.getElementById('sd-eval-result-heading');
    const finalMessage = document.getElementById('sd-eval-final-message');
    const matchLayout = document.getElementById('sdMatchQ2');
    const matchSvg = document.getElementById('sdMatchLinesQ2');
    /* =====================================================
       02. DATA JAWABAN DAN STATE
       ===================================================== */
    // Data ini dipertahankan untuk dua layar soal lama.
    // Layar 20 soal masih menggunakan kerangka sementara.
    const correctAnswers = {
        q1: {
            ikan_saluang: 'biotik',
            air_sungai: 'abiotik',
            tumbuhan_purun: 'biotik'
        },
        q2: {
            ikan: 'hewan',
            burung: 'hewan',
            katak: 'hewan',
            teratai: 'tumbuhan',
            purun: 'tumbuhan'
        }
    };
    const explanations = {
        q1: {
            ikan_saluang:
                'Ikan saluang termasuk biotik karena menjalankan proses kehidupan.',
            air_sungai:
                'Air sungai termasuk abiotik karena bukan makhluk hidup.',
            tumbuhan_purun:
                'Tumbuhan purun termasuk biotik karena tumbuh dan berkembang.'
        },
        q2: {
            ikan: 'Ikan termasuk kelompok hewan.',
            burung: 'Burung termasuk kelompok hewan.',
            katak: 'Katak termasuk kelompok hewan.',
            teratai: 'Teratai termasuk kelompok tumbuhan.',
            purun: 'Purun termasuk kelompok tumbuhan.'
        }
    };
    const answers = {
        q1: {},
        q2: {}
    };
    let activeScreen = 'overview';
    let selectedQ1Item = null;
    let q1Dragging = null;
    let q2Dragging = null;
    let suppressQ1Click = false;
    /* =====================================================
       03. HELPER ELEMEN DAN DATA GAMBAR
       ===================================================== */
    function getItem(question, itemId) {
        return $(
            `[data-eval-item][data-question="${question}"]` +
            `[data-item="${itemId}"]`
        );
    }
    function getTarget(question, targetId) {
        return $(
            `[data-eval-target][data-question="${question}"]` +
            `[data-target="${targetId}"]`
        );
    }
    function getImage(question, itemId) {
        const item = getItem(question, itemId);
        if (!item) return null;
        if (question === 'q2') {
            const row = item.closest('.sd-eval-match-row');
            return row ? row.querySelector('img') : null;
        }
        return item.querySelector('img');
    }
    function getImageLabel(question, itemId) {
        return getImage(question, itemId)?.alt || itemId;
    }
    function getTargetLabel(targetId) {
        return {
            biotik: 'Biotik',
            abiotik: 'Abiotik',
            hewan: 'Hewan',
            tumbuhan: 'Tumbuhan'
        }[targetId] || targetId;
    }
    function isComplete(question) {
        return Object.keys(correctAnswers[question]).every(
            itemId => Boolean(answers[question][itemId])
        );
    }
    function getCorrectCount(question) {
        return Object.entries(correctAnswers[question]).filter(
            ([itemId, targetId]) =>
                answers[question][itemId] === targetId
        ).length;
    }
    /* =====================================================
       04. PERPINDAHAN HALAMAN EVALUASI
       ===================================================== */
    function showScreen(name) {
        activeScreen = name;
        screens.forEach(screen => {
            screen.classList.toggle(
                'is-active',
                screen.dataset.evalScreen === name
            );
        });
        if (outerNavigation) {
            outerNavigation.hidden = name !== 'overview';
        }
        if (name === 'q2') {
            requestAnimationFrame(drawMatchLines);
        }
        if (name === 'result') {
            calculateFinalScore();
        }
    }
    // Navigasi untuk layar soal lama.
    // Tombol menuju "quiz" ditangani modal pada bagian 14.
    $$('[data-eval-open]').forEach(button => {
        if (button.dataset.evalOpen === 'quiz') return;
        button.addEventListener('click', () => {
            const destination = button.dataset.evalOpen;
            if (
                destination === 'q2' &&
                !isComplete('q1')
            ) {
                return;
            }
            showScreen(destination);
        });
    });
    /* =====================================================
       05. SOAL LAMA — MEMILIH GAMBAR
       ===================================================== */
    function clearQ1Selection() {
        if (selectedQ1Item) {
            selectedQ1Item.classList.remove('is-selected');
        }
        selectedQ1Item = null;
    }
    function chooseQ1Item(item) {
        clearQ1Selection();
        selectedQ1Item = item;
        item.classList.add('is-selected');
    }
    $$('[data-eval-item][data-question="q1"]').forEach(item => {
        item.addEventListener('click', () => {
            if (suppressQ1Click) {
                suppressQ1Click = false;
                return;
            }
            chooseQ1Item(item);
        });
    });
    /* =====================================================
       06. SOAL LAMA — DRAG-AND-DROP GAMBAR
       ===================================================== */
    function clearQ1DropHover() {
        $$('[data-eval-target][data-question="q1"]')
            .forEach(target => {
                target.classList.remove('is-drop-hover');
            });
    }
    function getQ1TargetAt(x, y) {
        return document.elementFromPoint(x, y)?.closest(
            '[data-eval-target][data-question="q1"]'
        ) || null;
    }
    function removeQ1DragGhost() {
        if (!q1Dragging) return;
        q1Dragging.ghost?.remove();
        q1Dragging.imageBox?.classList.remove('is-dragging');
        clearQ1DropHover();
        q1Dragging = null;
    }
    function startQ1Drag(item, event) {
        if (event.button !== undefined && event.button !== 0) {
            return;
        }
        const imageBox = item.querySelector('.sd-eval-emoji');
        const image = imageBox?.querySelector('img');
        if (!image) return;
        event.preventDefault();
        const ghost = document.createElement('div');
        ghost.className = 'sd-eval-drag-ghost';
        const ghostImage = document.createElement('img');
        ghostImage.src = image.src;
        ghostImage.alt = '';
        ghost.appendChild(ghostImage);
        document.body.appendChild(ghost);
        imageBox.classList.add('is-dragging');
        q1Dragging = {
            item,
            imageBox,
            ghost,
            pointerId: event.pointerId,
            startX: event.clientX,
            startY: event.clientY,
            moved: false
        };
        ghost.style.left = `${event.clientX}px`;
        ghost.style.top = `${event.clientY}px`;
    }
    function moveQ1Drag(event) {
        if (!q1Dragging) return;
        if (q1Dragging.pointerId !== event.pointerId) return;
        const distance = Math.hypot(
            event.clientX - q1Dragging.startX,
            event.clientY - q1Dragging.startY
        );
        if (distance > 7) {
            q1Dragging.moved = true;
        }
        q1Dragging.ghost.style.left = `${event.clientX}px`;
        q1Dragging.ghost.style.top = `${event.clientY}px`;
        clearQ1DropHover();
        getQ1TargetAt(
            event.clientX,
            event.clientY
        )?.classList.add('is-drop-hover');
    }
    function finishQ1Drag(event) {
        if (!q1Dragging) return;
        if (q1Dragging.pointerId !== event.pointerId) return;
        const item = q1Dragging.item;
        const moved = q1Dragging.moved;
        const target = getQ1TargetAt(
            event.clientX,
            event.clientY
        );
        removeQ1DragGhost();
        if (moved) {
            suppressQ1Click = true;
            if (target) {
                setAnswer(
                    'q1',
                    item.dataset.item,
                    target.dataset.target
                );
            }
        } else {
            chooseQ1Item(item);
        }
    }
    $$('[data-eval-item][data-question="q1"]').forEach(item => {
        const imageBox = item.querySelector('.sd-eval-emoji');
        if (!imageBox) return;
        imageBox.addEventListener('pointerdown', event => {
            startQ1Drag(item, event);
        });
        imageBox.addEventListener('dragstart', event => {
            event.preventDefault();
        });
    });
    /* =====================================================
       07. SOAL LAMA — TARGET PENGELOMPOKAN
       ===================================================== */
    function renderQ1Targets() {
        ['biotik', 'abiotik'].forEach(targetId => {
            const box = $(
                `[data-target-box="q1:${targetId}"]`
            );
            if (!box) return;
            box.replaceChildren();
            Object.entries(answers.q1).forEach(
                ([itemId, assignedTarget]) => {
                    if (assignedTarget !== targetId) return;
                    const image = getImage('q1', itemId);
                    if (!image) return;
                    const chip = document.createElement('span');
                    chip.className = 'sd-eval-chip';
                    chip.title = image.alt;
                    const thumbnail = document.createElement('img');
                    thumbnail.src = image.src;
                    thumbnail.alt = image.alt;
                    chip.appendChild(thumbnail);
                    box.appendChild(chip);
                }
            );
        });
        $$('[data-eval-item][data-question="q1"]').forEach(item => {
            item.classList.toggle(
                'is-assigned',
                Boolean(answers.q1[item.dataset.item])
            );
        });
    }
    $$('[data-eval-target][data-question="q1"]').forEach(target => {
        target.addEventListener('click', () => {
            if (!selectedQ1Item) return;
            setAnswer(
                'q1',
                selectedQ1Item.dataset.item,
                target.dataset.target
            );
            clearQ1Selection();
        });
    });
    /* =====================================================
       08. SOAL LAMA — MENYERET BULLET PENGHUBUNG
       ===================================================== */
    function bulletCenter(bullet) {
        if (!matchLayout || !bullet) return { x: 0, y: 0 };
        const rect = bullet.getBoundingClientRect();
        const layoutRect = matchLayout.getBoundingClientRect();
        return {
            x: rect.left + rect.width / 2 - layoutRect.left,
            y: rect.top + rect.height / 2 - layoutRect.top
        };
    }
    function pointerInMatchLayout(event) {
        const rect = matchLayout.getBoundingClientRect();
        return {
            x: event.clientX - rect.left,
            y: event.clientY - rect.top
        };
    }
    function getQ2TargetAt(event) {
        return document.elementFromPoint(
            event.clientX,
            event.clientY
        )?.closest(
            '[data-eval-target][data-question="q2"]'
        ) || null;
    }
    function curvePath(start, end) {
        const bend = Math.max(
            25,
            Math.abs(end.x - start.x) * 0.45
        );
        return (
            `M ${start.x} ${start.y} ` +
            `C ${start.x + bend} ${start.y}, ` +
            `${end.x - bend} ${end.y}, ` +
            `${end.x} ${end.y}`
        );
    }
    function createMatchPath(start, end, preview = false) {
        const path = document.createElementNS(
            'http://www.w3.org/2000/svg',
            'path'
        );
        path.setAttribute('d', curvePath(start, end));
        path.setAttribute('fill', 'none');
        path.setAttribute('stroke', '#2f9b6a');
        path.setAttribute('stroke-width', '3');
        path.setAttribute('stroke-linecap', 'round');
        if (preview) {
            path.setAttribute('stroke-dasharray', '7 5');
            path.setAttribute('data-preview-line', 'true');
        }
        return path;
    }
    function startQ2Drag(event, bullet) {
        if (!matchLayout || !matchSvg) return;
        if (event.button !== undefined && event.button !== 0) return;
        event.preventDefault();
        q2Dragging = {
            bullet,
            itemId: bullet.dataset.item,
            pointerId: event.pointerId,
            start: bulletCenter(bullet)
        };
        bullet.classList.add('is-selected');
        drawMatchLines();
        matchSvg.appendChild(
            createMatchPath(
                q2Dragging.start,
                pointerInMatchLayout(event),
                true
            )
        );
    }
    function moveQ2Drag(event) {
        if (!q2Dragging) return;
        if (q2Dragging.pointerId !== event.pointerId) return;
        event.preventDefault();
        const target = getQ2TargetAt(event);
        $$('[data-eval-target][data-question="q2"]').forEach(item => {
            item.classList.toggle('is-drop-hover', item === target);
        });
        const preview = matchSvg?.querySelector(
            '[data-preview-line="true"]'
        );
        if (!preview) return;
        const end = target
            ? bulletCenter(target)
            : pointerInMatchLayout(event);
        preview.setAttribute(
            'd',
            curvePath(q2Dragging.start, end)
        );
    }
    function finishQ2Drag(event) {
        if (!q2Dragging) return;
        if (q2Dragging.pointerId !== event.pointerId) return;
        const target = getQ2TargetAt(event);
        const itemId = q2Dragging.itemId;
        q2Dragging.bullet.classList.remove('is-selected');
        q2Dragging = null;
        $$('[data-eval-target][data-question="q2"]').forEach(item => {
            item.classList.remove('is-drop-hover');
        });
        if (target) {
            setAnswer('q2', itemId, target.dataset.target);
        } else {
            drawMatchLines();
        }
    }
    function cancelQ2Drag() {
        if (!q2Dragging) return;
        q2Dragging.bullet.classList.remove('is-selected');
        q2Dragging = null;
        $$('[data-eval-target][data-question="q2"]').forEach(item => {
            item.classList.remove('is-drop-hover');
        });
        drawMatchLines();
    }
    $$('[data-eval-item][data-question="q2"]').forEach(bullet => {
        bullet.addEventListener('pointerdown', event => {
            startQ2Drag(event, bullet);
        });
        bullet.addEventListener('dragstart', event => {
            event.preventDefault();
        });
    });
    /* =====================================================
       09. SOAL LAMA — MENGGAMBAR GARIS PERMANEN
       ===================================================== */
    function drawMatchLines() {
        if (!matchLayout || !matchSvg) return;
        matchSvg.replaceChildren();
        if (activeScreen !== 'q2') return;
        Object.entries(answers.q2).forEach(
            ([itemId, targetId]) => {
                const source = getItem('q2', itemId);
                const target = getTarget('q2', targetId);
                if (!source || !target) return;
                const start = bulletCenter(source);
                const end = bulletCenter(target);
                matchSvg.appendChild(
                    createMatchPath(start, end)
                );
            }
        );
    }
    function renderQ2Items() {
        $$('.sd-eval-match-row').forEach(row => {
            const itemId = row.dataset.matchRow;
            const targetId = answers.q2[itemId];
            row.classList.toggle(
                'is-assigned',
                Boolean(targetId)
            );
            const bullet = row.querySelector(
                '.sd-eval-bullet-source'
            );
            if (bullet) {
                bullet.title = targetId
                    ? `${getImageLabel('q2', itemId)} → ${getTargetLabel(targetId)}`
                    : `Seret untuk menghubungkan ${getImageLabel('q2', itemId)}`;
            }
        });
        requestAnimationFrame(drawMatchLines);
    }
    window.addEventListener('pointermove', event => {
        moveQ1Drag(event);
        moveQ2Drag(event);
    });
    window.addEventListener('pointerup', event => {
        finishQ1Drag(event);
        finishQ2Drag(event);
    });
    window.addEventListener('pointercancel', () => {
        removeQ1DragGhost();
        cancelQ2Drag();
    });
    window.addEventListener('resize', () => {
        if (activeScreen === 'q2') {
            requestAnimationFrame(drawMatchLines);
        }
    });
    window.addEventListener('scroll', () => {
        if (activeScreen === 'q2') {
            requestAnimationFrame(drawMatchLines);
        }
    }, true);
    /* =====================================================
       10. MENYIMPAN JAWABAN DAN FEEDBACK SOAL LAMA
       ===================================================== */
    function setAnswer(question, itemId, targetId) {
        if (!Object.hasOwn(correctAnswers[question], itemId)) {
            return;
        }
        if (
            !Object.values(correctAnswers[question])
                .includes(targetId)
        ) {
            return;
        }
        answers[question][itemId] = targetId;
        if (question === 'q1') {
            renderQ1Targets();
        }
        if (question === 'q2') {
            renderQ2Items();
        }
        renderFeedback(question);
    }
    function renderFeedback(question) {
        const feedback = question === 'q1'
            ? q1Feedback
            : q2Feedback;
        if (!feedback) return;
        const complete = isComplete(question);
        if (question === 'q1' && q1Next) {
            q1Next.hidden = !complete;
        }
        if (question === 'q2' && finishButton) {
            finishButton.hidden = !complete;
        }
        if (!complete) {
            feedback.hidden = true;
            feedback.replaceChildren();
            return;
        }
        const total = Object.keys(
            correctAnswers[question]
        ).length;
        const correct = getCorrectCount(question);
        feedback.hidden = false;
        feedback.className = correct === total
            ? 'sd-eval-feedback is-correct'
            : 'sd-eval-feedback is-warning';
        const heading = document.createElement('strong');
        heading.textContent = correct === total
            ? '✓ Jawaban benar! Semua pengelompokan tepat.'
            : `Kamu menjawab ${correct} dari ${total} dengan benar.`;
        const list = document.createElement('ul');
        Object.entries(correctAnswers[question]).forEach(
            ([itemId, correctTarget]) => {
                const item = document.createElement('li');
                const correctAnswer =
                    answers[question][itemId] === correctTarget;
                item.textContent =
                    `${getImageLabel(question, itemId)}: ` +
                    `${correctAnswer ? 'Benar.' : 'Belum tepat.'} ` +
                    explanations[question][itemId];
                list.appendChild(item);
            }
        );
        feedback.replaceChildren(heading, list);
    }
    /* =====================================================
       11. MENGHITUNG HASIL SOAL LAMA
       ===================================================== */
    function calculateFinalScore() {
        const total =
            Object.keys(correctAnswers.q1).length +
            Object.keys(correctAnswers.q2).length;
        const correct =
            getCorrectCount('q1') +
            getCorrectCount('q2');
        const score = total
            ? Math.round((correct / total) * 100)
            : 0;
        if (finalScore) {
            finalScore.textContent = String(score);
        }
        if (finalSummary) {
            finalSummary.textContent =
                `${correct} benar · ${total - correct} salah`;
        }
        if (score === 100) {
            if (finalHeading) {
                finalHeading.textContent =
                    'Hebat! Semua jawaban kamu benar.';
            }
            if (finalMessage) {
                finalMessage.textContent =
                    'Kamu memahami materi Bab 1 dengan sangat baik!';
            }
        } else if (score >= 70) {
            if (finalHeading) {
                finalHeading.textContent =
                    'Bagus! Hasil belajarmu sudah baik.';
            }
            if (finalMessage) {
                finalMessage.textContent =
                    'Kamu sudah memahami banyak materi. Tetap semangat!';
            }
        } else {
            if (finalHeading) {
                finalHeading.textContent =
                    'Tetap semangat belajar!';
            }
            if (finalMessage) {
                finalMessage.textContent =
                    'Pelajari kembali materinya dan coba lagi.';
            }
        }
    }
    if (finishButton) {
        finishButton.addEventListener('click', () => {
            if (!isComplete('q2')) return;
            showScreen('result');
        });
    }
    /* =====================================================
       12. RESET SOAL LAMA
       ===================================================== */
    function resetOldQuestions() {
        answers.q1 = {};
        answers.q2 = {};
        clearQ1Selection();
        removeQ1DragGhost();
        cancelQ2Drag();
        suppressQ1Click = false;
        [q1Feedback, q2Feedback].forEach(feedback => {
            if (!feedback) return;
            feedback.hidden = true;
            feedback.replaceChildren();
        });
        if (q1Next) q1Next.hidden = true;
        if (finishButton) finishButton.hidden = true;
        renderQ1Targets();
        renderQ2Items();
        if (matchSvg) {
            matchSvg.replaceChildren();
        }
    }
    if (retryButton) {
        retryButton.addEventListener('click', () => {
            resetOldQuestions();
            showScreen('overview');
        });
    }
    /* =====================================================
       13. KERANGKA 20 SOAL DAN TIMER 45 MENIT
       ===================================================== */
    /* 13.1. Pengaturan ujian */
    const EXAM_TOTAL_QUESTIONS = 20;
    const EXAM_DURATION_MINUTES = 45;
    const EXAM_DURATION_MS =
        EXAM_DURATION_MINUTES * 60 * 1000;
    // Key lama dipertahankan agar sesi sebelumnya
    // dapat dibersihkan dengan aman.
    const EXAM_END_KEY = 'saluang_eval_end_at_v1';
    const quizScreen = $('[data-eval-screen="quiz"]');
    const quizTimer = $('#sd-eval-timer');
    const quizTimerNotice = $('#sd-eval-timer-notice');
    const quizTimerPanel = $('.sd-eval-exam-timer-panel');
    const quizNumberGrid = $('#sd-eval-number-grid');
    const quizCurrentNumber = $('#sd-eval-current-number');
    const quizCurrentType = $('#sd-eval-current-type');
    const quizQuestionContent = $('#sd-eval-question-content');
    const quizPrev = $('#sd-eval-quiz-prev');
    const quizNext = $('#sd-eval-quiz-next');
    const quizLastNote = $('#sd-eval-quiz-last-note');
    let quizCurrentIndex = 0;
    let quizEndAt = null;
    let quizInterval = null;
    let quizExpired = false;
    /* 13.2. Membaca waktu sesi aktif */
    function readExamEndTime() {
        try {
            const value = Number(
                localStorage.getItem(EXAM_END_KEY)
            );
            return Number.isFinite(value) && value > 0
                ? value
                : null;
        } catch (error) {
            return null;
        }
    }
    /* 13.3. Menyimpan waktu sesi aktif */
    function saveExamEndTime(value) {
        try {
            localStorage.setItem(
                EXAM_END_KEY,
                String(value)
            );
        } catch (error) {
            // Timer tetap bisa berjalan di tab aktif.
        }
    }
    /* 13.4. Menghentikan interval timer */
    function stopExamInterval() {
        if (quizInterval !== null) {
            window.clearInterval(quizInterval);
            quizInterval = null;
        }
    }
    /* 13.5. RESET TOTAL SESI UJIAN */
    function resetExamSession() {
        stopExamInterval();
        try {
            localStorage.removeItem(EXAM_END_KEY);
        } catch (error) {
            // Reset state tetap dilanjutkan.
        }
        quizEndAt = null;
        quizExpired = false;
        quizCurrentIndex = 0;
        if (quizTimer) {
            quizTimer.textContent = '45:00';
        }
        if (quizTimerNotice) {
            quizTimerNotice.textContent = '';
        }
        if (quizTimerPanel) {
            quizTimerPanel.classList.remove(
                'is-urgent',
                'is-expired'
            );
        }
        renderExamQuestion();
        renderExamNumberGrid();
        renderExamNavigation();
        // Jawaban layar lama juga dikosongkan.
        resetOldQuestions();
    }
    /* 13.6. Memulai sesi baru dari 45 menit */
    function startNewExamSession() {
        // Hapus semua pengaruh sesi sebelumnya.
        resetExamSession();
        quizEndAt = Date.now() + EXAM_DURATION_MS;
        saveExamEndTime(quizEndAt);
        updateExamTimer();
        if (!quizExpired) {
            quizInterval = window.setInterval(
                updateExamTimer,
                1000
            );
        }
        showScreen('quiz');
        renderExamQuestion();
    }
    /* 13.7. Menampilkan sisa waktu */
    function updateExamTimer() {
        if (!quizEndAt || !quizTimer) return;
        const remainingMilliseconds = Math.max(
            0,
            quizEndAt - Date.now()
        );
        const remainingSeconds = Math.ceil(
            remainingMilliseconds / 1000
        );
        const minutes = Math.floor(
            remainingSeconds / 60
        );
        const seconds = remainingSeconds % 60;
        quizTimer.textContent =
            `${String(minutes).padStart(2, '0')}:` +
            `${String(seconds).padStart(2, '0')}`;
        if (quizTimerPanel) {
            quizTimerPanel.classList.toggle(
                'is-urgent',
                remainingSeconds > 0 &&
                remainingSeconds <= 300
            );
        }
        if (remainingSeconds === 0) {
            expireExamTimer();
        } else if (quizTimerNotice) {
            quizTimerNotice.textContent =
                'Waktu berjalan selama evaluasi berlangsung.';
        }
    }
    /* 13.8. Saat waktu ujian habis */
    function expireExamTimer() {
        if (quizExpired) return;
        quizExpired = true;
        stopExamInterval();
        if (quizTimer) {
            quizTimer.textContent = '00:00';
        }
        if (quizTimerPanel) {
            quizTimerPanel.classList.remove('is-urgent');
            quizTimerPanel.classList.add('is-expired');
        }
        if (quizTimerNotice) {
            quizTimerNotice.textContent =
                'Waktu pengerjaan telah habis.';
        }
        if (quizQuestionContent) {
            quizQuestionContent.innerHTML = `
                <div class="sd-eval-question-placeholder">
                    <i class="bi bi-clock-history"></i>
                    <h2>Waktu Evaluasi Habis</h2>
                    <p>
                        Sesi ini telah berakhir.
                        Pilih Kembali ke Evaluasi,
                        lalu mulai sesi baru jika ingin mencoba lagi.
                    </p>
                </div>
            `;
        }
        renderExamNavigation();
        renderExamNumberGrid();
    }
    /* 13.9. Jenis soal berdasarkan nomor */
    function getExamQuestionType(index) {
        if (index < 5) {
            return 'Pilihan Ganda ABCD';
        }
        if (index < 10) {
            return 'Drag-and-Drop Gambar';
        }
        if (index < 15) {
            return 'Menghubungkan Bullet';
        }
        return 'Mencari Gambar';
    }
    /* 13.10. Menampilkan nomor dan kerangka soal */
    function renderExamQuestion() {
        if (!quizScreen || quizExpired) return;
        const number = quizCurrentIndex + 1;
        const type = getExamQuestionType(quizCurrentIndex);
        if (quizCurrentNumber) {
            quizCurrentNumber.textContent =
                `Soal ${number} dari ${EXAM_TOTAL_QUESTIONS}`;
        }
        if (quizCurrentType) {
            quizCurrentType.textContent = type;
        }
        // Isi 20 soal sesungguhnya akan ditambahkan
        // pada tahap selanjutnya.
        if (quizQuestionContent) {
            quizQuestionContent.innerHTML = `
                <div class="sd-eval-question-placeholder">
                    <i class="bi bi-journal-text"></i>
                    <h2>Soal Nomor ${number}</h2>
                    <p>
                        Jenis soal: ${type}.
                        Isi soal akan dimasukkan pada tahap berikutnya.
                    </p>
                </div>
            `;
        }
        renderExamNavigation();
        renderExamNumberGrid();
    }
    /* 13.11. Navigasi Previous dan Next */
    function renderExamNavigation() {
        // Previous disembunyikan pada soal nomor 1.
        if (quizPrev) {
            const isFirstQuestion = quizCurrentIndex === 0;
            quizPrev.hidden = isFirstQuestion;
            quizPrev.disabled = quizExpired;
        }
        // Next tidak tampil pada soal terakhir.
        if (quizNext) {
            const isLastQuestion =
                quizCurrentIndex === EXAM_TOTAL_QUESTIONS - 1;
            quizNext.hidden = isLastQuestion;
            quizNext.disabled = quizExpired;
        }
        if (quizLastNote) {
            quizLastNote.hidden =
                quizExpired ||
                quizCurrentIndex !== EXAM_TOTAL_QUESTIONS - 1;
        }
    }
    function goToExamQuestion(index) {
        if (quizExpired) return;
        if (
            index < 0 ||
            index >= EXAM_TOTAL_QUESTIONS
        ) {
            return;
        }
        quizCurrentIndex = index;
        renderExamQuestion();
    }
    if (quizPrev) {
        quizPrev.addEventListener('click', () => {
            goToExamQuestion(quizCurrentIndex - 1);
        });
    }
    if (quizNext) {
        quizNext.addEventListener('click', () => {
            goToExamQuestion(quizCurrentIndex + 1);
        });
    }
    /* 13.12. Daftar nomor soal 1–20 */
    function renderExamNumberGrid() {
        if (!quizNumberGrid) return;
        quizNumberGrid.replaceChildren();
        for (
            let index = 0;
            index < EXAM_TOTAL_QUESTIONS;
            index++
        ) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'sd-eval-number-button';
            button.textContent = String(index + 1);
            button.setAttribute(
                'aria-label',
                `Buka soal nomor ${index + 1}`
            );
            if (index === quizCurrentIndex) {
                button.classList.add('is-active');
                button.setAttribute('aria-current', 'step');
            }
            button.disabled = quizExpired;
            button.addEventListener('click', () => {
                goToExamQuestion(index);
            });
            quizNumberGrid.appendChild(button);
        }
    }
    /* 13.13. Menjaga timer saat tab tidak aktif */
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden && quizEndAt) {
            updateExamTimer();
        }
    });
    window.addEventListener('pageshow', () => {
        if (quizEndAt) {
            updateExamTimer();
        }
    });
    /* =====================================================
       14. MODAL KONFIRMASI SALUANG
       ===================================================== */
    /* 14.1. Elemen modal */
    const confirmModal = $('#sd-eval-confirm');
    const confirmDialog = $('.sd-eval-confirm-dialog');
    const confirmTitle = $('#sd-eval-confirm-title');
    const confirmDescription = $('#sd-eval-confirm-description');
    const confirmInfo = $('#sd-eval-confirm-info');
    const confirmImage = $('#sd-eval-confirm-icon');
    const confirmAcceptText = $('#sd-eval-confirm-accept-text');
    const confirmAccept = $('#sd-eval-confirm-accept');
    const confirmCancel = $('#sd-eval-confirm-cancel');
    const confirmClose = $('#sd-eval-confirm-close');
    let pendingConfirmAction = null;
    let previousFocusedElement = null;
    /* 14.2. Membuka modal Mulai atau Keluar */
    let confirmImageRequest = 0;
    async function openEvaluationConfirm(type) {
        if (!confirmModal) return;
        const requestId = ++confirmImageRequest;
        pendingConfirmAction = type;
        previousFocusedElement = document.activeElement;
        const isExit = type === 'exit';
        confirmModal.classList.toggle('is-exit', isExit);
        /* 14.2.1. Tentukan gambar dan teks */
        const imagePath = isExit
            ? '/images/evaluasi/bekantan-sedih.png'
            : '/images/evaluasi/bekantan-bertanya.png';
        if (isExit) {
            /* MODAL KELUAR — BEKANTAN SEDIH */
            if (confirmTitle) {
                confirmTitle.textContent =
                    'Yakin Ingin Keluar dari Evaluasi?';
            }
            if (confirmDescription) {
                confirmDescription.textContent =
                    'Jika keluar sekarang, pengerjaan evaluasi ' +
                    'akan dibatalkan. Kamu dapat memulai kembali ' +
                    'dari soal pertama.';
            }
            const infoText = confirmInfo?.querySelector('span');
            if (infoText) {
                infoText.textContent =
                    'Jika memilih Ya, Keluar, timer direset. ' +
                    'Saat memulai lagi, waktu kembali menjadi 45 menit.';
            }
            if (confirmAcceptText) {
                confirmAcceptText.textContent = 'Ya, Keluar';
            }
        } else {
            /* MODAL MULAI — BEKANTAN BERTANYA */
            if (confirmTitle) {
                confirmTitle.textContent =
                    'Yakin Ingin Memulai Evaluasi?';
            }
            if (confirmDescription) {
                confirmDescription.textContent =
                    'Kamu akan mengerjakan 20 soal dengan waktu ' +
                    '45 menit. Pastikan sudah siap!';
            }
            const infoText = confirmInfo?.querySelector('span');
            if (infoText) {
                infoText.textContent =
                    'Waktu dimulai setelah kamu menekan Ya, Mulai.';
            }
            if (confirmAcceptText) {
                confirmAcceptText.textContent = 'Ya, Mulai';
            }
        }
        /* 14.2.2. Muat gambar sebelum membuka modal */
        if (confirmImage) {
            // Modal belum ditampilkan saat gambar dipersiapkan.
            confirmModal.hidden = true;
            const preparedImage = new Image();
            preparedImage.src = imagePath;
            try {
                await preparedImage.decode();
            } catch (error) {
                // Jika decode gagal, tetap coba tampilkan gambar.
            }
            // Abaikan permintaan lama jika ada permintaan baru.
            if (requestId !== confirmImageRequest) {
                return;
            }
            // Gunakan gambar yang sudah disiapkan.
            confirmImage.src = imagePath;
            confirmImage.alt = isExit
                ? 'Bekantan sedih karena siswa ingin keluar'
                : 'Bekantan sedang berpikir sebelum memulai evaluasi';
        }
        /* 14.2.3. Baru tampilkan modal */
        if (requestId !== confirmImageRequest) {
            return;
        }
        confirmModal.hidden = false;
        confirmDialog?.focus();
    }
    /* 14.3. Menutup modal */
    function closeEvaluationConfirm() {
        if (!confirmModal) return;
        confirmModal.hidden = true;
        confirmModal.classList.remove('is-exit');
        pendingConfirmAction = null;
        if (
            previousFocusedElement &&
            typeof previousFocusedElement.focus === 'function'
        ) {
            previousFocusedElement.focus();
        }
        previousFocusedElement = null;
    }
    /* 14.4. Klik Mulai dan Kembali ke Evaluasi */
    root.addEventListener('click', event => {
        const startButton = event.target.closest(
            '[data-eval-open="quiz"]'
        );
        if (startButton) {
            event.preventDefault();
            event.stopImmediatePropagation();
            openEvaluationConfirm('start');
            return;
        }
        const exitButton = event.target.closest(
            '#sd-eval-quiz-exit'
        );
        if (exitButton) {
            event.preventDefault();
            event.stopImmediatePropagation();
            openEvaluationConfirm('exit');
        }
    }, true);
    /* 14.5. Tombol Ya, Mulai dan Ya, Keluar */
    if (confirmAccept) {
        confirmAccept.addEventListener('click', () => {
            const action = pendingConfirmAction;
            closeEvaluationConfirm();
            if (action === 'start') {
                // SELALU mulai sesi baru dari 45:00.
                startNewExamSession();
                return;
            }
            if (action === 'exit') {
                // Hapus sesi, reset timer dan kembali.
                resetExamSession();
                showScreen('overview');
            }
        });
    }
    /* 14.6. Tombol Batal dan X */
    if (confirmCancel) {
        confirmCancel.addEventListener(
            'click',
            closeEvaluationConfirm
        );
    }
    if (confirmClose) {
        confirmClose.addEventListener(
            'click',
            closeEvaluationConfirm
        );
    }
    /* 14.7. Klik backdrop untuk batal */
    const confirmBackdrop = confirmModal?.querySelector(
        '.sd-eval-confirm-backdrop'
    );
    if (confirmBackdrop) {
        confirmBackdrop.addEventListener(
            'click',
            closeEvaluationConfirm
        );
    }
    /* 14.8. Tombol Escape */
    document.addEventListener('keydown', event => {
        if (!confirmModal || confirmModal.hidden) return;
        if (event.key === 'Escape') {
            event.preventDefault();
            closeEvaluationConfirm();
            return;
        }
        if (event.key === 'Tab') {
            const controls = [
                confirmClose,
                confirmCancel,
                confirmAccept
            ].filter(Boolean);
            const first = controls[0];
            const last = controls[controls.length - 1];
            if (
                event.shiftKey &&
                document.activeElement === first
            ) {
                event.preventDefault();
                last.focus();
            } else if (
                !event.shiftKey &&
                document.activeElement === last
            ) {
                event.preventDefault();
                first.focus();
            } else if (
                !controls.includes(document.activeElement)
            ) {
                event.preventDefault();
                first.focus();
            }
        }
    });
    /* =====================================================
       15. INISIALISASI EVALUASI
       ===================================================== */
    // Bersihkan timer lama jika sudah kedaluwarsa.
    // Tidak memunculkan alert atau dialog browser.
    const savedEndAt = readExamEndTime();
    if (savedEndAt && savedEndAt > Date.now()) {
        // Jika siswa me-refresh saat sesi masih aktif,
        // waktu tetap berdasarkan sesi tersebut.
        quizEndAt = savedEndAt;
        updateExamTimer();
        if (!quizExpired) {
            quizInterval = window.setInterval(
                updateExamTimer,
                1000
            );
        }
    } else {
        // Waktu percobaan lama dihapus otomatis.
        resetExamSession();
    }
    renderQ1Targets();
    renderQ2Items();
    renderExamNumberGrid();
    renderExamQuestion();
    showScreen('overview');
});