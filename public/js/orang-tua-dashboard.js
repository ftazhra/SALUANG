// Mengatur navigasi halaman Orang Tua.

        document.addEventListener('DOMContentLoaded', function() {

            const layout = document.getElementById('parentLayout');
            const toggle = document.getElementById('parentSidebarToggle');
            const title = document.getElementById('parentPageTitle');
            const main = document.getElementById('parentMain');
            const content = document.querySelector('.parent-content');

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

                button.addEventListener('click', function(event) {

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

               Aktivitas, Hasil, Progres, Chat:
               - Atas Previous dan Next
               - Bawah Previous dan Next

               Profil:
               - Atas Previous
               - Bawah Previous
               ===================================================== */

            function createBottomNavigations() {

                sections.forEach(function(section) {

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
                    ).forEach(function(oldNavigation) {
                        oldNavigation.remove();
                    });

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
                     * Semua halaman mempunyai navigasi bawah.
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
                    function(section) {
                        return section.dataset.parentSection === page;
                    }
                );

                if (!targetSection) {
                    return false;
                }

                /*
                 * Tampilkan hanya halaman yang dipilih.
                 */
                sections.forEach(function(section) {

                    const isTarget =
                        section.dataset.parentSection === page;

                    section.hidden = !isTarget;
                });

                currentPage = page;

                /*
                 * Sidebar mengikuti halaman aktif.
                 */
                menuButtons.forEach(function(button) {

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

            document.addEventListener('click', function(event) {

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

                    /*
                     * Dropdown Pengaturan langsung menuju
                     * bagian pengaturan pada halaman Profil.
                     */
                    if (
                        changed &&
                        pageButton.hasAttribute('data-parent-settings')
                    ) {

                        requestAnimationFrame(function() {

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
            ).forEach(function(button) {

                button.addEventListener('click', function() {

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

            document.addEventListener('keydown', function(event) {

                if (event.key === 'Escape') {
                    closeParentDropdowns();
                }
            });

            /* =====================================================
               08. SIDEBAR COLLAPSE
               ===================================================== */

            if (toggle && layout) {

                toggle.addEventListener('click', function() {

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
    