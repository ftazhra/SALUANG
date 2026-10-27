/* =====================================================
   SALUANG — EVALUASI SISWA BAB VI
   Versi integrasi bank soal Guru (15 soal)   
*/
document.addEventListener('DOMContentLoaded', () => {
    'use strict';
    const root = document.getElementById('sd-section-evaluasi');
    if (!root)
        return;
    const el = id => document.getElementById(id), api = '/siswa/evaluations';
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const screens = [...root.querySelectorAll('[data-eval-screen]')];
    const grid = el('sd-eval-number-grid'), content = el('sd-eval-question-content');
    const prev = el('sd-eval-quiz-prev'), next = el('sd-eval-quiz-next'), timer = el('sd-eval-timer');
    const modal = el('sd-eval-confirm');
    const startButton = root.querySelector('[data-eval-open="quiz"]');
    const retryButton = el('sd-eval-retry');
    let passed = false;
    let kkm = 70;
    let questions = [], answers = {}, index = 0, endAt = null, interval = null, mode = 'overview', selected = null, busy = false, saveQueue = Promise.resolve(), drag = null, matchContext = null, matchPointer = null;
    const labels = { pilihan_ganda: 'Pilihan Ganda', drag_drop: 'Drag-and-Drop', menghubungkan: 'Menghubungkan Pasangan' };
    const n = q => String(q.nomor_soal);
    function make(tag, txt = '', cls = '') { const x = document.createElement(tag); x.textContent = txt; x.className = cls; return x; }
    async function request(url, method = 'GET', body = null) {
        const opts = { method, credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf } };
        if (body !== null) {
            opts.headers['Content-Type'] = 'application/json';
            opts.body = JSON.stringify(body);
        }
        const r = await fetch(url, opts);
        let data;
        try {
            data = await r.json();
        }
        catch {
            throw Error('Respons server tidak valid.');
        }
        if (!r.ok)
            throw Error(Object.values(data.errors || {})[0]?.[0] || data.message || 'Terjadi kesalahan.');
        return data;
    }
    function message(text) {
        const notice = el('sd-eval-timer-notice');
        if (notice) {
            notice.textContent = String(text);
        } else {
            // Teks informasi bersifat opsional; jangan menggagalkan submit.
            console.info('SALUANG Evaluasi:', text);
        }
    }
    function show(name) {
        mode = name;
        screens.forEach(s => s.classList.toggle('is-active', s.dataset.evalScreen === name));
        const outer = root.querySelector('.sd-eval-outer-navigation');
        if (outer)
            outer.hidden = name !== 'overview';
    }
    function cleanSession() { clearInterval(interval); interval = null; endAt = null; questions = []; answers = {}; index = 0; selected = null; matchPointer = null; matchContext = null; }
    function remaining() { return endAt ? Math.max(0, new Date(endAt).getTime() - Date.now()) : 0; }
    // Timer mengikuti ends_at Laravel, bukan menghitung mundur secara lokal.
    // Pembaruan tetap berjalan sekalipun proses kirim jawaban mengalami error.
    let timerSubmitting = false;
    let nextExpiryRetryAt = 0;
    function updateTimer() {
        if (!endAt || !timer) return;
        const deadline = new Date(endAt).getTime();
        if (!Number.isFinite(deadline)) {
            message('Batas waktu evaluasi tidak valid. Silakan muat ulang halaman.');
            return;
        }
        const secondsLeft = Math.max(0, Math.ceil((deadline - Date.now()) / 1000));
        timer.textContent = `${String(Math.floor(secondsLeft / 60)).padStart(2, '0')}:${String(secondsLeft % 60).padStart(2, '0')}`;
        const panel = root.querySelector('.sd-eval-exam-timer-panel');
        panel?.classList.toggle('is-urgent', secondsLeft > 0 && secondsLeft <= 300);
        panel?.classList.toggle('is-expired', secondsLeft === 0);
        if (secondsLeft === 0 && mode === 'quiz' && !timerSubmitting && !busy && Date.now() >= nextExpiryRetryAt) {
            timerSubmitting = true;
            nextExpiryRetryAt = Date.now() + 15000;
            finish(true).finally(() => { timerSubmitting = false; });
        }
    }
    function runTimer() {
        if (interval !== null) window.clearInterval(interval);
        interval = null;
        if (!endAt) return;
        updateTimer();
        interval = window.setInterval(updateTimer, 1000);
    }
    // Browser dapat menunda interval pada tab tidak aktif; hitung ulang dari deadline.
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden && endAt) updateTimer();
    });
    window.addEventListener('focus', () => { if (endAt) updateTimer(); });
    window.addEventListener('pageshow', () => { if (endAt) updateTimer(); });

    function filled(q) {
        const a = answers[n(q)];
        if (q.tipe_soal === 'pilihan_ganda')
            return typeof a === 'string' && a.length > 0;
        const items = q.konten_soal?.items || [];
        return items.length > 0 && items.every(item => a && a[item.id]);
    }
    function nav() {
        grid.replaceChildren();
        questions.forEach((q, i) => {
            const b = make('button', String(q.nomor_soal), 'sd-eval-number-button');
            b.type = 'button';
            if (i === index)
                b.classList.add('is-active');
            if (filled(q))
                b.classList.add('is-answered');
            b.onclick = () => { index = i; render(); };
            grid.append(b);
        });
        prev.hidden = index === 0;
        // Gunakan tombol Next yang sudah ada pada layar ujian.
        // Pada soal ke-15 tombol yang sama mengirim evaluasi.
        const last = index === questions.length - 1;
        next.hidden = false;
        next.disabled = busy;
        next.textContent = last ? 'Selesai Evaluasi' : 'Next';
        const note = el('sd-eval-quiz-last-note');
        if (note) note.hidden = !last;
    }
    function setAnswer(q, value) {
        if (busy || remaining() <= 0)
            return;
        answers[n(q)] = value;
        nav();
        queueSave();
    }
    function queueSave() {
        const snapshot = JSON.parse(JSON.stringify(answers));
        saveQueue = saveQueue.catch(() => {}).then(() => request(`${api}/answers`, 'PUT', { answers: snapshot }));
        saveQueue.catch(e => message('Gagal menyimpan jawaban: ' + e.message));
    }
    // =====================================================
    // ELEMEN INTERAKTIF: GAMBAR DAN GARIS PENGHUBUNG
    // =====================================================
    function imageNode(item) {
        const img = document.createElement('img');
        img.src = item.image || '';
        img.alt = item.label || 'Ilustrasi soal';
        img.draggable = false;
        return img;
    }

    function itemCard(item, className) {
        const card = make('span', '', className);
        card.append(imageNode(item), make('span', item.label || '', 'se-image-caption'));
        return card;
    }

    const SVG_NS = 'http://www.w3.org/2000/svg';
    function svgPath(start, end, preview = false) {
        const path = document.createElementNS(SVG_NS, 'path');
        const bend = Math.max(32, (end.x - start.x) * 0.42);
        path.setAttribute('d', `M ${start.x} ${start.y} C ${start.x + bend} ${start.y}, ${end.x - bend} ${end.y}, ${end.x} ${end.y}`);
        path.setAttribute('fill', 'none');
        path.setAttribute('stroke', preview ? '#5cb899' : '#15835b');
        path.setAttribute('stroke-width', '3');
        path.setAttribute('stroke-linecap', 'round');
        if (preview) path.setAttribute('stroke-dasharray', '7 5');
        return path;
    }

    function relativeCenter(element, layout) {
        const a = element.getBoundingClientRect();
        const b = layout.getBoundingClientRect();
        return { x: a.left + a.width / 2 - b.left, y: a.top + a.height / 2 - b.top };
    }

    function relativePointer(event, layout) {
        const b = layout.getBoundingClientRect();
        return { x: event.clientX - b.left, y: event.clientY - b.top };
    }

    function redrawMatchLines() {
        if (!matchContext || mode !== 'quiz') return;
        const { layout, svg, question } = matchContext;
        if (!layout.isConnected) return;
        svg.replaceChildren();
        const pairs = answers[n(question)] || {};
        Object.entries(pairs).forEach(([itemId, targetId]) => {
            const source = [...layout.querySelectorAll('[data-source-bullet]')]
                .find(x => x.dataset.sourceBullet === String(itemId));
            const target = [...layout.querySelectorAll('[data-target-bullet]')]
                .find(x => x.dataset.targetBullet === String(targetId));
            if (source && target) {
                svg.append(svgPath(relativeCenter(source, layout), relativeCenter(target, layout)));
            }
        });
        if (matchPointer) {
            const source = [...layout.querySelectorAll('[data-source-bullet]')]
                .find(x => x.dataset.sourceBullet === String(matchPointer.itemId));
            if (source) svg.append(svgPath(relativeCenter(source, layout), matchPointer.point, true));
        }
    }

    function assignPair(question, itemId, targetId) {
        const previous = answers[n(question)] || {};
        setAnswer(question, { ...previous, [itemId]: targetId });
        selected = null;
        matchPointer = null;
        render();
    }

    window.addEventListener('pointermove', event => {
        if (!matchPointer || event.pointerId !== matchPointer.pointerId || !matchContext) return;
        matchPointer.point = relativePointer(event, matchContext.layout);
        redrawMatchLines();
    });

    window.addEventListener('pointerup', event => {
        if (!matchPointer || event.pointerId !== matchPointer.pointerId || !matchContext) return;
        const { question, layout } = matchContext;
        const target = document.elementFromPoint(event.clientX, event.clientY)?.closest('[data-match-target]');
        const targetId = target?.dataset.matchTarget;
        const valid = question.konten_soal?.targets?.some(t => String(t.id) === String(targetId));
        const itemId = matchPointer.itemId;
        matchPointer = null;
        if (valid) assignPair(question, itemId, targetId);
        else redrawMatchLines();
    });

    window.addEventListener('pointercancel', () => {
        matchPointer = null;
        redrawMatchLines();
    });
    window.addEventListener('resize', () => requestAnimationFrame(redrawMatchLines));
    window.addEventListener('scroll', () => requestAnimationFrame(redrawMatchLines), true);

    function renderResultDetails(details) {
        const list = el('sd-eval-result-details');
        if (!list) return;
        list.replaceChildren();
        if (!Array.isArray(details) || !details.length) {
            list.append(make('p', 'Rekap belum tersedia. Muat ulang halaman untuk mengambil hasil.', 'se-result-empty'));
            return;
        }
        details.forEach(detail => {
            const item = make('article', '', 'se-review-card ' + (detail.benar ? 'is-correct' : 'is-wrong'));
            const header = make('div', '', 'se-review-head');
            header.append(make('strong', `Soal ${detail.nomor_soal}`, ''),
                make('span', detail.benar ? 'Benar' : 'Salah', 'se-review-status'));
            item.append(header, make('p', detail.pertanyaan || '', 'se-review-question'));
            if (Array.isArray(detail.pairs) && detail.pairs.length) {
                detail.pairs.forEach(pair => {
                    const row = make('div', '', 'se-review-pair ' + (pair.benar ? 'is-correct' : 'is-wrong'));
                    row.append(make('strong', pair.label || '', ''),
                        make('span', `Jawabanmu: ${pair.jawaban_siswa || 'Belum dijawab'}`, ''),
                        make('span', `Kunci: ${pair.jawaban_benar || '-'}`, 'se-review-key'));
                    item.append(row);
                });
            } else {
                item.append(make('p', `Jawabanmu: ${detail.jawaban_siswa || 'Belum dijawab'}`, ''),
                    make('p', `Jawaban benar: ${detail.jawaban_benar || '-'}`, 'se-review-key'));
            }
            if (detail.pembahasan) item.append(make('p', `Pembahasan: ${detail.pembahasan}`, 'se-review-explanation'));
            list.append(item);
        });
    }

    function renderChoice(q) {
        const choices = q.konten_soal || {};
        const wrap = make('div', '', 'se-choices');
        ['A', 'B', 'C', 'D'].forEach(letter => {
            const b = make('button', '', 'se-choice');
            b.type = 'button';
            if (answers[n(q)] === letter)
                b.classList.add('is-chosen');
            b.append(make('span', letter, 'se-letter'), make('span', choices['pilihan_' + letter.toLowerCase()] || '', ''));
            b.onclick = () => { setAnswer(q, letter); render(); };
            wrap.append(b);
        });
        content.append(wrap);
    }
    function renderDrag(q) {
        const items = q.konten_soal?.items || [];
        const targets = q.konten_soal?.targets || [];
        const pair = answers[n(q)] || {};
        content.append(make('p', 'Seret gambar ke kategori yang tepat, atau pilih gambar lalu ketuk kategori.', 'se-tip'));
        const tray = make('div', '', 'se-items');
        const zones = make('div', '', 'se-targets');

        items.forEach(item => {
            const button = make('button', '', 'se-item');
            button.type = 'button';
            button.draggable = true;
            button.append(itemCard(item, 'se-image-card'));
            if (String(selected) === String(item.id)) button.classList.add('is-selected');
            if (pair[item.id]) button.classList.add('is-assigned');
            button.addEventListener('click', () => { selected = item.id; render(); });
            button.addEventListener('dragstart', event => {
                drag = item.id;
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', String(item.id));
                const preview = button.querySelector('img');
                if (preview) event.dataTransfer.setDragImage(preview, preview.width / 2, preview.height / 2);
            });
            button.addEventListener('dragend', () => { drag = null; });
            tray.append(button);
        });

        targets.forEach(target => {
            const zone = make('div', '', 'se-target se-drop-zone');
            zone.setAttribute('role', 'group');
            zone.setAttribute('aria-label', `Kategori ${target.label}`);
            const heading = make('button', target.label, 'se-drop-heading');
            heading.type = 'button';
            heading.addEventListener('click', () => {
                if (selected === null) return;
                setAnswer(q, { ...pair, [selected]: target.id });
                selected = null;
                render();
            });
            const collection = make('div', '', 'se-drop-collection');
            items.filter(item => String(pair[item.id]) === String(target.id)).forEach(item => {
                collection.append(itemCard(item, 'se-image-card is-dropped'));
            });
            zone.append(heading, collection);
            zone.addEventListener('dragover', event => {
                event.preventDefault();
                event.dataTransfer.dropEffect = 'move';
                zone.classList.add('is-hover');
            });
            zone.addEventListener('dragleave', event => {
                if (!zone.contains(event.relatedTarget)) zone.classList.remove('is-hover');
            });
            zone.addEventListener('drop', event => {
                event.preventDefault();
                zone.classList.remove('is-hover');
                const itemId = event.dataTransfer.getData('text/plain') || drag;
                if (!items.some(item => String(item.id) === String(itemId))) return;
                setAnswer(q, { ...pair, [itemId]: target.id });
                selected = null;
                drag = null;
                render();
            });
            zones.append(zone);
        });
        content.append(tray, zones);
    }
    function renderMatch(q) {
        const items = q.konten_soal?.items || [];
        const targets = q.konten_soal?.targets || [];
        content.append(make('p', 'Tarik bullet hijau pada gambar ke bullet jawaban, atau ketuk bullet sumber lalu target.', 'se-tip'));
        const layout = make('div', '', 'se-match-layout');
        const svg = document.createElementNS(SVG_NS, 'svg');
        svg.classList.add('se-match-lines');
        svg.setAttribute('aria-hidden', 'true');
        const left = make('div', '', 'se-match-column se-match-left');
        const right = make('div', '', 'se-match-column se-match-right');

        items.forEach(item => {
            const row = make('div', '', 'se-match-source-row');
            const card = itemCard(item, 'se-match-image-card');
            const bullet = make('button', '', 'se-match-bullet se-match-bullet-source');
            bullet.type = 'button';
            bullet.dataset.sourceBullet = item.id;
            bullet.setAttribute('aria-label', `Hubungkan ${item.label || 'gambar'}`);
            bullet.addEventListener('pointerdown', event => {
                if (event.button !== undefined && event.button !== 0) return;
                if (remaining() <= 0) return;
                event.preventDefault();
                selected = item.id;
                matchPointer = { pointerId: event.pointerId, itemId: item.id, point: relativePointer(event, layout) };
                redrawMatchLines();
            });
            bullet.addEventListener('click', () => {
                selected = item.id;
                row.classList.add('is-selected');
            });
            if (String(selected) === String(item.id)) row.classList.add('is-selected');
            row.append(card, bullet);
            left.append(row);
        });

        targets.forEach(target => {
            const row = make('button', '', 'se-match-target-row');
            row.type = 'button';
            row.dataset.matchTarget = target.id;
            const bullet = make('span', '', 'se-match-bullet se-match-bullet-target');
            bullet.dataset.targetBullet = target.id;
            row.append(bullet, make('span', target.label || '', 'se-match-target-title'));
            row.addEventListener('click', () => {
                if (selected === null || remaining() <= 0) return;
                assignPair(q, selected, target.id);
            });
            right.append(row);
        });
        layout.append(svg, left, right);
        content.append(layout);
        matchContext = { layout, svg, question: q };
        requestAnimationFrame(redrawMatchLines);
    }
    function render() {
        const q = questions[index];
        if (!q)
            return;
        el('sd-eval-current-number').textContent = `Soal ${q.nomor_soal} dari ${questions.length}`;
        el('sd-eval-current-type').textContent = labels[q.tipe_soal] || q.tipe_soal;
        matchContext = null;
        matchPointer = null;
        content.replaceChildren();
        content.append(make('h2', q.pertanyaan, 'se-question-title'));
        if (q.tipe_soal === 'pilihan_ganda')
            renderChoice(q);
        else if (q.tipe_soal === 'drag_drop')
            renderDrag(q);
        else
            renderMatch(q);
        nav();
    }
    // Menampilkan hasil secara aman: elemen ringkasan tambahan boleh belum ada.
    function setResultText(id, value) {
        const element = el(id);
        if (element) element.textContent = String(value);
    }

    function updateAccess() {
        if (startButton) {
            startButton.hidden = passed;
            startButton.disabled = passed;
        }
        if (retryButton) retryButton.hidden = passed;
        const lock = el('sd-eval-locked-message');
        if (lock) lock.hidden = !passed;
    }

    function results(data) {
        const score = Number(data?.score ?? 0);
        const correct = Number(data?.correct_count ?? 0);
        const wrong = Math.max(0, 15 - correct);
        passed = passed || score >= kkm;
        updateAccess();
        const panel = el('sd-eval-result-panel');
        if (panel) panel.classList.toggle('is-passed', passed);
        if (panel) panel.classList.toggle('is-unpassed', !passed);
        setResultText('sd-eval-result-kkm', kkm);
        setResultText('sd-eval-result-status', passed ? 'TUNTAS' : 'BELUM TUNTAS');

        // Pindah layar lebih dahulu; jangan sampai elemen opsional menghalangi hasil.
        show('result');
        setResultText('sd-eval-final-score', score);
        setResultText('sd-eval-final-summary', `${correct} benar · ${wrong} salah`);
        setResultText('sd-eval-summary-score', score);
        setResultText('sd-eval-summary-correct', correct);
        setResultText('sd-eval-summary-wrong', wrong);
        setResultText('sd-eval-result-heading', passed
            ? 'Hebat! Kamu sudah mencapai ketuntasan.'
            : 'Evaluasi selesai. Terus tingkatkan pemahamanmu!');
        setResultText('sd-eval-final-message', passed
            ? 'Selamat! Nilai kamu sudah memenuhi KKM. Evaluasi telah dikunci dan tidak perlu diulang.'
            : 'Nilaimu belum mencapai KKM. Pelajari kembali materi Bab VI, kemudian coba lagi.');

        cleanSession();
    }

    async function finish(auto = false) {
        if (busy || mode === 'result') return;
        busy = true;
        if (next) {
            next.disabled = true;
            next.textContent = 'Mengirim...';
        }
        message('Mengirim jawaban dan menyiapkan hasil evaluasi...');
        // Timer sengaja TIDAK dihentikan sebelum server mengonfirmasi selesai.
        try {
            // Jangan membiarkan kegagalan autosave lama memblokir pengiriman.
            await saveQueue.catch(error => {
                console.warn('SALUANG: autosave sebelumnya gagal, mencoba ulang.', error);
            });
            if (endAt && remaining() > 0) {
                // Kirim snapshot terakhir supaya jawaban tidak terlewat.
                await request(`${api}/answers`, 'PUT', { answers: { ...answers } });
            }
            const data = await request(`${api}/finish`, 'POST', {});
            results(data);
        } catch (error) {
            console.error('SALUANG: pengiriman evaluasi gagal:', error);
            // Bisa saja finish tersimpan (HTTP 200), tapi respons/renderer gagal.
            // Baca ulang status dari server sebelum menyimpulkan submit gagal.
            let recovered = false;
            try {
                const state = await request(`${api}/state`);
                if (state.status === 'completed' && state.attempt) {
                    results(state.attempt);
                    recovered = true;
                }
            } catch (stateError) {
                console.error('SALUANG: gagal mengecek status hasil:', stateError);
            }
            if (!recovered) {
                message('Belum bisa menyelesaikan evaluasi: ' + error.message + '. Coba klik Selesai Evaluasi lagi.');
            }
        } finally {
            busy = false;
            if (mode === 'quiz' && next) {
                next.disabled = false;
                next.textContent = index === questions.length - 1 ? 'Selesai Evaluasi' : 'Next';
            }
            if (mode === 'quiz' && endAt && interval === null) runTimer();
        }
    }
    async function start() {
        if (busy)
            return;
        busy = true;
        try {
            if (passed) return;
            const state = await request(`${api}/start`, 'POST', {});
            questions = state.questions;
            answers = {};
            index = 0;
            endAt = state.ends_at;
            selected = null;
            show('quiz');
            render();
            runTimer();
            message('Jawaban disimpan secara otomatis.');
        }
        catch (e) {
            alert(e.message);
        }
        finally {
            busy = false;
        }
    }
    function openConfirm(type) {
        const exit = type === 'exit';
        const submitting = type === 'finish';
        const missing = submitting ? questions.filter(q => !filled(q)).length : 0;
        modal.dataset.action = type;
        el('sd-eval-confirm-title').textContent = submitting
            ? 'Kirim Jawaban Evaluasi?'
            : (exit ? 'Keluar dari Evaluasi?' : 'Yakin Ingin Memulai Evaluasi?');
        el('sd-eval-confirm-description').textContent = submitting
            ? `Yakin ingin mengirim jawaban sekarang? Setelah dikirim, jawaban tidak dapat diubah.${missing ? ` Masih ada ${missing} soal belum lengkap.` : ''}`
            : (exit ? 'Waktu tetap berjalan jika kamu kembali ke pengantar.' : 'Kamu akan mengerjakan 15 soal dalam 45 menit.');
        el('sd-eval-confirm-accept-text').textContent = submitting
            ? 'Ya, Kirim Jawaban' : (exit ? 'Ya, Kembali' : 'Ya, Mulai');
        el('sd-eval-confirm-icon').src = exit ? '/images/evaluasi/bekantan-sedih.png' : '/images/evaluasi/bekantan-bertanya.png';
        modal.hidden = false;
    }
    function closeModal() { modal.hidden = true; }
    startButton?.addEventListener('click', () => { if (!passed) openConfirm('start'); });
    el('sd-eval-quiz-exit')?.addEventListener('click', () => openConfirm('exit'));
    el('sd-eval-confirm-cancel')?.addEventListener('click', closeModal);
    el('sd-eval-confirm-close')?.addEventListener('click', closeModal);
    modal.querySelector('.sd-eval-confirm-backdrop')?.addEventListener('click', closeModal);
    el('sd-eval-confirm-accept')?.addEventListener('click', () => {
        const action = modal.dataset.action;
        closeModal();
        if (action === 'start') start();
        else if (action === 'finish') finish();
        else if (action === 'exit') show('overview');
    });
    prev?.addEventListener('click', () => { if (index > 0) {
        index--;
        selected = null;
        render();
    } });
    next?.addEventListener('click', () => {
        if (index < questions.length - 1) {
            index++;
            selected = null;
            render();
        }
        else {
            openConfirm('finish');
        }
    });
    retryButton?.addEventListener('click', () => {
        if (passed) return;
        openConfirm('start');
    });
    root.querySelector('[data-eval-open="overview"]')?.addEventListener('click', () => {
        if (passed) updateAccess();
    });
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && !modal.hidden)
        closeModal(); });
    async function restore() {
        try {
            const state = await request(`${api}/state`);
            kkm = Number(state.kkm || 70);
            passed = Boolean(state.passed);
            updateAccess();
            if (state.status === 'active') {
                questions = state.questions;
                answers = state.attempt.answers || {};
                endAt = state.attempt.ends_at;
                show('quiz');
                render();
                runTimer();
                message('Sesi evaluasi dilanjutkan.');
            }
            else if (state.status === 'completed') {
                results(state.attempt);
            }
            else {
                show('overview');
                if (!state.ready) {
                    const btn = root.querySelector('[data-eval-open="quiz"]');
                    btn.disabled = true;
                    btn.title = 'Guru belum memublikasikan 15 soal lengkap.';
                }
            }
        }
        catch (e) {
            show('overview');
            message(e.message);
        }
    }
    restore();
});