
/* =====================================================
   SALUANG — FORUM DISKUSI GURU
   File: public/js/guru-forum.js
   ===================================================== */

document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    const root = document.getElementById('guruForumPage');
    if (!root) return;

    const $ = id => document.getElementById(id);

    const csrf = document.querySelector(
        'meta[name="csrf-token"]'
    )?.content || '';

    const API = '/guru/discussions';

    const list = $('gfDiscussionList');
    const pagination = $('gfPagination');

    const discussionModal = $('gfDiscussionModal');
    const detailModal = $('gfDetailModal');
    const deleteModal = $('gfDeleteModal');

    const discussionForm = $('gfDiscussionForm');
    const replyForm = $('gfReplyForm');

    const toast = $('gfToast');

    let discussions = [];
    let currentPage = 1;
    let lastPage = 1;
    let currentFilter = 'all';

    let selectedDiscussion = null;
    let pendingDeleteId = null;

    let searchTimer = null;
    let toastTimer = null;
    let listRequestVersion = 0;

    /* =========================================
       01. UTILITAS
       ========================================= */

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

    function formatDate(value) {
        if (!value) return '-';

        const date = new Date(value);

        if (Number.isNaN(date.getTime())) return '-';

        return date.toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });
    }

    function showToast(message, isError = false) {
        clearTimeout(toastTimer);

        toast.textContent = message;
        toast.classList.toggle('is-error', isError);
        toast.hidden = false;

        toastTimer = setTimeout(() => {
            toast.hidden = true;
        }, 3500);
    }

    function closeModals() {
        [discussionModal, detailModal, deleteModal]
            .forEach(modal => {
                if (modal) modal.hidden = true;
            });
    }

    function openModal(modal) {
        closeModals();
        modal.hidden = false;
    }

    function discussionUrl(id) {
        return `${API}/${encodeURIComponent(id)}`;
    }

    async function requestApi(url, method = 'GET', data = null) {
        const options = {
            method,
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf
            }
        };

        if (data !== null) {
            options.headers['Content-Type'] =
                'application/json';

            options.body = JSON.stringify(data);
        }

        const response = await fetch(url, options);

        let result;

        try {
            result = await response.json();
        } catch (_) {
            throw new Error(
                'Respons Laravel tidak valid. Periksa route atau login.'
            );
        }

        if (!response.ok) {
            const firstError = result.errors
                ? Object.values(result.errors).flat()[0]
                : null;

            throw new Error(
                firstError ||
                result.message ||
                'Permintaan gagal.'
            );
        }

        return result;
    }

    function emptyMessage(message) {
        list.innerHTML = `
            <div class="gf-empty">
                <i class="bi bi-chat-square-dots"></i>
                <p>${escapeHtml(message)}</p>
            </div>
        `;
    }

    /* =========================================
       02. DAFTAR DISKUSI
       ========================================= */

    async function loadDiscussions(page = 1) {
        const version = ++listRequestVersion;

        emptyMessage('Memuat daftar diskusi...');

        const params = new URLSearchParams({
            page: String(page),
            status: currentFilter
        });

        const chapter = $('gfChapterFilter').value;
        const subchapter = $('gfSubchapterFilter').value;
        const search = $('gfSearch').value.trim();

        if (chapter) params.set('chapter', chapter);
        if (subchapter) params.set('subchapter', subchapter);
        if (search) params.set('search', search);

        try {
            const result = await requestApi(
                `${API}?${params.toString()}`
            );

            if (version !== listRequestVersion) return;

            discussions = Array.isArray(result.data)
                ? result.data
                : [];

            currentPage = Number(result.current_page) || 1;
            lastPage = Number(result.last_page) || 1;

            renderDiscussions();
            renderPagination();

        } catch (error) {
            if (version !== listRequestVersion) return;

            pagination.hidden = true;
            emptyMessage(error.message);
            showToast(error.message, true);
        }
    }

    function renderDiscussions() {
        if (!discussions.length) {
            emptyMessage(
                'Belum ada diskusi yang sesuai dengan filter.'
            );
            return;
        }

        list.innerHTML = discussions.map(discussion => {
            const count = Number(
                discussion.replies_count || 0
            );

            const answered = count > 0;

            return `
                <article class="gf-discussion-card">

                    <div class="gf-card-header">
                        <div>
                            <h3>${escapeHtml(discussion.title)}</h3>

                            <p class="gf-card-meta">
                                ${escapeHtml(
                                    discussion.user?.name ||
                                    'Pengguna SALUANG'
                                )}
                                · ${formatDate(discussion.created_at)}
                                · ${escapeHtml(discussion.subchapter)}
                            </p>
                        </div>

                        <span class="gf-badge ${
                            answered ? 'is-answered' : ''
                        }">
                            ${
                                answered
                                    ? 'Sudah Terjawab'
                                    : 'Belum Terjawab'
                            }
                        </span>
                    </div>

                    <p class="gf-card-excerpt">
                        ${escapeHtml(discussion.body)}
                    </p>

                    <p class="gf-card-meta">
                        <i class="bi bi-chat-dots"></i>
                        ${count} tanggapan
                    </p>

                    <div class="gf-card-actions">
                        <button type="button"
                            class="gf-btn gf-btn-primary"
                            data-gf-action="view"
                            data-id="${discussion.id}">
                            <i class="bi bi-eye"></i>
                            Lihat Diskusi
                        </button>

                        ${discussion.is_owner ? `
                            <button type="button"
                                class="gf-btn gf-btn-outline"
                                data-gf-action="edit"
                                data-id="${discussion.id}">
                                <i class="bi bi-pencil-square"></i>
                                Edit
                            </button>

                            <button type="button"
                                class="gf-btn gf-btn-danger"
                                data-gf-action="delete"
                                data-id="${discussion.id}">
                                <i class="bi bi-trash3"></i>
                                Hapus
                            </button>
                        ` : ''}
                    </div>

                </article>
            `;
        }).join('');
    }

    /* =========================================
       03. PAGINASI
       ========================================= */

    function renderPagination() {
        pagination.hidden = lastPage <= 1;

        $('gfPageInfo').textContent =
            `Halaman ${currentPage} dari ${lastPage}`;

        $('gfPrevPage').disabled = currentPage <= 1;
        $('gfNextPage').disabled = currentPage >= lastPage;
    }

    $('gfPrevPage').addEventListener('click', () => {
        if (currentPage > 1) {
            loadDiscussions(currentPage - 1);
        }
    });

    $('gfNextPage').addEventListener('click', () => {
        if (currentPage < lastPage) {
            loadDiscussions(currentPage + 1);
        }
    });

    /* =========================================
       04. FILTER
       ========================================= */

    $('gfChapterFilter').addEventListener('change', () => {
        if ($('gfChapterFilter').value === '') {
            $('gfSubchapterFilter').value = '';
        }

        loadDiscussions(1);
    });

    $('gfSubchapterFilter').addEventListener(
        'change',
        () => loadDiscussions(1)
    );

    $('gfSearch').addEventListener('input', () => {
        clearTimeout(searchTimer);

        searchTimer = setTimeout(() => {
            loadDiscussions(1);
        }, 350);
    });

    root.querySelectorAll('[data-gf-filter]')
        .forEach(button => {
            button.addEventListener('click', () => {
                currentFilter = button.dataset.gfFilter;

                root.querySelectorAll('[data-gf-filter]')
                    .forEach(item => {
                        item.classList.toggle(
                            'is-active',
                            item === button
                        );
                    });

                loadDiscussions(1);
            });
        });

    /* =========================================
       05. FORM TAMBAH / EDIT DISKUSI
       ========================================= */

    function openDiscussionForm(discussion = null) {
        discussionForm.reset();

        $('gfDiscussionId').value =
            discussion?.id || '';

        $('gfFormChapter').value =
            discussion?.chapter || '';

        $('gfFormSubchapter').value =
            discussion?.subchapter || '';

        $('gfFormTitle').value =
            discussion?.title || '';

        $('gfFormBody').value =
            discussion?.body || '';

        $('gfDiscussionModalTitle').textContent =
            discussion ? 'Edit Diskusi' : 'Tambah Diskusi';

        openModal(discussionModal);
    }

    $('gfAddDiscussion').addEventListener('click', () => {
        openDiscussionForm();
    });

    discussionForm.addEventListener('submit', async event => {
        event.preventDefault();

        const button = $('gfSaveDiscussion');

        if (button.disabled) return;

        const id = $('gfDiscussionId').value;

        const payload = {
            chapter: $('gfFormChapter').value,
            subchapter: $('gfFormSubchapter').value,
            title: $('gfFormTitle').value.trim(),
            body: $('gfFormBody').value.trim()
        };

        button.disabled = true;

        try {
            const result = await requestApi(
                id ? discussionUrl(id) : API,
                id ? 'PUT' : 'POST',
                payload
            );

            closeModals();

            showToast(
                result.message || 'Diskusi berhasil disimpan.'
            );

            await loadDiscussions(1);

        } catch (error) {
            showToast(error.message, true);

        } finally {
            button.disabled = false;
        }
    });

    /* =========================================
       06. DETAIL DAN BALAS DISKUSI
       ========================================= */

    async function openDiscussionDetail(id) {
        try {
            const result = await requestApi(
                discussionUrl(id)
            );

            selectedDiscussion = result.discussion;

            const discussion = selectedDiscussion;

            $('gfDetailTitle').textContent =
                discussion.title;

            $('gfDetailContent').innerHTML = `
                <p class="gf-detail-meta">
                    Oleh ${escapeHtml(
                        discussion.user?.name ||
                        'Pengguna SALUANG'
                    )}
                    · ${formatDate(discussion.created_at)}
                    · Subbab ${escapeHtml(discussion.subchapter)}
                </p>

                <p class="gf-detail-text">
                    ${escapeHtml(discussion.body)}
                </p>
            `;

            renderReplies(discussion.replies || []);

            $('gfReplyDiscussionId').value =
                String(discussion.id);

            $('gfReplyBody').value = '';

            openModal(detailModal);

        } catch (error) {
            showToast(error.message, true);
        }
    }

    function renderReplies(replies) {
        if (!replies.length) {
            $('gfRepliesList').innerHTML = `
                <div class="gf-empty">
                    <i class="bi bi-chat"></i>
                    <p>Belum ada tanggapan.</p>
                </div>
            `;
            return;
        }

        $('gfRepliesList').innerHTML = replies.map(reply => `
            <div class="gf-reply-card">
                <strong>
                    ${escapeHtml(
                        reply.user?.name ||
                        'Pengguna SALUANG'
                    )}
                </strong>

                <p class="gf-detail-meta">
                    ${formatDate(reply.created_at)}
                </p>

                <p>${escapeHtml(reply.body)}</p>
            </div>
        `).join('');
    }

    replyForm.addEventListener('submit', async event => {
        event.preventDefault();

        const id = $('gfReplyDiscussionId').value;
        const body = $('gfReplyBody').value.trim();

        if (!id || !body) return;

        const button = $('gfSendReply');

        if (button.disabled) return;

        button.disabled = true;

        try {
            const result = await requestApi(
                `${discussionUrl(id)}/replies`,
                'POST',
                { body }
            );

            showToast(
                result.message || 'Tanggapan berhasil dikirim.'
            );

            await openDiscussionDetail(id);
            await loadDiscussions(currentPage);

        } catch (error) {
            showToast(error.message, true);

        } finally {
            button.disabled = false;
        }
    });

    /* =========================================
       07. HAPUS DISKUSI
       ========================================= */

    function askDelete(id) {
        pendingDeleteId = id;
        openModal(deleteModal);
    }

    $('gfConfirmDelete').addEventListener(
        'click',
        async event => {
            if (!pendingDeleteId) return;

            const button = event.currentTarget;
            button.disabled = true;

            try {
                const result = await requestApi(
                    discussionUrl(pendingDeleteId),
                    'DELETE'
                );

                pendingDeleteId = null;
                closeModals();

                showToast(
                    result.message || 'Diskusi berhasil dihapus.'
                );

                await loadDiscussions(1);

            } catch (error) {
                showToast(error.message, true);

            } finally {
                button.disabled = false;
            }
        }
    );

    /* =========================================
       08. AKSI KARTU DISKUSI
       ========================================= */

    root.addEventListener('click', event => {
        const button = event.target.closest(
            '[data-gf-action]'
        );

        if (!button) return;

        const id = Number(button.dataset.id);

        const discussion = discussions.find(
            item => Number(item.id) === id
        );

        const action = button.dataset.gfAction;

        if (action === 'view') {
            openDiscussionDetail(id);
        }

        if (action === 'edit' && discussion?.is_owner) {
            openDiscussionForm(discussion);
        }

        if (action === 'delete' && discussion?.is_owner) {
            askDelete(id);
        }
    });

    /* =========================================
       09. TUTUP MODAL
       ========================================= */

    root.querySelectorAll('[data-gf-close]')
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

    /* =========================================
       10. MUAT DATA AWAL
       ========================================= */

    loadDiscussions(1);
});
