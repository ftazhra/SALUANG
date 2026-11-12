            <section id="finalPage" hidden>

                <!-- Navigasi atas -->
                <div class="sd-navigation">

                    <button type="button" class="sd-nav-btn" data-page="5">
                        <i class="bi bi-chevron-left"></i>
                        Previous
                    </button>

                    <button type="button" class="sd-nav-btn next" data-page="8">
                        Next
                        <i class="bi bi-chevron-right"></i>
                    </button>

                </div>

                <article class="sd-card sd-forum-card">

                    <!-- Header -->
                    <div class="sd-forum-header">

                        <div>
                            <h1 class="sd-forum-title">
                                Forum Diskusi
                            </h1>

                            <p class="sd-forum-subtitle">
                                Diskusikan pembelajaran dengan konteks
                                Bab dan Subbab yang sama.
                            </p>
                        </div>

                        <button type="button" class="sd-forum-primary" id="forumCreateButton">
                            <i class="bi bi-plus-lg"></i>
                            Buat Diskusi
                        </button>

                    </div>

                    <!-- =================================================
                         13.1 DAFTAR DISKUSI
                    ================================================== -->
                    <div id="forumListScreen">

                        <div class="sd-forum-filters">

                            <div class="sd-forum-field">

                                <label for="forumChapterFilter">
                                    Bab
                                </label>

                                <select id="forumChapterFilter">
                                    <option value="">
                                        Semua Bab
                                    </option>

                                    <option value="bab-1">
                                        BAB 1 – Karakteristik dan Klasifikasi
                                        Makhluk Hidup
                                    </option>
                                </select>

                            </div>

                            <div class="sd-forum-field">

                                <label for="forumSubchapterFilter">
                                    Subbab
                                </label>

                                <select id="forumSubchapterFilter">

                                    <option value="">
                                        Semua Subbab
                                    </option>

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

                        <!-- Pencarian -->
                        <div class="sd-forum-field sd-forum-search">

                            <label for="forumSearchInput">
                                Cari diskusi
                            </label>

                            <input type="search" id="forumSearchInput"
                                placeholder="Cari pertanyaan atau judul diskusi">

                        </div>

                        <!-- Filter status -->
                        <div class="sd-forum-status-filters">

                            <button type="button" class="sd-forum-filter-btn active" data-forum-status="all">
                                Terbaru
                            </button>

                            <button type="button" class="sd-forum-filter-btn" data-forum-status="answered">
                                Sudah Terjawab
                            </button>

                            <button type="button" class="sd-forum-filter-btn" data-forum-status="unanswered">
                                Belum Terjawab
                            </button>

                        </div>

                        <p id="forumListMessage" class="sd-forum-message" role="status" aria-live="polite"></p>

                        <!-- Daftar dari database -->
                        <div id="forumDiscussionList"></div>

                        <!-- Paginasi -->
                        <div class="sd-forum-pagination" id="forumPagination"></div>

                    </div>

                    <!-- =================================================
                         13.2 BUAT DISKUSI
                    ================================================== -->
                    <div id="forumCreateScreen" hidden>

                        <div class="sd-forum-screen-header">

                            <h2>Buat Diskusi Baru</h2>

                            <button type="button" class="sd-forum-back" data-forum-back>
                                Kembali ke Daftar
                            </button>

                        </div>

                        <form id="forumCreateForm">

                            <div class="sd-forum-field">

                                <label for="forumCreateChapter">
                                    Bab
                                </label>

                                <select id="forumCreateChapter" required>
                                    <option value="bab-1">
                                        BAB 1 – Karakteristik dan
                                        Klasifikasi Makhluk Hidup
                                    </option>
                                </select>

                            </div>

                            <div class="sd-forum-field">

                                <label for="forumCreateSubchapter">
                                    Subbab
                                </label>

                                <select id="forumCreateSubchapter" required>
                                    <option value="">
                                        Pilih Subbab
                                    </option>

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

                            <div class="sd-forum-field">

                                <label for="forumCreateTitle">
                                    Judul Diskusi
                                </label>

                                <input type="text" id="forumCreateTitle" minlength="5" maxlength="200"
                                    placeholder="Tulis judul pertanyaan" required>

                            </div>

                            <div class="sd-forum-field">

                                <label for="forumCreateBody">
                                    Pertanyaan
                                </label>

                                <textarea id="forumCreateBody" minlength="10" maxlength="5000" rows="6"
                                    placeholder="Jelaskan pertanyaanmu di sini..." required></textarea>

                            </div>

                            <p id="forumCreateMessage" class="sd-forum-message" role="status" aria-live="polite">
                            </p>

                            <button type="submit" class="sd-forum-primary" id="forumCreateSubmit">
                                <i class="bi bi-send"></i>
                                Kirim Diskusi
                            </button>

                        </form>

                    </div>

                    <!-- =================================================
                         13.3 DETAIL DISKUSI
                    ================================================== -->
                    <div id="forumDetailScreen" hidden>

                        <div class="sd-forum-screen-header">

                            <h2>Detail Diskusi</h2>

                            <button type="button" class="sd-forum-back" data-forum-back>
                                Kembali ke Daftar
                            </button>

                        </div>

                        <!-- Pertanyaan dari database -->
                        <div id="forumDetailContent"></div>

                        <h3 class="sd-forum-replies-heading">
                            Tanggapan Diskusi
                        </h3>

                        <!-- Balasan dari database -->
                        <div id="forumRepliesList"></div>

                        <form id="forumReplyForm">

                            <div class="sd-forum-field">

                                <label for="forumReplyBody">
                                    Tulis Tanggapan
                                </label>

                                <textarea id="forumReplyBody" minlength="2" maxlength="5000" rows="4" placeholder="Tuliskan tanggapanmu..."
                                    required></textarea>

                            </div>

                            <p id="forumReplyMessage" class="sd-forum-message" role="status" aria-live="polite">
                            </p>

                            <button type="submit" class="sd-forum-primary" id="forumReplySubmit">
                                <i class="bi bi-send"></i>
                                Kirim Tanggapan
                            </button>

                        </form>

                    </div>

                </article>

                <!-- =================================================
                     13.4 MODAL HAPUS DISKUSI
                ================================================== -->
                <div class="sd-forum-confirm" id="forumDeleteModal" hidden>

                    <div class="sd-forum-confirm-backdrop" id="forumDeleteBackdrop"></div>

                    <div class="sd-forum-confirm-dialog" role="dialog" aria-modal="true"
                        aria-labelledby="forumDeleteTitle" aria-describedby="forumDeleteDescription" tabindex="-1">

                        <button type="button" class="sd-forum-confirm-close" id="forumDeleteClose"
                            aria-label="Tutup konfirmasi">
                            <i class="bi bi-x-lg"></i>
                        </button>

                        <div class="sd-forum-confirm-icon">
                            <i class="bi bi-trash3"></i>
                        </div>

                        <h2 class="sd-forum-confirm-title" id="forumDeleteTitle">
                            Yakin Ingin Menghapus Diskusi?
                        </h2>

                        <p class="sd-forum-confirm-description" id="forumDeleteDescription">
                            Diskusi ini beserta seluruh tanggapannya
                            akan dihapus secara permanen.
                        </p>

                        <div class="sd-forum-confirm-info">

                            <span>Diskusi yang akan dihapus:</span>

                            <strong id="forumDeleteDiscussionTitle"></strong>

                        </div>

                        <p class="sd-forum-confirm-warning">
                            Tindakan ini tidak dapat dibatalkan.
                        </p>

                        <div class="sd-forum-confirm-actions">

                            <button type="button" class="sd-forum-confirm-cancel" id="forumDeleteCancel">
                                Batal
                            </button>

                            <button type="button" class="sd-forum-confirm-danger" id="forumDeleteAccept">
                                Ya, Hapus
                            </button>

                        </div>

                        <p class="sd-forum-confirm-error" id="forumDeleteError" role="alert" hidden></p>

                    </div>

                </div>

                <!-- Navigasi bawah forum -->
                <div class="sd-navigation">

                    <button type="button" class="sd-nav-btn" data-page="5">
                        <i class="bi bi-chevron-left"></i>
                        Previous
                    </button>

                    <button type="button" class="sd-nav-btn next" data-page="8">
                        Next
                        <i class="bi bi-chevron-right"></i>
                    </button>

                </div>

            </section>
