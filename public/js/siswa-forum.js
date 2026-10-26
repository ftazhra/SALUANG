/* =====================================================
   SALUANG — JAVASCRIPT FORUM DISKUSI SISWA
   Terhubung ke Laravel dan database MySQL
   ===================================================== */
document.addEventListener('DOMContentLoaded', function () {
    'use strict';
    /* 01. KONFIGURASI */
    const API = '/forum/discussions';
    const csrfToken = document.querySelector(
        'meta[name="csrf-token"]'
    )?.content || '';
    const $ = (id) => document.getElementById(id);
    const screens = {
        list: $('forumListScreen'),
        create: $('forumCreateScreen'),
        detail: $('forumDetailScreen'),
    };
    if (!screens.list || !screens.create || !screens.detail) {
        return;
    }
    $('forumChapterFilter').value = '';
    $('forumSubchapterFilter').value = '';
    let status = 'all';
    let currentPage = 1;
    let selectedDiscussionId = null;
    let editingDiscussionId = null;
    let deletingDiscussionId = null;
    let listRequestNumber = 0;
    // Mengikuti sumber data yang sama dengan sidebar Materi Siswa.
    // Nilai forum tetap menggunakan kode lama: bab-N dan N.M.
    let forumChapters = [];
    const chapterLabels = new Map();
    const subchapterLabels = new Map();
    function chapterCode(chapter) {
        return 'bab-' + String(chapter.nomor_bab);
    }
    function subchapterCode(chapter, subchapter) {
        return String(chapter.nomor_bab) + '.' + String(subchapter.nomor_subbab);
    }
    function chapterLabel(code) {
        return chapterLabels.get(String(code)) || String(code || 'Bab');
    }
    function subchapterLabel(code) {
        return subchapterLabels.get(String(code)) || String(code || 'Subbab');
    }
    function fillChapterSelect(id, placeholder, chosen = '') {
        const select = $(id);
        if (!select) return;
        select.replaceChildren(new Option(placeholder, ''));
        forumChapters.forEach(chapter => {
            const code = chapterCode(chapter);
            select.add(new Option(chapterLabels.get(code), code));
        });
        select.value = chosen;
    }
    function fillSubchapterSelect(id, selectedChapter = '', chosen = '') {
        const select = $(id);
        if (!select) return;
        const filter = id === 'forumSubchapterFilter';
        select.replaceChildren(new Option(filter ? 'Semua Subbab' : 'Pilih Subbab', ''));
        forumChapters
            .filter(chapter => !selectedChapter || chapterCode(chapter) === selectedChapter)
            .forEach(chapter => {
                (chapter.subchapters || []).forEach(subchapter => {
                    const code = subchapterCode(chapter, subchapter);
                    select.add(new Option(subchapterLabel(code), code));
                });
            });
        select.value = chosen;
    }
    async function loadForumMaterials() {
        const filters = ['forumChapterFilter', 'forumSubchapterFilter',
            'forumCreateChapter', 'forumCreateSubchapter'];
        filters.forEach(id => { if ($(id)) $(id).disabled = true; });
        try {
            const result = await request('/siswa/materials');
            if (!Array.isArray(result.chapters)) {
                throw new Error('Daftar Bab dari Guru tidak tersedia.');
            }
            forumChapters = result.chapters.slice().sort(
                (a, b) => Number(a.nomor_bab) - Number(b.nomor_bab)
            );
            chapterLabels.clear();
            subchapterLabels.clear();
            forumChapters.forEach(chapter => {
                chapterLabels.set(chapterCode(chapter),
                    `BAB ${chapter.nomor_bab} – ${chapter.judul}`);
                (chapter.subchapters || []).forEach(subchapter => {
                    const code = subchapterCode(chapter, subchapter);
                    subchapterLabels.set(code, `${code} ${subchapter.judul}`);
                });
            });
            fillChapterSelect('forumChapterFilter', 'Semua Bab');
            fillSubchapterSelect('forumSubchapterFilter');
            fillChapterSelect('forumCreateChapter', 'Pilih Bab');
            fillSubchapterSelect('forumCreateSubchapter', '');
        } catch (error) {
            console.error('Gagal memuat Bab/Subbab forum:', error);
            showMessage('forumListMessage', error.message, true);
        } finally {
            filters.forEach(id => { if ($(id)) $(id).disabled = false; });
        }
    }
    /* 02. FUNGSI BANTUAN */
    function showScreen(name) {
        Object.keys(screens).forEach(function (key) {
            screens[key].hidden = key !== name;
        });
        $('forumCreateButton').hidden = name !== 'list';
    }
    function showMessage(id, message, isError = false) {
        const element = $(id);
        element.textContent = message;
        element.classList.toggle('is-error', isError);
    }
    function makeElement(tag, className, text) {
        const element = document.createElement(tag);
        if (className) {
            element.className = className;
        }
        if (text !== undefined && text !== null) {
            element.textContent = String(text);
        }
        return element;
    }
    function formatDate(value) {
        if (!value) return '';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            return '';
        }
        return date.toLocaleString('id-ID', {
            dateStyle: 'medium',
            timeStyle: 'short',
        });
    }
    async function request(url, options = {}) {
        const response = await fetch(url, {
            credentials: 'same-origin',
            ...options,
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                ...options.headers,
            },
        });
        const contentType = response.headers.get(
            'content-type'
        ) || '';
        const result = contentType.includes('application/json')
            ? await response.json()
            : {};
        if (!response.ok) {
            let message = result.message ||
                'Terjadi kesalahan saat menghubungi server.';
            if (response.status === 401) {
                message = 'Silakan login terlebih dahulu untuk menggunakan Forum Diskusi.';
            } else if (response.status === 403) {
                message = 'Akses Forum Diskusi hanya tersedia untuk akun siswa.';
            } else if (response.status === 419) {
                message = 'Sesi telah kedaluwarsa. Muat ulang halaman lalu coba lagi.';
            } else if (
                response.status === 422 &&
                result.errors
            ) {
                const firstError = Object.values(
                    result.errors
                )[0];
                if (Array.isArray(firstError)) {
                    message = firstError[0];
                }
            }
            throw new Error(message);
        }
        return result;
    }
    // Avatar penulis menggunakan foto akun yang sama di Forum Guru dan Siswa.
    // Bila tidak ada foto atau gambar gagal dimuat, tampilkan inisial nama.
    function makeForumAvatar(user) {
        const name = String(user?.name || 'Pengguna SALUANG').trim();
        const avatar = makeElement('span', 'sd-forum-comment-avatar');
        const initial = Array.from(name)[0]?.toUpperCase() || 'P';
        const fallback = makeElement('span', 'sd-forum-comment-initial', initial);
        avatar.appendChild(fallback);
        const url = String(user?.forum_avatar_url || user?.profile_photo_url || '').trim();
        if (url) {
            const img = document.createElement('img');
            img.alt = '';
            img.decoding = 'async';
            img.hidden = true;
            img.addEventListener('load', () => {
                if (img.naturalWidth > 0) {
                    fallback.hidden = true;
                    img.hidden = false;
                }
            });
            img.addEventListener('error', () => {
                img.remove();
                fallback.hidden = false;
            });
            // Penting: gambar HARUS masuk DOM sebelum pemuatan dimulai.
            // Jangan pakai loading=lazy pada gambar yang baru dimasukkan
            // setelah event load; itu bisa membuat event tidak pernah terjadi.
            avatar.appendChild(img);
            img.src = url;
        }
        return avatar;
    }
    function makeForumAuthorLine(user, date) {
        const row = makeElement('div', 'sd-forum-comment-author-line');
        row.appendChild(makeForumAvatar(user));
        const info = makeElement('div', 'sd-forum-comment-author-info');
        info.appendChild(makeElement('strong', 'sd-forum-reply-author', user?.name || 'Pengguna SALUANG'));
        info.appendChild(makeElement('span', 'sd-forum-card-meta', formatDate(date)));
        row.appendChild(info);
        return row;
    }
    function createDiscussionCard(discussion) {
        const card = makeElement(
            'article',
            'sd-forum-discussion-card'
        );
        const topic = makeElement(
            'div',
            'sd-forum-topic',
            chapterLabel(discussion.chapter)
        );
        const meta = makeElement(
            'p',
            'sd-forum-card-meta',
            'Subbab ' + discussion.subchapter +
            ' ' + (subchapterLabels.get(String(discussion.subchapter)) || '') +
            ' · ' + (discussion.user?.name || 'Siswa') +
            ' · ' + formatDate(discussion.created_at)
        );
        const title = makeElement(
            'h3',
            'sd-forum-discussion-title',
            discussion.title
        );
        const body = makeElement(
            'p',
            'sd-forum-discussion-excerpt',
            discussion.body
        );
        const footer = makeElement(
            'div',
            'sd-forum-card-footer'
        );
        const total = Number(discussion.replies_count) || 0;
        const count = makeElement(
            'span',
            'sd-forum-reply-count',
            total + ' tanggapan · ' +
            (total > 0 ? 'Sudah Terjawab' : 'Belum Terjawab')
        );
        const button = makeElement(
            'button',
            'sd-forum-outline',
            'Lihat Diskusi'
        );
        button.type = 'button';
        button.addEventListener('click', function () {
            loadDetail(discussion.id);
        });
        const actions = makeElement(
            'div',
            'sd-forum-card-actions'
        );
        actions.appendChild(button);
        // Edit dan Hapus hanya terlihat pada diskusi milik sendiri.
        if (discussion.is_owner === true) {
            const editButton = makeElement(
                'button',
                'sd-forum-outline sd-forum-edit',
                'Edit'
            );
            editButton.type = 'button';
            editButton.addEventListener('click', function () {
                openEditDiscussion(discussion);
            });
            const deleteButton = makeElement(
                'button',
                'sd-forum-outline sd-forum-delete',
                'Hapus'
            );
            deleteButton.type = 'button';
            deleteButton.addEventListener('click', function () {
                deleteDiscussion(discussion);
            });
            actions.append(editButton, deleteButton);
        }
        footer.append(count, actions);
        card.append(topic, meta, title, body, footer);
        return card;
    }
    /* =====================================================
       02.1. EDIT DAN HAPUS DISKUSI
       ===================================================== */
    function openEditDiscussion(discussion) {
        editingDiscussionId = discussion.id;
        selectedDiscussionId = null;
        $('forumCreateForm').reset();
        $('forumCreateChapter').value = discussion.chapter;
        fillSubchapterSelect('forumCreateSubchapter', discussion.chapter, discussion.subchapter);
        $('forumCreateTitle').value = discussion.title;
        $('forumCreateBody').value = discussion.body;
        screens.create.querySelector(
            '.sd-forum-screen-header h2'
        ).textContent = 'Edit Diskusi';
        $('forumCreateSubmit').textContent = 'Simpan Perubahan';
        showMessage('forumCreateMessage', '');
        showScreen('create');
    }
    /* =====================================================
       02.2. MODAL KONFIRMASI HAPUS DISKUSI
       ===================================================== */
    let pendingDeleteDiscussion = null;
    const deleteModal = $('forumDeleteModal');
    const deleteDialog = deleteModal.querySelector(
        '.sd-forum-confirm-dialog'
    );
    const deleteAccept = $('forumDeleteAccept');
    const deleteError = $('forumDeleteError');
    let previousFocus = null;
    function openDeleteModal(discussion) {
        if (deletingDiscussionId !== null) {
            return;
        }
        pendingDeleteDiscussion = discussion;
        previousFocus = document.activeElement;
        $('forumDeleteDiscussionTitle').textContent =
            discussion.title;
        deleteError.textContent = '';
        deleteError.hidden = true;
        deleteAccept.disabled = false;
        deleteAccept.textContent = 'Ya, Hapus';
        deleteModal.hidden = false;
        deleteDialog.focus();
    }
    function closeDeleteModal() {
        if (deletingDiscussionId !== null) {
            return;
        }
        deleteModal.hidden = true;
        pendingDeleteDiscussion = null;
        if (
            previousFocus &&
            previousFocus.isConnected
        ) {
            previousFocus.focus();
        }
    }
    function deleteDiscussion(discussion) {
        openDeleteModal(discussion);
    }
    // Tombol Batal dan tutup
    $('forumDeleteCancel').addEventListener(
        'click',
        closeDeleteModal
    );
    $('forumDeleteClose').addEventListener(
        'click',
        closeDeleteModal
    );
    // Klik area gelap di luar kotak modal
    $('forumDeleteBackdrop').addEventListener(
        'click',
        closeDeleteModal
    );
    // Tombol Escape
    deleteModal.addEventListener(
        'keydown',
        function (event) {
            if (event.key === 'Escape') {
                event.preventDefault();
                closeDeleteModal();
            }
            // Menjaga fokus di dalam modal.
            if (event.key === 'Tab') {
                const controls = [
                    $('forumDeleteClose'),
                    $('forumDeleteCancel'),
                    deleteAccept,
                ].filter(function (button) {
                    return !button.disabled;
                });
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
                }
            }
        }
    );
    // Hapus dari MySQL hanya setelah menekan Ya, Hapus
    deleteAccept.addEventListener(
        'click',
        async function () {
            if (
                !pendingDeleteDiscussion ||
                deletingDiscussionId !== null
            ) {
                return;
            }
            const discussion = pendingDeleteDiscussion;
            deletingDiscussionId = discussion.id;
            deleteAccept.disabled = true;
            $('forumDeleteCancel').disabled = true;
            $('forumDeleteClose').disabled = true;
            deleteAccept.textContent = 'Menghapus...';
            deleteError.hidden = true;
            try {
                await request(
                    API + '/' + discussion.id,
                    {
                        method: 'DELETE',
                    }
                );
                deleteModal.hidden = true;
                pendingDeleteDiscussion = null;
                // Data terbaru diambil lagi dari MySQL.
                await loadDiscussions(currentPage);
            } catch (error) {
                deleteError.textContent = error.message;
                deleteError.hidden = false;
            } finally {
                deletingDiscussionId = null;
                deleteAccept.disabled = false;
                $('forumDeleteCancel').disabled = false;
                $('forumDeleteClose').disabled = false;
                deleteAccept.textContent = 'Ya, Hapus';
            }
        }
    );
    /* 03. MENGAMBIL DAFTAR DARI DATABASE */
    async function loadDiscussions(page = 1) {
        const requestNumber = ++listRequestNumber;
        currentPage = page;
        showScreen('list');
        showMessage(
            'forumListMessage',
            'Memuat diskusi...'
        );
        const list = $('forumDiscussionList');
        const pagination = $('forumPagination');
        list.replaceChildren();
        pagination.replaceChildren();
        const params = new URLSearchParams();
        params.set('page', String(page));
        params.set('status', status);
        const chapter = $('forumChapterFilter').value;
        const subchapter = $('forumSubchapterFilter').value;
        const search = $('forumSearchInput').value.trim();
        if (chapter) params.set('chapter', chapter);
        if (subchapter) params.set('subchapter', subchapter);
        if (search) params.set('search', search);
        try {
            const result = await request(
                API + '?' + params.toString()
            );
            if (requestNumber !== listRequestNumber) {
                return;
            }
            const discussions = result.data || [];
            if (!discussions.length) {
                showMessage(
                    'forumListMessage',
                    'Belum ada diskusi yang sesuai. Kamu bisa membuat pertanyaan baru.'
                );
            } else {
                showMessage('forumListMessage', '');
                discussions.forEach(function (discussion) {
                    list.appendChild(
                        createDiscussionCard(discussion)
                    );
                });
            }
            const lastPage = Number(result.last_page) || 1;
            if (lastPage > 1) {
                const previous = makeElement(
                    'button',
                    'sd-forum-outline',
                    '‹ Sebelumnya'
                );
                previous.type = 'button';
                previous.disabled = page <= 1;
                previous.addEventListener('click', function () {
                    loadDiscussions(page - 1);
                });
                const info = makeElement(
                    'span',
                    'sd-forum-page-info',
                    'Halaman ' + page + ' dari ' + lastPage
                );
                const next = makeElement(
                    'button',
                    'sd-forum-outline',
                    'Berikutnya ›'
                );
                next.type = 'button';
                next.disabled = page >= lastPage;
                next.addEventListener('click', function () {
                    loadDiscussions(page + 1);
                });
                pagination.append(previous, info, next);
            }
        } catch (error) {
            if (requestNumber !== listRequestNumber) {
                return;
            }
            showMessage(
                'forumListMessage',
                error.message,
                true
            );
        }
    }
    /* =====================================================
       04. MEMBUAT ATAU MENGEDIT DISKUSI
       ===================================================== */
    $('forumCreateButton').addEventListener(
        'click',
        function () {
            editingDiscussionId = null;
            $('forumCreateForm').reset();
            fillSubchapterSelect('forumCreateSubchapter', '');
            screens.create.querySelector(
                '.sd-forum-screen-header h2'
            ).textContent = 'Buat Diskusi Baru';
            $('forumCreateSubmit').textContent = 'Kirim Diskusi';
            showMessage('forumCreateMessage', '');
            showScreen('create');
        }
    );
    $('forumCreateForm').addEventListener(
        'submit',
        async function (event) {
            event.preventDefault();
            const button = $('forumCreateSubmit');
            if (button.disabled) return;
            button.disabled = true;
            const isEditing = editingDiscussionId !== null;
            showMessage(
                'forumCreateMessage',
                isEditing
                    ? 'Menyimpan perubahan...'
                    : 'Menyimpan diskusi...'
            );
            try {
                const data = {
                    chapter: $('forumCreateChapter').value,
                    subchapter: $('forumCreateSubchapter').value,
                    title: $('forumCreateTitle').value.trim(),
                    body: $('forumCreateBody').value.trim(),
                };
                const url = isEditing
                    ? API + '/' + editingDiscussionId
                    : API;
                await request(url, {
                    method: isEditing ? 'PATCH' : 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data),
                });
                editingDiscussionId = null;
                $('forumCreateForm').reset();
                status = 'all';
                updateStatusButtons();
                await loadDiscussions(1);
            } catch (error) {
                showMessage(
                    'forumCreateMessage',
                    error.message,
                    true
                );
            } finally {
                button.disabled = false;
            }
        }
    );
    /* 05. MELIHAT DETAIL DISKUSI */
    async function loadDetail(id) {
        selectedDiscussionId = id;
        showScreen('detail');
        showMessage('forumReplyMessage', '');
        $('forumDetailContent').replaceChildren();
        $('forumRepliesList').replaceChildren();
        try {
            const result = await request(API + '/' + id);
            if (selectedDiscussionId !== id) {
                return;
            }
            const discussion = result.discussion;
            const content = $('forumDetailContent');
            content.append(
                makeForumAuthorLine(discussion.user, discussion.created_at),
                makeElement(
                    'p',
                    'sd-forum-card-meta sd-forum-comment-topic',
                    chapterLabel(discussion.chapter) + ' · Subbab ' +
                    subchapterLabel(discussion.subchapter)
                ),
                makeElement(
                    'h2',
                    'sd-forum-detail-title',
                    discussion.title
                ),
                makeElement(
                    'p',
                    'sd-forum-detail-body',
                    discussion.body
                )
            );
            const replies = discussion.replies || [];
            if (!replies.length) {
                $('forumRepliesList').appendChild(
                    makeElement(
                        'p',
                        'sd-forum-message',
                        'Belum ada tanggapan. Jadilah yang pertama membalas.'
                    )
                );
            } else {
                replies.forEach(function (reply) {
                    appendReply(reply);
                });
            }
        } catch (error) {
            $('forumDetailContent').appendChild(
                makeElement(
                    'p',
                    'sd-forum-message is-error',
                    error.message
                )
            );
        }
    }
    function appendReply(reply) {
        const card = makeElement(
            'article',
            'sd-forum-reply-card'
        );
        const layout = makeElement('div', 'sd-forum-reply-layout');
        layout.appendChild(makeForumAvatar(reply.user));
        const body = makeElement('div', 'sd-forum-reply-content');
        body.appendChild(makeElement('strong', 'sd-forum-reply-author', reply.user?.name || 'Pengguna SALUANG'));
        body.appendChild(makeElement('span', 'sd-forum-card-meta', formatDate(reply.created_at)));
        body.appendChild(makeElement('p', 'sd-forum-reply-body', reply.body));
        layout.appendChild(body);
        card.appendChild(layout);
        $('forumRepliesList').appendChild(card);
    }
    /* 06. MENGIRIM TANGGAPAN */
    $('forumReplyForm').addEventListener(
        'submit',
        async function (event) {
            event.preventDefault();
            if (!selectedDiscussionId) return;
            const button = $('forumReplySubmit');
            const id = selectedDiscussionId;
            button.disabled = true;
            showMessage(
                'forumReplyMessage',
                'Mengirim tanggapan...'
            );
            try {
                const result = await request(
                    API + '/' + id + '/replies',
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            body: $('forumReplyBody').value.trim(),
                        }),
                    }
                );
                $('forumReplyForm').reset();
                if (selectedDiscussionId === id) {
                    await loadDetail(id);
                    showMessage(
                        'forumReplyMessage',
                        'Tanggapan berhasil dikirim.'
                    );
                }
            } catch (error) {
                showMessage(
                    'forumReplyMessage',
                    error.message,
                    true
                );
            } finally {
                button.disabled = false;
            }
        }
    );
    /* 07. FILTER DAN PENCARIAN */
    function updateStatusButtons() {
        document.querySelectorAll(
            '[data-forum-status]'
        ).forEach(function (button) {
            button.classList.toggle(
                'active',
                button.dataset.forumStatus === status
            );
        });
    }
    document.querySelectorAll(
        '[data-forum-status]'
    ).forEach(function (button) {
        button.addEventListener('click', function () {
            status = button.dataset.forumStatus;
            updateStatusButtons();
            loadDiscussions(1);
        });
    });
    $('forumChapterFilter').addEventListener(
        'change',
        function () {
            fillSubchapterSelect('forumSubchapterFilter', this.value);
            loadDiscussions(1);
        }
    );
    $('forumCreateChapter').addEventListener('change', function () {
        fillSubchapterSelect('forumCreateSubchapter', this.value);
    });
    $('forumSubchapterFilter').addEventListener(
        'change',
        function () {
            loadDiscussions(1);
        }
    );
    let searchTimeout;
    $('forumSearchInput').addEventListener(
        'input',
        function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function () {
                loadDiscussions(1);
            }, 350);
        }
    );
    /* 08. KEMBALI KE DAFTAR */
    document.querySelectorAll(
        '[data-forum-back]'
    ).forEach(function (button) {
        button.addEventListener('click', function () {
            selectedDiscussionId = null;
            loadDiscussions(currentPage);
        });
    });
    /* 09. DIPANGGIL DARI siswa.js */
    let materialsReady = loadForumMaterials();
    window.saluangForumLoad = async function () {
        selectedDiscussionId = null;
        await materialsReady;
        loadDiscussions(1);
    };
});