
{{-- ==========================================================
     SALUANG - KELOLA MATERI GURU
     File: resources/views/guru/materi.blade.php

     PERBAIKAN:
     - Menghapus script editor ganda
     - Editor sepenuhnya diatur guru-materi.js
     - Mempertahankan seluruh ID CRUD Bab dan Subbab
     - Mempertahankan toolbar pemformatan teks
     - Tidak mengubah database ataupun navigasi Siswa
     ========================================================== --}}

<section
    class="gd-material-page"
    data-guru-section="materi"
    id="guruMaterialPage"
    hidden
>

    {{-- ======================================================
         01. DAFTAR BAB
         ====================================================== --}}

    <div id="guruMaterialList">

        <div class="gd-page-navigation">
            <button
                type="button"
                class="gd-nav-btn"
                data-guru-previous
            >
                <i class="bi bi-chevron-left"></i>
                Previous
            </button>

            <button
                type="button"
                class="gd-nav-btn gd-nav-btn-next"
                data-guru-next
            >
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

            <button
                type="button"
                class="gm-btn gm-btn-primary"
                id="gmAddChapter"
            >
                <i class="bi bi-plus-lg"></i>
                Tambah Bab
            </button>
        </div>

        <div
            class="gm-chapter-list"
            id="gmChapterList"
        >
            <div class="gm-empty">
                <i class="bi bi-journal-text"></i>
                <p>Memuat daftar Bab...</p>
            </div>
        </div>

        <div class="gd-page-navigation gd-navigation-bottom">
            <button
                type="button"
                class="gd-nav-btn"
                data-guru-previous
            >
                <i class="bi bi-chevron-left"></i>
                Previous
            </button>

            <button
                type="button"
                class="gd-nav-btn gd-nav-btn-next"
                data-guru-next
            >
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
            <button
                type="button"
                class="gm-btn gm-btn-outline"
                id="gmBackToChapters"
            >
                Kembali ke Kelola Materi
            </button>

            <button
                type="button"
                class="gm-btn gm-btn-outline"
                id="gmBackToHome"
            >
                <i class="bi bi-house-door"></i>
                Kembali ke Beranda
            </button>
        </div>

        <article class="gm-detail-card">

            <div class="gm-detail-top">
                <div>
                    <span
                        class="gm-chapter-label"
                        id="gmDetailNumber"
                    >
                        BAB
                    </span>

                    <h1 id="gmDetailTitle">
                        Detail Bab
                    </h1>
                </div>

                <div class="gm-detail-actions">
                    <button
                        type="button"
                        class="gm-btn gm-btn-outline"
                        id="gmEditChapter"
                    >
                        <i class="bi bi-pencil-square"></i>
                        Edit Bab
                    </button>

                    <button
                        type="button"
                        class="gm-btn gm-btn-danger"
                        id="gmDeleteChapter"
                    >
                        <i class="bi bi-trash3"></i>
                        Hapus Bab
                    </button>
                </div>
            </div>

            <p
                class="gm-detail-description"
                id="gmDetailDescription"
            ></p>

            {{-- PENGANTAR BAB --}}

            <div class="gm-detail-section">
                <h2>
                    <i class="bi bi-book-half"></i>
                    Pengantar Bab
                </h2>

                <p id="gmDetailIntroduction">
                    Belum ada pengantar Bab.
                </p>
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

            {{-- PERTANYAAN PEMANTIK --}}

            <div class="gm-detail-section">
                <h2>
                    <i class="bi bi-lightbulb"></i>
                    Pertanyaan Pemantik
                </h2>

                <p id="gmDetailTriggerQuestion">
                    Belum ada pertanyaan pemantik.
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

                <button
                    type="button"
                    class="gm-btn gm-btn-primary"
                    id="gmAddSubchapter"
                >
                    <i class="bi bi-plus-lg"></i>
                    Tambah Subbab
                </button>
            </div>

            <div
                class="gm-subchapter-list"
                id="gmSubchapterList"
            >
                <p>Memuat Subbab...</p>
            </div>

        </section>

    </div>


    {{-- ======================================================
         03. MODAL TAMBAH / EDIT BAB
         ====================================================== --}}

    <div
        class="gm-modal"
        id="gmChapterModal"
        hidden
    >

        <div
            class="gm-modal-backdrop"
            data-gm-close
        ></div>

        <div
            class="gm-modal-dialog"
            role="dialog"
            aria-modal="true"
            aria-labelledby="gmChapterModalTitle"
        >

            <div class="gm-modal-header">
                <h2 id="gmChapterModalTitle">
                    Tambah Bab
                </h2>

                <button
                    type="button"
                    class="gm-modal-close"
                    data-gm-close
                    aria-label="Tutup"
                >
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <form id="gmChapterForm">

                <input
                    type="hidden"
                    id="gmChapterId"
                >

                {{-- NOMOR BAB --}}

                <div class="gm-field">
                    <label for="gmChapterNumber">
                        Nomor Bab
                    </label>

                    <input
                        type="number"
                        id="gmChapterNumber"
                        min="1"
                        placeholder="Contoh: 1"
                        required
                    >
                </div>

                {{-- JUDUL BAB --}}

                <div class="gm-field">
                    <label for="gmChapterTitle">
                        Judul Bab
                    </label>

                    <input
                        type="text"
                        id="gmChapterTitle"
                        maxlength="255"
                        placeholder="Masukkan judul Bab"
                        required
                    >
                </div>

                {{-- DESKRIPSI BAB --}}

                <div class="gm-field">
                    <label for="gmChapterDescription">
                        Deskripsi Bab
                    </label>

                    <textarea
                        id="gmChapterDescription"
                        class="gm-multiline-input"
                        rows="4"
                        placeholder="Tuliskan gambaran umum Bab"
                    ></textarea>

                    <small>
                        Ringkasan yang menjelaskan isi
                        dan cakupan materi pada Bab ini.
                    </small>
                </div>

                <input type="hidden" id="gmChapterIntroduction">

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

                    <button
                        type="button"
                        class="gm-btn gm-btn-outline"
                        id="gmAddObjective"
                    >
                        <i class="bi bi-plus-lg"></i>
                        Tambah Tujuan Pembelajaran
                    </button>
                </div>

                {{-- PERTANYAAN PEMANTIK --}}

                <div class="gm-field">
                    <label for="gmChapterTriggerQuestion">
                        Pertanyaan Pemantik
                    </label>

                    <p class="gm-field-help">
                        Tuliskan satu pertanyaan pemantik.
                    </p>

                    <textarea
                        id="gmChapterTriggerQuestion"
                        class="gm-multiline-input"
                        rows="3"
                        placeholder="Tuliskan satu pertanyaan pemantik"
                    ></textarea>

                    <small>
                        Pertanyaan ini akan ditampilkan kepada Siswa pada halaman Pengenalan Bab.
                    </small>
                </div>

                {{-- PUBLIKASI BAB --}}

                <label class="gm-checkbox">
                    <input
                        type="checkbox"
                        id="gmChapterPublished"
                    >
                    Publikasikan Bab
                </label>

                <div class="gm-modal-actions">
                    <button
                        type="button"
                        class="gm-btn gm-btn-outline"
                        data-gm-close
                    >
                        Batal
                    </button>

                    <button
                        type="submit"
                        class="gm-btn gm-btn-primary"
                        id="gmSaveChapter"
                    >
                        Simpan Bab
                    </button>
                </div>

            </form>

        </div>
    </div>


    {{-- ======================================================
         04. MODAL TAMBAH / EDIT SUBBAB
         ====================================================== --}}

    <div
        class="gm-modal"
        id="gmSubchapterModal"
        hidden
    >

        <div
            class="gm-modal-backdrop"
            data-gm-close
        ></div>

        <div
            class="gm-modal-dialog gm-editor-modal-dialog"
            role="dialog"
            aria-modal="true"
            aria-labelledby="gmSubchapterModalTitle"
        >

            <div class="gm-modal-header">
                <h2 id="gmSubchapterModalTitle">
                    Tambah Subbab
                </h2>

                <button
                    type="button"
                    class="gm-modal-close"
                    data-gm-close
                    aria-label="Tutup"
                >
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <form id="gmSubchapterForm">

                <input
                    type="hidden"
                    id="gmSubchapterId"
                >

                <div class="gm-subchapter-fields">
                    <div class="gm-field">
                        <label for="gmSubchapterNumber">Nomor Subbab</label>
                        <input
                            type="number"
                            id="gmSubchapterNumber"
                            min="1"
                            placeholder="Contoh: 1"
                            required
                        >
                    </div>

                    <div class="gm-field">
                        <label for="gmSubchapterTitle">Judul Subbab</label>
                        <input
                            type="text"
                            id="gmSubchapterTitle"
                            maxlength="255"
                            placeholder="Contoh: Makhluk Hidup atau Benda Mati?"
                            required
                        >
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

                    <ol
                        class="gm-content-block-list"
                        id="gmContentBlockList"
                        aria-label="Pratinjau urutan blok materi"
                        aria-live="polite"
                    ></ol>
                    <p class="gm-content-block-empty" id="gmContentBlockEmpty">
                        Belum ada blok. Tambahkan blok teks, media, atau kesimpulan.
                    </p>
                    <p class="gm-content-block-placeholder" id="gmContentBlockPlaceholder" hidden>
                        Pilih Edit pada blok untuk mengubah isinya. Perubahan tetap di formulir sampai Simpan Materi ditekan.
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

                        <div
                            class="gm-rich-toolbar"
                            id="gmRichToolbar"
                            role="toolbar"
                            aria-label="Alat pemformatan materi"
                        >

                            {{-- BOLD --}}

                            <button
                                type="button"
                                class="gm-rich-tool"
                                data-gm-command="bold"
                                title="Tebal (Ctrl+B)"
                                aria-label="Tebal"
                            >
                                <strong>B</strong>
                            </button>

                            {{-- ITALIC --}}

                            <button
                                type="button"
                                class="gm-rich-tool"
                                data-gm-command="italic"
                                title="Miring (Ctrl+I)"
                                aria-label="Miring"
                            >
                                <em>I</em>
                            </button>

                            {{-- UNDERLINE --}}

                            <button
                                type="button"
                                class="gm-rich-tool"
                                data-gm-command="underline"
                                title="Garis bawah (Ctrl+U)"
                                aria-label="Garis bawah"
                            >
                                <u>U</u>
                            </button>

                            <span
                                class="gm-rich-separator"
                                aria-hidden="true"
                            ></span>

                            {{-- HEADING 2 --}}

                            <button
                                type="button"
                                class="gm-rich-tool gm-rich-tool-text"
                                data-gm-block="H2"
                                title="Judul bagian"
                                aria-label="Judul bagian"
                            >
                                H2
                            </button>

                            {{-- HEADING 3 --}}

                            <button
                                type="button"
                                class="gm-rich-tool gm-rich-tool-text"
                                data-gm-block="H3"
                                title="Subjudul"
                                aria-label="Subjudul"
                            >
                                H3
                            </button>

                            {{-- PARAGRAF --}}

                            <button
                                type="button"
                                class="gm-rich-tool gm-rich-tool-text"
                                data-gm-block="P"
                                title="Paragraf biasa"
                                aria-label="Paragraf biasa"
                            >
                                ¶
                            </button>

                            <span
                                class="gm-rich-separator"
                                aria-hidden="true"
                            ></span>

                            {{-- ORDERED LIST --}}

                            <button
                                type="button"
                                class="gm-rich-tool"
                                data-gm-command="insertOrderedList"
                                title="Daftar bernomor"
                                aria-label="Daftar bernomor"
                            >
                                <i class="bi bi-list-ol"></i>
                            </button>

                            {{-- UNORDERED LIST --}}

                            <button
                                type="button"
                                class="gm-rich-tool"
                                data-gm-command="insertUnorderedList"
                                title="Daftar bullet"
                                aria-label="Daftar bullet"
                            >
                                <i class="bi bi-list-ul"></i>
                            </button>

                            <span
                                class="gm-rich-separator"
                                aria-hidden="true"
                            ></span>

                            {{-- REMOVE FORMATTING --}}

                            <button
                                type="button"
                                class="gm-rich-tool"
                                data-gm-command="removeFormat"
                                title="Hapus pemformatan"
                                aria-label="Hapus pemformatan"
                            >
                                <i class="bi bi-eraser"></i>
                            </button>

                        </div>

                        {{-- AREA EDITOR YANG TERLIHAT --}}

                        <textarea
                            id="gmRichContentEditor"
                            class="gm-rich-content gm-multiline-input"
                            aria-label="Isi materi pembelajaran"
                            rows="8"
                            placeholder="Mulai tulis materi di sini. Gunakan toolbar untuk mengatur judul, paragraf, dan daftar..."
                        ></textarea>

                    </div>

                    {{-- TEXTAREA UNTUK KOMPATIBILITAS API --}}

                    <textarea
                        id="gmSubchapterContent"
                        rows="8"
                        class="gm-rich-source"
                        aria-label="Data sumber materi"
                        tabindex="-1"
                        placeholder="Tuliskan isi materi pembelajaran"
                    ></textarea>

                    <small>
                        Blok teks yang ingin diformat, lalu
                        pilih Bold, Italic, Underline, Heading,
                        daftar angka, atau bullet.
                    </small>

                    <p
                        class="gm-editor-status"
                        id="gmEditorStatus"
                        role="status"
                        aria-live="polite"
                    ></p>
                </div>

                <div class="gm-content-block-callout" id="gmCalloutEditor" hidden>
                    <div class="gm-field">
                        <label for="gmCalloutTitle">Judul informasi <span>(opsional)</span></label>
                        <input
                            type="text"
                            id="gmCalloutTitle"
                            maxlength="160"
                            placeholder="Contoh: Kesimpulan"
                        >
                    </div>
                    <div class="gm-field">
                        <label for="gmCalloutContent">Isi informasi / kesimpulan</label>
                        <textarea
                            id="gmCalloutContent"
                            class="gm-multiline-input"
                            rows="3"
                            maxlength="50000"
                            placeholder="Tuliskan ringkasan singkat untuk Siswa"
                        ></textarea>
                    </div>
                </div>

                <div class="gm-content-block-media-editor" id="gmBlockMediaEditor" hidden>
                    <p id="gmBlockMediaHelp" class="gm-field-help"></p>
                    <div class="gm-field">
                        <label for="gmBlockMediaSource">Sumber media</label>
                        <select id="gmBlockMediaSource"></select>
                    </div>
                    <div class="gm-field" id="gmBlockExistingMediaField" hidden>
                        <label for="gmBlockExistingMedia">Pilih media Subbab</label>
                        <select id="gmBlockExistingMedia"></select>
                    </div>
                    <div class="gm-field" id="gmBlockUploadField" hidden>
                        <label for="gmBlockMediaFile">Pilih berkas</label>
                        <input type="file" id="gmBlockMediaFile">
                        <small id="gmBlockMediaFileHelp"></small>
                    </div>
                    <div class="gm-field" id="gmBlockYoutubeField" hidden>
                        <label for="gmBlockYoutubeUrl">Tautan YouTube</label>
                        <input
                            type="url"
                            id="gmBlockYoutubeUrl"
                            maxlength="2048"
                            placeholder="https://www.youtube.com/watch?v=..."
                        >
                    </div>
                    <div class="gm-field">
                        <label for="gmBlockMediaTitle">Judul media <span>(opsional)</span></label>
                        <input
                            type="text"
                            id="gmBlockMediaTitle"
                            maxlength="160"
                            placeholder="Judul singkat media"
                        >
                    </div>
                    <div class="gm-field">
                        <label for="gmBlockMediaCaption">Keterangan media <span>(opsional)</span></label>
                        <textarea
                            id="gmBlockMediaCaption"
                            class="gm-multiline-input"
                            rows="2"
                            maxlength="500"
                            placeholder="Keterangan singkat"
                        ></textarea>
                    </div>
                </div>

                {{-- MEDIA GAMBAR DAN VIDEO --}}
                <details class="gm-legacy-media-details">
                    <summary>Media Pembelajaran</summary>
                    <p class="gm-legacy-media-help">
                        Unggah atau pilih gambar dan video untuk mendukung materi pembelajaran pada Subbab ini.
                    </p>
                    <section class="gm-media-fields" aria-labelledby="gmMediaHeading">
                    <div class="gm-media-heading">
                        <div class="gm-media-heading-icon" aria-hidden="true">
                            <i class="bi bi-collection-play"></i>
                        </div>
                        <div>
                            <h3 id="gmMediaHeading">Media Pembelajaran</h3>
                            <p>Tambahkan ilustrasi atau video pendukung untuk Subbab ini.</p>
                        </div>
                    </div>

                    <section class="gm-media-panel" aria-labelledby="gmImageHeading">
                        <div class="gm-media-panel-heading">
                            <span class="gm-media-type-icon gm-media-image-icon" aria-hidden="true">
                                <i class="bi bi-image"></i>
                            </span>
                            <div>
                                <h4 id="gmImageHeading">Gambar</h4>
                                <p>JPG, PNG, atau WebP · maksimal 5 MB</p>
                            </div>
                        </div>

                        <input type="hidden" id="gmImageAction" value="none">
                        <div class="gm-media-choices" role="radiogroup" aria-labelledby="gmImageHeading">
                            <label class="gm-media-choice">
                                <input type="radio" name="gmImageActionChoice" value="none" data-media-choice="image">
                                <span class="gm-media-choice-icon"><i class="bi bi-dash-circle"></i></span>
                                <span class="gm-media-choice-copy"><strong>Tanpa gambar</strong><small>Tidak menampilkan gambar</small></span>
                            </label>
                            <label class="gm-media-choice" data-needs-image>
                                <input type="radio" name="gmImageActionChoice" value="keep" data-media-choice="image">
                                <span class="gm-media-choice-icon"><i class="bi bi-check2-circle"></i></span>
                                <span class="gm-media-choice-copy"><strong>Gunakan saat ini</strong><small>Pertahankan gambar aktif</small></span>
                            </label>
                            <label class="gm-media-choice" data-image-library-choice>
                                <input type="radio" name="gmImageActionChoice" value="library" data-media-choice="image">
                                <span class="gm-media-choice-icon"><i class="bi bi-images"></i></span>
                                <span class="gm-media-choice-copy"><strong>Galeri SALUANG</strong><small>Pilih ilustrasi tersedia</small></span>
                            </label>
                            <label class="gm-media-choice">
                                <input type="radio" name="gmImageActionChoice" value="upload" data-media-choice="image">
                                <span class="gm-media-choice-icon"><i class="bi bi-cloud-arrow-up"></i></span>
                                <span class="gm-media-choice-copy"><strong>Upload baru</strong><small>Pilih gambar dari perangkat</small></span>
                            </label>
                            <label class="gm-media-choice gm-media-choice-danger" data-needs-image>
                                <input type="radio" name="gmImageActionChoice" value="remove" data-media-choice="image">
                                <span class="gm-media-choice-icon"><i class="bi bi-trash3"></i></span>
                                <span class="gm-media-choice-copy"><strong>Hapus gambar</strong><small>Hapus saat perubahan disimpan</small></span>
                            </label>
                        </div>

                        <figure class="gm-media-preview" id="gmImagePreview" hidden>
                            <img id="gmImagePreviewImage" alt="Pratinjau gambar Subbab">
                            <figcaption id="gmImagePreviewCaption"></figcaption>
                        </figure>

                        <div class="gm-media-gallery" id="gmImageGallery" data-image-action="library" hidden></div>
                        <input type="hidden" id="gmImageLibrary">

                        <div class="gm-media-field" data-image-action="upload" hidden>
                            <label for="gmImageFile">Pilih gambar</label>
                            <input type="file" id="gmImageFile" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                            <small>Pastikan seluruh ilustrasi terlihat jelas dan berukuran maksimal 5 MB.</small>
                        </div>

                        <p class="gm-media-action-note" data-image-action="remove" hidden>
                            Gambar aktif akan dihapus setelah Subbab berhasil disimpan.
                        </p>

                        <div class="gm-media-metadata" data-media-metadata="image">
                            <div class="gm-media-field">
                                <label for="gmImageTitle">Judul gambar <span>(opsional)</span></label>
                                <input type="text" id="gmImageTitle" maxlength="160" placeholder="Contoh: Ekosistem sungai">
                            </div>
                            <div class="gm-media-field">
                                <label for="gmImageCaption">Keterangan gambar <span>(opsional)</span></label>
                                <textarea id="gmImageCaption" class="gm-multiline-input" rows="2" maxlength="500" placeholder="Jelaskan singkat gambar ini"></textarea>
                            </div>
                        </div>
                    </section>

                    <section class="gm-media-panel" aria-labelledby="gmVideoHeading">
                        <div class="gm-media-panel-heading">
                            <span class="gm-media-type-icon gm-media-video-icon" aria-hidden="true">
                                <i class="bi bi-play-btn"></i>
                            </span>
                            <div>
                                <h4 id="gmVideoHeading">Video</h4>
                                <p>Upload MP4 maksimal 30 MB atau gunakan video YouTube.</p>
                            </div>
                        </div>

                        <input type="hidden" id="gmVideoAction" value="none">
                        <div class="gm-media-choices" role="radiogroup" aria-labelledby="gmVideoHeading">
                            <label class="gm-media-choice">
                                <input type="radio" name="gmVideoActionChoice" value="none" data-media-choice="video">
                                <span class="gm-media-choice-icon"><i class="bi bi-dash-circle"></i></span>
                                <span class="gm-media-choice-copy"><strong>Tanpa video</strong><small>Tidak menampilkan video</small></span>
                            </label>
                            <label class="gm-media-choice" data-needs-video>
                                <input type="radio" name="gmVideoActionChoice" value="keep" data-media-choice="video">
                                <span class="gm-media-choice-icon"><i class="bi bi-check2-circle"></i></span>
                                <span class="gm-media-choice-copy"><strong>Gunakan saat ini</strong><small>Pertahankan video aktif</small></span>
                            </label>
                            <label class="gm-media-choice">
                                <input type="radio" name="gmVideoActionChoice" value="upload" data-media-choice="video">
                                <span class="gm-media-choice-icon"><i class="bi bi-cloud-arrow-up"></i></span>
                                <span class="gm-media-choice-copy"><strong>Upload MP4</strong><small>Maksimal 30 MB</small></span>
                            </label>
                            <label class="gm-media-choice">
                                <input type="radio" name="gmVideoActionChoice" value="youtube" data-media-choice="video">
                                <span class="gm-media-choice-icon"><i class="bi bi-youtube"></i></span>
                                <span class="gm-media-choice-copy"><strong>Link YouTube</strong><small>Tempel tautan video</small></span>
                            </label>
                            <label class="gm-media-choice gm-media-choice-danger" data-needs-video>
                                <input type="radio" name="gmVideoActionChoice" value="remove" data-media-choice="video">
                                <span class="gm-media-choice-icon"><i class="bi bi-trash3"></i></span>
                                <span class="gm-media-choice-copy"><strong>Hapus video</strong><small>Hapus saat perubahan disimpan</small></span>
                            </label>
                            <label class="gm-media-choice" data-video-library-choice>
                                <input type="radio" name="gmVideoActionChoice" value="library" data-media-choice="video">
                                <span class="gm-media-choice-icon"><i class="bi bi-collection-play"></i></span>
                                <span class="gm-media-choice-copy"><strong>Video tersimpan</strong><small>Pilih dari pustaka SALUANG</small></span>
                            </label>
                        </div>

                        <div class="gm-media-current-video" id="gmVideoCurrentPreview" hidden></div>
                        <div class="gm-media-gallery gm-media-video-gallery" id="gmVideoGallery" data-video-action="library" hidden></div>
                        <input type="hidden" id="gmVideoLibrary">

                        <div class="gm-media-field" data-video-action="upload" hidden>
                            <label for="gmVideoFile">Pilih video MP4</label>
                            <input type="file" id="gmVideoFile" accept=".mp4,video/mp4">
                            <small>Video akan ditampilkan dengan kontrol pemutar standar dan tanpa autoplay.</small>
                        </div>

                        <div class="gm-media-field" data-video-action="youtube" hidden>
                            <label for="gmYoutubeUrl">Tautan YouTube</label>
                            <input type="url" id="gmYoutubeUrl" maxlength="2048" placeholder="https://www.youtube.com/watch?v=...">
                            <small>Tempel link video YouTube; tidak perlu memasukkan kode iframe.</small>
                        </div>

                        <p class="gm-media-action-note" data-video-action="remove" hidden>
                            Video aktif akan dihapus setelah Subbab berhasil disimpan.
                        </p>

                        <div class="gm-media-metadata" data-media-metadata="video">
                            <div class="gm-media-field">
                                <label for="gmVideoTitle">Judul video <span>(opsional)</span></label>
                                <input type="text" id="gmVideoTitle" maxlength="160" placeholder="Contoh: Mengenal ekosistem sungai">
                            </div>
                            <div class="gm-media-field">
                                <label for="gmVideoCaption">Keterangan video <span>(opsional)</span></label>
                                <textarea id="gmVideoCaption" class="gm-multiline-input" rows="2" maxlength="500" placeholder="Tuliskan keterangan singkat"></textarea>
                            </div>
                        </div>
                    </section>
                    </section>
                </details>

                {{-- PUBLIKASI SUBBAB --}}

                <label class="gm-checkbox">
                    <input
                        type="checkbox"
                        id="gmSubchapterPublished"
                    >
                    Publikasikan Subbab
                </label>

            </form>

            <div class="gm-modal-actions">
                <button
                    type="button"
                    class="gm-btn gm-btn-outline"
                    data-gm-close
                >
                    Batal
                </button>

                <button
                    type="submit"
                    class="gm-btn gm-btn-primary"
                    id="gmSaveSubchapter"
                    form="gmSubchapterForm"
                >
                    Simpan Materi
                </button>
            </div>

        </div>
    </div>


    {{-- ======================================================
         05. MODAL KONFIRMASI HAPUS
         ====================================================== --}}

    <div
        class="gm-modal"
        id="gmDeleteModal"
        hidden
    >

        <div
            class="gm-modal-backdrop"
            data-gm-close
        ></div>

        <div
            class="gm-modal-dialog gm-delete-dialog"
            role="dialog"
            aria-modal="true"
            aria-labelledby="gmDeleteTitle"
        >

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
                <button
                    type="button"
                    class="gm-btn gm-btn-outline"
                    data-gm-close
                >
                    Batal
                </button>

                <button
                    type="button"
                    class="gm-btn gm-btn-danger"
                    id="gmConfirmDelete"
                >
                    Ya, Hapus
                </button>
            </div>

        </div>
    </div>


    {{-- ======================================================
         06. PESAN STATUS CRUD
         ====================================================== --}}

    <div
        class="gm-toast"
        id="gmToast"
        role="status"
        aria-live="polite"
        hidden
    ></div>

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
</style>

{{-- ==========================================================
     SELESAI

     Tidak ada script editor di file Blade ini.

     Editor dijalankan oleh:
     public/js/guru-materi.js

     Jangan menambahkan lagi script editor lama
     agar HTML tidak berubah menjadi teks literal.
     ========================================================== --}}
