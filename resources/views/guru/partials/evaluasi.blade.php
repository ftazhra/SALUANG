{{-- =====================================================
     SALUANG — HALAMAN KELOLA EVALUASI GURU
     File: resources/views/guru/partials/evaluasi.blade.php
     ===================================================== --}}
<section class="ge-page" id="guruEvaluationPage" data-guru-section="evaluasi" hidden>
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
    <div class="ge-panel">
        {{-- JUDUL --}}

        <div class="ge-heading">
            <div>
                <h1>Kelola Evaluasi</h1>
                <p>
                    Kelola 15 soal Evaluasi Akhir yang akan
                    dikerjakan oleh Siswa.
                </p>
            </div>
            <button type="button" class="ge-btn ge-btn-primary" id="geAddQuestion">
                <i class="bi bi-plus-lg"></i>
                Tambah Soal
            </button>
        </div>
        {{-- RINGKASAN --}}

        <div class="ge-summary">
            <div class="ge-summary-card">
                <i class="bi bi-list-ol"></i>
                <span>Jumlah Soal</span>
                <strong>
                    <span id="geTotalQuestions">0</span>/15
                </strong>
            </div>
            <div class="ge-summary-card">
                <i class="bi bi-check-circle"></i>
                <span>Dipublikasikan</span>
                <strong id="gePublishedQuestions">0</strong>
            </div>
            <div class="ge-summary-card">
                <i class="bi bi-clock-history"></i>
                <span>Durasi Evaluasi</span>
                <strong>45 Menit</strong>
            </div>
            <div class="ge-summary-card">
                <i class="bi bi-award"></i>
                <span>Nilai Ketuntasan</span>
                <strong>70</strong>
            </div>
        </div>
        {{-- PETUNJUK TIPE SOAL --}}

        <div class="ge-info">
            <i class="bi bi-info-circle"></i>
            <div class="ge-info-content">
                <strong>Pembagian Jenis Soal Evaluasi</strong>
                <ul class="ge-info-list">
                    <li>
                        <strong>Nomor 1–5:</strong>
                        Pilihan Ganda A–D
                    </li>
                    <li>
                        <strong>Nomor 6–10:</strong>
                        Drag-and-Drop Gambar
                    </li>
                    <li>
                        <strong>Nomor 11–15:</strong>
                        Menghubungkan Pasangan Gambar
                    </li>
                </ul>
            </div>
        </div>
        {{-- DAFTAR NOMOR SOAL --}}

        <div class="ge-section-heading">
            <div>
                <h2>Daftar Soal Evaluasi</h2>
                <p>
                    Pilih nomor soal untuk melihat,
                    mengedit, atau menghapusnya.
                </p>
            </div>
        </div>
        <div class="ge-question-grid" id="geQuestionGrid">
            {{-- Diisi JavaScript --}}

        </div>
        {{-- DETAIL SOAL --}}

        <div class="ge-question-detail" id="geQuestionDetail" hidden>
            <div class="ge-detail-header">
                <div>
                    <span class="ge-detail-number" id="geDetailNumber"></span>
                    <h2 id="geDetailTitle"></h2>
                    <p id="geDetailType"></p>
                </div>
                <span class="ge-status" id="geDetailStatus"></span>
            </div>
            <div class="ge-detail-body" id="geDetailContent">
                {{-- Dibuat JavaScript --}}

            </div>
            <div class="ge-detail-actions">
                <button type="button" class="ge-btn ge-btn-outline" id="gePreviewQuestion">
                    <i class="bi bi-eye"></i>
                    Pratinjau
                </button>
                <button type="button" class="ge-btn ge-btn-primary" id="geEditQuestion">
                    <i class="bi bi-pencil-square"></i>
                    Edit Soal
                </button>
                <button type="button" class="ge-btn ge-btn-danger" id="geDeleteQuestion">
                    <i class="bi bi-trash3"></i>
                    Hapus Soal
                </button>
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
    {{-- =========================================
         MODAL TAMBAH / EDIT SOAL
         ========================================= --}}
    <div class="ge-modal" id="geQuestionModal" hidden>
        <div class="ge-modal-backdrop" data-ge-close></div>
        <div class="ge-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="geQuestionModalTitle">
            <div class="ge-modal-header">
                <div>
                    <h2 id="geQuestionModalTitle">
                        Tambah Soal Evaluasi
                    </h2>
                    <p>
                        Isi pertanyaan dan kunci jawaban
                        sesuai jenis soal.
                    </p>
                </div>
                <button type="button" class="ge-modal-close" data-ge-close aria-label="Tutup">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <form id="geQuestionForm">
                <input type="hidden" id="geQuestionId">
                <div class="ge-field-grid">
                    <div class="ge-field">
                        <label for="geQuestionNumber">
                            Nomor Soal
                        </label>
                        <select id="geQuestionNumber" required>
                            <option value="">Pilih Nomor</option>
                            @for ($nomor = 1; $nomor <= 15; $nomor++)
                                <option value="{{ $nomor }}">
                                    Soal {{ $nomor }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div class="ge-field">
                        <label for="geQuestionType">
                            Jenis Soal
                        </label>
                        <select id="geQuestionType" disabled>
                            <option value="">
                                Pilih nomor soal terlebih dahulu
                            </option>
                            <option value="pilihan_ganda">
                                Pilihan Ganda
                            </option>
                            <option value="drag_drop">
                                Drag-and-Drop
                            </option>
                            <option value="menghubungkan">
                                Menghubungkan Pasangan
                            </option>
                        </select>
                    </div>
                </div>
                <div class="ge-field">
                    <label for="geQuestionText">
                        Pertanyaan
                    </label>
                    <textarea id="geQuestionText" rows="4" placeholder="Tuliskan pertanyaan evaluasi..." required></textarea>
                </div>
                {{-- PILIHAN GANDA --}}

                <div class="ge-type-fields" id="geMultipleChoiceFields" hidden>
                    <h3>Opsi Pilihan Ganda</h3>
                    @foreach (['A', 'B', 'C', 'D'] as $letter)
                        <div class="ge-field">
                            <label for="geOption{{ $letter }}">
                                Pilihan {{ $letter }}
                            </label>
                            <input type="text" id="geOption{{ $letter }}"
                                placeholder="Isi pilihan {{ $letter }}">
                        </div>
                    @endforeach
                    <div class="ge-field">
                        <label for="geCorrectChoice">
                            Kunci Jawaban
                        </label>
                        <select id="geCorrectChoice">
                            <option value="">Pilih Kunci</option>
                            @foreach (['A', 'B', 'C', 'D'] as $letter)
                                <option value="{{ $letter }}">
                                    {{ $letter }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                {{-- SOAL INTERAKTIF --}}

                <div class="ge-type-fields" id="geInteractiveFields" hidden>
                    <h3 id="geInteractiveTitle">
                        Pengaturan Soal Bergambar
                    </h3>
                    <p class="ge-field-help" id="geInteractiveHelp">
                        Tambahkan gambar dan tentukan jawaban yang benar.
                    </p>
                    {{-- KATEGORI / TARGET JAWABAN --}}

                    <div id="geTargetsSection" class="ge-visual-section">
                        <div class="ge-visual-heading">
                            <h4>Kategori / Tujuan Pasangan</h4>
                            <button type="button" class="ge-btn ge-btn-outline" id="geAddTarget">
                                <i class="bi bi-plus-lg"></i>
                                Tambah Kategori
                            </button>
                        </div>
                        <p class="ge-field-help">
                            Contoh: Biotik dan Abiotik, atau Hewan dan Tumbuhan.
                        </p>
                        <div id="geTargetsList"></div>
                    </div>
                    {{-- DAFTAR GAMBAR --}}

                    <div class="ge-visual-section">
                        <div class="ge-visual-heading">
                            <h4 id="geItemsHeading">Daftar Gambar</h4>
                            <button type="button" class="ge-btn ge-btn-outline" id="geAddImage">
                                <i class="bi bi-plus-lg"></i>
                                Tambah Gambar
                            </button>
                        </div>
                        <p class="ge-field-help" id="geImagesHelp">
                            Pilih gambar dan tentukan pasangan jawaban yang benar.
                        </p>
                        <div id="geImagesList"></div>
                    </div>
                    {{-- DATA TERSEMBUNYI UNTUK KOMPATIBILITAS --}}

                    <input type="hidden" id="geContentJson">
                    <input type="hidden" id="geAnswerJson">
                </div>
                {{-- PEMBAHASAN --}}

                <div class="ge-field">
                    <label for="geExplanation">
                        Pembahasan
                    </label>
                    <textarea id="geExplanation" rows="4" placeholder="Penjelasan jawaban yang benar"></textarea>
                </div>
                {{-- PUBLIKASI --}}

                <label class="ge-publish-field">
                    <input type="checkbox" id="gePublished">
                    <span>Publikasikan soal evaluasi ini</span>
                </label>
                <div class="ge-form-actions">
                    <button type="button" class="ge-btn ge-btn-outline" data-ge-close>
                        Batal
                    </button>
                    <button type="submit" class="ge-btn ge-btn-primary" id="geSaveQuestion">
                        <i class="bi bi-check-lg"></i>
                        Simpan Soal
                    </button>
                </div>
            </form>
        </div>
    </div>
    {{-- =========================================
         MODAL PRATINJAU
         ========================================= --}}
    <div class="ge-modal" id="gePreviewModal" hidden>
        <div class="ge-modal-backdrop" data-ge-close></div>
        <div class="ge-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="gePreviewTitle">
            <div class="ge-modal-header">
                <h2 id="gePreviewTitle">Pratinjau Soal</h2>
                <button type="button" class="ge-modal-close" data-ge-close aria-label="Tutup">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div id="gePreviewContent"></div>
            <div class="ge-form-actions">
                <button type="button" class="ge-btn ge-btn-outline" data-ge-close>
                    Tutup
                </button>
            </div>
        </div>
    </div>
    {{-- =========================================
         MODAL KONFIRMASI HAPUS
         ========================================= --}}
    <div class="ge-modal" id="geDeleteModal" hidden>
        <div class="ge-modal-backdrop" data-ge-close></div>
        <div class="ge-modal-dialog ge-modal-dialog-small" role="dialog" aria-modal="true"
            aria-labelledby="geDeleteTitle">
            <div class="ge-delete-icon">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            <h2 id="geDeleteTitle">Hapus Soal?</h2>
            <p>
                Soal evaluasi yang dihapus tidak dapat
                dikembalikan.
            </p>
            <div class="ge-form-actions">
                <button type="button" class="ge-btn ge-btn-outline" data-ge-close>
                    Batal
                </button>
                <button type="button" class="ge-btn ge-btn-danger" id="geConfirmDelete">
                    Ya, Hapus
                </button>
            </div>
        </div>
    </div>
    {{-- NOTIFIKASI --}}

    <div class="ge-toast" id="geToast" hidden></div>
</section>
