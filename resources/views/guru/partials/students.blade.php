<section class="gstu-page" id="guruStudentsPage" data-guru-section="students" hidden>
    <div class="gd-page-navigation">
        <button type="button" class="gd-nav-btn" data-guru-previous><i class="bi bi-chevron-left"></i> Previous</button>
        <button type="button" class="gd-nav-btn gd-nav-btn-next" data-guru-next>Next <i
                class="bi bi-chevron-right"></i></button>
    </div>

    <div class="gstu-heading">
        <div>
            <h1>Daftar Siswa</h1>
        </div>
    </div>

    <div class="gstu-filters">
        <label>Nama atau NISN
            <input id="gstuSearch" type="search" maxlength="200" placeholder="Cari nama atau NISN">
        </label>
        <label>Kelas
            <select id="gstuClass">
                <option value="">Semua kelas terdaftar</option>
            </select>
        </label>
    </div>
    <div class="gstu-table-wrap">
        <table class="gstu-table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>NISN</th>
                    <th>Kelas</th>
                    <th>Email</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody id="gstuRows">
                <tr>
                    <td colspan="5" class="gstu-empty">Memuat daftar siswa...</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="gstu-pagination">
        <span id="gstuPageInfo" aria-live="polite"></span>

        <div class="gstu-pagination-controls">
            <button type="button" class="gstu-outline" id="gstuPrevious" aria-label="Halaman sebelumnya"
                title="Halaman sebelumnya" disabled>
                <i class="bi bi-chevron-left" aria-hidden="true"></i>
            </button>

            <button type="button" class="gstu-outline" id="gstuNext" aria-label="Halaman berikutnya"
                title="Halaman berikutnya" disabled>
                <i class="bi bi-chevron-right" aria-hidden="true"></i>
            </button>
        </div>
    </div>

    <p class="gstu-error" id="gstuPageError" role="alert" hidden></p>

    <div class="gstu-modal" id="gstuFormModal" hidden>
        <div class="gstu-backdrop" data-gstu-close></div>
        <section class="gstu-dialog" role="dialog" aria-modal="true" aria-labelledby="gstuFormTitle">
            <button type="button" class="gstu-modal-close" data-gstu-close aria-label="Tutup"><i
                    class="bi bi-x-lg"></i></button>
            <h2 id="gstuFormTitle">Edit Informasi Siswa</h2>
            <form id="gstuForm">
                <div class="gstu-form-grid">
                    <label>Nama Lengkap<input name="name" maxlength="255" required></label>
                    <label>Username<input name="username" maxlength="50" required></label>
                    <label>NISN (10 digit)<input name="nisn" inputmode="numeric" pattern="[0-9]{10}" maxlength="10"
                            required></label>
                    <label>Email<input name="email" type="email" maxlength="255" required></label>
                    <label>Kelas<select name="kelas" id="gstuFormClass" required></select></label>
                    <label>Sekolah<input name="sekolah" maxlength="255"></label>
                </div>
                <p class="gstu-error" id="gstuFormError" role="alert" hidden></p>
                <div class="gstu-actions">
                    <button type="button" class="gstu-outline" data-gstu-close>Batal</button>
                    <button type="submit" class="gstu-primary" id="gstuSaveButton">Simpan</button>
                </div>
            </form>
        </section>
    </div>

    <div class="gstu-modal" id="gstuDetailModal" hidden>
        <div class="gstu-backdrop" data-gstu-close></div>
        <section class="gstu-dialog" role="dialog" aria-modal="true" aria-labelledby="gstuDetailTitle">
            <button type="button" class="gstu-modal-close" data-gstu-close aria-label="Tutup"><i
                    class="bi bi-x-lg"></i></button>
            <h2 id="gstuDetailTitle">Detail Siswa</h2>
            <dl class="gstu-detail-grid" id="gstuDetailContent"></dl>
            <div class="gstu-actions"><button type="button" class="gstu-primary" data-gstu-close>Tutup</button>
            </div>
        </section>
    </div>

    <div class="gstu-modal" id="gstuDeleteModal" hidden>
        <div class="gstu-backdrop" data-gstu-close></div>
        <section class="gstu-dialog gstu-confirm" role="dialog" aria-modal="true"
            aria-labelledby="gstuDeleteTitle">
            <h2 id="gstuDeleteTitle">Hapus akun siswa?</h2>
            <p>Anda akan menghapus akun <strong id="gstuDeleteStudentName"></strong>. Penghapusan hanya dapat dilakukan
                jika tidak ada relasi atau riwayat yang dilindungi.</p>
            <p class="gstu-error" id="gstuDeleteError" role="alert" hidden></p>
            <div class="gstu-actions">
                <button type="button" class="gstu-outline" data-gstu-close>Batal</button>
                <button type="button" class="gstu-danger" id="gstuDeleteConfirm">Ya, Hapus</button>
            </div>
        </section>
    </div>

    <div class="gstu-toast" id="gstuToast" role="status" aria-live="polite" hidden></div>

    <div class="gd-page-navigation gd-navigation-bottom">
        <button type="button" class="gd-nav-btn" data-guru-previous><i class="bi bi-chevron-left"></i>
            Previous</button>
        <button type="button" class="gd-nav-btn gd-nav-btn-next" data-guru-next>Next <i
                class="bi bi-chevron-right"></i></button>
    </div>
</section>
