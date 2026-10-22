{{-- =====================================================
     SALUANG — HUBUNGI ORANG TUA GURU
     File: resources/views/guru/partials/orang-tua.blade.php
     ===================================================== --}}

<section class="gp-page" id="guruParentPage" data-guru-section="orang-tua" hidden>

    {{-- NAVIGASI ATAS --}}
    <div class="gd-page-navigation">
        <button type="button" class="gd-nav-btn" data-guru-previous>
            <i class="bi bi-chevron-left"></i>
            Previous
        </button>

        <button type="button" class="gd-nav-btn gd-nav-btn-next" data-guru-next>
            Next
            <i class="bi bi-chevron-right"></i>
        </button>
    </div>

    {{-- PANEL UTAMA --}}
    <div class="gp-panel">

        <div class="gp-heading">
            <div>
                <h1>Hubungi Orang Tua</h1>
                <p>
                    Temukan narahubung Ayah, Ibu, atau Wali
                    untuk berkomunikasi mengenai perkembangan siswa.
                </p>
            </div>
        </div>

        <div class="gp-layout">

            {{-- =========================================
                 DAFTAR KONTAK ORANG TUA
                 ========================================= --}}
            <aside class="gp-sidebar">

                <div class="gp-sidebar-heading">
                    <h2>Daftar Narahubung</h2>
                    <span id="gpContactCount">0 kontak</span>
                </div>

                <div class="gp-field">
                    <label for="gpSearchContact">
                        Cari Siswa atau Orang Tua
                    </label>

                    <div class="gp-search-box">
                        <i class="bi bi-search"></i>

                        <input type="search" id="gpSearchContact" maxlength="200"
                            placeholder="Cari nama siswa / orang tua">
                    </div>
                </div>


                {{-- DAFTAR KELAS VII --}}
                <div class="gp-class-list" id="gpClassList">

                    <button type="button" class="gp-class-item" data-gp-class="VII A" aria-expanded="false">
                        <i class="bi bi-people"></i>
                        <span>Kelas VII A</span>
                        <i class="bi bi-chevron-down"></i>
                    </button>

                    <button type="button" class="gp-class-item" data-gp-class="VII B" aria-expanded="false">
                        <i class="bi bi-people"></i>
                        <span>Kelas VII B</span>
                        <i class="bi bi-chevron-down"></i>
                    </button>

                    <button type="button" class="gp-class-item" data-gp-class="VII C" aria-expanded="false">
                        <i class="bi bi-people"></i>
                        <span>Kelas VII C</span>
                        <i class="bi bi-chevron-down"></i>
                    </button>

                    <button type="button" class="gp-class-item" data-gp-class="VII D" aria-expanded="false">
                        <i class="bi bi-people"></i>
                        <span>Kelas VII D</span>
                        <i class="bi bi-chevron-down"></i>
                    </button>

                    <button type="button" class="gp-class-item" data-gp-class="VII E" aria-expanded="false">
                        <i class="bi bi-people"></i>
                        <span>Kelas VII E</span>
                        <i class="bi bi-chevron-down"></i>
                    </button>

                </div>

                {{-- DAFTAR KONTAK KELAS YANG DIPILIH --}}
                <div class="gp-contact-list" id="gpContactList" aria-live="polite">

                    <div class="gp-empty">
                        <i class="bi bi-people"></i>
                        <p>Pilih kelas untuk melihat daftar narahubung.</p>
                    </div>

                </div>


            </aside>

            {{-- =========================================
                 DETAIL NARAHUBUNG DAN AREA CHAT
                 ========================================= --}}
            <div class="gp-main">

                <div class="gp-contact-detail" id="gpContactDetail" hidden>

                    <div class="gp-detail-heading">
                        <div class="gp-contact-avatar">
                            <i class="bi bi-person-heart"></i>
                        </div>

                        <div>
                            <h2 id="gpGuardianName">
                                Nama Orang Tua
                            </h2>

                            <span id="gpGuardianRelation">
                                Ayah / Ibu / Wali
                            </span>
                        </div>
                    </div>

                    <div class="gp-info-grid">

                        <div class="gp-info-item">
                            <small>Nama Siswa</small>
                            <strong id="gpStudentName">—</strong>
                        </div>

                        <div class="gp-info-item">
                            <small>Kelas</small>
                            <strong id="gpStudentClass">—</strong>
                        </div>

                        <div class="gp-info-item">
                            <small>NISN</small>
                            <strong id="gpStudentNisn">—</strong>
                        </div>

                        <div class="gp-info-item">
                            <small>Hubungan Keluarga</small>
                            <strong id="gpRelationship">—</strong>
                        </div>

                        <div class="gp-info-item gp-info-phone">
                            <small>Nomor Telepon / WhatsApp</small>

                            <div class="gp-phone-row">
                                <strong id="gpGuardianPhone">—</strong>

                                <a id="gpWhatsappLink" class="gp-btn gp-btn-whatsapp" href="#" target="_blank"
                                    rel="noopener noreferrer" hidden>
                                    <i class="bi bi-whatsapp"></i>
                                    WhatsApp
                                </a>
                            </div>
                        </div>

                    </div>

                </div>

                {{-- HEADER CHAT --}}
                <div class="gp-chat-heading">
                    <div>
                        <h2>Pesan Orang Tua</h2>
                        <p id="gpChatSubtitle">
                            Pilih narahubung untuk melihat detail kontak.
                        </p>
                    </div>
                </div>

                {{-- TEMPAT RIWAYAT PERCAKAPAN --}}
                <div class="gp-chat-messages" id="gpChatMessages" aria-live="polite">

                    <div class="gp-chat-placeholder">
                        <i class="bi bi-chat-dots"></i>

                        <h3>Belum ada percakapan dipilih</h3>

                        <p>
                            Pilih Ayah, Ibu, atau Wali dari daftar
                            narahubung di sebelah kiri.
                        </p>
                    </div>

                </div>

                {{-- FORM CHAT INTERNAL --}}
                {{-- Akan diaktifkan setelah API pesan dibuat --}}
                <form class="gp-chat-form" id="gpChatForm">

                    <input type="hidden" id="gpSelectedRelationshipId">

                    <label for="gpMessageInput">
                        Pesan untuk Orang Tua
                    </label>

                    <div class="gp-chat-compose">
                        <textarea id="gpMessageInput" rows="3" maxlength="5000"
                            placeholder="Chat internal akan tersedia setelah integrasi pesan selesai..." disabled></textarea>

                        <button type="submit" class="gp-btn gp-btn-send" id="gpSendMessage" disabled>
                            <i class="bi bi-send"></i>
                            Kirim
                        </button>
                    </div>

                </form>

            </div>

        </div>

    </div>

    {{-- NAVIGASI BAWAH --}}
    <div class="gd-page-navigation gd-navigation-bottom">

        <button type="button" class="gd-nav-btn" data-guru-previous>
            <i class="bi bi-chevron-left"></i>
            Previous
        </button>

        <button type="button" class="gd-nav-btn gd-nav-btn-next" data-guru-next>
            Next
            <i class="bi bi-chevron-right"></i>
        </button>

    </div>

    {{-- NOTIFIKASI --}}
    <div class="gp-toast" id="gpToast" role="status" aria-live="polite" hidden></div>

</section>
