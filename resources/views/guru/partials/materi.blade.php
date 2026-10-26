<section class="gd-material-page" data-guru-section="materi" id="guruMaterialPage" hidden>


    {{-- ======================================================
         01. DAFTAR BAB
         ====================================================== --}}
    <div id="guruMaterialList">
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
        <div class="gm-heading">
            <div>
                <h1>Kelola Materi</h1>
                <p>
                    Kelola Bab, Subbab, dan materi pembelajaran
                    IPA untuk siswa melalui SALUANG.
                </p>
            </div>
            <button type="button" class="gm-btn gm-btn-primary" id="gmAddChapter">
                <i class="bi bi-plus-lg"></i>
                Tambah Bab
            </button>
        </div>
        <div class="gm-chapter-list" id="gmChapterList">
            <div class="gm-empty">
                <i class="bi bi-journal-text"></i>
                <p>Memuat daftar Bab...</p>
            </div>
        </div>
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
    </div>


    {{-- ======================================================
         02. DETAIL BAB
         ====================================================== --}}
    <div id="guruMaterialDetail" hidden>
        <div class="gm-back-actions">
            <button type="button" class="gm-btn gm-btn-outline" id="gmBackToChapters">
                Kembali ke Kelola Materi
            </button>
            <button type="button" class="gm-btn gm-btn-outline" id="gmBackToHome">
                <i class="bi bi-house-door"></i>
                Kembali ke Beranda
            </button>
        </div>
        <article class="gm-detail-card">
            <div class="gm-detail-top">
                <div>
                    <span class="gm-chapter-label" id="gmDetailNumber">
                        BAB
                    </span>
                    <h1 id="gmDetailTitle">
                        Detail Bab
                    </h1>
                </div>
                <div class="gm-detail-actions">
                    <button type="button" class="gm-btn gm-btn-outline" id="gmEditChapter">
                        <i class="bi bi-pencil-square"></i>
                        Edit Bab
                    </button>
                    <button type="button" class="gm-btn gm-btn-danger" id="gmDeleteChapter">
                        <i class="bi bi-trash3"></i>
                        Hapus Bab
                    </button>
                </div>
            </div>
            <div class="gm-introduction-layout">
                <p class="gm-detail-description" id="gmDetailDescription"></p>
                <figure class="gm-introduction-figure" id="gmDetailIntroductionFigure" hidden>
                    <img class="gm-introduction-image" id="gmDetailIntroductionImage" alt="">
                    <figcaption class="gm-introduction-caption" id="gmDetailIntroductionCaption" hidden>
                        <strong id="gmDetailIntroductionTitle" hidden></strong>
                    </figcaption>
                </figure>
            </div>


            {{-- TUJUAN PEMBELAJARAN --}}
            <div class="gm-detail-section">
                <h2>
                    <i class="bi bi-bullseye"></i>
                    Tujuan Pembelajaran
                </h2>
                <ol id="gmDetailObjectives">
                    <li>
                        Belum ada tujuan pembelajaran.
                    </li>
                </ol>
            </div>


            {{-- YUK, PIKIRKAN --}}
            <div class="gm-detail-section">
                <h2>
                    <i class="bi bi-lightbulb"></i>
                    Yuk, Pikirkan!
                </h2>
                <p id="gmDetailTriggerQuestion">
                    Belum ada pertanyaan.
                </p>
            </div>
        </article>


        {{-- DAFTAR SUBBAB --}}
        <section class="gm-subchapter-section">
            <div class="gm-heading">
                <div>
                    <h2>Daftar Subbab</h2>
                    <p>
                        Tambahkan dan kelola isi materi
                        untuk setiap Subbab.
                    </p>
                </div>
                <button type="button" class="gm-btn gm-btn-primary" id="gmAddSubchapter">
                    <i class="bi bi-plus-lg"></i>
                    Tambah Subbab
                </button>
            </div>
            <div class="gm-subchapter-list" id="gmSubchapterList">
                <p>Memuat Subbab...</p>
            </div>
        </section>
    </div>


    {{-- ======================================================
         03. MODAL TAMBAH / EDIT BAB
         ====================================================== --}}
    <div class="gm-modal" id="gmChapterModal" hidden>
        <div class="gm-modal-backdrop" data-gm-close></div>
        <div class="gm-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="gmChapterModalTitle">
            <div class="gm-modal-header">
                <h2 id="gmChapterModalTitle">
                    Tambah Bab
                </h2>
                <button type="button" class="gm-modal-close" data-gm-close aria-label="Tutup">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <form id="gmChapterForm">
                <input type="hidden" id="gmChapterId">


                {{-- NOMOR BAB --}}
                <div class="gm-field">
                    <label for="gmChapterNumber">
                        Nomor Bab
                    </label>
                    <input type="number" id="gmChapterNumber" min="1" placeholder="Contoh: 1" required>
                </div>


                {{-- JUDUL BAB --}}
                <div class="gm-field">
                    <label for="gmChapterTitle">
                        Judul Bab
                    </label>
                    <input type="text" id="gmChapterTitle" maxlength="255" placeholder="Masukkan judul Bab"
                        required>
                </div>


                {{-- DESKRIPSI BAB --}}
                <div class="gm-field">
                    <label for="gmChapterDescription">
                        Deskripsi Bab
                    </label>
                    <textarea id="gmChapterDescription" class="gm-multiline-input" rows="4"
                        placeholder="Tuliskan gambaran umum Bab"></textarea>
                    <small>
                        Ringkasan yang menjelaskan isi
                        dan cakupan materi pada Bab ini.
                    </small>
                </div>


                {{-- GAMBAR PENGENALAN BAB --}}
                <div class="gm-field">
                    <label for="gmChapterIntroductionImage">
                        Gambar Pengenalan Bab
                    </label>
                    <input type="file" id="gmChapterIntroductionImage" class="gm-chapter-intro-file"
                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                    <small>JPG, JPEG, PNG, atau WebP; ukuran maksimal 4 MB.</small>
                    <img class="gm-introduction-preview" id="gmChapterIntroductionPreview"
                        alt="Pratinjau gambar pengenalan Bab" hidden>
                </div>
                <div class="gm-field">
                    <label for="gmChapterIntroductionTitle">
                        Judul Gambar
                    </label>
                    <input type="text" id="gmChapterIntroductionTitle" maxlength="255"
                        placeholder="Contoh: Ekosistem Lahan Basah Kalimantan Selatan">
                </div>


                {{-- TUJUAN PEMBELAJARAN --}}
                <div class="gm-field">
                    <label>
                        Tujuan Pembelajaran
                    </label>
                    <p class="gm-field-help">
                        Masukkan setiap tujuan secara terpisah.
                        Tujuan akan ditampilkan dengan nomor urut.
                    </p>
                    <div id="gmObjectivesList">


                        {{-- Input dibuat JavaScript Guru --}}
                    </div>
                    <button type="button" class="gm-btn gm-btn-outline" id="gmAddObjective">
                        <i class="bi bi-plus-lg"></i>
                        Tambah Tujuan Pembelajaran
                    </button>
                </div>


                {{-- YUK, PIKIRKAN --}}
                <div class="gm-field">
                    <label for="gmChapterTriggerQuestion">
                        Yuk, Pikirkan!
                    </label>
                    <p class="gm-field-help">
                        Tuliskan satu pertanyaan untuk dipikirkan siswa.
                    </p>
                    <textarea id="gmChapterTriggerQuestion" class="gm-multiline-input" rows="3"
                        placeholder="Tuliskan satu pertanyaan untuk dipikirkan siswa"></textarea>
                    <small>
                        Pertanyaan ini akan ditampilkan kepada Siswa pada halaman Pengenalan Bab.
                    </small>
                </div>


                {{-- PUBLIKASI BAB --}}
                <label class="gm-checkbox">
                    <input type="checkbox" id="gmChapterPublished">
                    Publikasikan Bab
                </label>
                <div class="gm-modal-actions">
                    <button type="button" class="gm-btn gm-btn-outline" data-gm-close>
                        Batal
                    </button>
                    <button type="submit" class="gm-btn gm-btn-primary" id="gmSaveChapter">
                        Simpan Bab
                    </button>
                </div>
            </form>
        </div>
    </div>


    {{-- ======================================================
         04. MODAL TAMBAH / EDIT SUBBAB
         ====================================================== --}}
    <div class="gm-modal" id="gmSubchapterModal" hidden>
        <div class="gm-modal-backdrop" data-gm-close></div>
        <div class="gm-modal-dialog gm-editor-modal-dialog" role="dialog" aria-modal="true"
            aria-labelledby="gmSubchapterModalTitle">
            <div class="gm-modal-header">
                <h2 id="gmSubchapterModalTitle">
                    Tambah Subbab
                </h2>
                <button type="button" class="gm-modal-close" data-gm-close aria-label="Tutup">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <form id="gmSubchapterForm">
                <input type="hidden" id="gmSubchapterId">
                <div class="gm-subchapter-fields">
                    <div class="gm-field">
                        <label for="gmSubchapterNumber">Nomor Subbab</label>
                        <input type="number" id="gmSubchapterNumber" min="1" placeholder="Contoh: 1"
                            required>
                    </div>
                    <div class="gm-field">
                        <label for="gmSubchapterTitle">Judul Subbab</label>
                        <input type="text" id="gmSubchapterTitle" maxlength="255"
                            placeholder="Contoh: Makhluk Hidup atau Benda Mati?" required>
                    </div>
                </div>
                <section class="gm-content-block-editor" aria-labelledby="gmContentBlocksHeading">
                    <div class="gm-content-block-heading">
                        <div>
                            <h3 id="gmContentBlocksHeading">Urutan Blok Materi</h3>
                            <p>Susun pratinjau materi dengan tombol Naik dan Turun.</p>
                        </div>
                    </div>
                    <div class="gm-content-block-add">
                        <label class="visually-hidden" for="gmContentBlockType">Jenis blok</label>
                        <select id="gmContentBlockType">
                            <option value="text">Teks penjelasan</option>
                            <option value="image">Gambar pembelajaran</option>
                            <option value="video">Video MP4 atau YouTube</option>
                            <option value="activity">Aktivitas Pembelajaran (Ayo...)</option>
                            <option value="callout">Informasi / Kesimpulan</option>
                        </select>
                        <button type="button" class="gm-btn gm-btn-outline" id="gmAddContentBlock">
                            Tambah blok
                        </button>
                    </div>
                    <p class="gm-content-block-help">
                        Media baru dapat diunggah atau dipilih di editor blok.
                        Kartu media lama tetap tersedia; file tidak dihapus saat blok dilepas.
                    </p>
                    <ol class="gm-content-block-list" id="gmContentBlockList"
                        aria-label="Pratinjau urutan blok materi" aria-live="polite"></ol>
                    <p class="gm-content-block-empty" id="gmContentBlockEmpty">
                        Belum ada blok. Tambahkan blok teks, media, atau kesimpulan.
                    </p>
                    <p class="gm-content-block-placeholder" id="gmContentBlockPlaceholder" hidden>
                        Pilih Edit pada blok untuk mengubah isinya. Perubahan tetap di formulir sampai Simpan Materi
                        ditekan.
                    </p>
                </section>


                {{-- EDITOR ISI MATERI --}}
                <div class="gm-field" id="gmTextBlockEditor" hidden>
                    <label for="gmRichContentEditor">Editor teks blok terpilih</label>
                    <p class="gm-field-help">
                        Pilih Edit pada blok teks. Pemformatan rich text tetap tersedia.
                    </p>
                    <div class="gm-rich-editor">


                        {{-- TOOLBAR --}}
                        <div class="gm-rich-toolbar" id="gmRichToolbar" role="toolbar"
                            aria-label="Alat pemformatan materi">


                            {{-- BOLD --}}
                            <button type="button" class="gm-rich-tool" data-gm-command="bold"
                                title="Tebal (Ctrl+B)" aria-label="Tebal">
                                <strong>B</strong>
                            </button>


                            {{-- ITALIC --}}
                            <button type="button" class="gm-rich-tool" data-gm-command="italic"
                                title="Miring (Ctrl+I)" aria-label="Miring">
                                <em>I</em>
                            </button>


                            {{-- UNDERLINE --}}
                            <button type="button" class="gm-rich-tool" data-gm-command="underline"
                                title="Garis bawah (Ctrl+U)" aria-label="Garis bawah">
                                <u>U</u>
                            </button>
                            <span class="gm-rich-separator" aria-hidden="true"></span>


                            {{-- HEADING 2 --}}
                            <button type="button" class="gm-rich-tool gm-rich-tool-text" data-gm-block="H2"
                                title="Judul bagian" aria-label="Judul bagian">
                                H2
                            </button>


                            {{-- HEADING 3 --}}
                            <button type="button" class="gm-rich-tool gm-rich-tool-text" data-gm-block="H3"
                                title="Subjudul" aria-label="Subjudul">
                                H3
                            </button>


                            {{-- PARAGRAF --}}
                            <button type="button" class="gm-rich-tool gm-rich-tool-text" data-gm-block="P"
                                title="Paragraf biasa" aria-label="Paragraf biasa">
                                ¶
                            </button>
                            <span class="gm-rich-separator" aria-hidden="true"></span>


                            {{-- ORDERED LIST --}}
                            <button type="button" class="gm-rich-tool" data-gm-command="insertOrderedList"
                                title="Daftar bernomor" aria-label="Daftar bernomor">
                                <i class="bi bi-list-ol"></i>
                            </button>


                            {{-- UNORDERED LIST --}}
                            <button type="button" class="gm-rich-tool" data-gm-command="insertUnorderedList"
                                title="Daftar bullet" aria-label="Daftar bullet">
                                <i class="bi bi-list-ul"></i>
                            </button>
                            <span class="gm-rich-separator" aria-hidden="true"></span>


                            {{-- REMOVE FORMATTING --}}
                            <button type="button" class="gm-rich-tool" data-gm-command="removeFormat"
                                title="Hapus pemformatan" aria-label="Hapus pemformatan">
                                <i class="bi bi-eraser"></i>
                            </button>
                        </div>


                        {{-- AREA EDITOR YANG TERLIHAT --}}
                        <textarea id="gmRichContentEditor" class="gm-rich-content gm-multiline-input" aria-label="Isi materi pembelajaran"
                            rows="8"
                            placeholder="Mulai tulis materi di sini. Gunakan toolbar untuk mengatur judul, paragraf, dan daftar..."></textarea>
                    </div>


                    {{-- TEXTAREA UNTUK KOMPATIBILITAS API --}}
                    <textarea id="gmSubchapterContent" rows="8" class="gm-rich-source" aria-label="Data sumber materi"
                        tabindex="-1" placeholder="Tuliskan isi materi pembelajaran"></textarea>
                    <small>
                        Blok teks yang ingin diformat, lalu
                        pilih Bold, Italic, Underline, Heading,
                        daftar angka, atau bullet.
                    </small>
                    <p class="gm-editor-status" id="gmEditorStatus" role="status" aria-live="polite"></p>
                </div>


                {{-- Aktivitas disimpan sebagai blok teks agar aman tanpa perubahan database. --}}
                <div class="gm-content-block-callout" id="gmActivityEditor" hidden>
                    <div class="gm-field">
                        <label for="gmActivityType">Jenis Aktivitas Pembelajaran</label>
                        <select id="gmActivityType" class="gm-multiline-input">
                            <option value="Ayo Mengamati!">Ayo Mengamati!</option>
                            <option value="Ayo Berpikir!">Ayo Berpikir!</option>
                            <option value="Ayo Berdiskusi!">Ayo Berdiskusi!</option>
                            <option value="Ayo Mencoba!">Ayo Mencoba!</option>
                            <option value="Ayo Refleksi!">Ayo Refleksi!</option>
                            <option value="Ayo Berlatih!">Ayo Berlatih!</option>
                        </select>
                    </div>
                    <div class="gm-field">
                        <label for="gmActivityContent">Isi atau pertanyaan aktivitas</label>
                        <textarea id="gmActivityContent" class="gm-multiline-input" rows="6"
                            placeholder="Tuliskan pertanyaan dan kegiatan di sini. Gunakan baris baru untuk daftar atau paragraf."></textarea>
                    </div>
                </div>
                <div class="gm-content-block-callout" id="gmCalloutEditor" hidden>
                    <div class="gm-field">
                        <label for="gmCalloutTitle">Judul informasi <span>(opsional)</span></label>
                        <input type="text" id="gmCalloutTitle" maxlength="160" placeholder="Contoh: Kesimpulan">
                    </div>
                    <div class="gm-field">
                        <label for="gmCalloutContent">Isi informasi / kesimpulan</label>
                        <textarea id="gmCalloutContent" class="gm-multiline-input" rows="3" maxlength="50000"
                            placeholder="Tuliskan ringkasan singkat untuk Siswa"></textarea>
                    </div>
                </div>
                <div class="gm-content-block-media-editor" id="gmBlockMediaEditor" hidden>
                    <p id="gmBlockMediaHelp" class="gm-field-help"></p>
                    <div class="gm-field">
                        <label id="gmBlockMediaSourceLabel" for="gmBlockMediaSource">Sumber media</label>
                        <select id="gmBlockMediaSource"></select>
                    </div>
                    <div class="gm-field" id="gmBlockExistingMediaField" hidden>
                        <label id="gmBlockExistingMediaLabel" for="gmBlockExistingMedia">Pilih media tersimpan</label>
                        <select id="gmBlockExistingMedia"></select>
                    </div>
                    <div class="gm-field" id="gmBlockUploadField" hidden>
                        <label id="gmBlockMediaFileLabel" for="gmBlockMediaFile">Pilih berkas</label>
                        <input type="file" id="gmBlockMediaFile">
                        <small id="gmBlockMediaFileHelp"></small>
                    </div>
                    <div class="gm-field" id="gmBlockYoutubeField" hidden>
                        <label for="gmBlockYoutubeUrl">Tautan YouTube</label>
                        <input type="url" id="gmBlockYoutubeUrl" maxlength="2048"
                            placeholder="https\://www\.youtube.com/watch?v=...">
                    </div>
                    <div class="gm-field">
                        <label for="gmBlockMediaTitle">Judul media <span>(opsional)</span></label>
                        <input type="text" id="gmBlockMediaTitle" maxlength="160"
                            placeholder="Judul singkat media">
                    </div>
                    <div class="gm-field">
                        <label for="gmBlockMediaCaption">Keterangan media <span>(opsional)</span></label>
                        <textarea id="gmBlockMediaCaption" class="gm-multiline-input" rows="2" maxlength="500"
                            placeholder="Keterangan singkat"></textarea>
                    </div>
                </div>


                {{-- PUBLIKASI SUBBAB --}}
                <label class="gm-checkbox">
                    <input type="checkbox" id="gmSubchapterPublished">
                    Publikasikan Subbab
                </label>
            </form>
            <div class="gm-modal-actions">
                <button type="button" class="gm-btn gm-btn-outline" data-gm-close>
                    Batal
                </button>
                <button type="submit" class="gm-btn gm-btn-primary" id="gmSaveSubchapter" form="gmSubchapterForm">
                    Simpan Materi
                </button>
            </div>
        </div>
    </div>


    {{-- ======================================================
         05. MODAL KONFIRMASI HAPUS
         ====================================================== --}}
    <div class="gm-modal" id="gmDeleteModal" hidden>
        <div class="gm-modal-backdrop" data-gm-close></div>
        <div class="gm-modal-dialog gm-delete-dialog" role="dialog" aria-modal="true"
            aria-labelledby="gmDeleteTitle">
            <div class="gm-delete-icon">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            <h2 id="gmDeleteTitle">
                Hapus Materi?
            </h2>
            <p id="gmDeleteDescription">
                Apakah kamu yakin ingin menghapus
                materi ini?
            </p>
            <div class="gm-modal-actions">
                <button type="button" class="gm-btn gm-btn-outline" data-gm-close>
                    Batal
                </button>
                <button type="button" class="gm-btn gm-btn-danger" id="gmConfirmDelete">
                    Ya, Hapus
                </button>
            </div>
        </div>
    </div>


    {{-- ======================================================
         06. PESAN STATUS CRUD
         ====================================================== --}}
    <div class="gm-toast" id="gmToast" role="status" aria-live="polite" hidden></div>
</section>


{{-- ==========================================================
     07. STYLING KHUSUS EDITOR
     Hanya memengaruhi modal Subbab Guru.
     Tidak mengubah CSS Dashboard Siswa.
     ========================================================== --}}
<style>
    #gmSubchapterModal .gm-rich-editor {
        overflow: visible;
        background: transparent;
    }

    #gmSubchapterModal .gm-rich-toolbar {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 5px;
        padding: 10px 12px;
        border-bottom: 1px solid #dce9e2;
        background: #f5faf7;
    }

    #gmSubchapterModal .gm-rich-tool {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 36px;
        height: 36px;
        padding: 6px 9px;
        border: 1px solid transparent;
        border-radius: 7px;
        background: transparent;
        color: #19566a;
        font-family: inherit;
        font-size: 15px;
        cursor: pointer;
        transition:
            background .15s ease,
            border-color .15s ease;
    }

    #gmSubchapterModal .gm-rich-tool:hover,
    #gmSubchapterModal .gm-rich-tool:focus-visible {
        border-color: #b8dcc8;
        background: #e0f2e8;
        outline: none;
    }

    #gmSubchapterModal .gm-rich-tool-text {
        font-size: 13px;
        font-weight: 700;
    }

    #gmSubchapterModal .gm-rich-separator {
        width: 1px;
        height: 25px;
        margin: 0 5px;
        background: #cbded5;
    }

    #gmSubchapterModal .gm-rich-content {
        min-height: 150px;
        max-height: none;
        overflow-y: visible;
        padding: 10px 12px;
        border: 1px solid #ccdfdd;
        border-radius: 9px;
        outline: none;
        background: #fff;
        color: #075477;
        font-family: inherit;
        font-size: 11px;
        line-height: 1.7;
        overflow-wrap: anywhere;
        white-space: normal;
    }

    #gmSubchapterModal .gm-rich-content:focus {
        border-color: #65b69a;
        box-shadow: 0 0 0 3px rgba(101, 182, 154, .13);
    }

    #gmSubchapterModal .gm-rich-content:empty::before {
        content: attr(data-placeholder);
        color: #8299a2;
        pointer-events: none;
    }

    #gmSubchapterModal .gm-rich-content h2 {
        margin: 20px 0 11px;
        color: #085775;
        font-size: 22px;
        font-weight: 700;
        line-height: 1.4;
    }

    #gmSubchapterModal .gm-rich-content h3 {
        margin: 17px 0 9px;
        color: #085775;
        font-size: 18px;
        font-weight: 700;
    }

    #gmSubchapterModal .gm-rich-content p {
        margin: 0 0 14px;
    }

    #gmSubchapterModal .gm-rich-content ul,
    #gmSubchapterModal .gm-rich-content ol {
        margin: 10px 0 18px;
        padding-left: 28px;
    }

    #gmSubchapterModal .gm-rich-content li {
        margin-bottom: 7px;
    }

    #gmSubchapterModal .gm-rich-content strong,
    #gmSubchapterModal .gm-rich-content b {
        font-weight: 700;
    }

    #gmSubchapterModal .gm-rich-content em,
    #gmSubchapterModal .gm-rich-content i {
        font-style: italic;
    }

    #gmSubchapterModal .gm-rich-content u {
        text-decoration: underline;
    }

    #gmSubchapterModal .gm-rich-source {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        border: 0;
        overflow: hidden;
        opacity: 0;
        pointer-events: none;
    }

    #gmSubchapterModal .gm-editor-status {
        min-height: 20px;
        margin: 8px 0 0;
        color: #317b55;
        font-size: 12px;
    }

    @media (max-width: 767.98px) {
        #gmSubchapterModal .gm-rich-toolbar {
            padding: 8px;
        }

        #gmSubchapterModal .gm-rich-content {
            padding: 10px 12px;
            min-height: 150px;
            font-size: 11px;
        }
    }

    /* SALUANG: file picker Pengenalan Bab, native input & ID dipertahankan. */
    #gmChapterModal #gmChapterIntroductionImage.gm-chapter-intro-file {
        display: block;
        width: 100%;
        min-height: 58px;
        box-sizing: border-box;
        padding: 8px 12px;
        border: 1px solid #c8e1d9;
        border-radius: 12px;
        background: #fff;
        color: #527887;
        font: inherit;
        font-size: 13px;
        line-height: 1.5;
        cursor: pointer;
    }

    #gmChapterModal #gmChapterIntroductionImage.gm-chapter-intro-file::file-selector-button {
        min-height: 36px;
        margin-right: 12px;
        padding: 6px 14px;
        border: 1px solid #8dc4a1;
        border-radius: 10px;
        background: #dcf2e5;
        color: #176646;
        font: inherit;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
    }

    #gmChapterModal #gmChapterIntroductionImage.gm-chapter-intro-file:hover::file-selector-button {
        background: #ceeada;
    }

    #gmChapterModal #gmChapterIntroductionImage.gm-chapter-intro-file:focus-visible {
        outline: 3px solid #66b89c;
        outline-offset: 2px;
    }

    @media (max-width: 575px) {
        #gmChapterModal #gmChapterIntroductionImage.gm-chapter-intro-file {
            min-height: 54px;
            padding: 7px 10px;
            font-size: 13px;
        }

        #gmChapterModal #gmChapterIntroductionImage.gm-chapter-intro-file::file-selector-button {
            min-height: 34px;
            padding: 5px 11px;
            margin-right: 8px;
            font-size: 13px;
        }
    }
</style>
