// Mengatur navigasi halaman Orang Tua.

document.addEventListener('DOMContentLoaded', function () {

    const layout = document.getElementById('parentLayout');
    const toggle = document.getElementById('parentSidebarToggle');
    const mobileToggle = document.getElementById('parentSidebarMobileToggle');
    const mobileClose = document.getElementById('parentSidebarClose');
    const backdrop = document.getElementById('parentSidebarBackdrop');
    const title = document.getElementById('parentPageTitle');
    const main = document.getElementById('parentMain');
    const content = document.querySelector('.parent-content');
    const mobileMedia = window.matchMedia('(max-width: 900px)');

    const sections = Array.from(
        document.querySelectorAll(
            '.parent-content > [data-parent-section]'
        )
    );

    const menuButtons = Array.from(
        document.querySelectorAll(
            '.parent-navigation .parent-menu[data-parent-page]'
        )
    );

    const pageOrder = [
        'beranda',
        'aktivitas',
        'hasil',
        'progres',
        'chat',
        'profil'
    ];

    const pageNames = {
        beranda: 'Dashboard Orang Tua',
        aktivitas: 'Aktivitas Anak',
        hasil: 'Hasil Belajar',
        progres: 'Progres Belajar',
        chat: 'Chat Guru',
        profil: 'Profil Orang Tua'
    };

    const notificationButton =
        document.getElementById('parentNotifButton');

    const notificationMenu =
        document.getElementById('parentNotifMenu');

    const profileButton =
        document.getElementById('parentProfileButton');

    const profileMenu =
        document.getElementById('parentProfileMenu');

    let currentPage = 'beranda';

    function setParentMobileSidebarOpen(isOpen) {
        if (!layout) return;
        layout.classList.toggle('sidebar-open', isOpen);
        backdrop?.classList.toggle('is-visible', isOpen);
        mobileToggle?.setAttribute('aria-expanded', String(isOpen));
        mobileToggle?.setAttribute('aria-label', isOpen ? 'Tutup menu' : 'Buka menu');
        document.body.classList.toggle('parent-sidebar-open', isOpen && mobileMedia.matches);
    }

    function syncParentMobileSidebar() {
        if (!layout) return;

        if (mobileMedia.matches) {
            // Drawer mobile selalu menampilkan label dan tidak memakai state sidebar desktop yang dilipat.
            layout.classList.remove('sidebar-collapsed');
            toggle?.setAttribute('aria-expanded', 'true');
            toggle?.setAttribute('aria-label', 'Perkecil sidebar');
            document.body.classList.toggle(
                'parent-sidebar-open',
                layout.classList.contains('sidebar-open')
            );
            return;
        }

        setParentMobileSidebarOpen(false);
        const collapsed = layout.classList.contains('sidebar-collapsed');
        toggle?.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        toggle?.setAttribute(
            'aria-label',
            collapsed ? 'Perbesar sidebar' : 'Perkecil sidebar'
        );
    }

    if (mobileToggle) {
        mobileToggle.addEventListener('click', function () {
            setParentMobileSidebarOpen(!layout.classList.contains('sidebar-open'));
        });
    }

    mobileClose?.addEventListener('click', function () {
        setParentMobileSidebarOpen(false);
    });
    backdrop?.addEventListener('click', function () {
        setParentMobileSidebarOpen(false);
    });

    window.addEventListener('resize', syncParentMobileSidebar);
    syncParentMobileSidebar();

    /* =====================================================
       01. DROPDOWN
       ===================================================== */

    function closeParentDropdowns() {

        if (notificationMenu) {
            notificationMenu.hidden = true;
        }

        if (profileMenu) {
            profileMenu.hidden = true;
        }

        notificationButton?.setAttribute(
            'aria-expanded',
            'false'
        );

        profileButton?.setAttribute(
            'aria-expanded',
            'false'
        );
    }

    function toggleParentDropdown(button, menu) {

        if (!button || !menu) {
            return;
        }

        button.addEventListener('click', function (event) {

            event.stopPropagation();

            const shouldOpen = menu.hidden;

            closeParentDropdowns();

            menu.hidden = !shouldOpen;

            button.setAttribute(
                'aria-expanded',
                String(shouldOpen)
            );
        });
    }

    toggleParentDropdown(
        notificationButton,
        notificationMenu
    );

    toggleParentDropdown(
        profileButton,
        profileMenu
    );

    /* =====================================================
       02. MEMBUAT TOMBOL PREVIOUS / NEXT
       ===================================================== */

    function createNavigationButton(direction, target) {

        const button = document.createElement('button');

        button.type = 'button';

        button.className =
            direction === 'next' ?
                'gd-nav-btn gd-nav-btn-next' :
                'gd-nav-btn';

        button.setAttribute(
            'data-parent-nav-target',
            target
        );

        button.setAttribute(
            'aria-label',
            direction === 'next' ?
                'Halaman berikutnya' :
                'Halaman sebelumnya'
        );

        const icon = document.createElement('i');

        icon.className =
            direction === 'next' ?
                'bi bi-chevron-right' :
                'bi bi-chevron-left';

        icon.setAttribute('aria-hidden', 'true');

        const label = document.createElement('span');

        label.textContent =
            direction === 'next' ?
                'Next' :
                'Previous';

        if (direction === 'next') {
            button.append(label, icon);
        } else {
            button.append(icon, label);
        }

        return button;
    }

    /* =====================================================
       03. NAVIGASI ATAS DAN BAWAH HALAMAN

       Beranda:
       - Bawah Next

       Aktivitas, Hasil, Progres:
       - Atas Previous dan Next
       - Bawah Previous dan Next

       Profil:
       - Atas Previous
       - Bawah Previous
       ===================================================== */

    function createBottomNavigations() {

        sections.forEach(function (section) {

            const page = section.dataset.parentSection;
            const index = pageOrder.indexOf(page);

            if (index === -1) {
                return;
            }

            /*
             * Hapus navigasi halaman lama.
             * Tidak menyentuh tombol lain dalam partial.
             */
            section.querySelectorAll(
                '.parent-page-navigation, .parent-page-nav-arrow'
            ).forEach(function (oldNavigation) {
                oldNavigation.remove();
            });

            if (page === 'chat' || page === 'hasil') {
                return;
            }

            /*
             * Satu fungsi untuk menghasilkan navigasi
             * atas maupun bawah tanpa menduplikasi
             * logika tombol.
             */
            function createNavigation(position) {

                const nav = document.createElement('nav');

                nav.className =
                    'parent-page-navigation parent-page-navigation-' + position;

                nav.setAttribute(
                    'aria-label',
                    'Navigasi ' +
                    (position === 'top' ? 'atas' : 'bawah') +
                    ' halaman'
                );

                if (index === 0) {
                    nav.classList.add(
                        'parent-page-navigation-home'
                    );
                }

                if (index === pageOrder.length - 1) {
                    nav.classList.add(
                        'parent-page-navigation-last'
                    );
                }

                /*
                 * Previous tersedia apabila
                 * bukan halaman pertama.
                 */
                if (index > 0) {

                    nav.appendChild(
                        createNavigationButton(
                            'previous',
                            pageOrder[index - 1]
                        )
                    );
                }

                /*
                 * Next tersedia apabila
                 * bukan halaman terakhir.
                 */
                if (index < pageOrder.length - 1) {

                    nav.appendChild(
                        createNavigationButton(
                            'next',
                            pageOrder[index + 1]
                        )
                    );
                }

                return nav;
            }

            /*
             * Beranda tidak mempunyai navigasi atas.
             * Halaman lainnya mempunyai navigasi atas.
             */
            if (page !== 'beranda') {

                const topNavigation =
                    createNavigation('top');

                section.prepend(topNavigation);
            }

            /*
             * Semua halaman selain Chat mempunyai navigasi bawah.
             */
            const bottomNavigation =
                createNavigation('bottom');

            section.appendChild(bottomNavigation);
        });
    }

    createBottomNavigations();

    /* =====================================================
       04. PINDAH HALAMAN
       ===================================================== */

    function showPage(page) {

        if (!Object.prototype.hasOwnProperty.call(
            pageNames,
            page
        )) {
            return false;
        }

        /*
         * sections adalah Array, bukan NodeList.
         * Oleh sebab itu .find() dapat digunakan.
         */
        const targetSection = sections.find(
            function (section) {
                return section.dataset.parentSection === page;
            }
        );

        if (!targetSection) {
            return false;
        }

        /*
         * Tampilkan hanya halaman yang dipilih.
         */
        sections.forEach(function (section) {

            const isTarget =
                section.dataset.parentSection === page;

            section.hidden = !isTarget;
        });

        currentPage = page;

        /*
         * Sidebar mengikuti halaman aktif.
         */
        menuButtons.forEach(function (button) {

            const isActive =
                button.dataset.parentPage === page;

            button.classList.toggle(
                'active',
                isActive
            );

            if (isActive) {
                button.setAttribute(
                    'aria-current',
                    'page'
                );
            } else {
                button.removeAttribute(
                    'aria-current'
                );
            }
        });

        /*
         * Judul topbar mengikuti halaman aktif.
         */
        if (title) {
            title.textContent = pageNames[page];
        }

        closeParentDropdowns();

        /*
         * Kembali ke atas setelah halaman berpindah.
         * Mendukung scroll browser dan scroll internal.
         */
        window.scrollTo(0, 0);

        document.documentElement.scrollTop = 0;
        document.body.scrollTop = 0;

        if (main) {
            main.scrollTop = 0;
        }

        if (content) {
            content.scrollTop = 0;
        }

        return true;
    }

    /* =====================================================
       05. EVENT KLIK NAVIGASI
       ===================================================== */

    document.addEventListener('click', function (event) {

        if (!(event.target instanceof Element)) {
            return;
        }

        /*
         * Tombol Previous dan Next,
         * baik di atas maupun di bawah.
         */
        const navigationButton = event.target.closest(
            '[data-parent-nav-target]'
        );

        if (navigationButton) {

            event.preventDefault();

            const target =
                navigationButton.dataset.parentNavTarget;

            showPage(target);

            return;
        }

        /*
         * Tombol sidebar dan tombol shortcut.
         */
        const pageButton = event.target.closest(
            '[data-parent-page]'
        );

        if (pageButton) {

            const target =
                pageButton.dataset.parentPage;

            if (!Object.prototype.hasOwnProperty.call(
                pageNames,
                target
            )) {
                return;
            }

            event.preventDefault();

            const changed = showPage(target);
            if (changed && mobileMedia.matches) {
                setParentMobileSidebarOpen(false);
            }

            /*
             * Dropdown Pengaturan langsung menuju
             * bagian pengaturan pada halaman Profil.
             */
            if (
                changed &&
                pageButton.hasAttribute('data-parent-settings')
            ) {

                requestAnimationFrame(function () {

                    document.getElementById(
                        'parentProfileSettings'
                    )?.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                });
            }

            return;
        }

        /*
         * Klik di luar dropdown menutup dropdown.
         */
        if (!event.target.closest('.parent-dropdown')) {
            closeParentDropdowns();
        }
    });

    /* =====================================================
       06. ACCORDION CATATAN GURU
       ===================================================== */

    document.querySelectorAll(
        '.parent-teacher-assessment-toggle'
    ).forEach(function (button) {

        button.addEventListener('click', function () {

            const panel = document.getElementById(
                button.getAttribute('aria-controls')
            );

            if (!panel) {
                return;
            }

            const expanded =
                button.getAttribute('aria-expanded') !== 'true';

            button.setAttribute(
                'aria-expanded',
                String(expanded)
            );

            panel.hidden = !expanded;

            const chevron = button.querySelector(
                '[data-parent-note-chevron]'
            );

            if (chevron) {

                chevron.className = expanded ?
                    'bi bi-chevron-up parent-teacher-note-chevron' :
                    'bi bi-chevron-down parent-teacher-note-chevron';
            }
        });
    });

    /* =====================================================
       07. DROPDOWN ESCAPE
       ===================================================== */

    document.addEventListener('keydown', function (event) {

        if (event.key === 'Escape') {
            if (layout?.classList.contains('sidebar-open')) {
                setParentMobileSidebarOpen(false);
            }
            closeParentDropdowns();
        }
    });

    /* =====================================================
       08. SIDEBAR COLLAPSE
       ===================================================== */

    if (toggle && layout) {

        toggle.addEventListener('click', function () {
            if (mobileMedia.matches) return;

            const collapsed =
                layout.classList.toggle(
                    'sidebar-collapsed'
                );

            toggle.setAttribute(
                'aria-expanded',
                collapsed ? 'false' : 'true'
            );

            toggle.setAttribute(
                'aria-label',
                collapsed ?
                    'Perbesar sidebar' :
                    'Perkecil sidebar'
            );
        });
    }

    /* =====================================================
       09. INISIALISASI
       ===================================================== */

    const requestedPage = new URLSearchParams(window.location.search).get('page');
    showPage(Object.prototype.hasOwnProperty.call(pageNames, requestedPage)
        ? requestedPage
        : 'beranda');
});

// Chat Orang Tua dijalankan dari dashboard karena aset chat terpisah diblokir hosting.
function initParentChat() {
    if (window.__saluangParentChatInitialized) return;
    const open = document.getElementById('pcChatOpen');
    if (!open) return;
    window.__saluangParentChatInitialized = true;
    const card = document.getElementById('pcChatTeacherCard');
    const name = document.getElementById('pcTeacherName');
    const statusText = document.getElementById('pcTeacherStatus');
    const statusDot = document.getElementById('pcTeacherPresenceDot');
    const schoolName = document.getElementById('pcTeacherSchool');
    const studentLabel = document.getElementById('pcTeacherStudent');
    const unreadBadge = document.getElementById('pcTeacherUnread');
    const chatPage = document.querySelector('[data-parent-section="chat"]');
    const pane = document.getElementById('pcConversation');
    const messages = document.getElementById('pcMessages');
    const errorBox = document.getElementById('pcChatError');
    const emptyState = document.getElementById('pcTeacherEmpty');
    const retryButton = document.getElementById('pcChatRetry');
    const form = document.getElementById('pcChatForm');
    const input = document.getElementById('pcMessageInput');
    const send = document.getElementById('pcSendMessage');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const api = '/orang-tua/chat';
    let teacherId = null;
    let studentId = null;
    let teacherThreads = [];
    let pendingMessage = null;
    let messageRequestVersion = 0;
    let teacherSelect = null;
    let requestedThreadOpened = false;
    function notify(text, retry = false) {
        errorBox.textContent = text;
        errorBox.hidden = !text;
        retryButton.hidden = !retry;
    }
    function displayDate(value) {
        const date = new Date(value);
        return Number.isNaN(date.getTime()) ? String(value ?? '') : new Intl.DateTimeFormat('id-ID', {
            day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit'
        }).format(date);
    }
    async function call(path, method = 'GET', body = null) {
        const opts = { method, credentials: 'same-origin', cache: 'no-store', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf } };
        if (body !== null) { opts.headers['Content-Type'] = 'application/json'; opts.body = JSON.stringify(body); }
        const controller = new AbortController();
        const timeout = window.setTimeout(() => controller.abort(), 15000);
        opts.signal = controller.signal;
        try {
            const response = await fetch(api + path, opts);
            const data = await response.json().catch(() => ({
                message: response.redirected
                    ? 'Sesi berakhir atau halaman dialihkan. Muat ulang halaman lalu login kembali.'
                    : `Server tidak mengembalikan JSON yang valid (HTTP ${response.status}).`
            }));
            if (!response.ok) {
                const statusMessage = {
                    401: 'Sesi login berakhir. Muat ulang halaman lalu login kembali.',
                    419: 'Sesi keamanan kedaluwarsa. Muat ulang halaman lalu coba lagi.',
                    403: 'Akses chat tidak diizinkan untuk akun ini.',
                    404: 'Percakapan atau relasi anak dengan Guru tidak ditemukan.',
                    503: 'Fitur chat belum siap. Migrasi chat atau penugasan kelas perlu diterapkan.',
                }[response.status];
                throw new Error(Object.values(data.errors || {})[0]?.[0] || statusMessage || data.message || `Permintaan gagal (HTTP ${response.status}).`);
            }
            return data;
        } catch (error) {
            if (error.name === 'AbortError') {
                throw new Error('Permintaan daftar Guru terlalu lama. Periksa koneksi lalu coba lagi.');
            }
            throw error;
        } finally {
            window.clearTimeout(timeout);
        }
    }
    function renderUnreadCount(count) {
        const unread = Number(count) || 0;
        unreadBadge.hidden = unread < 1;
        unreadBadge.textContent = unread ? `${unread} pesan belum dibaca` : '';
    }
    async function loadTeachers(silent = false) {
        try {
            const result = await call('/teachers');
            teacherThreads = Array.isArray(result.teachers)
                ? result.teachers.filter(thread =>
                    Number.isInteger(Number(thread?.id)) && Number(thread.id) > 0
                    && Number.isInteger(Number(thread?.student_id)) && Number(thread.student_id) > 0
                    && typeof thread?.name === 'string'
                )
                : [];
            notify('');
            emptyState.hidden = true;
            statusDot.hidden = false;
            if (!teacherThreads.length) {
                teacherId = null;
                studentId = null;
                requestedThreadOpened = false;
                open.disabled = true;
                pane.hidden = true;
                name.textContent = 'Belum ada Guru yang dapat dihubungi';
                statusText.textContent = 'Guru belum tersedia';
                statusDot.hidden = true;
                emptyState.textContent = 'Belum ada Guru IPA yang terhubung ke sekolah dan kelas anak Anda. Coba lagi nanti atau hubungi pengelola SALUANG.';
                emptyState.hidden = false;
                if (teacherSelect) {
                    teacherSelect.remove();
                    teacherSelect = null;
                }
                renderUnreadCount(0);
                return;
            }
            const currentThread = teacherThreads.find(thread =>
                Number(thread.id) === teacherId && Number(thread.student_id) === studentId
            ) || teacherThreads.find(thread =>
                Number(thread.id) === Number(new URLSearchParams(location.search).get('teacher_id'))
                && Number(thread.student_id) === Number(new URLSearchParams(location.search).get('student_id'))
            ) || teacherThreads[0];
            if (Number(currentThread.id) !== teacherId || Number(currentThread.student_id) !== studentId) {
                selectThread(currentThread);
            } else {
                name.textContent = currentThread.name;
                renderTeacherPresence(currentThread);
            }
            schoolName.textContent = currentThread.school_name || 'Sekolah belum tercantum';
            studentLabel.textContent = `Siswa: ${currentThread.student_name || 'Siswa'} · ${currentThread.class_name || '-'}`;
            open.disabled = false;
            if (teacherSelect) teacherSelect.disabled = false;
            if (teacherThreads.length > 1) {
                if (!teacherSelect) {
                    teacherSelect = document.createElement('select');
                    teacherSelect.className = 'pc-teacher-select';
                    teacherSelect.setAttribute('aria-label', 'Pilih Guru dan Siswa');
                    teacherSelect.addEventListener('change', () => {
                        const thread = teacherThreads.find(item => `${item.student_id}:${item.id}` === teacherSelect.value);
                        if (!thread) return;
                        selectThread(thread);
                        pane.hidden = true;
                        notify('');
                    });
                    card.querySelector('div').append(teacherSelect);
                }
                teacherSelect.replaceChildren(...teacherThreads.map(thread => {
                    const multipleStudentsForTeacher = teacherThreads.some(other =>
                        Number(other.id) === Number(thread.id)
                        && Number(other.student_id) !== Number(thread.student_id)
                    );
                    const label = multipleStudentsForTeacher
                        ? `${thread.name} · ${thread.student_name}`
                        : thread.name;
                    return new Option(label, `${thread.student_id}:${thread.id}`);
                }));
                teacherSelect.value = `${currentThread.student_id}:${currentThread.id}`;
            } else if (teacherSelect) {
                teacherSelect.remove();
                teacherSelect = null;
            }
            renderUnreadCount(currentThread.unread_count);
            if (!requestedThreadOpened
                && Number(currentThread.id) === Number(new URLSearchParams(location.search).get('teacher_id'))
                && Number(currentThread.student_id) === Number(new URLSearchParams(location.search).get('student_id'))) {
                requestedThreadOpened = true;
                pane.hidden = false;
                loadMessages().catch(error => notify(error.message));
            }
        } catch (e) {
            if (!teacherThreads.length) {
                teacherId = null;
                studentId = null;
                open.disabled = true;
                name.textContent = 'Daftar Guru tidak dapat dimuat';
                statusText.textContent = 'Status belum tersedia';
                statusDot.hidden = true;
                emptyState.textContent = 'Daftar Guru belum berhasil dimuat. Periksa koneksi Anda, lalu coba lagi.';
                emptyState.hidden = false;
            }
            if (!silent || !teacherThreads.length) notify(e.message, true);
        }
    }
    retryButton.addEventListener('click', () => {
        retryButton.disabled = true;
        loadTeachers().finally(() => { retryButton.disabled = false; });
    });
    function formatPresenceLabel(value) {
        if (!value) return 'Offline';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return 'Offline';
        const minutesAgo = Math.max(0, Math.round((Date.now() - date.getTime()) / 60000));
        if (minutesAgo < 1) return 'Terakhir aktif kurang dari 1 menit lalu';
        if (minutesAgo < 60) return `Terakhir aktif ${minutesAgo} menit lalu`;
        const hoursAgo = Math.round(minutesAgo / 60);
        if (hoursAgo < 24) return `Terakhir aktif ${hoursAgo} jam lalu`;
        return `Terakhir aktif ${Math.round(hoursAgo / 24)} hari lalu`;
    }
    function renderTeacherPresence(thread) {
        const isOnline = thread?.is_online === true;
        const label = isOnline ? 'Online' : formatPresenceLabel(thread?.last_seen_at);
        if (statusDot) {
            statusDot.classList.toggle('is-online', isOnline);
            statusDot.title = label;
        }
        if (statusText) statusText.textContent = label;
    }
    function selectThread(thread) {
        teacherId = Number(thread.id);
        studentId = Number(thread.student_id);
        name.textContent = thread.name;
        schoolName.textContent = thread.school_name || 'Sekolah belum tercantum';
        studentLabel.textContent = `Siswa: ${thread.student_name || 'Siswa'} · ${thread.class_name || '-'}`;
        renderTeacherPresence(thread);
        renderUnreadCount(thread.unread_count);
        messageRequestVersion += 1;
    }
    function refreshTeacherThreads() {
        if (document.visibilityState === 'visible') loadTeachers(true);
    }
    function newMessageId() {
        if (window.crypto?.randomUUID) return window.crypto.randomUUID();
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, character => {
            const random = Math.floor(Math.random() * 16);
            return (character === 'x' ? random : (random & 3) | 8).toString(16);
        });
    }
    async function loadMessages() {
        if (!teacherId || !studentId) return;
        const requestVersion = ++messageRequestVersion;
        const selectedStudentId = studentId;
        const selectedTeacherId = teacherId;
        const params = new URLSearchParams({ student_id: String(studentId), teacher_id: String(teacherId) });
        const result = await call('/messages?' + params.toString());
        if (requestVersion !== messageRequestVersion || selectedStudentId !== studentId || selectedTeacherId !== teacherId) return;
        renderUnreadCount(0);
        messages.replaceChildren();
        if (!result.messages?.length) { const empty = document.createElement('p'); empty.className = 'pc-empty'; empty.textContent = 'Belum ada pesan. Mulai percakapan dengan Guru.'; messages.append(empty); return; }
        result.messages.forEach(m => {
            const node = document.createElement('div');
            node.className = 'pc-message' + (m.is_mine ? ' mine' : '');
            node.textContent = m.body;
            const meta = document.createElement('small'); meta.className = 'pc-message-meta'; meta.textContent = m.sender_name + ' · ' + displayDate(m.created_at);
            node.append(meta); messages.append(node);
        });
        messages.scrollTop = messages.scrollHeight;
    }
    open.addEventListener('click', async () => { notify(''); pane.hidden = false; try { await loadMessages(); } catch (e) { notify(e.message); } });
    document.getElementById('pcChatClose')?.addEventListener('click', () => { pane.hidden = true; });
    window.setInterval(() => {
        if (pane && !pane.hidden && chatPage && !chatPage.hidden && document.visibilityState === 'visible') {
            loadMessages().catch(error => notify(error.message));
        }
    }, 20000);
    form.addEventListener('submit', async (e) => {
        e.preventDefault(); if (!teacherId || !studentId || !input.value.trim()) return;
        const body = input.value.trim();
        if (!pendingMessage || pendingMessage.body !== body || pendingMessage.studentId !== studentId || pendingMessage.teacherId !== teacherId) {
            pendingMessage = { body, studentId, teacherId, id: newMessageId() };
        }
        send.disabled = true; notify('');
        try { await call('/messages', 'POST', { student_id: studentId, teacher_id: teacherId, body, client_message_id: pendingMessage.id }); pendingMessage = null; input.value = ''; await loadMessages(); }
        catch (err) { notify(err.message); } finally { send.disabled = false; }
    });
    loadTeachers();
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible' && chatPage && !chatPage.hidden) {
            loadTeachers(true);
        }
    });
    window.addEventListener('pageshow', event => {
        if (event.persisted && chatPage && !chatPage.hidden) loadTeachers(true);
    });
    window.setInterval(refreshTeacherThreads, 30000);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initParentChat, { once: true });
} else {
    initParentChat();
}
