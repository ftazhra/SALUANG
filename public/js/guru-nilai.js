
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
                <td colspan="8" class="gn-empty">
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
                <td>${displayScore(student.quiz_score)}</td>
                <td>${displayScore(student.evaluation_score)}</td>
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
                    Apakah kamu yakin ingin mengirim catatan ini
                    kepada Orang Tua/Wali?
                    Catatan akan tersimpan dalam riwayat
                    dan dapat dibaca melalui Dashboard Orang Tua.
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

            const cancelButton = overlay.querySelector(
                '[data-gn-confirm-cancel]'
            );

            const okButton = overlay.querySelector(
                '[data-gn-confirm-ok]'
            );

            const previousFocus = document.activeElement;
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
                `Detail Nilai — ${student.name || 'Siswa'}`;

            const studentInfo = `
                <div class="gn-detail-grid">
                    ${detailItem('Nama Siswa', student.name)}
                    ${detailItem('NISN', student.nisn)}
                    ${detailItem('Kelas', student.class)}
                    ${detailItem('Jumlah Riwayat', results.length)}
                </div>
            `;

            let history = '';

            if (!results.length) {
                history = `
                    <p style="margin-top:20px">
                        Belum ada hasil Kuis atau Evaluasi
                        yang tersimpan untuk siswa ini.
                    </p>
                `;
            } else {
                history = `
                    <h3 style="margin-top:22px">
                        Riwayat Hasil Belajar
                    </h3>

                    <div class="gn-table-wrapper">
                        <table class="gn-table">
                            <thead>
                                <tr>
                                    <th>Jenis</th>
                                    <th>Bab</th>
                                    <th>Benar</th>
                                    <th>Nilai</th>
                                    <th>Selesai</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${results.map(item => `
                                    <tr>
                                        <td>
                                            ${item.assessment_type === 'quiz'
                                                ? 'Kuis'
                                                : 'Evaluasi'}
                                        </td>
                                        <td>${displayValue(item.chapter_name)}</td>
                                        <td>
                                            ${displayValue(item.correct_answers)}
                                            /
                                            ${displayValue(item.total_questions)}
                                        </td>
                                        <td>${displayScore(item.score)}</td>
                                        <td>${displayDate(item.completed_at)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
            }

            detailContent.innerHTML = studentInfo + history + `
                <section class="gn-teacher-feedback"
                         aria-label="Catatan Guru untuk Orang Tua">

                    <h3>Catatan Guru untuk Orang Tua/Wali</h3>

                    <p class="gn-note-help">
                        Simpan sebagai draf jika belum siap,
                        atau kirim agar dapat dibaca Orang Tua/Wali.
                    </p>

                    <label for="gnTeacherNote">
                        Catatan perkembangan siswa untuk Orang Tua/Wali
                    </label>

                    <textarea
                        id="gnTeacherNote"
                        rows="5"
                        maxlength="5000"
                        placeholder="Tuliskan apresiasi, saran, atau arahan belajar untuk siswa..."
                    ></textarea>

                    <div class="gn-note-actions">
                        <span id="gnNoteStatus"
                              role="status"
                              aria-live="polite">
                            Memuat catatan...
                        </span>

                        <div class="gn-note-buttons">
                            <button
                                type="button"
                                class="gn-btn gn-btn-outline"
                                id="gnSaveTeacherNote">
                                <i class="bi bi-save"></i>
                                Simpan Draf
                            </button>

                            <button
                                type="button"
                                class="gn-btn gn-btn-pdf"
                                id="gnPublishTeacherNote">
                                <i class="bi bi-send"></i>
                                Kirim ke Orang Tua
                            </button>
                        </div>
                    </div>
                </section>
            `;

            const noteInput = byId('gnTeacherNote');
            const draftButton = byId('gnSaveTeacherNote');
            const publishButton = byId('gnPublishTeacherNote');
            const noteStatus = byId('gnNoteStatus');

            const noteUrl =
                `${API}/${encodeURIComponent(studentId)}/feedback`;

            function updateNoteStatus(status = 'kosong') {
                if (status === 'terkirim') {
                    noteInput.value = '';

                    noteStatus.innerHTML = `
                        <span class="gn-note-success">
                            <i class="bi bi-check-circle-fill"></i>
                            Catatan berhasil dikirim.
                            Silakan tulis catatan berikutnya.
                        </span>
                    `;

                } else if (status === 'draf') {
                    noteStatus.textContent =
                        'Draf catatan berhasil disimpan.';

                } else {
                    noteStatus.textContent =
                        'Belum ada catatan. Silakan tulis catatan baru.';
                }

                publishButton.innerHTML =
                    '<i class="bi bi-send"></i> Kirim ke Orang Tua';
            }

            draftButton.disabled = true;
            publishButton.disabled = true;

            try {
                const existing = await requestApi(noteUrl);

                if (
                    detailModal.hidden ||
                    version !== detailVersion ||
                    !noteInput.isConnected
                ) {
                    return;
                }

                noteInput.value = existing.note || '';

                if (existing.note) {
                    updateNoteStatus('draf');
                } else {
                    updateNoteStatus('kosong');
                }

                draftButton.disabled = false;
                publishButton.disabled = false;

            } catch (error) {
                if (noteInput.isConnected) {
                    noteStatus.textContent = error.message;
                }
            }

            async function saveTeacherNote(action) {
                const note = noteInput.value.trim();

                if (!note) {
                    noteStatus.textContent =
                        'Isi catatan terlebih dahulu.';
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

                draftButton.disabled = true;
                publishButton.disabled = true;

                noteStatus.textContent =
                    action === 'publish'
                        ? 'Mengirim catatan...'
                        : 'Menyimpan draf...';

                try {
                    const csrf = document.querySelector(
                        'meta[name="csrf-token"]'
                    )?.content || '';

                    const response = await fetch(noteUrl, {
                        method: 'PUT',
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrf
                        },
                        body: JSON.stringify({
                            note: note,
                            audience: 'orang_tua',
                            action: action
                        })
                    });

                    const result = await response.json()
                        .catch(() => ({}));

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
                        updateNoteStatus('terkirim');

                        showToast(
                            'Catatan berhasil dikirim ke Orang Tua/Wali.'
                        );
                    } else {
                        updateNoteStatus('draf');

                        showToast(
                            'Draf catatan berhasil disimpan.'
                        );
                    }

                } catch (error) {
                    if (noteStatus.isConnected) {
                        noteStatus.textContent = error.message;
                    }

                    showToast(error.message, true);

                } finally {
                    if (draftButton.isConnected) {
                        draftButton.disabled = false;
                    }

                    if (publishButton.isConnected) {
                        publishButton.disabled = false;
                    }
                }
            }

            draftButton.addEventListener('click', () => {
                saveTeacherNote('draft');
            });

            publishButton.addEventListener('click', () => {
                saveTeacherNote('publish');
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

            const url = `${API}/export/excel${
                query ? `?${query}` : ''
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

            const url = `${API}/export/pdf${
                query ? `?${query}` : ''
            }`;

            window.location.assign(url);
        });
    }

    /* 11. MUAT DATA AWAL */

    loadGrades();
});
