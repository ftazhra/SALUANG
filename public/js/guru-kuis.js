/* =========================================================
   SALUANG — KELOLA SOAL KUIS GURU
   File: public/js/guru-kuis.js
   ========================================================= */
document.addEventListener('DOMContentLoaded', () => {
    const page = document.getElementById('guruQuizPage');
    if (!page) return;
    /* 01. ELEMEN HALAMAN */
    const chapterSelect = document.getElementById('gqChapterSelect');
    const subchapterSelect = document.getElementById('gqSubchapterSelect');
    const workspace = document.getElementById('gqQuestionWorkspace');
    const initialEmpty = document.getElementById('gqInitialEmpty');
    const selectedTitle = document.getElementById(
        'gqSelectedSubchapterTitle'
    );
    const questionTabs = document.getElementById('gqQuestionTabs');
    const questionDetail = document.getElementById('gqQuestionDetail');
    const questionModal = document.getElementById('gqQuestionModal');
    const previewModal = document.getElementById('gqPreviewModal');
    const deleteModal = document.getElementById('gqDeleteModal');
    const deleteQuestionNumber = document.getElementById('gqDeleteQuestionNumber');
    const questionForm = document.getElementById('gqQuestionForm');
    const activityTypeSelect = document.getElementById('gqActivityType');
    const mcqFields = document.getElementById('gqMcqFields');
    const activityFields = document.getElementById('gqActivityFields');
    const activityEditor = document.getElementById('gqActivityEditor');
    const toast = document.getElementById('gqToast');
    const csrf = document.querySelector(
        'meta[name="csrf-token"]'
    )?.content || '';
    /* 02. DATA SEMENTARA TAMPILAN */
    let chapters = [];
    let questions = [];
    let selectedChapterId = null;
    let selectedSubchapterId = null;
    let selectedQuestionId = null;
    let pendingDeleteId = null;
    let deleteRequestInProgress = false;
    let toastTimer = null;
    let questionLoadVersion = 0;
    let chapterLoadVersion = 0;
    let activityDraft = null;
    /* 03. API LARAVEL */
    async function api(url, method = 'GET', data = null) {
        const options = {
            method: method,
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf
            }
        };
        if (data !== null) {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(data);
        }
        let response;
        try {
            response = await fetch(url, options);
        } catch (_) {
            throw new Error(
                'Tidak dapat terhubung ke server Laravel.'
            );
        }
        let result;
        try {
            result = await response.json();
        } catch (_) {
            throw new Error(
                'Respons server tidak valid. Periksa login dan route Laravel.'
            );
        }
        if (!response.ok) {
            const validationError = result.errors
                ? Object.values(result.errors).flat()[0]
                : null;
            throw new Error(
                validationError ||
                result.message ||
                `Permintaan gagal (${response.status}).`
            );
        }
        return result;
    }
    /* 04. FUNGSI BANTUAN */
    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, char => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        })[char]);
    }
    function showToast(message, isError = false) {
        if (!toast) return;
        clearTimeout(toastTimer);
        toast.textContent = message;
        toast.classList.toggle('is-error', isError);
        toast.hidden = false;
        toastTimer = setTimeout(() => {
            toast.hidden = true;
        }, 3500);
    }
    function closeModals() {
        [questionModal, previewModal, deleteModal]
            .forEach(modal => {
                if (modal) modal.hidden = true;
            });
        previewLineObserver?.disconnect();
        previewLineObserver = null;
        previewLineUpdate = null;
    }
    function openModal(modal) {
        closeModals();
        if (modal) modal.hidden = false;
    }
    function questionBaseUrl() {
        return `/guru/subchapters/${encodeURIComponent(
            selectedSubchapterId
        )}/questions`;
    }
    function questionUrl(id) {
        return `${questionBaseUrl()}/${encodeURIComponent(id)}`;
    }
    function displayQuestionNumber(question) {
        return Number(question?.nomor_soal) || 1;
    }
    function getSelectedQuestion() {
        return questions.find(
            item => Number(item.id) === Number(selectedQuestionId)
        ) || null;
    }
    function getSelectedChapter() {
        return chapters.find(
            item => Number(item.id) === Number(selectedChapterId)
        ) || null;
    }
    function getSelectedSubchapter() {
        const chapter = getSelectedChapter();
        return (chapter?.subchapters || []).find(
            item => Number(item.id) === Number(selectedSubchapterId)
        ) || null;
    }
    function resetQuestionArea() {
        questionLoadVersion++;
        questions = [];
        selectedQuestionId = null;
        questionTabs.innerHTML = '';
        questionDetail.innerHTML = `
            <div class="gq-empty">
                <i class="bi bi-card-checklist"></i>
                <p>Belum ada soal di Subbab ini.</p>
            </div>
        `;
    }
    function emptyMessage(message) {
        return `
            <div class="gq-empty">
                <i class="bi bi-journal-check"></i>
                <p>${escapeHtml(message)}</p>
            </div>
        `;
    }
    /* 05. MEMUAT BAB DAN SUBBAB */
    async function loadChapters() {
        const version = ++chapterLoadVersion;
        chapterSelect.disabled = true;
        chapterSelect.innerHTML = `
            <option value="">Memuat daftar Bab...</option>
        `;
        try {
            const result = await api('/guru/quiz/chapters');
            if (version !== chapterLoadVersion) return;
            chapters = result.chapters || [];
            if (!chapters.length) {
                chapterSelect.innerHTML = `
                    <option value="">
                        Belum ada Bab. Buat di Kelola Materi.
                    </option>
                `;
                return;
            }
            chapterSelect.innerHTML = `
                <option value="">-- Pilih Bab --</option>
                ${chapters.map(chapter => `
                    <option value="${chapter.id}">
                        BAB ${escapeHtml(chapter.nomor_bab)}
                        : ${escapeHtml(chapter.judul)}
                    </option>
                `).join('')}
            `;
            chapterSelect.disabled = false;
        } catch (error) {
            chapterSelect.innerHTML = `
                <option value="">Gagal memuat Bab</option>
            `;
            showToast(error.message, true);
        }
    }
    function updateSubchapters() {
        closeModals();
        pendingDeleteId = null;
        selectedChapterId = chapterSelect.value
            ? Number(chapterSelect.value)
            : null;
        selectedSubchapterId = null;
        resetQuestionArea();
        workspace.hidden = true;
        initialEmpty.hidden = false;
        subchapterSelect.disabled = true;
        const chapter = getSelectedChapter();
        if (!chapter) {
            subchapterSelect.innerHTML = `
                <option value="">Pilih Bab terlebih dahulu</option>
            `;
            return;
        }
        const subchapters = chapter.subchapters || [];
        if (!subchapters.length) {
            subchapterSelect.innerHTML = `
                <option value="">
                    Belum ada Subbab di Bab ini
                </option>
            `;
            return;
        }
        subchapterSelect.innerHTML = `
            <option value="">-- Pilih Subbab --</option>
            ${subchapters.map(item => `
                <option value="${item.id}">
                    ${escapeHtml(chapter.nomor_bab)}.
                    ${escapeHtml(item.nomor_subbab)}
                    : ${escapeHtml(item.judul)}
                </option>
            `).join('')}
        `;
        subchapterSelect.disabled = false;
    }
    /* 06. MEMUAT SOAL */
    async function loadQuestions(preferredQuestionId = null) {
        if (!selectedSubchapterId) return;
        const subchapterId = selectedSubchapterId;
        const version = ++questionLoadVersion;
        questionTabs.innerHTML = '';
        questionDetail.innerHTML = emptyMessage(
            'Memuat soal kuis...'
        );
        try {
            const result = await api(
                `/guru/subchapters/${encodeURIComponent(
                    subchapterId
                )}/questions`
            );
            if (
                version !== questionLoadVersion ||
                subchapterId !== selectedSubchapterId
            ) {
                return;
            }
            questions = result.questions || [];
            const preferred = questions.find(
                item => Number(item.id) === Number(preferredQuestionId)
            );
            selectedQuestionId = preferred
                ? preferred.id
                : (questions[0]?.id || null);
            renderQuestionTabs();
            renderQuestionDetail();
        } catch (error) {
            if (version !== questionLoadVersion) return;
            questionDetail.innerHTML = emptyMessage(error.message);
            showToast(error.message, true);
        }
    }
    async function selectSubchapter() {
        closeModals();
        pendingDeleteId = null;
        selectedSubchapterId = subchapterSelect.value
            ? Number(subchapterSelect.value)
            : null;
        resetQuestionArea();
        if (!selectedSubchapterId) {
            workspace.hidden = true;
            initialEmpty.hidden = false;
            return;
        }
        const chapter = getSelectedChapter();
        const subchapter = getSelectedSubchapter();
        selectedTitle.textContent = chapter && subchapter
            ? `${chapter.nomor_bab}.${subchapter.nomor_subbab} ${subchapter.judul}`
            : 'Daftar Soal Kuis';
        initialEmpty.hidden = true;
        workspace.hidden = false;
        await loadQuestions();
    }
    /* 07. TOMBOL SOAL 1, SOAL 2, DST. */
    function renderQuestionTabs() {
        if (!questions.length) {
            questionTabs.innerHTML = '';
            return;
        }
        questionTabs.innerHTML = questions.map((item, index) => {
            const active = Number(item.id) ===
                Number(selectedQuestionId);
            return `
                <button type="button"
                    class="gq-question-tab ${active ? 'active' : ''}"
                    data-gq-action="select"
                    data-id="${item.id}"
                    aria-pressed="${active}">
                    Soal ${Number(item.nomor_soal) || index + 1}
                </button>
            `;
        }).join('');
    }
    const activityImageMarkup = (item, fallbackLabel) => {
        const label = item.label || fallbackLabel;
        if (!item.image_url) return '';
        return `
            <span class="gq-activity-detail-image-frame">
                <img src="${escapeHtml(item.image_url)}" alt="${escapeHtml(label)}" loading="lazy">
                <span class="gq-activity-detail-image-fallback" hidden>Gambar tidak tersedia</span>
            </span>
        `;
    };
    const activityCardMarkup = (item, fallbackLabel) => `
        <article class="gq-activity-detail-card ${item.image_url ? 'has-image' : 'is-text-only'}">
            ${activityImageMarkup(item, fallbackLabel)}
            <span class="gq-activity-detail-caption">${escapeHtml(item.label || fallbackLabel)}</span>
        </article>
    `;
    const questionTypeLabel = question => {
        if (question?.activity_type === 'drag_drop') return 'Drag & Drop';
        if (question?.activity_type && question.activity_type !== 'multiple_choice') return 'Menghubungkan';
        return 'Pilihan Ganda';
    };
    const questionHeadingMarkup = question => `
        <div class="gq-question-heading">
            <h3>Soal ${displayQuestionNumber(question)}</h3>
            <span class="gq-question-type-badge">${questionTypeLabel(question)}</span>
        </div>
    `;
    function buildActivityBoardMarkup(question) {
        const payload = question.activity_payload || {};
        const isDragDrop = question.activity_type === 'drag_drop';
        const items = isDragDrop ? (payload.items || []) : (payload.left_items || []);
        const targets = isDragDrop ? (payload.categories || []) : (payload.right_items || []);

        if (isDragDrop) {
            const categoryMarkup = targets.map(category => {
                const assignedItems = items.filter(
                    item => payload.assignments?.[item.id] === category.id
                );
                return `
                    <div class="gq-activity-detail-category">
                        <strong>${escapeHtml(category.label || 'Kategori')}</strong>
                        <div class="gq-activity-detail-category-objects is-card-grid">
                            ${assignedItems.length
                        ? assignedItems.map(item => activityCardMarkup(item, 'Objek')).join('')
                        : '<span class="gq-activity-detail-empty-state">Belum ada objek</span>'}
                        </div>
                    </div>
                `;
            }).join('');

            return {
                boardClass: 'is-drag-drop',
                markup: `
                    <div class="gq-activity-detail-drag-columns">
                        <section class="gq-activity-detail-column">
                            <h4>Objek</h4>
                            <div class="gq-activity-detail-grid">
                                ${items.map(item => activityCardMarkup(item, 'Objek')).join('')}
                            </div>
                        </section>
                        <section class="gq-activity-detail-column">
                            <h4>Kategori</h4>
                            <div class="gq-activity-detail-category-list">${categoryMarkup}</div>
                        </section>
                    </div>
                `
            };
        }

        const previewState = window.SaluangMatchingPreview.createState(
            targets,
            items,
            payload.pairs
        );
        const pairs = normalizeMatchingPairs(payload.pairs);
        const matchingRows = (collection, side) => collection.map(item => `
            <div class="gq-activity-detail-match-row is-${side}"
                data-match-${side}="${escapeHtml(item.id)}">
                ${side === 'right' ? '<span class="gq-activity-detail-dot" aria-hidden="true"></span>' : ''}
                ${activityCardMarkup(item, side === 'left' ? 'Objek kiri' : 'Objek kanan')}
                ${side === 'left' ? '<span class="gq-activity-detail-dot" aria-hidden="true"></span>' : ''}
            </div>
        `).join('');

        return {
            boardClass: 'is-matching',
            markup: `
                <div class="gq-activity-detail-matching"
                    data-matching-pairs="${escapeHtml(JSON.stringify(pairs))}">
                    <svg class="gq-activity-detail-lines" aria-hidden="true"></svg>
                    <section class="gq-activity-detail-match-column is-left">
                        <h4>Bagian Kiri</h4>
                        ${matchingRows(items, 'left')}
                    </section>
                    <section class="gq-activity-detail-match-column is-right">
                        <h4>Bagian Kanan</h4>
                        ${matchingRows(previewState.rightOrder, 'right')}
                    </section>
                </div>
            `
        };
    }
    const bindActivityImageFallback = root => {
        root?.querySelectorAll('.gq-activity-detail-image-frame img')
            .forEach(image => {
                image.addEventListener('error', () => {
                    image.hidden = true;
                    const fallback = image.nextElementSibling;
                    if (fallback) fallback.hidden = false;
                }, { once: true });
            });
    };
    let detailLineUpdate = null;
    let detailLineObserver = null;
    const refreshDetailLines = () => detailLineUpdate?.();
    window.addEventListener('resize', refreshDetailLines);
    document.addEventListener('scroll', refreshDetailLines, {
        capture: true,
        passive: true
    });
    const bindActivityMatchingLines = root => {
        detailLineObserver?.disconnect();
        detailLineObserver = null;
        detailLineUpdate = null;
        const board = root?.querySelector('.gq-activity-detail-matching');
        const lines = board?.querySelector('.gq-activity-detail-lines');
        if (!board || !lines) return;
        const pairs = JSON.parse(board.dataset.matchingPairs || '[]');
        const updateLines = () => window.SaluangMatchingPreview.drawLines(
            board,
            lines,
            pairs,
            'match-left',
            'match-right',
            'gq-activity-detail-dot',
            'gq-activity-detail-line'
        );
        detailLineUpdate = updateLines;
        if (typeof ResizeObserver !== 'undefined') {
            detailLineObserver = new ResizeObserver(updateLines);
            detailLineObserver.observe(board);
            board.querySelectorAll('.gq-activity-detail-match-column')
                .forEach(column => detailLineObserver.observe(column));
        }
        requestAnimationFrame(updateLines);
    };
    /* 08. DETAIL SOAL */
    function renderQuestionDetail() {
        detailLineObserver?.disconnect();
        detailLineObserver = null;
        detailLineUpdate = null;
        if (!questions.length) {
            questionDetail.innerHTML = emptyMessage(
                'Belum ada soal di Subbab ini. Klik Tambah Nomor Soal.'
            );
            return;
        }
        const question = getSelectedQuestion();
        if (!question) {
            questionDetail.innerHTML = emptyMessage(
                'Silakan pilih nomor soal.'
            );
            return;
        }
        if (question.activity_type && question.activity_type !== 'multiple_choice') {
            const activityBoard = buildActivityBoardMarkup(question);
            questionDetail.innerHTML = `
                <article class="gq-question-card gq-activity-question-card gq-question-card--uniform">
                    ${questionHeadingMarkup(question)}
                    <p class="gq-question-text">${escapeHtml(question.pertanyaan)}</p>
                    <div class="gq-activity-detail-board ${activityBoard.boardClass}">
                        ${activityBoard.markup}
                    </div>
                    <div class="gq-question-actions">
                        <button type="button" class="gq-btn gq-btn-outline"
                            data-gq-action="preview" data-id="${question.id}">
                            <i class="bi bi-eye"></i> Pratinjau
                        </button>
                        <button type="button" class="gq-btn gq-btn-primary"
                            data-gq-action="edit" data-id="${question.id}">
                            <i class="bi bi-pencil-square"></i> Edit Soal
                        </button>
                        <button type="button" class="gq-btn gq-btn-danger"
                            data-gq-action="delete" data-id="${question.id}">
                            <i class="bi bi-trash3"></i> Hapus Soal
                        </button>
                    </div>
                </article>
            `;
            bindActivityImageFallback(questionDetail);
            bindActivityMatchingLines(questionDetail);
            return;
        }
        const options = ['A', 'B', 'C', 'D'];
        questionDetail.innerHTML = `
            <article class="gq-question-card gq-question-card--uniform">
                ${questionHeadingMarkup(question)}
                <p class="gq-question-text">${escapeHtml(
            question.pertanyaan
        )}</p>
                <div class="gq-answer-list">
                    ${options.map(letter => {
            const answer = question[
                `pilihan_${letter.toLowerCase()}`
            ];
            const correct =
                question.kunci_jawaban === letter;
            return `
                            <div class="gq-answer-item ${correct ? 'is-correct' : ''
                }">
                                <span class="gq-answer-letter">
                                    ${letter}
                                </span>
                                <span>${escapeHtml(answer)}</span>
                            </div>
                        `;
        }).join('')}
                </div>
                <div class="gq-question-meta">
                    <strong>Kunci Jawaban:</strong>
                    ${escapeHtml(question.kunci_jawaban)}
                </div>
                <div class="gq-question-meta">
                    <strong>Pembahasan:</strong>
                    <p style="white-space: pre-wrap; margin: 8px 0 0;">${escapeHtml(
            question.pembahasan || 'Belum ada pembahasan.'
        )}</p>
                </div>
                <div class="gq-question-actions">
                    <button type="button"
                        class="gq-btn gq-btn-outline"
                        data-gq-action="preview"
                        data-id="${question.id}">
                        <i class="bi bi-eye"></i>
                        Pratinjau
                    </button>
                    <button type="button"
                        class="gq-btn gq-btn-primary"
                        data-gq-action="edit"
                        data-id="${question.id}">
                        <i class="bi bi-pencil-square"></i>
                        Edit Soal
                    </button>
                    <button type="button"
                        class="gq-btn gq-btn-danger"
                        data-gq-action="delete"
                        data-id="${question.id}">
                        <i class="bi bi-trash3"></i>
                        Hapus Soal
                    </button>
                </div>
            </article>
        `;
    }

    /* 09. EDITOR KEGIATAN */
    let activityIdSequence = 0;
    function newActivityId(prefix) {
        activityIdSequence++;
        const random = globalThis.crypto?.randomUUID
            ? globalThis.crypto.randomUUID().replaceAll('-', '')
            : `${Date.now()}_${activityIdSequence}`;
        return `${prefix}_${random}`;
    }

    function defaultActivityDraft(type) {
        if (type === 'drag_drop') {
            const firstCategory = newActivityId('category');
            const secondCategory = newActivityId('category');
            return {
                items: [
                    { label: '', image_url: '', category_id: firstCategory },
                    { label: '', image_url: '', category_id: secondCategory }
                ],
                categories: [
                    { id: firstCategory, label: '' },
                    { id: secondCategory, label: '' }
                ]
            };
        }
        return {
            left_items: [
                { label: '', image_url: '', right_indices: [0] },
                { label: '', image_url: '', right_indices: [1] }
            ],
            right_items: [
                { label: '', image_url: '' },
                { label: '', image_url: '' }
            ]
        };
    }
    function draftFromQuestion(question, type) {
        const payload = question?.activity_payload || {};
        if (type === 'drag_drop') {
            const categories = (payload.categories || []).map((item, index) => ({
                id: item.id || `category_${index + 1}`,
                label: item.label || ''
            }));
            return {
                categories,
                items: (payload.items || []).map(item => ({
                    label: item.label || '',
                    image_url: item.image_url || '',
                    category_id: payload.assignments?.[item.id] || ''
                }))
            };
        }
        const rightItems = payload.right_items || [];
        return {
            right_items: rightItems.map(item => ({
                label: item.label || '',
                image_url: item.image_url || ''
            })),
            left_items: (payload.left_items || []).map(item => ({
                label: item.label || '',
                image_url: item.image_url || '',
                right_indices: (() => {
                    const pairs = payload.pairs || {};
                    const selectedRightIds = Array.isArray(pairs)
                        ? pairs.flatMap(pair => {
                            if (pair?.left_id === item.id) return [pair.right_id];
                            return [];
                        })
                        : Array.isArray(pairs[item.id])
                            ? pairs[item.id]
                            : pairs[item.id]
                                ? [pairs[item.id]]
                                : [];
                    return selectedRightIds
                        .map(rightId => rightItems.findIndex(right => right.id === rightId))
                        .filter(index => index >= 0);
                })()
            }))
        };
    }
    let activeMatchingMenu = null;
    function closeMatchingMenu() {
        if (!activeMatchingMenu) return;
        activeMatchingMenu.button.setAttribute('aria-expanded', 'false');
        activeMatchingMenu.menu.remove();
        activeMatchingMenu = null;
    }
    function matchingSelectionMarkup(leftIndex) {
        const leftItem = activityDraft?.left_items?.[leftIndex];
        const selectedIndices = (leftItem?.right_indices || [])
            .map(Number)
            .filter(index => index >= 0 && index < (activityDraft?.right_items?.length || 0));
        const selection = activityEditor.querySelector(
            `[data-matching-selection="${leftIndex}"]`
        );
        if (!selection || !activityDraft) return;
        const selectedLabels = selectedIndices.map(index => {
            const target = activityDraft.right_items[index];
            return `
                <button type="button" class="gq-matching-chip"
                    data-matching-remove="${leftIndex}" data-matching-target="${index}"
                    aria-label="Batalkan pilihan ${escapeHtml(target.label || `Pasangan ${index + 1}`)}">
                    <span>${escapeHtml(target.label || `Pasangan ${index + 1}`)}</span>
                    <span aria-hidden="true">&times;</span>
                </button>
            `;
        }).join('');
        selection.innerHTML = `
            <div class="gq-matching-chips">
                ${selectedLabels || '<span class="gq-matching-placeholder">Belum ada pasangan dipilih</span>'}
            </div>
            <button type="button" class="gq-matching-select-trigger"
                data-matching-toggle="${leftIndex}" aria-expanded="${activeMatchingMenu?.leftIndex === leftIndex}">
                <span>${selectedIndices.length ? 'Ubah pasangan jawaban' : 'Pilih pasangan jawaban'}</span>
                <i class="bi bi-chevron-down" aria-hidden="true"></i>
            </button>
        `;
    }
    function refreshMatchingSelections() {
        activityDraft?.left_items?.forEach((_, index) => matchingSelectionMarkup(index));
        if (activeMatchingMenu) {
            const { leftIndex, menu } = activeMatchingMenu;
            const selected = (activityDraft.left_items[leftIndex]?.right_indices || []).map(Number);
            menu.innerHTML = activityDraft.right_items.map((target, targetIndex) => `
                <label class="gq-matching-option">
                    <input type="checkbox" value="${targetIndex}"
                        ${selected.includes(targetIndex) ? 'checked' : ''}>
                    <span>${escapeHtml(target.label || `Pasangan ${targetIndex + 1}`)}</span>
                </label>
            `).join('');
        }
    }
    function positionMatchingMenu(menu, trigger) {
        const rect = trigger.getBoundingClientRect();
        const width = Math.min(
            Math.max(rect.width, 260),
            Math.max(220, window.innerWidth - 24)
        );
        menu.style.width = `${width}px`;
        menu.style.left = `${Math.max(12, Math.min(rect.left, window.innerWidth - width - 12))}px`;
        menu.style.maxHeight = `${Math.max(120, window.innerHeight - 24)}px`;
        const menuHeight = Math.min(menu.scrollHeight, window.innerHeight - 24);
        const below = window.innerHeight - rect.bottom - 12;
        const top = below >= Math.min(menuHeight, 220) || rect.top < menuHeight + 24
            ? rect.bottom + 6
            : rect.top - menuHeight - 6;
        menu.style.top = `${Math.max(12, Math.min(top, window.innerHeight - menuHeight - 12))}px`;
    }
    function openMatchingMenu(trigger, leftIndex) {
        closeMatchingMenu();
        const menu = document.createElement('div');
        menu.className = 'gq-matching-options';
        menu.setAttribute('role', 'group');
        menu.setAttribute('aria-label', 'Pilihan pasangan jawaban');
        const leftItem = activityDraft.left_items[leftIndex];
        const selected = (leftItem.right_indices || []).map(Number);
        menu.innerHTML = activityDraft.right_items.map((target, targetIndex) => `
            <label class="gq-matching-option">
                <input type="checkbox" value="${targetIndex}"
                    ${selected.includes(targetIndex) ? 'checked' : ''}>
                <span>${escapeHtml(target.label || `Pasangan ${targetIndex + 1}`)}</span>
            </label>
        `).join('');
        menu.addEventListener('change', event => {
            const checkbox = event.target.closest('input[type="checkbox"]');
            if (!checkbox) return;
            const selectedIndices = [...menu.querySelectorAll('input:checked')]
                .map(option => Number(option.value));
            activityDraft.left_items[leftIndex].right_indices = selectedIndices;
            matchingSelectionMarkup(leftIndex);
            activeMatchingMenu.button = activityEditor.querySelector(
                `[data-matching-toggle="${leftIndex}"]`
            );
            positionMatchingMenu(menu, activeMatchingMenu.button);
        });
        document.body.appendChild(menu);
        trigger.setAttribute('aria-expanded', 'true');
        activeMatchingMenu = { leftIndex, menu, button: trigger };
        positionMatchingMenu(menu, trigger);
    }
    document.addEventListener('pointerdown', event => {
        if (
            activeMatchingMenu
            && !activeMatchingMenu.menu.contains(event.target)
            && !activeMatchingMenu.button.contains(event.target)
        ) {
            closeMatchingMenu();
        }
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') closeMatchingMenu();
    });
    window.addEventListener('resize', () => {
        if (activeMatchingMenu) {
            positionMatchingMenu(activeMatchingMenu.menu, activeMatchingMenu.button);
        }
    });
    function renderActivityEditor() {
        if (!activityEditor || !activityDraft) return;
        closeMatchingMenu();
        const type = activityTypeSelect.value;
        const isDragDrop = type === 'drag_drop';
        const objectCollection = isDragDrop ? 'items' : 'left_items';
        const targetCollection = isDragDrop ? 'categories' : 'right_items';
        const objects = activityDraft[objectCollection] || [];
        const targets = activityDraft[targetCollection] || [];
        const objectLabel = isDragDrop ? 'Objek' : 'Bagian kiri';
        const targetLabel = isDragDrop ? 'Kategori' : 'Bagian kanan';
        const imageField = (collection, index, item) => `
            <div class="gq-activity-image-editor">
                <div class="gq-activity-image-preview" data-image-preview>
                    ${item.image_preview_url || item.image_url
                ? `<img src="${escapeHtml(item.image_preview_url || item.image_url)}" alt="Pratinjau ${escapeHtml(item.label || objectLabel.toLowerCase())}">`
                : '<span><i class="bi bi-image"></i> Pratinjau gambar</span>'}
                </div>
                <label class="gq-file-label">
                    Unggah JPG, JPEG, PNG, atau WebP (maks. 4 MB)
                    <input type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                        data-activity-upload="${collection}" data-activity-index="${index}">
                </label>
                ${item.image_url ? `
                    <button type="button" class="gq-btn gq-btn-outline"
                        data-activity-image-remove="${collection}" data-activity-index="${index}">
                        Hapus gambar
                    </button>
                ` : ''}
            </div>
        `;
        const objectEditors = objects.map((item, index) => `
            <fieldset class="gq-activity-item gq-object-card">
                <legend>${objectLabel} ${index + 1}</legend>
                ${imageField(objectCollection, index, item)}
                <div class="gq-field">
                    <label>${objectLabel} / caption</label>
                    <input type="text" maxlength="180" required
                        data-activity-collection="${objectCollection}"
                        data-activity-index="${index}" data-activity-field="label"
                        value="${escapeHtml(item.label)}"
                        placeholder="Contoh: Ikan Gabus">
                </div>
                ${isDragDrop ? `
                    <div class="gq-field">
                        <label>Kunci jawaban: kategori</label>
                        <select required data-activity-collection="items"
                            data-activity-index="${index}" data-activity-field="category_id">
                            <option value="">Pilih kategori</option>
                            ${targets.map((target, targetIndex) => `
                                <option data-category-option="${escapeHtml(target.id)}"
                                    value="${escapeHtml(target.id)}"
                                    ${item.category_id === target.id ? 'selected' : ''}>
                                    ${escapeHtml(target.label || `Kategori ${targetIndex + 1}`)}
                                </option>
                            `).join('')}
                        </select>
                    </div>
                ` : `
                    <div class="gq-field">
                        <label>Kunci jawaban: pasangan (bisa memilih beberapa)</label>
                        <div class="gq-matching-selection" data-matching-selection="${index}">
                            <div class="gq-matching-chips">
                                ${(item.right_indices || []).map(Number)
                .filter(targetIndex => targetIndex >= 0 && targetIndex < targets.length)
                .map(targetIndex => `
                                    <button type="button" class="gq-matching-chip"
                                        data-matching-remove="${index}" data-matching-target="${targetIndex}"
                                        aria-label="Batalkan pilihan ${escapeHtml(targets[targetIndex].label || `Pasangan ${targetIndex + 1}`)}">
                                        <span>${escapeHtml(targets[targetIndex].label || `Pasangan ${targetIndex + 1}`)}</span>
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                `).join('')
                || '<span class="gq-matching-placeholder">Belum ada pasangan dipilih</span>'}
                            </div>
                            <button type="button" class="gq-matching-select-trigger"
                                data-matching-toggle="${index}" aria-expanded="false">
                                <span>${(item.right_indices || []).length
                ? 'Ubah pasangan jawaban'
                : 'Pilih pasangan jawaban'}</span>
                                <i class="bi bi-chevron-down" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                `}
                <button type="button" class="gq-btn gq-btn-outline"
                    data-activity-remove="${objectCollection}" data-activity-index="${index}">
                    Hapus ${objectLabel.toLowerCase()}
                </button>
            </fieldset>
        `).join('');
        const targetEditors = targets.map((item, index) => `
            <fieldset class="gq-activity-item">
                <legend>${targetLabel} ${index + 1}</legend>
                <div class="gq-field">
                    <label>${targetLabel} / nama</label>
                    <input type="text" maxlength="100" required
                        data-activity-collection="${targetCollection}"
                        data-activity-index="${index}" data-activity-field="label"
                        value="${escapeHtml(item.label)}"
                        placeholder="Contoh: Makhluk Hidup">
                </div>
                ${!isDragDrop ? imageField(targetCollection, index, item) : ''}
                <button type="button" class="gq-btn gq-btn-outline"
                    data-activity-remove="${targetCollection}" data-activity-index="${index}">
                    Hapus ${targetLabel.toLowerCase()}
                </button>
            </fieldset>
        `).join('');

        activityEditor.innerHTML = `
            <p class="gq-activity-hint">
                ${isDragDrop
                ? 'Buat kategori terlebih dahulu. Nama kategori yang diubah akan langsung diperbarui pada kunci jawaban objek.'
                : 'Tambahkan objek kiri dan pasangannya di sisi kanan.'}
            </p>
            <h4 class="gq-activity-subheading">${targetLabel}</h4>
            <div class="gq-activity-list">
                ${targetEditors}
            </div>
            <button type="button" class="gq-btn gq-btn-outline"
                data-activity-add="${targetCollection}">
                <i class="bi bi-plus-lg"></i> Tambah ${targetLabel}
            </button>
            <h4 class="gq-activity-subheading">${objectLabel}</h4>
            <div class="gq-activity-list">${objectEditors}</div>
            <button type="button" class="gq-btn gq-btn-outline"
                data-activity-add="${objectCollection}">
                <i class="bi bi-plus-lg"></i> Tambah ${objectLabel}
            </button>
            <p class="gq-activity-hint">
                ${isDragDrop
                ? 'Setiap objek dapat masuk ke kategori yang dipilih.'
                : 'Pilih satu atau beberapa pasangan yang sesuai untuk setiap objek.'}
            </p>
        `;
    }
    function setFormActivityType(type) {
        const isMcq = type === 'multiple_choice';
        mcqFields.hidden = !isMcq;
        activityFields.hidden = isMcq;
        questionModal.querySelector('.gq-modal-dialog')
            ?.classList.toggle('is-activity', !isMcq);
        mcqFields.querySelectorAll('textarea, select').forEach(field => {
            field.required = isMcq && field.id !== 'gqExplanation';
        });
        if (!isMcq) renderActivityEditor();
    }
    function activityPayloadFromForm(type) {
        const payload = {
            activity_type: type,
            activity_instruction:
                document.getElementById('gqActivityInstruction').value.trim()
        };
        if (type === 'drag_drop') {
            payload.items = activityDraft.items.map(item => ({
                label: item.label,
                image_url: item.image_url || null,
                category_id: item.category_id || ''
            }));
            payload.categories = activityDraft.categories.map(item => ({
                id: item.id,
                label: item.label
            }));
        } else {
            payload.left_items = activityDraft.left_items.map(item => ({
                label: item.label,
                image_url: item.image_url || null,
                right_indices: item.right_indices || (
                    item.right_index === undefined || item.right_index === ''
                        ? []
                        : [item.right_index]
                )
            }));
            payload.right_items = activityDraft.right_items.map(item => ({
                label: item.label,
                image_url: item.image_url || null
            }));
        }
        return payload;
    }
    async function uploadActivityImage(file) {
        const formData = new FormData();
        formData.append('image', file);
        const response = await fetch(
            `/guru/subchapters/${encodeURIComponent(selectedSubchapterId)}/questions/upload-image`,
            {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf
                },
                body: formData
            }
        );
        let result;
        try {
            result = await response.json();
        } catch (_) {
            throw new Error('Server tidak mengembalikan respons upload yang valid.');
        }
        if (!response.ok) {
            const firstError = result.errors
                ? Object.values(result.errors).flat()[0]
                : null;
            throw new Error(firstError || result.message || 'Gagal mengunggah gambar.');
        }
        return result.url;
    }
    /* 10. FORM TAMBAH / EDIT SOAL */
    function openQuestionForm(question = null) {
        if (!selectedSubchapterId) {
            showToast('Pilih Subbab terlebih dahulu.', true);
            return;
        }
        questionForm.reset();
        const type = question?.activity_type || 'multiple_choice';
        activityTypeSelect.value = type;
        activityTypeSelect.disabled = Boolean(question);
        activityDraft = question
            ? draftFromQuestion(question, type)
            : defaultActivityDraft(type === 'multiple_choice' ? 'drag_drop' : type);
        document.getElementById('gqQuestionId').value =
            question?.id || '';
        const highestNumber = questions.reduce(
            (max, item) => Math.max(
                max,
                Number(item.nomor_soal) || 0
            ),
            0
        );
        document.getElementById('gqQuestionNumber').value =
            question?.nomor_soal || (highestNumber + 1);
        document.getElementById('gqQuestionText').value =
            question?.pertanyaan || '';
        document.getElementById('gqOptionA').value =
            question?.pilihan_a || '';
        document.getElementById('gqOptionB').value =
            question?.pilihan_b || '';
        document.getElementById('gqOptionC').value =
            question?.pilihan_c || '';
        document.getElementById('gqOptionD').value =
            question?.pilihan_d || '';
        document.getElementById('gqCorrectAnswer').value =
            question?.kunci_jawaban || '';
        document.getElementById('gqExplanation').value =
            question?.pembahasan || '';
        document.getElementById('gqPublished').checked =
            Boolean(question?.is_published);
        document.getElementById('gqActivityInstruction').value =
            question?.activity_payload?.instruction || '';
        setFormActivityType(type);
        document.getElementById('gqQuestionModalTitle').textContent =
            question ? 'Edit Soal' : 'Tambah Soal';
        document.getElementById('gqSaveQuestion').textContent =
            question ? 'Simpan Perubahan' : 'Simpan Soal';
        openModal(questionModal);
    }
    questionForm.addEventListener('submit', async event => {
        event.preventDefault();
        if (!selectedSubchapterId) return;
        const id = document.getElementById('gqQuestionId').value;
        const type = activityTypeSelect.value;
        if (
            type === 'matching'
            && activityDraft.left_items.some(item => !(item.right_indices || []).length)
        ) {
            showToast('Pilih setidaknya satu pasangan jawaban untuk setiap Bagian Kiri.', true);
            return;
        }
        const saveButton = document.getElementById('gqSaveQuestion');
        const payload = {
            nomor_soal: Number(
                document.getElementById('gqQuestionNumber').value
            ),
            activity_type: type,
            pertanyaan:
                document.getElementById('gqQuestionText').value.trim(),
            is_published:
                document.getElementById('gqPublished').checked
        };
        if (type === 'multiple_choice') {
            Object.assign(payload, {
                pilihan_a: document.getElementById('gqOptionA').value.trim(),
                pilihan_b: document.getElementById('gqOptionB').value.trim(),
                pilihan_c: document.getElementById('gqOptionC').value.trim(),
                pilihan_d: document.getElementById('gqOptionD').value.trim(),
                kunci_jawaban: document.getElementById('gqCorrectAnswer').value,
                pembahasan: document.getElementById('gqExplanation').value.trim()
            });
        } else {
            Object.assign(payload, activityPayloadFromForm(type));
        }
        const subchapterAtSubmit = selectedSubchapterId;
        saveButton.disabled = true;
        try {
            const result = await api(
                id ? questionUrl(id) : questionBaseUrl(),
                id ? 'PUT' : 'POST',
                payload
            );
            closeModals();
            showToast(result.message || 'Soal berhasil disimpan.');
            if (selectedSubchapterId === subchapterAtSubmit) {
                await loadQuestions(result.question?.id);
            }
        } catch (error) {
            showToast(error.message, true);
        } finally {
            saveButton.disabled = false;
        }
    });
    /* 10. PRATINJAU SOAL */
    let previewLineUpdate = null;
    let previewLineObserver = null;
    const refreshPreviewLines = () => previewLineUpdate?.();
    window.addEventListener('resize', refreshPreviewLines);
    document.addEventListener('scroll', refreshPreviewLines, {
        capture: true,
        passive: true
    });

    function normalizeMatchingPairs(pairs) {
        return Array.isArray(pairs)
            ? pairs.filter(pair => pair && pair.left_id != null && pair.right_id != null)
            : Object.entries(pairs || {}).flatMap(([leftId, rightIds]) =>
                (Array.isArray(rightIds) ? rightIds : [rightIds])
                    .filter(rightId => rightId != null)
                    .map(rightId => ({ left_id: leftId, right_id: rightId }))
            );
    }
    function renderActivityPreview(question, previewContent) {
        const payload = question.activity_payload || {};
        const isDragDrop = question.activity_type === 'drag_drop';
        const items = isDragDrop ? (payload.items || []) : (payload.left_items || []);
        const targets = isDragDrop ? (payload.categories || []) : (payload.right_items || []);
        const matchingState = isDragDrop
            ? null
            : window.SaluangMatchingPreview.createState(
                targets,
                items,
                payload.pairs
            );
        const answers = isDragDrop ? {} : matchingState.answers;
        let selected = null;
        let suppressClickUntil = 0;
        let previewChecked = false;
        const rightOrder = isDragDrop ? [...targets] : matchingState.rightOrder;

        const appendCardImage = (card, item) => {
            if (item.image_url) {
                card.classList.add('has-image');
                const frame = document.createElement('span');
                frame.className = 'gq-preview-image-frame';
                const image = document.createElement('img');
                image.src = item.image_url;
                image.alt = item.label || 'Gambar aktivitas';
                image.draggable = false;
                image.onerror = () => {
                    frame.replaceChildren();
                    const fallback = document.createElement('span');
                    fallback.className = 'gq-preview-image-fallback';
                    fallback.textContent = 'Gambar tidak tersedia';
                    frame.appendChild(fallback);
                };
                frame.appendChild(image);
                card.appendChild(frame);
            } else {
                card.classList.add('is-text-only');
            }
            const caption = document.createElement('span');
            caption.textContent = item.label || '';
            card.appendChild(caption);
        };
        const toggleMatchingPair = (leftId, rightId) => {
            const existingIndex = answers.findIndex(pair =>
                pair.left_id === leftId && pair.right_id === rightId
            );
            if (existingIndex >= 0) answers.splice(existingIndex, 1);
            else answers.push({ left_id: leftId, right_id: rightId });
        };
        const renderPreviewFeedback = () => {
            const panel = document.createElement('section');
            panel.className = 'gq-preview-feedback';
            panel.setAttribute('role', 'status');
            panel.setAttribute('aria-live', 'polite');
            panel.appendChild(Object.assign(document.createElement('strong'), {
                textContent: 'Hasil aktivitas'
            }));

            if (isDragDrop) {
                items.forEach(item => {
                    const selectedCategory = targets.find(
                        category => category.id === answers[item.id]
                    );
                    const correctCategory = targets.find(
                        category => category.id === payload.assignments?.[item.id]
                    );
                    const isCorrect = Boolean(
                        selectedCategory
                        && correctCategory
                        && selectedCategory.id === correctCategory.id
                    );
                    const row = document.createElement('p');
                    row.className = `gq-preview-feedback-row ${isCorrect ? 'is-correct' : 'is-incorrect'}`;
                    row.append(
                        Object.assign(document.createElement('strong'), {
                            textContent: isCorrect ? 'Benar.' : 'Salah.'
                        }),
                        document.createTextNode(
                            ` ${item.label || 'Objek'} ${selectedCategory
                                ? `ditempatkan pada kategori ${selectedCategory.label}.`
                                : 'belum ditempatkan.'
                            }${isCorrect
                                ? ''
                                : ` Kategori yang benar adalah ${correctCategory?.label || 'belum tersedia'}.`
                            }`
                        )
                    );
                    panel.appendChild(row);
                });
                return panel;
            }

            const correctPairs = normalizeMatchingPairs(payload.pairs);
            items.forEach(item => {
                const expectedIds = correctPairs
                    .filter(pair => pair.left_id === item.id)
                    .map(pair => pair.right_id);
                const selectedIds = answers
                    .filter(pair => pair.left_id === item.id)
                    .map(pair => pair.right_id);
                const expectedLabels = expectedIds
                    .map(id => targets.find(target => target.id === id)?.label)
                    .filter(Boolean);
                const selectedLabels = selectedIds
                    .map(id => targets.find(target => target.id === id)?.label)
                    .filter(Boolean);
                const isCorrect = expectedIds.length > 0
                    && expectedIds.every(id => selectedIds.includes(id))
                    && selectedIds.every(id => expectedIds.includes(id));
                const row = document.createElement('p');
                row.className = `gq-preview-feedback-row ${isCorrect ? 'is-correct' : 'is-incorrect'}`;
                row.append(
                    Object.assign(document.createElement('strong'), {
                        textContent: isCorrect ? 'Benar.' : 'Salah.'
                    }),
                    document.createTextNode(
                        ` ${item.label || 'Bagian kiri'} ${selectedLabels.length
                            ? `dipasangkan dengan ${selectedLabels.join(' dan ')}.`
                            : 'belum dipasangkan.'
                        }${isCorrect
                            ? ''
                            : ` Pasangan yang benar adalah ${expectedLabels.join(' dan ') || 'belum tersedia'}.`
                        }`
                    )
                );
                panel.appendChild(row);
            });

            return panel;
        };
        const bindTouchDrag = (card, itemId, onDrop) => {
            let startX = 0;
            let startY = 0;
            let dragging = false;
            let activeZone = null;
            let moveHandler;
            let upHandler;
            card.addEventListener('pointerdown', event => {
                if (event.pointerType !== 'touch') return;
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
                    )?.closest('[data-preview-dropzone]');
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
                    suppressClickUntil = Date.now() + 400;
                    const zone = document.elementFromPoint(
                        upEvent.clientX,
                        upEvent.clientY
                    )?.closest('[data-preview-dropzone]');
                    if (zone) onDrop(itemId, zone);
                };
                document.addEventListener('pointermove', moveHandler, { passive: false });
                document.addEventListener('pointerup', upHandler, { once: true });
                document.addEventListener('pointercancel', upHandler, { once: true });
            });
        };
        const highlightDropZone = zone => {
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
        };

        const render = () => {
            previewLineObserver?.disconnect();
            previewLineObserver = null;
            previewContent.replaceChildren();
            const heading = document.createElement('div');
            heading.className = 'gq-preview-question';
            heading.textContent = `Soal ${displayQuestionNumber(question)}`;
            previewContent.appendChild(heading);
            previewContent.appendChild(Object.assign(document.createElement('p'), {
                className: 'gq-preview-question-prompt',
                textContent: question.pertanyaan || ''
            }));
            const board = document.createElement('div');
            board.className = isDragDrop
                ? 'gq-preview-activity-board is-drag-drop'
                : 'gq-preview-activity-board is-matching';

            if (isDragDrop) {
                const objectColumn = document.createElement('div');
                objectColumn.className = 'gq-preview-activity-items';
                objectColumn.appendChild(Object.assign(
                    document.createElement('strong'),
                    { className: 'gq-matching-label', textContent: 'Objek' }
                ));
                items.filter(item => answers[item.id] === undefined).forEach(item => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'gq-preview-activity-item is-drag-card';
                    button.draggable = true;
                    button.setAttribute('aria-label', `Seret kartu ${item.label || 'objek'} ke kategori`);
                    if (selected === item.id) button.classList.add('is-selected');
                    appendCardImage(button, item);
                    button.addEventListener('click', () => {
                        if (suppressClickUntil > Date.now()) {
                            suppressClickUntil = 0;
                            return;
                        }
                        selected = item.id;
                        render();
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
                    button.addEventListener('dragend', () =>
                        button.classList.remove('is-dragging')
                    );
                    bindTouchDrag(button, item.id, (draggedId, zone) => {
                        if (zone.dataset.previewDropzone === 'source') {
                            delete answers[draggedId];
                        } else {
                            answers[draggedId] = zone.dataset.categoryId;
                        }
                        selected = null;
                        render();
                    });
                    objectColumn.appendChild(button);
                });
                objectColumn.addEventListener('click', event => {
                    if (event.target.closest('.gq-preview-activity-item')) return;
                    if (!selected) return;
                    delete answers[selected];
                    selected = null;
                    render();
                });
                objectColumn.addEventListener('dragover', event => event.preventDefault());
                objectColumn.dataset.previewDropzone = 'source';
                highlightDropZone(objectColumn);
                objectColumn.addEventListener('drop', event => {
                    event.preventDefault();
                    const itemId = event.dataTransfer?.getData('text/plain');
                    if (items.some(item => item.id === itemId)) {
                        delete answers[itemId];
                        selected = null;
                        render();
                    }
                });
                const categoryColumn = document.createElement('div');
                categoryColumn.className = 'gq-preview-activity-targets';
                targets.forEach(category => {
                    const zone = document.createElement('section');
                    zone.className = 'gq-preview-activity-target';
                    zone.dataset.previewDropzone = 'category';
                    zone.dataset.categoryId = category.id;
                    zone.tabIndex = 0;
                    zone.setAttribute('role', 'group');
                    const title = document.createElement('strong');
                    title.textContent = category.label || 'Kategori';
                    zone.appendChild(title);
                    const assignedList = document.createElement('div');
                    assignedList.className = 'gq-preview-assigned-list';
                    items.filter(item => answers[item.id] === category.id).forEach(item => {
                        const assigned = document.createElement('div');
                        assigned.className = 'gq-preview-assigned is-drag-card';
                        assigned.draggable = true;
                        assigned.tabIndex = 0;
                        assigned.setAttribute('role', 'button');
                        assigned.setAttribute(
                            'aria-label',
                            `Objek ${item.label || ''}, berada di kategori ${category.label || ''}. Pilih atau seret untuk memindahkan.`
                        );
                        if (selected === item.id) assigned.classList.add('is-selected');
                        appendCardImage(assigned, item);
                        assigned.addEventListener('click', event => {
                            event.stopPropagation();
                            if (suppressClickUntil > Date.now()) {
                                suppressClickUntil = 0;
                                return;
                            }
                            if (selected && selected !== item.id) {
                                answers[selected] = category.id;
                                selected = null;
                                render();
                                return;
                            }
                            selected = item.id;
                            render();
                        });
                        assigned.addEventListener('keydown', event => {
                            if (!['Enter', ' '].includes(event.key)) return;
                            event.preventDefault();
                            selected = item.id;
                            render();
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
                        bindTouchDrag(assigned, item.id, (draggedId, dropZone) => {
                            if (dropZone.dataset.previewDropzone === 'source') {
                                delete answers[draggedId];
                            } else {
                                answers[draggedId] = dropZone.dataset.categoryId;
                            }
                            selected = null;
                            render();
                        });
                        assignedList.appendChild(assigned);
                    });
                    zone.appendChild(assignedList);
                    zone.addEventListener('click', () => {
                        if (!selected) return;
                        answers[selected] = category.id;
                        selected = null;
                        render();
                    });
                    zone.addEventListener('dragover', event => event.preventDefault());
                    highlightDropZone(zone);
                    zone.addEventListener('drop', event => {
                        event.preventDefault();
                        const itemId = event.dataTransfer?.getData('text/plain');
                        if (items.some(item => item.id === itemId)) {
                            answers[itemId] = category.id;
                            selected = null;
                            render();
                        }
                    });
                    categoryColumn.appendChild(zone);
                });
                board.append(objectColumn, categoryColumn);
            } else {
                const lines = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                lines.classList.add('gq-preview-matching-lines');
                lines.setAttribute('aria-hidden', 'true');
                const leftColumn = document.createElement('div');
                leftColumn.className = 'gq-preview-match-column is-left';
                const rightColumn = document.createElement('div');
                rightColumn.className = 'gq-preview-match-column is-right';
                const updateLines = () => {
                    window.SaluangMatchingPreview.drawLines(
                        board,
                        lines,
                        answers,
                        'preview-left',
                        'preview-right',
                        'gq-preview-dot',
                        'gq-preview-matching-line'
                    );
                };
                items.forEach(item => {
                    const row = document.createElement('div');
                    row.className = 'gq-preview-match-row is-left';
                    row.dataset.previewLeft = item.id;
                    const button = document.createElement('div');
                    button.className = 'gq-preview-match-item';
                    appendCardImage(button, item);
                    const dot = document.createElement('span');
                    dot.className = 'gq-preview-dot';
                    if (selected === item.id) dot.classList.add('is-selected');
                    dot.setAttribute('role', 'button');
                    dot.tabIndex = 0;
                    dot.setAttribute('aria-label', `Titik penghubung ${item.label || ''}`);
                    row.append(button, dot);
                    const selectLeft = () => {
                        selected = item.id;
                        render();
                    };
                    dot.addEventListener('click', event => {
                        event.stopPropagation();
                        selectLeft();
                    });
                    dot.addEventListener('keydown', event => {
                        if (!['Enter', ' '].includes(event.key)) return;
                        event.preventDefault();
                        selectLeft();
                    });
                    dot.addEventListener('pointerdown', event => {
                        event.stopPropagation();
                        selected = item.id;
                        dot.classList.add('is-selected');
                        const line = document.createElementNS(
                            'http://www.w3.org/2000/svg',
                            'line'
                        );
                        line.classList.add('gq-preview-matching-line', 'is-temporary');
                        lines.appendChild(line);
                        const updateTemporary = pointerEvent => {
                            const rect = board.getBoundingClientRect();
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
                            if (pointerEvent.type === 'pointercancel') return;
                            const targetDot = document.elementFromPoint(
                                pointerEvent.clientX,
                                pointerEvent.clientY
                            )?.closest('.gq-preview-match-row.is-right .gq-preview-dot');
                            const target = targetDot?.closest('[data-preview-right]');
                            const targetId = target?.dataset.previewRight;
                            if (targetId) {
                                toggleMatchingPair(item.id, targetId);
                                selected = null;
                                render();
                            }
                        };
                        updateTemporary(event);
                        document.addEventListener('pointermove', onPointerMove);
                        document.addEventListener('pointerup', onPointerUp, { once: true });
                        document.addEventListener('pointercancel', onPointerUp, { once: true });
                    });
                    leftColumn.appendChild(row);
                });
                rightOrder.forEach(item => {
                    const row = document.createElement('div');
                    row.className = 'gq-preview-match-row is-right';
                    row.dataset.previewRight = item.id;
                    const button = document.createElement('div');
                    button.className = 'gq-preview-match-item';
                    const dot = document.createElement('span');
                    dot.className = 'gq-preview-dot';
                    if (selected) dot.classList.add('is-target-ready');
                    dot.setAttribute('role', 'button');
                    dot.tabIndex = 0;
                    dot.setAttribute('aria-label', `Titik pasangan ${item.label || ''}`);
                    appendCardImage(button, item);
                    row.append(dot, button);
                    const selectRight = () => {
                        if (!selected) return;
                        toggleMatchingPair(selected, item.id);
                        selected = null;
                        render();
                    };
                    dot.addEventListener('click', event => {
                        event.stopPropagation();
                        selectRight();
                    });
                    dot.addEventListener('keydown', event => {
                        if (!['Enter', ' '].includes(event.key)) return;
                        event.preventDefault();
                        selectRight();
                    });
                    rightColumn.appendChild(row);
                });
                board.append(lines, leftColumn, rightColumn);
                previewLineUpdate = updateLines;
                if (typeof ResizeObserver !== 'undefined') {
                    previewLineObserver = new ResizeObserver(updateLines);
                    previewLineObserver.observe(board);
                    previewLineObserver.observe(leftColumn);
                    previewLineObserver.observe(rightColumn);
                }
                requestAnimationFrame(updateLines);
            }

            previewContent.appendChild(board);
            const checkButton = document.createElement('button');
            checkButton.type = 'button';
            checkButton.className = 'gq-btn gq-btn-primary';
            checkButton.textContent = 'Periksa Aktivitas';
            checkButton.addEventListener('click', () => {
                previewChecked = true;
                render();
            });
            previewContent.appendChild(checkButton);
            if (previewChecked) {
                previewContent.appendChild(renderPreviewFeedback());
            }
        };
        render();
    }

    function openPreview(question) {
        if (!question) return;
        const previewContent = document.getElementById(
            'gqPreviewContent'
        );
        previewLineUpdate = null;
        if (question.activity_type && question.activity_type !== 'multiple_choice') {
            openModal(previewModal);
            renderActivityPreview(question, previewContent);
            requestAnimationFrame(() => previewLineUpdate?.());
            return;
        }
        previewContent.innerHTML = `
            <div class="gq-preview-question">
                Soal ${displayQuestionNumber(question)}.
                ${escapeHtml(question.pertanyaan)}
            </div>
            <div class="gq-preview-options">
                ${['A', 'B', 'C', 'D'].map(letter => `
                    <div class="gq-preview-option">
                        <strong>${letter}.</strong>
                        ${escapeHtml(
            question[`pilihan_${letter.toLowerCase()}`]
        )}
                    </div>
                `).join('')}
            </div>
            <p class="gq-question-meta">
                Ini adalah pratinjau tampilan soal.
                Kunci jawaban tidak ditampilkan kepada Siswa.
            </p>
        `;
        openModal(previewModal);
    }
    /* 11. HAPUS SOAL */
    function askDelete(question) {
        if (!question) return;
        pendingDeleteId = {
            questionId: question.id,
            subchapterId: selectedSubchapterId
        };
        if (deleteQuestionNumber) {
            deleteQuestionNumber.textContent = displayQuestionNumber(question);
        }
        openModal(deleteModal);
    }
    document.getElementById('gqConfirmDelete')
        .addEventListener('click', async event => {
            if (
                deleteRequestInProgress
                || !pendingDeleteId
                || !selectedSubchapterId
            ) return;
            const button = event.currentTarget;
            const { questionId, subchapterId } = pendingDeleteId;
            if (Number(subchapterId) !== Number(selectedSubchapterId)) return;
            deleteRequestInProgress = true;
            button.disabled = true;
            try {
                const result = await api(
                    questionUrl(questionId),
                    'DELETE'
                );
                const deletedIndex = questions.findIndex(
                    item => Number(item.id) === Number(questionId)
                );
                const preferredQuestionId = questions[deletedIndex + 1]?.id
                    || questions[deletedIndex - 1]?.id
                    || null;
                closeModals();
                pendingDeleteId = null;
                showToast(result.message || 'Soal berhasil dihapus.');
                if (selectedSubchapterId === subchapterId) {
                    await loadQuestions(preferredQuestionId);
                }
            } catch (error) {
                showToast(error.message, true);
            } finally {
                deleteRequestInProgress = false;
                button.disabled = false;
            }
        });
    /* 12. EVENT DROPDOWN */
    chapterSelect.addEventListener('change', updateSubchapters);
    subchapterSelect.addEventListener('change', selectSubchapter);
    activityTypeSelect.addEventListener('change', () => {
        if (activityTypeSelect.disabled) return;
        activityDraft = defaultActivityDraft(activityTypeSelect.value);
        setFormActivityType(activityTypeSelect.value);
    });
    activityEditor.addEventListener('input', event => {
        const field = event.target.closest('[data-activity-field]');
        if (!field || !activityDraft) return;
        const collection = field.dataset.activityCollection;
        const item = activityDraft[collection]?.[Number(field.dataset.activityIndex)];
        if (!item) return;
        const key = field.dataset.activityField;
        item[key] = key === 'right_indices'
            ? [...field.selectedOptions].map(option => Number(option.value))
            : ['category_index', 'right_index'].includes(key)
                ? (field.value === '' ? '' : Number(field.value))
                : field.value;
        if (key === 'label' && collection === 'categories') {
            activityEditor.querySelectorAll('[data-category-option]').forEach(option => {
                const category = activityDraft.categories.find(
                    entry => entry.id === option.dataset.categoryOption
                );
                const index = activityDraft.categories.indexOf(category);
                option.textContent = category?.label.trim()
                    || `Kategori ${index + 1}`;
            });
        }
        if (key === 'label' && collection === 'right_items') {
            refreshMatchingSelections();
        }
    });
    activityEditor.addEventListener('change', event => {
        const upload = event.target.closest('[data-activity-upload]');
        if (upload) {
            const file = upload.files?.[0];
            if (!file) return;
            const collection = upload.dataset.activityUpload;
            const index = Number(upload.dataset.activityIndex);
            const item = activityDraft[collection]?.[index];
            if (!item) return;
            if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)
                || file.size > 4 * 1024 * 1024) {
                showToast('Pilih JPG, JPEG, PNG, atau WebP dengan ukuran maksimal 4 MB.', true);
                upload.value = '';
                return;
            }
            const previewUrl = URL.createObjectURL(file);
            item.image_preview_url = previewUrl;
            renderActivityEditor();
            uploadActivityImage(file).then(url => {
                item.image_url = url;
                delete item.image_preview_url;
                URL.revokeObjectURL(previewUrl);
                renderActivityEditor();
                showToast('Gambar aktivitas berhasil diunggah.');
            }).catch(error => {
                delete item.image_preview_url;
                URL.revokeObjectURL(previewUrl);
                renderActivityEditor();
                showToast(error.message, true);
            });
            return;
        }
        const field = event.target.closest('[data-activity-field]');
        if (!field || !activityDraft) return;
        const key = field.dataset.activityField;
        if (!['category_id', 'category_index', 'right_index', 'right_indices'].includes(key)) return;
        const item = activityDraft[field.dataset.activityCollection]?.[
            Number(field.dataset.activityIndex)
        ];
        if (item) {
            item[key] = key === 'right_indices'
                ? [...field.selectedOptions].map(option => Number(option.value))
                : key === 'category_id'
                    ? field.value
                    : field.value === '' ? '' : Number(field.value);
        }
    });
    activityEditor.addEventListener('click', event => {
        const matchingToggle = event.target.closest('[data-matching-toggle]');
        if (matchingToggle && activityDraft) {
            const leftIndex = Number(matchingToggle.dataset.matchingToggle);
            if (activeMatchingMenu?.leftIndex === leftIndex) {
                closeMatchingMenu();
            } else {
                openMatchingMenu(matchingToggle, leftIndex);
            }
            return;
        }
        const matchingRemove = event.target.closest('[data-matching-remove]');
        if (matchingRemove && activityDraft) {
            const leftIndex = Number(matchingRemove.dataset.matchingRemove);
            const targetIndex = Number(matchingRemove.dataset.matchingTarget);
            activityDraft.left_items[leftIndex].right_indices = (
                activityDraft.left_items[leftIndex].right_indices || []
            ).map(Number).filter(index => index !== targetIndex);
            refreshMatchingSelections();
            return;
        }
        const addButton = event.target.closest('[data-activity-add]');
        const removeButton = event.target.closest('[data-activity-remove]');
        const removeImageButton = event.target.closest('[data-activity-image-remove]');
        if (removeImageButton && activityDraft) {
            const item = activityDraft[removeImageButton.dataset.activityImageRemove]?.[
                Number(removeImageButton.dataset.activityIndex)
            ];
            if (item) {
                item.image_url = '';
                delete item.image_preview_url;
                renderActivityEditor();
            }
            return;
        }
        if (addButton && activityDraft) {
            const collection = addButton.dataset.activityAdd;
            const items = activityDraft[collection];
            if (collection === 'categories') {
                items.push({ id: newActivityId('category'), label: '' });
            } else if (collection === 'right_items') {
                items.push({ label: '', image_url: '' });
            } else if (collection === 'items') {
                items.push({
                    label: '',
                    image_url: '',
                    category_id: ''
                });
            } else {
                items.push({
                    label: '',
                    image_url: '',
                    right_indices: []
                });
            }
            renderActivityEditor();
            return;
        }
        if (removeButton && activityDraft) {
            const collection = removeButton.dataset.activityRemove;
            const removedIndex = Number(removeButton.dataset.activityIndex);
            const [removedItem] = activityDraft[collection].splice(removedIndex, 1);
            if (collection === 'categories') {
                activityDraft.items.forEach(item => {
                    if (item.category_id === removedItem.id) item.category_id = '';
                });
            }
            if (collection === 'right_items') {
                activityDraft.left_items.forEach(item => {
                    item.right_indices = (item.right_indices || [])
                        .filter(index => index !== removedIndex)
                        .map(index => index > removedIndex ? index - 1 : index);
                    if (item.right_index === removedIndex) item.right_index = '';
                    else if (item.right_index > removedIndex) item.right_index--;
                });
            }
            renderActivityEditor();
        }
    });
    /* 13. TOMBOL TAMBAH SOAL */
    document.getElementById('gqAddQuestion')
        .addEventListener('click', () => {
            openQuestionForm();
        });
    /* 14. KLIK TOMBOL SOAL / CRUD */
    page.addEventListener('click', event => {
        const button = event.target.closest('[data-gq-action]');
        if (!button) return;
        const action = button.dataset.gqAction;
        const id = Number(button.dataset.id);
        const question = questions.find(
            item => Number(item.id) === id
        );
        if (action === 'select') {
            selectedQuestionId = id;
            renderQuestionTabs();
            renderQuestionDetail();
        }
        if (action === 'preview') {
            openPreview(question);
        }
        if (action === 'edit') {
            openQuestionForm(question);
        }
        if (action === 'delete') {
            askDelete(question);
        }
    });
    /* 16. TUTUP MODAL */
    page.querySelectorAll('[data-gq-close]')
        .forEach(button => {
            button.addEventListener('click', () => {
                closeModals();
                pendingDeleteId = null;
            });
        });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            closeModals();
            pendingDeleteId = null;
        }
    });
    /* 17. MUAT DATA AWAL */
    loadChapters();
});