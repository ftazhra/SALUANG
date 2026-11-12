document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    const page = document.getElementById('guruStudentsPage');
    if (!page) return;

    const byId = id => document.getElementById(id);

    const search = byId('gstuSearch');
    const classFilter = byId('gstuClass');
    const rows = byId('gstuRows');
    const errorBox = byId('gstuPageError');

    const formModal = byId('gstuFormModal');
    const detailModal = byId('gstuDetailModal');
    const deleteModal = byId('gstuDeleteModal');

    const form = byId('gstuForm');
    const formError = byId('gstuFormError');
    const deleteError = byId('gstuDeleteError');
    const deleteStudentName = byId('gstuDeleteStudentName');

    const toast = byId('gstuToast');
    const formClass = byId('gstuFormClass');

    const saveButton = byId('gstuSaveButton');
    const deleteConfirm = byId('gstuDeleteConfirm');

    const csrf =
        document.querySelector('meta[name="csrf-token"]')?.content || '';

    let pageNumber = 1;
    let lastPage = 1;
    let studentDetailVersion = 0;
    let editingId = null;
    let deletingId = null;

    let debounce;
    let toastTimer;
    let deleting = false;

    /* =====================================================
       ERROR DAN NOTIFIKASI
       ===================================================== */

    function showError(element, message) {
        if (!element) return;

        element.textContent = message;
        element.hidden = false;
    }

    function clearError(element) {
        if (!element) return;

        element.textContent = '';
        element.hidden = true;
    }

    function notify(message) {
        if (!toast) return;

        toast.textContent = message;
        toast.hidden = false;

        clearTimeout(toastTimer);

        toastTimer = setTimeout(() => {
            toast.hidden = true;
        }, 3500);
    }

    /* =====================================================
       API REQUEST
       ===================================================== */

    async function api(url, method = 'GET', body = null) {
        const options = {
            method,
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf
            }
        };

        if (body !== null) {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(body);
        }

        let response;

        try {
            response = await fetch(url, options);
        } catch (error) {
            throw new Error(
                'Tidak dapat terhubung ke server Laravel. ' +
                'Pastikan server masih berjalan.'
            );
        }

        let result;

        try {
            result = await response.json();
        } catch (error) {
            if (response.status === 419) {
                throw new Error(
                    'Sesi telah kedaluwarsa. ' +
                    'Silakan muat ulang halaman dan coba lagi.'
                );
            }

            throw new Error(
                `Server tidak mengembalikan JSON yang valid ` +
                `(HTTP ${response.status}).`
            );
        }

        if (!response.ok) {
            const firstError = result.errors
                ? Object.values(result.errors).flat()[0]
                : null;

            throw new Error(
                firstError ||
                result.message ||
                `Permintaan gagal (HTTP ${response.status}).`
            );
        }

        return result;
    }

    /* =====================================================
       PILIHAN KELAS
       ===================================================== */

    function fillClassOptions(classes, registeredClasses) {
        const currentFilter = classFilter.value;

        classFilter.replaceChildren(
            new Option('Semua kelas terdaftar', '')
        );

        formClass.replaceChildren();

        classes.forEach(name => {
            formClass.add(new Option(name, name));
        });
        registeredClasses.forEach(name => {
            classFilter.add(new Option(name, name));
        });

        if (registeredClasses.includes(currentFilter)) {
            classFilter.value = currentFilter;
        }
    }

    /* =====================================================
       TOMBOL AKSI SISWA
       ===================================================== */

    function actionButton(label, icon, action, id) {
        const button = document.createElement('button');

        button.type = 'button';
        button.className = 'gstu-row-action';

        button.dataset.action = action;
        button.dataset.id = String(id);
        button.classList.add(`is-${action}`);

        button.setAttribute('aria-label', label);
        button.title = label;

        const glyph = document.createElement('i');
        glyph.className = `bi ${icon}`;

        const text = document.createElement('span');
        text.textContent = action === 'show' ? 'Lihat' : action === 'edit' ? 'Edit' : 'Hapus';
        button.append(glyph, text);

        return button;
    }

    /* =====================================================
       TAMPILKAN SISWA
       ===================================================== */

    function renderStudents(students) {
        rows.replaceChildren();

        if (!students.length) {
            const tr = document.createElement('tr');
            const cell = document.createElement('td');

            cell.colSpan = 5;
            cell.className = 'gstu-empty';
            cell.textContent = 'Tidak ada siswa pada filter ini.';

            tr.append(cell);
            rows.append(tr);

            return;
        }

        students.forEach(student => {
            const tr = document.createElement('tr');

            [
                student.name,
                student.nisn,
                student.kelas,
                student.email
            ].forEach(value => {
                const td = document.createElement('td');

                td.textContent = value || '-';
                tr.append(td);
            });

            const actions = document.createElement('td');
            actions.className = 'gstu-row-actions';

            actions.append(
                actionButton(
                    'Lihat detail',
                    'bi-eye',
                    'show',
                    student.id
                )
            );
            const editButton = actionButton(
                'Edit siswa',
                'bi-pencil-square',
                'edit',
                student.id
            );
            const deleteButton = actionButton(
                'Hapus siswa',
                'bi-trash3',
                'delete',
                student.id
            );
            deleteButton.dataset.studentName =
                student.name || 'Siswa';
            actions.append(editButton, deleteButton);

            tr.append(actions);
            rows.append(tr);
        });
    }

    /* =====================================================
       MUAT DAFTAR SISWA
       ===================================================== */

    async function loadStudents() {
        clearError(errorBox);

        rows.innerHTML =
            '<tr><td colspan="5" class="gstu-empty">' +
            'Memuat daftar siswa...</td></tr>';

        const params = new URLSearchParams({
            page: String(pageNumber)
        });

        if (search.value.trim()) {
            params.set('search', search.value.trim());
        }

        if (classFilter.value) {
            params.set('class', classFilter.value);
        }

        try {
            const result = await api(
                `/guru/students?${params.toString()}`
            );

            fillClassOptions(result.classes || [], result.registered_classes || []);
            renderStudents(result.students || []);

            const registeredTotal = byId('gdTotalStudents');

            if (
                registeredTotal &&
                Number.isInteger(result.registered_total)
            ) {
                registeredTotal.textContent =
                    String(result.registered_total);
            }

            pageNumber = result.pagination.current_page;
            lastPage = result.pagination.last_page;

            byId('gstuPageInfo').textContent =
                `${result.pagination.total} siswa · ` +
                `Halaman ${pageNumber} dari ${lastPage}`;

            byId('gstuPrevious').disabled = pageNumber <= 1;
            byId('gstuNext').disabled = pageNumber >= lastPage;

        } catch (error) {
            showError(errorBox, error.message);
            rows.replaceChildren();
        }
    }

    /* =====================================================
       MODAL
       ===================================================== */

    function openModal(modal) {
        if (!modal) return;

        modal.hidden = false;

        const dialog = modal.querySelector('[role="dialog"]');

        if (dialog) {
            if (!dialog.hasAttribute('tabindex')) {
                dialog.setAttribute('tabindex', '-1');
            }

            dialog.focus();
        }
    }

    function closeModals() {
        if (deleting) return;

        studentDetailVersion++;
        [formModal, detailModal, deleteModal].forEach(modal => {
            if (modal) {
                modal.hidden = true;
            }
        });

        deletingId = null;
    }

    /* =====================================================
       TAMBAH SISWA
       ===================================================== */

    /* =====================================================
       DETAIL SISWA
       ===================================================== */

    function detailField(label, value) {
        const group = document.createElement('div');
        const term = document.createElement('dt');
        const description = document.createElement('dd');

        term.textContent = label;
        description.textContent = value || '-';

        group.append(term, description);

        return group;
    }

    async function showStudent(id, editing = false) {
        const requestVersion = ++studentDetailVersion;
        clearError(errorBox);

        try {
            const { student } = await api(
                `/guru/students/${id}`
            );
            if (requestVersion !== studentDetailVersion) return;

            if (editing) {
                editingId = student.id;

                form.reset();

                Object.entries(student).forEach(([key, value]) => {
                    if (form.elements[key]) {
                        form.elements[key].value = value ?? '';
                    }
                });

                byId('gstuFormTitle').textContent =
                    'Edit Informasi Siswa';

                clearError(formError);

                openModal(formModal);
                form.elements.name.focus();

                return;
            }

            const content = byId('gstuDetailContent');

            content.replaceChildren(
                detailField('Nama', student.name),
                detailField('NISN', student.nisn),
                detailField('Email', student.email),
                detailField('Kelas', student.kelas)
            );

            openModal(detailModal);

        } catch (error) {
            showError(errorBox, error.message);
        }
    }

    /* =====================================================
       PAGINATION
       ===================================================== */

    byId('gstuPrevious').addEventListener('click', () => {
        if (pageNumber > 1) {
            pageNumber--;
            loadStudents();
        }
    });

    byId('gstuNext').addEventListener('click', () => {
        if (pageNumber < lastPage) {
            pageNumber++;
            loadStudents();
        }
    });

    /* =====================================================
       FILTER
       ===================================================== */

    search.addEventListener('input', () => {
        clearTimeout(debounce);

        debounce = setTimeout(() => {
            pageNumber = 1;
            loadStudents();
        }, 250);
    });

    classFilter.addEventListener('change', () => {
        pageNumber = 1;
        loadStudents();
    });

    /* =====================================================
       AKSI PADA BARIS TABEL
       ===================================================== */

    rows.addEventListener('click', event => {
        const button = event.target.closest('[data-action]');

        if (!button) return;

        const id = Number(button.dataset.id);

        if (button.dataset.action === 'show') {
            showStudent(id);
            return;
        }

        if (button.dataset.action === 'edit') {
            showStudent(id, true);
            return;
        }

        if (button.dataset.action === 'delete') {
            if (!Number.isInteger(id) || id <= 0) {
                showError(
                    errorBox,
                    'ID siswa tidak valid.'
                );
                return;
            }

            deletingId = id;

            deleteStudentName.textContent =
                button.dataset.studentName || 'Siswa';

            clearError(deleteError);

            deleteConfirm.disabled = false;
            deleteConfirm.textContent = 'Ya, Hapus';

            openModal(deleteModal);
        }
    });

    /* =====================================================
       SIMPAN TAMBAH / EDIT SISWA
       ===================================================== */

    form.addEventListener('submit', async event => {
        event.preventDefault();

        clearError(formError);

        saveButton.disabled = true;

        const data = Object.fromEntries(
            new FormData(form).entries()
        );

        try {
            if (!editingId) {
                throw new Error('Guru tidak dapat menambahkan akun siswa dari halaman ini.');
            }
            const result = await api(
                `/guru/students/${editingId}`,
                'PUT',
                data
            );

            closeModals();

            notify(
                result.message || 'Data siswa berhasil disimpan.'
            );

            pageNumber = 1;
            await loadStudents();

        } catch (error) {
            showError(formError, error.message);

        } finally {
            saveButton.disabled = false;
        }
    });

    /* =====================================================
       HAPUS SISWA - PERBAIKAN UTAMA
       ===================================================== */

    if (deleteConfirm) {
        deleteConfirm.addEventListener('click', async event => {
            event.preventDefault();
            event.stopPropagation();

            if (deleting) return;

            if (
                deletingId === null ||
                !Number.isInteger(Number(deletingId)) ||
                Number(deletingId) <= 0
            ) {
                showError(
                    deleteError,
                    'Siswa yang akan dihapus belum dipilih.'
                );
                return;
            }

            const studentId = Number(deletingId);

            deleting = true;

            clearError(deleteError);

            deleteConfirm.disabled = true;
            deleteConfirm.textContent = 'Menghapus...';

            deleteConfirm.setAttribute(
                'aria-busy',
                'true'
            );

            try {
                console.info(
                    '[Daftar Siswa] Mengirim permintaan hapus:',
                    studentId
                );

                const result = await api(
                    `/guru/students/${studentId}`,
                    'DELETE'
                );

                console.info(
                    '[Daftar Siswa] Respons penghapusan:',
                    result
                );

                // Mengizinkan modal ditutup kembali.
                deleting = false;

                closeModals();

                notify(
                    result.message ||
                    'Akun siswa berhasil dihapus.'
                );

                // Muat ulang halaman pertama agar
                // jumlah siswa dan pagination diperbarui.
                pageNumber = 1;

                await loadStudents();

            } catch (error) {
                console.error(
                    '[Daftar Siswa] Gagal menghapus siswa:',
                    error
                );

                showError(
                    deleteError,
                    error.message ||
                    'Akun siswa gagal dihapus.'
                );

                // Modal tetap terbuka agar pesan error terbaca.
            } finally {
                deleting = false;

                deleteConfirm.disabled = false;
                deleteConfirm.textContent = 'Ya, Hapus';

                deleteConfirm.removeAttribute('aria-busy');
            }
        });
    }

    /* =====================================================
       TUTUP MODAL
       ===================================================== */

    page.querySelectorAll('[data-gstu-close]').forEach(button => {
        button.addEventListener('click', closeModals);
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            closeModals();
        }
    });

    /* =====================================================
       INISIALISASI
       ===================================================== */

    loadStudents();
});