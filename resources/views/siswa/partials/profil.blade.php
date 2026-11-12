            <section id="profilePage" hidden>

                <!-- Navigasi atas -->
                <div class="sd-navigation">

                    <button type="button" class="sd-nav-btn" data-page="6">
                        <i class="bi bi-chevron-left"></i>
                        Previous
                    </button>

                </div>

                <div class="sd-profile-container">

                    <!-- Judul -->
                    <div class="sd-profile-heading">
                        <h1>Profil Saya</h1>
                        <p>
                            Kelola informasi pribadi
                            dan pengaturan akunmu.
                        </p>
                    </div>

                    <!-- =================================================
                         14.1 IDENTITAS SISWA
                    ================================================== -->
                    <article class="sd-profile-card sd-profile-identity">

                        <div class="sd-profile-avatar" id="profileAvatar">

                            @if (auth()->user()?->profile_photo_path)
                                <img id="profileAvatarImage"
                                    src="{{ asset('storage/' . auth()->user()->profile_photo_path) }}"
                                    alt="Foto profil siswa">
                            @else
                                <span id="profileAvatarInitial">
                                    {{ strtoupper(substr(auth()->user()?->name ?? 'S', 0, 1)) }}
                                </span>
                            @endif

                        </div>

                        <div class="sd-profile-identity-info">

                            <h2 id="profileDisplayName">
                                {{ auth()->user()?->name ?? 'Pengguna SALUANG' }}
                            </h2>

                            <p id="profileDisplaySchoolClass">
                                {{ auth()->user()?->sekolah ?? 'Sekolah belum diisi' }}
                                ·
                                {{ auth()->user()?->kelas ?? 'Kelas belum diisi' }}
                            </p>

                            <div class="sd-profile-photo-actions">

                                <button type="button" class="sd-profile-outline" id="profilePhotoButton">
                                    Ubah Foto Profil
                                </button>

                                <input type="file" id="profilePhotoInput" accept="image/png,image/jpeg,image/webp"
                                    hidden>

                                <button type="button" class="sd-profile-delete-photo" id="profileDeletePhotoButton"
                                    @if (!auth()->user()?->profile_photo_path) hidden @endif>
                                    Hapus Foto
                                </button>

                                <span id="profilePhotoFilename">
                                    Belum ada file dipilih
                                </span>

                            </div>

                        </div>

                    </article>

                    <!-- =================================================
                         14.2 INFORMASI PRIBADI
                    ================================================== -->
                    <article class="sd-profile-card">

                        <div class="sd-profile-card-header">

                            <h2>Informasi Pribadi</h2>

                            <button type="button" class="sd-profile-outline" id="profileEditButton">
                                Edit Profil
                            </button>

                        </div>

                        <div id="profileInformation">

                            <div class="sd-profile-info-grid">

                                <div class="sd-profile-info-item">
                                    <span>Nama Lengkap</span>

                                    <strong id="profileInfoName">
                                        {{ auth()->user()?->name ?? 'Belum diisi' }}
                                    </strong>
                                </div>

                                <div class="sd-profile-info-item">
                                    <span>Username</span>

                                    <strong id="profileInfoUsername">
                                        {{ auth()->user()?->username ?? 'Belum diisi' }}
                                    </strong>
                                </div>

                                <div class="sd-profile-info-item">
                                    <span>Email</span>

                                    <strong id="profileInfoEmail">
                                        {{ auth()->user()?->email ?? 'Belum diisi' }}
                                    </strong>
                                </div>

                                <div class="sd-profile-info-item">
                                    <span>NISN</span>

                                    <strong id="profileInfoNisn">
                                        {{ auth()->user()?->nisn ?? 'Belum diisi' }}
                                    </strong>
                                </div>

                                <div class="sd-profile-info-item">
                                    <span>Kelas</span>

                                    <strong id="profileInfoClass">
                                        {{ auth()->user()?->kelas ?? 'Belum diisi' }}
                                    </strong>
                                </div>

                                <div class="sd-profile-info-item">
                                    <span>Sekolah</span>

                                    <strong id="profileInfoSchool">
                                        {{ auth()->user()?->sekolah ?? 'Belum diisi' }}
                                    </strong>
                                </div>

                                <div class="sd-profile-info-item">
                                    <span>Nomor Telepon</span>

                                    <strong id="profileInfoPhone">
                                        {{ auth()->user()?->nomor_telepon ?? 'Belum diisi' }}
                                    </strong>
                                </div>

                            </div>

                        </div>

                        <!-- Form Edit Profil -->
                        <form id="profileEditForm" class="sd-profile-form" hidden>

                            <div class="sd-profile-form-grid">

                                <label>
                                    Nama Lengkap

                                    <input type="text" id="profileEditName"
                                        value="{{ auth()->user()?->name ?? '' }}" maxlength="255" required>
                                </label>

                                <label>
                                    Username

                                    <input type="text" id="profileEditUsername"
                                        value="{{ auth()->user()?->username ?? '' }}" maxlength="50" required>
                                </label>

                                <label>
                                    Email

                                    <input type="email" id="profileEditEmail"
                                        value="{{ auth()->user()?->email ?? '' }}" placeholder="Masukkan email">
                                </label>

                                <label>
                                    NISN

                                    <input type="text" value="{{ auth()->user()?->nisn ?? '' }}" readonly>
                                </label>

                                <label>
                                    Kelas

                                    <input type="text" id="profileEditClass"
                                        value="{{ auth()->user()?->kelas ?? '' }}" placeholder="Contoh: VII A">
                                </label>

                                <label>
                                    Sekolah

                                    <input type="text" id="profileEditSchool"
                                        value="{{ auth()->user()?->sekolah ?? '' }}" placeholder="Nama sekolah">
                                </label>

                                <label>
                                    Nomor Telepon

                                    <input type="tel" id="profileEditPhone"
                                        value="{{ auth()->user()?->nomor_telepon ?? '' }}" maxlength="20"
                                        placeholder="Nomor telepon (opsional)">
                                </label>

                            </div>

                            <div class="sd-profile-form-actions">

                                <button type="button" class="sd-profile-outline" id="profileCancelEdit">
                                    Kembali ke Profil
                                </button>

                                <button type="submit" class="sd-profile-primary">
                                    Simpan Perubahan
                                </button>

                            </div>

                            <p id="profileEditMessage" class="sd-profile-message" role="status"></p>

                        </form>

                    </article>

                    <!-- =================================================
                         14.3 PENGATURAN AKUN
                    ================================================== -->
                    <article class="sd-profile-card" id="profileSettingsCard">

                        <div class="sd-profile-card-header">
                            <h2>Pengaturan Akun</h2>
                        </div>

                        <div class="sd-profile-password-summary">

                            <div>
                                <span>Kata Sandi</span>
                                <strong>••••••••••</strong>
                            </div>

                            <button type="button" class="sd-profile-outline" id="profilePasswordButton">
                                Ubah Kata Sandi
                            </button>

                        </div>

                        <form id="profilePasswordForm" class="sd-profile-form" hidden>

                            <div class="sd-profile-form-grid">

                                <button type="button" class="sd-profile-outline sd-profile-back"
                                    id="profilePasswordBack">
                                    Kembali ke Profil
                                </button>

                                <label>
                                    Password Saat Ini

                                    <input type="password" id="profileCurrentPassword"
                                        autocomplete="current-password" placeholder="Password Saat Ini" required>
                                </label>

                                <label>
                                    Password Baru

                                    <input type="password" id="profileNewPassword" autocomplete="new-password"
                                        minlength="8" placeholder="Password Baru" required>
                                </label>

                                <label>
                                    Konfirmasi Password Baru

                                    <input type="password" id="profileConfirmPassword" autocomplete="new-password"
                                        minlength="8" placeholder="Konfirmasi Password Baru" required>
                                </label>

                            </div>

                            <div class="sd-profile-password-actions">

                                <button type="submit" class="sd-profile-primary">
                                    Simpan Password
                                </button>

                            </div>

                            <p id="profilePasswordMessage" class="sd-profile-message" role="status"></p>

                        </form>

                    </article>


                    {{-- PENGHUBUNG AKUN ORANG TUA --}}
                    @auth
                        <article class="sd-profile-card" id="studentParentLinkCard">

                            <div class="sd-profile-card-header">
                                <h2>
                                    <i class="bi bi-people"></i>
                                    Hubungkan Orang Tua
                                </h2>
                            </div>

                            <p class="sd-muted">
                                Buat kode untuk menghubungkan akun Orang Tua
                                dengan akun Siswa. Kode berlaku selama 10 menit
                                dan hanya dapat digunakan satu kali.
                            </p>


                            <button type="button" class="sd-profile-primary" id="generateParentCode"
                                style="display: inline-flex; align-items: center; justify-content: center; gap: 10px;">

                                <i class="bi bi-key" aria-hidden="true"></i>
                                <span>Buat Kode Orang Tua</span>

                            </button>


                            <div id="parentCodeResult" class="mt-3" hidden>
                                <p class="sd-muted mb-2">
                                    Kode penghubung:
                                </p>

                                <strong id="parentCodeValue" style="font-size:26px; letter-spacing:5px;">
                                </strong>

                                <p class="sd-muted mt-2">
                                    Berikan kode ini hanya kepada
                                    Orang Tua atau Wali yang kamu kenal.
                                </p>

                                <small id="parentCodeCountdown"></small>
                            </div>

                            <p id="parentCodeError" class="sd-profile-message mt-2" role="status" aria-live="polite">
                            </p>

                        </article>
                    @endauth

                    <!-- Keluar dari akun -->
                    @auth
                        <form method="POST" action="{{ route('logout') }}" data-sd-logout-form>
                            @csrf

                            <button type="submit" class="sd-profile-logout">
                                Keluar dari Akun
                            </button>
                        </form>
                    @endauth

                    <!-- Kembali Beranda -->
                    <button type="button" class="sd-profile-home" data-page="0">
                        Kembali ke Beranda
                    </button>

                </div>

                <!-- Navigasi bawah -->
                <div class="sd-navigation">

                    <button type="button" class="sd-nav-btn" data-page="6">
                        <i class="bi bi-chevron-left"></i>
                        Previous
                    </button>

                </div>

                <!-- =================================================
                     14.4 MODAL HAPUS FOTO PROFIL
                ================================================== -->
                <div class="sd-profile-photo-modal" id="profileDeletePhotoModal" hidden>

                    <div class="sd-profile-photo-backdrop"></div>

                    <div class="sd-profile-photo-dialog" role="dialog" aria-modal="true"
                        aria-labelledby="profileDeletePhotoTitle">

                        <div class="sd-profile-photo-icon">
                            <i class="bi bi-trash3"></i>
                        </div>

                        <h2 id="profileDeletePhotoTitle">
                            Yakin Ingin Menghapus Foto?
                        </h2>

                        <p>
                            Foto profilmu akan dihapus
                            dan diganti dengan avatar inisial nama.
                        </p>

                        <p id="profileDeletePhotoError" class="sd-profile-message is-error" role="alert" hidden>
                        </p>

                        <div class="sd-profile-photo-actions">

                            <button type="button" id="profileDeletePhotoCancel" class="sd-profile-photo-cancel">
                                Batal
                            </button>

                            <button type="button" id="profileDeletePhotoConfirm" class="sd-profile-photo-danger">
                                Ya, Hapus
                            </button>

                        </div>

                    </div>

                </div>

            </section>
