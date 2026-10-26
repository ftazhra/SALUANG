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
    const questionForm = document.getElementById('gqQuestionForm');
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
    let toastTimer = null;
    let questionLoadVersion = 0;
    let chapterLoadVersion = 0;
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
                        — ${escapeHtml(chapter.judul)}
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
                    — ${escapeHtml(item.judul)}
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
        questionTabs.innerHTML = questions.map(item => {
            const active = Number(item.id) ===
                Number(selectedQuestionId);
            return `
                <button type="button"
                    class="gq-question-tab ${active ? 'active' : ''}"
                    data-gq-action="select"
                    data-id="${item.id}"
                    aria-pressed="${active}">
                    Soal ${escapeHtml(item.nomor_soal)}
                </button>
            `;
        }).join('');
    }
    /* 08. DETAIL SOAL */
    function renderQuestionDetail() {
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
        const options = ['A', 'B', 'C', 'D'];
        questionDetail.innerHTML = `
            <article class="gq-question-card">
                <h3>Soal ${escapeHtml(question.nomor_soal)}</h3>
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
                            <div class="gq-answer-item ${
                                correct ? 'is-correct' : ''
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
                    <br>
                    <strong>Status:</strong>
                    ${question.is_published
                        ? 'Dipublikasikan'
                        : 'Draft'}
                </div>
                <div class="gq-question-meta">
                    <strong>Pembahasan:</strong>
                    <p style="white-space: pre-wrap;">${escapeHtml(
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
    /* 09. FORM TAMBAH / EDIT SOAL */
    function openQuestionForm(question = null) {
        if (!selectedSubchapterId) {
            showToast('Pilih Subbab terlebih dahulu.', true);
            return;
        }
        questionForm.reset();
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
        const saveButton = document.getElementById('gqSaveQuestion');
        const payload = {
            nomor_soal: Number(
                document.getElementById('gqQuestionNumber').value
            ),
            pertanyaan:
                document.getElementById('gqQuestionText').value.trim(),
            pilihan_a:
                document.getElementById('gqOptionA').value.trim(),
            pilihan_b:
                document.getElementById('gqOptionB').value.trim(),
            pilihan_c:
                document.getElementById('gqOptionC').value.trim(),
            pilihan_d:
                document.getElementById('gqOptionD').value.trim(),
            kunci_jawaban:
                document.getElementById('gqCorrectAnswer').value,
            pembahasan:
                document.getElementById('gqExplanation').value.trim(),
            is_published:
                document.getElementById('gqPublished').checked
        };
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
    function openPreview(question) {
        if (!question) return;
        const previewContent = document.getElementById(
            'gqPreviewContent'
        );
        previewContent.innerHTML = `
            <div class="gq-preview-question">
                Soal ${escapeHtml(question.nomor_soal)}.
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
        pendingDeleteId = question.id;
        openModal(deleteModal);
    }
    document.getElementById('gqConfirmDelete')
        .addEventListener('click', async event => {
            if (!pendingDeleteId || !selectedSubchapterId) return;
            const button = event.currentTarget;
            const id = pendingDeleteId;
            const subchapterAtSubmit = selectedSubchapterId;
            button.disabled = true;
            try {
                const result = await api(
                    questionUrl(id),
                    'DELETE'
                );
                closeModals();
                pendingDeleteId = null;
                showToast(result.message || 'Soal berhasil dihapus.');
                if (selectedSubchapterId === subchapterAtSubmit) {
                    await loadQuestions();
                }
            } catch (error) {
                showToast(error.message, true);
            } finally {
                button.disabled = false;
            }
        });
    /* 12. EVENT DROPDOWN */
    chapterSelect.addEventListener('change', updateSubchapters);
    subchapterSelect.addEventListener('change', selectSubchapter);
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