// Mengatur sidebar, navigasi, dropdown, dan inisialisasi Dashboard Guru.
document.addEventListener('DOMContentLoaded', () => {
    /* =============================================
       07.1 SIDEBAR LIPAT
       ============================================= */
    const sidebar = document.getElementById('guruSidebar');
    const main = document.getElementById('guruMain');
    const fold = document.getElementById('guruFold');
    if (sidebar && main && fold) {
        fold.addEventListener('click', () => {
            const collapsed = sidebar.classList.toggle('collapsed');
            main.classList.toggle('expanded', collapsed);
            fold.setAttribute(
                'aria-expanded',
                String(!collapsed)
            );
            fold.setAttribute(
                'aria-label',
                collapsed ?
                'Perbesar sidebar' :
                'Perkecil sidebar'
            );
            fold.setAttribute(
                'title',
                collapsed ?
                'Perbesar sidebar' :
                'Perkecil sidebar'
            );
        });
    }
    /* =============================================
       07.2 URUTAN HALAMAN
       ============================================= */
    const pageOrder = [
        'beranda',
        'materi',
        'kuis',
        'evaluasi',
        'forum',
        'nilai',
        'students',
        'orang-tua',
        'profil'
    ];
    const pageButtons = document.querySelectorAll(
        '[data-guru-page]'
    );
    const sections = document.querySelectorAll(
        '[data-guru-section]'
    );
    let currentPage = 'beranda';
    /* =============================================
       07.3 NOTIFIKASI DAN DROPDOWN PROFIL
       ============================================= */
    const notifButton = document.getElementById(
        'guruNotifButton'
    );
    const notifMenu = document.getElementById(
        'guruNotifMenu'
    );
    const profileButton = document.getElementById(
        'guruProfileButton'
    );
    const profileMenu = document.getElementById(
        'guruProfileMenu'
    );

    function closeGuruDropdowns() {
        if (notifMenu) {
            notifMenu.hidden = true;
        }
        if (profileMenu) {
            profileMenu.hidden = true;
        }
        notifButton?.setAttribute(
            'aria-expanded',
            'false'
        );
        profileButton?.setAttribute(
            'aria-expanded',
            'false'
        );
    }

    function toggleGuruDropdown(button, menu) {
        if (!button || !menu) return;
        button.addEventListener('click', (event) => {
            event.stopPropagation();
            const shouldOpen = menu.hidden;
            closeGuruDropdowns();
            menu.hidden = !shouldOpen;
            button.setAttribute(
                'aria-expanded',
                String(shouldOpen)
            );
        });
    }
    toggleGuruDropdown(
        notifButton,
        notifMenu
    );
    toggleGuruDropdown(
        profileButton,
        profileMenu
    );
    /* =============================================
       07.4 TAMPILKAN HALAMAN
       ============================================= */
    function showGuruPage(page) {
        if (!pageOrder.includes(page)) {
            return;
        }
        currentPage = page;
        // Sembunyikan halaman lain
        sections.forEach((section) => {
            section.hidden =
                section.dataset.guruSection !== page;
        });
        // Sesuaikan menu aktif di sidebar
        document.querySelectorAll(
            '.gd-nav .gd-menu'
        ).forEach((menu) => {
            const active =
                menu.dataset.guruPage === page;
            menu.classList.toggle(
                'active',
                active
            );
            if (active) {
                menu.setAttribute(
                    'aria-current',
                    'page'
                );
            } else {
                menu.removeAttribute(
                    'aria-current'
                );
            }
        });
        // Tutup dropdown setelah berpindah
        closeGuruDropdowns();
        // Kembali ke bagian atas
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }

    function syncGuruPageUrl(page, replace = false) {
        const url = new URL(window.location.href);
        url.searchParams.set('page', page);
        url.searchParams.delete('student_id');
        if (replace) {
            window.history.replaceState({ ...window.history.state, page }, '', url);
        } else {
            window.history.pushState({ ...window.history.state, page }, '', url);
        }
    }

    function navigateGuruPage(page, { replace = false } = {}) {
        if (!pageOrder.includes(page)) return;
        showGuruPage(page);
        syncGuruPageUrl(page, replace);
    }
    /* =============================================
       07.5 KLIK MENU SIDEBAR / PROFIL
       ============================================= */
    pageButtons.forEach((button) => {
        button.addEventListener('click', () => {
            navigateGuruPage(button.dataset.guruPage);
            if (button.hasAttribute('data-guru-scroll-settings')) {
                // Konten Profil baru terlihat setelah showGuruPage().
                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                        const settings = document.querySelector(
                            '#guruProfilePage .gp-profile-settings-card, ' +
                            '#guruProfilePage #gpProfileSettingsCard'
                        );
                        settings?.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    });
                });
            }
        });
    });
    /* =============================================
       07.6 PREVIOUS
       BERLAKU UNTUK ATAS DAN BAWAH
       ============================================= */
    document.querySelectorAll(
        '[data-guru-previous]'
    ).forEach((button) => {
        button.addEventListener('click', () => {
            const index = pageOrder.indexOf(
                currentPage
            );
            if (index > 0) {
                navigateGuruPage(
                    pageOrder[index - 1]
                );
            }
        });
    });
    /* =============================================
       07.7 NEXT
       BERLAKU UNTUK ATAS DAN BAWAH
       ============================================= */
    document.querySelectorAll(
        '[data-guru-next]'
    ).forEach((button) => {
        button.addEventListener('click', () => {
            const index = pageOrder.indexOf(
                currentPage
            );
            if (index < pageOrder.length - 1) {
                navigateGuruPage(
                    pageOrder[index + 1]
                );
            }
        });
    });
    /* =============================================
       07.8 TUTUP DROPDOWN KETIKA KLIK DI LUAR
       ============================================= */
    document.addEventListener('click', (event) => {
        if (!event.target.closest('.gd-dropdown')) {
            closeGuruDropdowns();
        }
    });
    /* =============================================
       07.9 TUTUP DROPDOWN DENGAN ESCAPE
       ============================================= */
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeGuruDropdowns();
        }
    });
    /* =============================================
       07.10 HALAMAN PERTAMA
       ============================================= */
    window.addEventListener('popstate', () => {
        const requestedPage = new URLSearchParams(window.location.search).get('page');
        showGuruPage(pageOrder.includes(requestedPage) ? requestedPage : 'beranda');
    });

    const initialParams = new URLSearchParams(window.location.search);
    const requestedPage = initialParams.get('page');
    const hasStudentDeepLink = initialParams.has('student_id');
    const initialPage = hasStudentDeepLink
        ? 'nilai'
        : pageOrder.includes(requestedPage) ? requestedPage : 'beranda';
    showGuruPage(initialPage);
    if (hasStudentDeepLink && requestedPage !== 'nilai') {
        syncGuruPageUrl('nilai', true);
    }
});
