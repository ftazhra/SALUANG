{{-- =====================================================
     SALUANG — FORUM DISKUSI GURU
     File: resources/views/guru/partials/forum.blade.php
     ===================================================== --}}

<section class="gf-page" id="guruForumPage" data-guru-section="forum" hidden>

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

    {{-- PANEL FORUM --}}
    <div class="gf-panel">

        <div class="gf-heading">
            <div>
                <h1>Forum Diskusi</h1>
                <p>
                    Diskusikan pembelajaran dengan konteks Bab
                    dan Subbab yang sama.
                </p>
            </div>

            <button type="button" class="gf-btn gf-btn-primary" id="gfAddDiscussion">
                <i class="bi bi-plus-lg"></i>
                Tambah Diskusi
            </button>
        </div>

        {{-- FILTER BAB DAN SUBBAB --}}
        <div class="gf-filter-grid">

            <div class="gf-field">
                <label for="gfChapterFilter">Bab</label>
                <select id="gfChapterFilter">
                    <option value="">Semua Bab</option>
                    <option value="bab-1">
                        BAB 1 — Karakteristik dan Klasifikasi Makhluk Hidup
                    </option>
                </select>
            </div>

            <div class="gf-field">
                <label for="gfSubchapterFilter">Subbab</label>
                <select id="gfSubchapterFilter">
                    <option value="">Semua Subbab</option>
                    <option value="1.1">
                        1.1 Makhluk Hidup atau Benda Mati?
                    </option>
                    <option value="1.2">
                        1.2 Mengapa Makhluk Hidup Dikelompokkan?
                    </option>
                    <option value="1.3">
                        1.3 Makhluk Hidup Beraneka Ragam
                    </option>
                </select>
            </div>

        </div>

        {{-- PENCARIAN --}}
        <div class="gf-field">
            <label for="gfSearch">Cari Diskusi</label>
            <input type="search" id="gfSearch" maxlength="200" placeholder="Cari pertanyaan atau judul diskusi">
        </div>

        {{-- FILTER STATUS --}}
        <div class="gf-status-filters" role="group" aria-label="Filter status diskusi">

            <button type="button" class="gf-filter-btn is-active" data-gf-filter="all">
                Terbaru
            </button>

            <button type="button" class="gf-filter-btn" data-gf-filter="answered">
                Sudah Terjawab
            </button>

            <button type="button" class="gf-filter-btn" data-gf-filter="unanswered">
                Belum Terjawab
            </button>

        </div>

        {{-- DAFTAR DISKUSI --}}
        <div class="gf-discussion-list" id="gfDiscussionList" aria-live="polite">

            <div class="gf-empty">
                <i class="bi bi-arrow-repeat"></i>
                <p>Memuat daftar diskusi...</p>
            </div>

        </div>

        {{-- PAGINASI --}}
        <div class="gf-pagination" id="gfPagination" hidden>

            <button type="button" class="gf-btn gf-btn-outline" id="gfPrevPage">
                <i class="bi bi-chevron-left"></i>
                Sebelumnya
            </button>

            <span id="gfPageInfo"></span>

            <button type="button" class="gf-btn gf-btn-outline" id="gfNextPage">
                Berikutnya
                <i class="bi bi-chevron-right"></i>
            </button>
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

    {{-- =========================================
         MODAL TAMBAH / EDIT DISKUSI
         ========================================= --}}

    <div class="gf-modal" id="gfDiscussionModal" hidden>

        <div class="gf-modal-backdrop" data-gf-close></div>

        <div class="gf-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="gfDiscussionModalTitle">

            <div class="gf-modal-header">
                <h2 id="gfDiscussionModalTitle">
                    Tambah Diskusi
                </h2>

                <button type="button" class="gf-modal-close" data-gf-close aria-label="Tutup">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <form id="gfDiscussionForm">

                <input type="hidden" id="gfDiscussionId">

                <div class="gf-filter-grid">
                    <div class="gf-field">
                        <label for="gfFormChapter">Bab</label>
                        <select id="gfFormChapter" required>
                            <option value="">Pilih Bab</option>
                            <option value="bab-1">BAB 1</option>
                        </select>
                    </div>

                    <div class="gf-field">
                        <label for="gfFormSubchapter">Subbab</label>
                        <select id="gfFormSubchapter" required>
                            <option value="">Pilih Subbab</option>
                            <option value="1.1">1.1 Makhluk Hidup atau Benda Mati?</option>
                            <option value="1.2">1.2 Mengapa Makhluk Hidup Dikelompokkan?</option>
                            <option value="1.3">1.3 Makhluk Hidup Beraneka Ragam</option>
                        </select>
                    </div>
                </div>

                <div class="gf-field">
                    <label for="gfFormTitle">Judul Diskusi</label>
                    <input type="text" id="gfFormTitle" minlength="5" maxlength="200"
                        placeholder="Masukkan judul diskusi" required>
                </div>

                <div class="gf-field">
                    <label for="gfFormBody">Isi Diskusi</label>
                    <textarea id="gfFormBody" rows="5" minlength="10" maxlength="5000"
                        placeholder="Tuliskan pertanyaan atau topik diskusi..." required></textarea>
                </div>

                <div class="gf-form-actions">
                    <button type="button" class="gf-btn gf-btn-outline" data-gf-close>
                        Batal
                    </button>

                    <button type="submit" class="gf-btn gf-btn-primary" id="gfSaveDiscussion">
                        Simpan Diskusi
                    </button>
                </div>

            </form>
        </div>
    </div>

    {{-- =========================================
         MODAL DETAIL DAN BALAS DISKUSI
         ========================================= --}}

    <div class="gf-modal" id="gfDetailModal" hidden>

        <div class="gf-modal-backdrop" data-gf-close></div>

        <div class="gf-modal-dialog gf-modal-dialog-large" role="dialog" aria-modal="true"
            aria-labelledby="gfDetailTitle">

            <div class="gf-modal-header">
                <h2 id="gfDetailTitle">Detail Diskusi</h2>

                <button type="button" class="gf-modal-close" data-gf-close aria-label="Tutup">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div id="gfDetailContent"></div>

            <h3 class="gf-reply-heading">Tanggapan Diskusi</h3>
            <div id="gfRepliesList"></div>

            <form id="gfReplyForm">
                <input type="hidden" id="gfReplyDiscussionId">

                <div class="gf-field">
                    <label for="gfReplyBody">Tulis Tanggapan</label>
                    <textarea id="gfReplyBody" rows="4" minlength="2" maxlength="5000"
                        placeholder="Tuliskan tanggapan untuk diskusi ini..." required></textarea>
                </div>

                <div class="gf-form-actions">
                    <button type="button" class="gf-btn gf-btn-outline" data-gf-close>
                        Tutup
                    </button>

                    <button type="submit" class="gf-btn gf-btn-primary" id="gfSendReply">
                        <i class="bi bi-send"></i>
                        Kirim Tanggapan
                    </button>
                </div>
            </form>

        </div>
    </div>

    {{-- =========================================
         MODAL KONFIRMASI HAPUS
         ========================================= --}}

    <div class="gf-modal" id="gfDeleteModal" hidden>

        <div class="gf-modal-backdrop" data-gf-close></div>

        <div class="gf-modal-dialog gf-modal-dialog-small" role="dialog" aria-modal="true"
            aria-labelledby="gfDeleteTitle">

            <div class="gf-delete-icon">
                <i class="bi bi-exclamation-triangle"></i>
            </div>

            <h2 id="gfDeleteTitle">Hapus Diskusi?</h2>

            <p>
                Diskusi dan seluruh tanggapannya akan terhapus
                secara permanen.
            </p>

            <div class="gf-form-actions">
                <button type="button" class="gf-btn gf-btn-outline" data-gf-close>
                    Batal
                </button>

                <button type="button" class="gf-btn gf-btn-danger" id="gfConfirmDelete">
                    Ya, Hapus
                </button>
            </div>

        </div>
    </div>

    {{-- NOTIFIKASI --}}
    <div class="gf-toast" id="gfToast" role="status" aria-live="polite" hidden></div>

</section>
