
/* =====================================================
   SALUANG — NILAI SISWA GURU
   File: public/js/guru-nilai.js
   ===================================================== */

document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    const page = document.getElementById('guruNilaiPage');
    if (!page) return;

    const byId = id => document.getElementById(id);
    const API = '/guru/student-grades';

    const tableBody = byId('gnStudentTableBody');
    const classFilter = byId('gnClassFilter');
    const completionFilter = byId('gnCompletionFilter');
    const searchInput = byId('gnSearchStudent');
    const studentCount = byId('gnStudentCount');
    const detailModal = byId('gnDetailModal');
    const detailTitle = byId('gnDetailTitle');
    const detailContent = byId('gnDetailContent');
    const toast = byId('gnToast');

    let searchTimer = null;
    let toastTimer = null;
    let requestVersion = 0;
    let detailVersion = 0;

    /* 01. UTILITAS */

    function escapeHtml(value) {
        return String(value ?? '').replace(
            /[&<>"']/g,
            character => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            })[character]
        );
    }

    function displayValue(value) {
        if (value === null || value === undefined || value === '') {
            return '—';
        }
        return escapeHtml(value);
    }

    function displayScore(value) {
        if (value === null || value === undefined || value === '') {
            return '—';
        }

        const number = Number(value);
        if (!Number.isFinite(number)) return '—';

        return number.toLocaleString('id-ID', {
            maximumFractionDigits: 2
        });
    }

    function displayDate(value) {
        if (!value) return '—';

        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return '—';

        return date.toLocaleString('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function showToast(message, isError = false) {
        if (!toast) return;

        clearTimeout(toastTimer);

        toast.textContent = message;
        toast.hidden = false;
        toast.style.background = isError ? '#b53838' : '#075477';

        toastTimer = setTimeout(() => {
            toast.hidden = true;
        }, 4000);
    }

    async function requestApi(url) {
        const response = await fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        let result;

        try {
            result = await response.json();
        } catch (_) {
            throw new Error(
                'Respons server tidak valid. Periksa login dan route Laravel.'
            );
        }

        if (!response.ok) {
            throw new Error(
                result.message || 'Gagal mengambil data nilai siswa.'
            );
        }

        return result;
    }

    /* 02. STATUS KETUNTASAN */

    function renderStatus(status) {
        if (status === 'tuntas') {
            return '<span class="gn-status is-complete">Tuntas</span>';
        }

        if (status === 'belum_tuntas') {
            return '<span class="gn-status is-incomplete">Belum Tuntas</span>';
        }

        return '<span class="gn-status is-pending">Belum Dinilai</span>';
    }

    /* 03. TABEL NILAI */

    function showEmpty(message) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="6" class="gn-empty">
                    ${escapeHtml(message)}
                </td>
            </tr>
        `;
    }

    function renderTable(students) {
        studentCount.textContent =
            `${students.length} siswa ditampilkan`;

        if (!students.length) {
            showEmpty(
                'Belum ada siswa yang sesuai dengan pencarian atau filter.'
            );
            return;
        }

        tableBody.innerHTML = students.map(student => `
            <tr>
                <td>${displayValue(student.name)}</td>
                <td>${displayValue(student.nisn)}</td>
                <td>${displayValue(student.class)}</td>
                <td>${displayScore(student.final_score)}</td>
                <td>${renderStatus(student.completion_status)}</td>
                <td>
                    <button
                        type="button"
                        class="gn-btn gn-btn-outline"
                        data-gn-detail="${Number(student.id)}">
                        <i class="bi bi-eye"></i> Lihat
                    </button>
                </td>
            </tr>
        `).join('');
    }

    /* 04. FILTER KELAS */

    function renderClasses(classes) {
        const previousValue = classFilter.value;

        classFilter.replaceChildren();
        classFilter.add(new Option('Semua Kelas', ''));

        classes.forEach(className => {
            if (className === null ||
                className === undefined ||
                className === '') {
                return;
            }

            classFilter.add(
                new Option(String(className), String(className))
            );
        });

        const hasPrevious = Array.from(classFilter.options)
            .some(option => option.value === previousValue);

        classFilter.value = hasPrevious ? previousValue : '';
    }

    /* 05. AMBIL NILAI */

    async function loadGrades() {
        const version = ++requestVersion;
        showEmpty('Memuat data nilai siswa...');

        const params = new URLSearchParams();
        const search = searchInput.value.trim();
        const className = classFilter.value;
        const completion = completionFilter.value;

        if (search) params.set('search', search);
        if (className) params.set('class', className);
        if (completion) params.set('completion', completion);

        const url = params.toString()
            ? `${API}?${params.toString()}`
            : API;

        try {
            const result = await requestApi(url);
            if (version !== requestVersion) return;

            const students = Array.isArray(result.data)
                ? result.data : [];

            const classes = Array.isArray(result.classes)
                ? result.classes : [];

            renderClasses(classes);
            renderTable(students);

        } catch (error) {
            if (version !== requestVersion) return;

            studentCount.textContent = 'Data gagal dimuat';
            showEmpty(error.message);
            showToast(error.message, true);
        }
    }

    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimer);

        searchTimer = setTimeout(() => {
            loadGrades();
        }, 350);
    });

    classFilter.addEventListener('change', loadGrades);
    completionFilter.addEventListener('change', loadGrades);

    /* 06. MODAL KONFIRMASI CATATAN */

    function ensurePublishConfirmModal() {
        let overlay = byId('gnPublishConfirmOverlay');

        if (overlay) return overlay;

        overlay = document.createElement('div');
        overlay.id = 'gnPublishConfirmOverlay';
        overlay.className = 'gn-confirm-overlay';
        overlay.hidden = true;

        overlay.innerHTML = `
            <div class="gn-confirm-box"
                 role="dialog"
                 aria-modal="true"
                 aria-labelledby="gnConfirmTitle"
                 aria-describedby="gnConfirmDescription">

                <div class="gn-confirm-icon">
                    <i class="bi bi-send-check"></i>
                </div>

                <h3 class="gn-confirm-title" id="gnConfirmTitle">
                    Kirim Catatan?
                </h3>

                <p class="gn-confirm-text"
                   id="gnConfirmDescription">
                    Kirim catatan ini kepada Orang Tua/Wali?
                </p>

                <div class="gn-confirm-actions">
                    <button type="button"
                            class="gn-confirm-btn gn-confirm-btn-cancel"
                            data-gn-confirm-cancel>
                        Batal
                    </button>

                    <button type="button"
                            class="gn-confirm-btn gn-confirm-btn-ok"
                            data-gn-confirm-ok>
                        <i class="bi bi-send"></i>
                        Ya, Kirim
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(overlay);

        return overlay;
    }

    function openPublishConfirm() {
        return new Promise(resolve => {
            const overlay = ensurePublishConfirmModal();
            const description = overlay.querySelector('#gnConfirmDescription');

            const cancelButton = overlay.querySelector(
                '[data-gn-confirm-cancel]'
            );

            const okButton = overlay.querySelector(
                '[data-gn-confirm-ok]'
            );

            const previousFocus = document.activeElement;
            description.textContent =
                'Kirim catatan ini kepada Orang Tua/Wali?';
            overlay.hidden = false;

            function close(confirmed) {
                overlay.hidden = true;

                cancelButton.removeEventListener('click', onCancel);
                okButton.removeEventListener('click', onConfirm);
                overlay.removeEventListener('click', onOutsideClick);
                document.removeEventListener('keydown', onKeyDown, true);

                if (previousFocus?.isConnected) {
                    previousFocus.focus();
                }

                resolve(confirmed);
            }

            function onCancel() {
                close(false);
            }

            function onConfirm() {
                close(true);
            }

            function onOutsideClick(event) {
                if (event.target === overlay) {
                    close(false);
                }
            }

            function onKeyDown(event) {
                if (event.key === 'Escape') {
                    event.preventDefault();
                    event.stopImmediatePropagation();
                    close(false);
                }

                if (event.key === 'Tab') {
                    const buttons = [cancelButton, okButton];

                    if (event.shiftKey &&
                        document.activeElement === cancelButton) {
                        event.preventDefault();
                        okButton.focus();
                    } else if (!event.shiftKey &&
                        document.activeElement === okButton) {
                        event.preventDefault();
                        cancelButton.focus();
                    }
                }
            }

            cancelButton.addEventListener('click', onCancel);
            okButton.addEventListener('click', onConfirm);
            overlay.addEventListener('click', onOutsideClick);
            document.addEventListener('keydown', onKeyDown, true);

            okButton.focus();
        });
    }

    /* 07. DETAIL NILAI SISWA */

    function detailItem(label, value) {
        return `
            <div class="gn-detail-item">
                <small>${escapeHtml(label)}</small>
                <strong>${displayValue(value)}</strong>
            </div>
        `;
    }

    async function openStudentDetail(studentId) {
        const version = ++detailVersion;

        detailTitle.textContent = 'Detail Nilai Siswa';
        detailContent.innerHTML =
            '<p>Memuat detail nilai siswa...</p>';

        detailModal.hidden = false;

        try {
            const result = await requestApi(
                `${API}/${encodeURIComponent(studentId)}`
            );

            if (detailModal.hidden || version !== detailVersion) return;

            const student = result.student || {};
            const results = Array.isArray(result.results)
                ? result.results : [];

            detailTitle.textContent =
                `Detail Nilai Siswa: ${student.name || 'Siswa'}`;

            const studentInfo = `
                <div class="gn-detail-grid">
                    ${detailItem('Nama Siswa', student.name)}
                    ${detailItem('NISN', student.nisn)}
                    ${detailItem('Kelas', student.class)}
                    ${detailItem(
                'Kuis selesai',
                `${student.grading?.quiz_completed_count ?? 0} / ${student.grading?.quiz_required_count ?? 0}`
            )}
                    ${detailItem('Rata-rata Kuis Subbab', displayScore(student.grading?.quiz_score))}
                    ${detailItem('Evaluasi Akhir', displayScore(student.grading?.evaluation_score))}
                    ${detailItem('Nilai Akhir', displayScore(student.grading?.final_score))}
                    ${detailItem(
                'Ketuntasan',
                student.grading?.completion_status === 'tuntas'
                    ? 'Tuntas'
                    : student.grading?.completion_status === 'belum_tuntas'
                        ? 'Belum Tuntas'
                        : 'Belum Dinilai'
            )}
                    ${detailItem('Jumlah Hasil Terbaru', results.length)}
                </div>
            `;

            let history = '';

            if (!results.length) {
                history = `
                    <p class="gn-history-empty">
                        Belum ada hasil Kuis atau Evaluasi yang berlaku untuk siswa ini.
                    </p>
                `;
            } else {
                history = `
                    <h3 class="gn-history-title">Hasil Belajar Terbaru</h3>
                    <div class="gn-table-wrapper">
                        <table class="gn-table gn-history-table">
                            <colgroup>
                                <col class="gn-history-topic-column">
                                <col class="gn-history-type-column">
                                <col class="gn-history-correct-column">
                                <col class="gn-history-score-column">
                                <col class="gn-history-date-column">
                                <col class="gn-history-note-column">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th class="gn-history-topic-cell">Bab / Subbab</th>
                                    <th class="gn-history-type-cell">Jenis</th>
                                    <th class="gn-history-correct-cell">Jawaban benar</th>
                                    <th class="gn-history-score-cell">Nilai</th>
                                    <th class="gn-history-date-cell">Waktu pengerjaan</th>
                                    <th class="gn-history-note-cell">Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${results.map((item, index) => {
                    const type = item.assessment_type === 'quiz'
                        ? 'quiz'
                        : 'evaluation';
                    const typeLabel = type === 'quiz'
                        ? 'Kuis Subbab'
                        : 'Evaluasi Akhir';
                    const chapter = item.chapter_name || 'Bab tidak tersedia';
                    const subchapter = item.subchapter_name || 'Subbab tidak tercatat';
                    const context = type === 'quiz'
                        ? `${typeLabel}, ${chapter}, ${subchapter}`
                        : `${typeLabel} dari ${displayValue(item.teacher_name || 'Guru')}`;
                    const gradingNote = type === 'evaluation'
                        && item.valid_for_grading === false
                        ? 'Nilai ini tetap tercatat dalam riwayat, tetapi tidak dihitung dalam Nilai Akhir karena bank Evaluasi telah berubah.'
                        : '';
                    const resultKey = `${type}:${item.id}`;
                    const textareaId = `gnTeacherNote${index}`;

                    return `
                                        <tr>
                                            <td class="gn-history-topic-cell">
                                                ${type === 'quiz'
                            ? `${displayValue(chapter)}<br><small>${displayValue(subchapter)}</small>`
                            : `Evaluasi oleh ${displayValue(item.teacher_name || 'Guru')}`}
                                            </td>
                                            <td class="gn-history-type-cell">${typeLabel}</td>
                                            <td class="gn-history-correct-cell">
                                                ${displayValue(item.correct_answers)}
                                                /
                                                ${displayValue(item.total_questions)}
                                            </td>
                                            <td class="gn-history-score-cell">${displayScore(item.score)}</td>
                                            <td class="gn-history-date-cell">${displayDate(item.completed_at)}</td>
                                            <td class="gn-history-note-cell">
                                                <button type="button"
                                                        class="gn-btn gn-btn-outline gn-note-open"
                                                        data-note-toggle
                                                        data-note-key="${escapeHtml(resultKey)}"
                                                        aria-expanded="false">
                                                    Beri Catatan
                                                </button>
                                            </td>
                                        </tr>
                                        <tr class="gn-note-row"
                                            data-note-panel
                                            data-note-key="${escapeHtml(resultKey)}"
                                            hidden>
                                            <td colspan="6" class="gn-history-form-cell">
                                                <section class="gn-teacher-feedback"
                                                         aria-label="Catatan untuk ${escapeHtml(context)}">
                                                    <h4>Catatan untuk ${escapeHtml(context)}</h4>
                                                    <p class="gn-note-help">
                                                        ${gradingNote || `Hasil ini memperoleh nilai ${displayScore(item.score)} dengan ${displayValue(item.correct_answers)} dari ${displayValue(item.total_questions)} jawaban benar, selesai ${displayDate(item.completed_at)}.`}
                                                    </p>
                                                    <label for="${textareaId}">
                                                        Catatan perkembangan untuk Orang Tua/Wali
                                                    </label>
                                                    <textarea id="${textareaId}"
                                                              class="gn-note-input"
                                                              rows="4"
                                                              maxlength="5000"
                                                              placeholder="Tuliskan apresiasi, saran, atau arahan belajar untuk hasil ini..."></textarea>
                                                    <div class="gn-note-actions">
                                                        <span class="gn-note-status"
                                                              role="status"
                                                              aria-live="polite">Muat draf untuk hasil ini.</span>
                                                        <div class="gn-note-buttons">
                                                            <button type="button"
                                                                    class="gn-btn gn-btn-outline"
                                                                    data-note-action="draft">
                                                                <i class="bi bi-save"></i>
                                                                Simpan Draf
                                                            </button>
                                                            <button type="button"
                                                                    class="gn-btn gn-btn-pdf"
                                                                    data-note-action="publish">
                                                                <i class="bi bi-send"></i>
                                                                Kirim ke Orang Tua
                                                            </button>
                                                        </div>
                                                    </div>
                                                </section>
                                            </td>
                                        </tr>
                                    `;
                }).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
            }

            detailContent.innerHTML = studentInfo + history;

            const feedbackUrl =
                `${API}/${encodeURIComponent(studentId)}/feedback`;

            function updateNoteStatus(statusElement, value) {
                const messages = {
                    empty: 'Belum ada draf. Silakan tulis catatan baru.',
                    draft: 'Draf catatan untuk hasil ini berhasil disimpan.',
                    sent: 'Catatan berhasil dikirim. Silakan tulis catatan berikutnya.',
                };
                statusElement.textContent = messages[value] || value;
            }

            async function loadDraft(panel, type, resultId) {
                const noteInput = panel.querySelector('.gn-note-input');
                const status = panel.querySelector('.gn-note-status');
                const actionButtons = panel.querySelectorAll('[data-note-action]');
                panel.dataset.loading = 'true';
                noteInput.disabled = true;
                const query = new URLSearchParams({
                    assessment_type: type,
                    assessment_result_id: resultId,
                    audience: 'orang_tua',
                });

                actionButtons.forEach(button => {
                    button.disabled = true;
                });
                status.textContent = 'Memuat draf catatan...';

                try {
                    const existing = await requestApi(`${feedbackUrl}?${query}`);

                    if (
                        detailModal.hidden ||
                        version !== detailVersion ||
                        !noteInput.isConnected
                    ) {
                        return;
                    }

                    noteInput.value = existing.note || '';
                    updateNoteStatus(status, noteInput.value ? 'draft' : 'empty');
                    panel.dataset.loaded = 'true';
                } catch (error) {
                    if (noteInput.isConnected) {
                        status.textContent = error.message;
                    }
                    showToast(error.message, true);
                } finally {
                    delete panel.dataset.loading;
                    if (noteInput.isConnected) noteInput.disabled = false;
                    actionButtons.forEach(button => {
                        if (button.isConnected) button.disabled = false;
                    });
                }
            }

            async function saveTeacherNote(panel, type, resultId, action) {
                const noteInput = panel.querySelector('.gn-note-input');
                const status = panel.querySelector('.gn-note-status');
                const actionButtons = panel.querySelectorAll('[data-note-action]');
                const note = noteInput.value.trim();

                if (!note) {
                    status.textContent = 'Isi catatan terlebih dahulu.';
                    noteInput.focus();
                    return;
                }

                if (action === 'publish') {
                    const confirmed = await openPublishConfirm();
                    if (!confirmed) return;

                    if (
                        detailModal.hidden ||
                        version !== detailVersion ||
                        !noteInput.isConnected
                    ) {
                        return;
                    }
                }

                actionButtons.forEach(button => {
                    button.disabled = true;
                });
                status.textContent = action === 'publish'
                    ? 'Mengirim catatan...'
                    : 'Menyimpan draf...';

                try {
                    const csrf = document.querySelector(
                        'meta[name="csrf-token"]'
                    )?.content || '';
                    const response = await fetch(feedbackUrl, {
                        method: 'PUT',
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: JSON.stringify({
                            note,
                            audience: 'orang_tua',
                            action,
                            assessment_type: type,
                            assessment_result_id: resultId,
                        }),
                    });
                    const result = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        throw new Error(
                            Object.values(result.errors || {}).flat()[0] ||
                            result.message ||
                            `Gagal menyimpan (HTTP ${response.status}).`
                        );
                    }

                    if (
                        detailModal.hidden ||
                        version !== detailVersion ||
                        !noteInput.isConnected
                    ) {
                        return;
                    }

                    if (action === 'publish') {
                        noteInput.value = '';
                        panel.dataset.loaded = 'true';
                        updateNoteStatus(status, 'sent');
                        showToast('Catatan berhasil dikirim ke Orang Tua/Wali.');
                    } else {
                        updateNoteStatus(status, 'draft');
                        showToast('Draf catatan berhasil disimpan.');
                    }
                } catch (error) {
                    if (status.isConnected) status.textContent = error.message;
                    showToast(error.message, true);
                } finally {
                    actionButtons.forEach(button => {
                        if (button.isConnected) button.disabled = false;
                    });
                }
            }

            detailContent.querySelectorAll('[data-note-toggle]').forEach(button => {
                button.addEventListener('click', () => {
                    const key = button.dataset.noteKey;
                    const panel = Array.from(
                        detailContent.querySelectorAll('[data-note-panel]')
                    ).find(candidate => candidate.dataset.noteKey === key);
                    if (!panel) return;

                    panel.hidden = !panel.hidden;
                    button.setAttribute('aria-expanded', String(!panel.hidden));
                    if (panel.hidden) return;

                    const [type, resultId] = key.split(':');
                    if (!panel.dataset.loaded && !panel.dataset.loading) {
                        loadDraft(panel, type, resultId);
                    }
                });
            });

            detailContent.querySelectorAll('[data-note-panel]').forEach(panel => {
                const key = panel.dataset.noteKey;
                const [type, resultId] = key.split(':');
                panel.querySelectorAll('[data-note-action]').forEach(button => {
                    button.addEventListener('click', () => {
                        saveTeacherNote(
                            panel,
                            type,
                            resultId,
                            button.dataset.noteAction
                        );
                    });
                });
            });

        } catch (error) {
            if (version !== detailVersion) return;

            detailContent.textContent = error.message;
            showToast(error.message, true);
        }
    }

    tableBody.addEventListener('click', event => {
        const button = event.target.closest('[data-gn-detail]');
        if (!button) return;

        const studentId = Number(button.dataset.gnDetail);

        if (!Number.isSafeInteger(studentId) || studentId <= 0) {
            return;
        }

        openStudentDetail(studentId);
    });

    /* 08. TUTUP MODAL DETAIL */

    page.querySelectorAll('[data-gn-close]').forEach(button => {
        button.addEventListener('click', () => {
            detailVersion++;
            detailModal.hidden = true;
        });
    });

    document.addEventListener('keydown', event => {
        if (
            event.key === 'Escape' &&
            !byId('gnPublishConfirmOverlay')?.hidden
        ) {
            return;
        }

        if (event.key === 'Escape') {
            detailVersion++;
            detailModal.hidden = true;
        }
    });

    /* 09. UNDUH EXCEL */

    const downloadExcelButton = byId('gnDownloadExcel');

    if (downloadExcelButton) {
        downloadExcelButton.addEventListener('click', () => {
            const params = new URLSearchParams();

            const search = searchInput.value.trim();
            const className = classFilter.value;
            const completion = completionFilter.value;

            if (search) params.set('search', search);
            if (className) params.set('class', className);
            if (completion) params.set('completion', completion);

            const query = params.toString();

            const url = `${API}/export/excel${query ? `?${query}` : ''
                }`;

            window.location.assign(url);
        });
    }

    /* 10. UNDUH PDF */

    const downloadPdfButton = byId('gnDownloadPdf');

    if (downloadPdfButton) {
        downloadPdfButton.addEventListener('click', () => {
            const params = new URLSearchParams();

            const search = searchInput.value.trim();
            const className = classFilter.value;
            const completion = completionFilter.value;

            if (search) params.set('search', search);
            if (className) params.set('class', className);
            if (completion) params.set('completion', completion);

            const query = params.toString();

            const url = `${API}/export/pdf${query ? `?${query}` : ''
                }`;

            window.location.assign(url);
        });
    }

    /* 11. MUAT DATA AWAL */

    loadGrades();

    window.addEventListener('load', () => {
        const url = new URL(window.location.href);
        const rawStudentId = url.searchParams.get('student_id');
        if (rawStudentId === null) return;

        const studentId = Number(rawStudentId);
        url.searchParams.delete('student_id');
        url.searchParams.set('page', 'nilai');
        window.history.replaceState(
            { ...window.history.state, page: 'nilai' },
            '',
            url
        );

        if (Number.isSafeInteger(studentId) && studentId > 0) {
            openStudentDetail(studentId);
        }
    }, { once: true });
});
