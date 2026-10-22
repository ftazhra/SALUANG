{{-- =========================================================
     SALUANG — KELOLA SOAL KUIS GURU
     File: resources/views/guru/partials/kuis.blade.php
     ========================================================= --}}

<section class="gq-page" data-guru-section="kuis" id="guruQuizPage" hidden>


    {{-- 01. NAVIGASI ATAS HALAMAN KUIS --}}
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

    {{-- 02. KARTU UTAMA --}}
    <div class="gq-panel">

        <div class="gq-heading">
            <div>
                <h1>Kelola Soal Kuis</h1>
                <p>Pilih Bab dan Subbab untuk mengelola soal kuis.</p>
            </div>
        </div>

        {{-- 03. PILIH BAB DAN SUBBAB --}}
        <div class="gq-filter-grid">

            <div class="gq-field">
                <label for="gqChapterSelect">Bab</label>
                <select id="gqChapterSelect">
                    <option value="">Memuat daftar Bab...</option>
                </select>
            </div>

            <div class="gq-field">
                <label for="gqSubchapterSelect">Subbab</label>
                <select id="gqSubchapterSelect" disabled>
                    <option value="">Pilih Bab terlebih dahulu</option>
                </select>
            </div>

        </div>

        {{-- 04. AREA SOAL --}}
        <div id="gqQuestionWorkspace" hidden>

            <div class="gq-question-heading">
                <h2 id="gqSelectedSubchapterTitle">
                    Pilih Subbab
                </h2>

                <button type="button" class="gq-btn gq-btn-primary" id="gqAddQuestion">
                    <i class="bi bi-plus-lg"></i>
                    Tambah Nomor Soal
                </button>
            </div>

            {{-- TOMBOL SOAL 1, SOAL 2, DST. --}}
            <div class="gq-question-tabs" id="gqQuestionTabs" role="group" aria-label="Pilih nomor soal">
            </div>

            {{-- KARTU SOAL YANG DIPILIH --}}
            <div id="gqQuestionDetail">
                <div class="gq-empty">
                    <i class="bi bi-card-checklist"></i>
                    <p>Belum ada soal di Subbab ini.</p>
                </div>
            </div>

        </div>

        {{-- INFORMASI SAAT BELUM MEMILIH SUBBAB --}}
        <div class="gq-empty" id="gqInitialEmpty">
            <i class="bi bi-journal-check"></i>
            <p>Pilih Bab dan Subbab untuk melihat soal kuis.</p>
        </div>

    </div>

    {{-- 05. NAVIGASI HALAMAN DASHBOARD --}}
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


    {{-- =====================================================
         06. MODAL TAMBAH / EDIT SOAL
         ===================================================== --}}
    <div class="gq-modal" id="gqQuestionModal" hidden>

        <div class="gq-modal-backdrop" data-gq-close></div>

        <div class="gq-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="gqQuestionModalTitle">

            <div class="gq-modal-header">
                <h2 id="gqQuestionModalTitle">Tambah Soal</h2>

                <button type="button" class="gq-close-button" data-gq-close aria-label="Tutup formulir">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <form id="gqQuestionForm">

                <input type="hidden" id="gqQuestionId">

                <div class="gq-field">
                    <label for="gqQuestionNumber">Nomor Soal</label>
                    <input type="number" id="gqQuestionNumber" min="1" required placeholder="Contoh: 1">
                </div>

                <div class="gq-field">
                    <label for="gqQuestionText">Pertanyaan</label>
                    <textarea id="gqQuestionText" rows="4" required placeholder="Tulis pertanyaan kuis"></textarea>
                </div>

                <div class="gq-options-grid">

                    <div class="gq-field">
                        <label for="gqOptionA">Pilihan A</label>
                        <textarea id="gqOptionA" rows="2" required placeholder="Jawaban A"></textarea>
                    </div>

                    <div class="gq-field">
                        <label for="gqOptionB">Pilihan B</label>
                        <textarea id="gqOptionB" rows="2" required placeholder="Jawaban B"></textarea>
                    </div>

                    <div class="gq-field">
                        <label for="gqOptionC">Pilihan C</label>
                        <textarea id="gqOptionC" rows="2" required placeholder="Jawaban C"></textarea>
                    </div>

                    <div class="gq-field">
                        <label for="gqOptionD">Pilihan D</label>
                        <textarea id="gqOptionD" rows="2" required placeholder="Jawaban D"></textarea>
                    </div>

                </div>

                <div class="gq-field">
                    <label for="gqCorrectAnswer">Kunci Jawaban</label>
                    <select id="gqCorrectAnswer" required>
                        <option value="">Pilih kunci jawaban</option>
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="D">D</option>
                    </select>
                </div>

                <div class="gq-field">
                    <label for="gqExplanation">Pembahasan</label>
                    <textarea id="gqExplanation" rows="4" placeholder="Tuliskan pembahasan jawaban"></textarea>
                </div>

                <label class="gq-checkbox">
                    <input type="checkbox" id="gqPublished">
                    Publikasikan soal untuk Siswa
                </label>

                <div class="gq-modal-actions">
                    <button type="button" class="gq-btn gq-btn-outline" data-gq-close>
                        Batal
                    </button>

                    <button type="submit" class="gq-btn gq-btn-primary" id="gqSaveQuestion">
                        Simpan Soal
                    </button>
                </div>

            </form>
        </div>
    </div>


    {{-- =====================================================
         07. MODAL PRATINJAU SOAL
         ===================================================== --}}
    <div class="gq-modal" id="gqPreviewModal" hidden>

        <div class="gq-modal-backdrop" data-gq-close></div>

        <div class="gq-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="gqPreviewTitle">

            <div class="gq-modal-header">
                <h2 id="gqPreviewTitle">Pratinjau Soal</h2>

                <button type="button" class="gq-close-button" data-gq-close aria-label="Tutup pratinjau">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div id="gqPreviewContent">
                <p>Memuat pratinjau soal...</p>
            </div>

            <div class="gq-modal-actions">
                <button type="button" class="gq-btn gq-btn-outline" data-gq-close>
                    Tutup
                </button>
            </div>
        </div>
    </div>


    {{-- =====================================================
         08. MODAL KONFIRMASI HAPUS
         ===================================================== --}}
    <div class="gq-modal" id="gqDeleteModal" hidden>

        <div class="gq-modal-backdrop" data-gq-close></div>

        <div class="gq-modal-dialog gq-delete-dialog" role="dialog" aria-modal="true"
            aria-labelledby="gqDeleteTitle">

            <div class="gq-delete-icon">
                <i class="bi bi-exclamation-triangle"></i>
            </div>

            <h2 id="gqDeleteTitle">Hapus Soal?</h2>

            <p>
                Apakah kamu yakin ingin menghapus soal ini?
                Tindakan ini tidak dapat dibatalkan.
            </p>

            <div class="gq-modal-actions">
                <button type="button" class="gq-btn gq-btn-outline" data-gq-close>
                    Batal
                </button>

                <button type="button" class="gq-btn gq-btn-danger" id="gqConfirmDelete">
                    Ya, Hapus Soal
                </button>
            </div>
        </div>
    </div>


    {{-- =====================================================
         09. PESAN HASIL CRUD
         ===================================================== --}}
    <div class="gq-toast" id="gqToast" role="status" aria-live="polite" hidden>
    </div>

</section>
