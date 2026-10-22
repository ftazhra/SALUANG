{{-- =====================================================
     SALUANG — NILAI SISWA GURU
     File: resources/views/guru/partials/nilai.blade.php
     ===================================================== --}}

<section class="gn-page" id="guruNilaiPage" data-guru-section="nilai" hidden>

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

    {{-- PANEL NILAI SISWA --}}
    <div class="gn-panel">

        <div class="gn-heading">
            <div>
                <h1>Nilai Siswa</h1>
                <p>
                    Pantau hasil belajar, ketuntasan, dan
                    perkembangan nilai siswa.
                </p>
            </div>

            {{-- TOMBOL UNDUH --}}
            <div class="gn-download-actions">


                <button type="button" class="gn-btn gn-btn-pdf" id="gnDownloadPdf">
                    <i class="bi bi-file-earmark-pdf"></i>
                    Unduh PDF
                </button>


                <button type="button" class="gn-btn gn-btn-excel" id="gnDownloadExcel">
                    <i class="bi bi-file-earmark-excel"></i>
                    Unduh Excel
                </button>

            </div>
        </div>

        {{-- PENCARIAN DAN FILTER --}}
        <div class="gn-filters">

            <div class="gn-field">
                <label for="gnSearchStudent">
                    Cari Nama Siswa
                </label>
                <input type="search" id="gnSearchStudent" placeholder="Cari nama atau NISN siswa">
            </div>

            <div class="gn-field">
                <label for="gnClassFilter">Kelas</label>
                <select id="gnClassFilter">
                    <option value="">Semua Kelas</option>
                </select>
            </div>

            <div class="gn-field">
                <label for="gnCompletionFilter">
                    Ketuntasan
                </label>
                <select id="gnCompletionFilter">
                    <option value="">Semua</option>
                    <option value="tuntas">Tuntas</option>
                    <option value="belum_tuntas">
                        Belum Tuntas
                    </option>
                    <option value="belum_dinilai">
                        Belum Dinilai
                    </option>
                </select>
            </div>

        </div>

        {{-- TABEL NILAI --}}
        <div class="gn-table-wrapper">
            <table class="gn-table">
                <thead>
                    <tr>
                        <th>Nama Siswa</th>
                        <th>NISN</th>
                        <th>Kelas</th>
                        <th>Kuis</th>
                        <th>Evaluasi</th>
                        <th>Nilai Akhir</th>
                        <th>Ketuntasan</th>
                        <th>Detail</th>
                    </tr>
                </thead>

                <tbody id="gnStudentTableBody">
                    <tr>
                        <td colspan="8" class="gn-empty">
                            Data nilai siswa belum tersedia.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- INFORMASI DATA --}}
        <div class="gn-table-footer">
            <span id="gnStudentCount">
                0 siswa ditampilkan
            </span>

            <span>
                Data nilai diambil dari hasil pengerjaan siswa.
            </span>
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
         MODAL DETAIL NILAI SISWA
         ========================================= --}}
    <div class="gn-modal" id="gnDetailModal" hidden>

        <div class="gn-modal-backdrop" data-gn-close></div>

        <div class="gn-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="gnDetailTitle">

            <div class="gn-modal-header">
                <h2 id="gnDetailTitle">
                    Detail Nilai Siswa
                </h2>

                <button type="button" class="gn-modal-close" data-gn-close aria-label="Tutup detail nilai">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div id="gnDetailContent">
                <p>Belum ada data nilai siswa.</p>
            </div>

            <div class="gn-modal-footer">
                <button type="button" class="gn-btn gn-btn-outline" data-gn-close>
                    Tutup
                </button>
            </div>
        </div>
    </div>

    {{-- NOTIFIKASI --}}
    <div class="gn-toast" id="gnToast" role="status" aria-live="polite" hidden></div>

</section>
