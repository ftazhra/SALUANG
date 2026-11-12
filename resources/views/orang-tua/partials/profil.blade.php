                <section class="parent-section gp-profile-page" data-parent-section="profil" hidden>

                    <div class="gp-profile-container">

                        <div class="gp-profile-heading">

                            <h1>Profil Saya</h1>

                            <p>
                                Kelola informasi pribadi dan pengaturan
                                akun Orang Tua/Wali.
                            </p>

                            <p class="gp-profile-message" id="parentProfileStatus" role="status"
                                aria-live="polite"></p>

                        </div>

                        {{-- IDENTITAS --}}
                        <article class="gp-profile-card gp-profile-identity">

                            <div class="gp-profile-avatar" id="parentProfileAvatar">

                                @if ($parent?->profile_photo_path)
                                    <img id="parentProfileAvatarImage"
                                        src="{{ asset('storage/' . $parent->profile_photo_path) }}"
                                        alt="Foto profil Orang Tua">
                                @else
                                    <span id="parentProfileAvatarInitial">
                                        {{ strtoupper(mb_substr($parentName, 0, 1)) }}
                                    </span>
                                @endif

                            </div>

                            <div class="gp-profile-identity-info">

                                <h2 id="parentProfileDisplayName">
                                    {{ $parentName }}
                                </h2>

                                <p>Orang Tua/Wali SALUANG</p>

                                <div class="gp-profile-photo-actions">

                                    <button type="button" class="gp-profile-outline" id="parentProfilePhotoButton">
                                        Ubah Foto Profil
                                    </button>

                                    <input type="file" id="parentProfilePhotoInput"
                                        accept="image/png,image/jpeg,image/webp" hidden>

                                    <button type="button" class="gp-profile-delete-photo"
                                        id="parentProfileDeletePhotoButton"
                                        @if (!$parent?->profile_photo_path) hidden @endif>
                                        Hapus Foto
                                    </button>

                                    <span class="gp-profile-photo-filename" id="parentProfilePhotoMessage">
                                        Foto profil
                                    </span>

                                </div>

                            </div>

                        </article>

                        {{-- INFORMASI PRIBADI --}}
                        <article class="gp-profile-card">

                            <div class="gp-profile-card-header">

                                <h2>Informasi Pribadi</h2>

                                <button type="button" class="gp-profile-outline" id="parentProfileEditButton">
                                    Edit Profil
                                </button>

                            </div>

                            <div id="parentProfileInformation">

                                <div class="gp-profile-info-grid">

                                    <div class="gp-profile-info-item">
                                        <span>Nama Lengkap</span>
                                        <strong id="parentProfileInfoName">
                                            {{ $parentName }}
                                        </strong>
                                    </div>

                                    <div class="gp-profile-info-item">
                                        <span>Username</span>
                                        <strong id="parentProfileInfoUsername">
                                            {{ $parent?->username ?? '-' }}
                                        </strong>
                                    </div>

                                    <div class="gp-profile-info-item">
                                        <span>Email</span>
                                        <strong id="parentProfileInfoEmail">
                                            {{ $parent?->email ?? '-' }}
                                        </strong>
                                    </div>

                                    <div class="gp-profile-info-item">
                                        <span>Nomor Telepon</span>
                                        <strong id="parentProfileInfoPhone">
                                            {{ $parent?->nomor_telepon ?? '-' }}
                                        </strong>
                                    </div>

                                    <div class="gp-profile-info-item">
                                        <span>Peran</span>
                                        <strong>Orang Tua/Wali</strong>
                                    </div>

                                </div>

                            </div>

                            <form id="parentProfileEditForm" class="gp-profile-form" hidden>

                                <div class="gp-profile-form-grid">

                                    <label>
                                        Nama Lengkap
                                        <input name="name" id="parentProfileName" value="{{ $parentName }}"
                                            maxlength="255" required>
                                    </label>

                                    <label>
                                        Username
                                        <input name="username" id="parentProfileUsername"
                                            value="{{ $parent?->username }}" maxlength="50" required>
                                    </label>

                                    <label>
                                        Email
                                        <input name="email" id="parentProfileEmail" type="email"
                                            value="{{ $parent?->email }}" maxlength="255">
                                    </label>

                                    <label>
                                        Nomor Telepon
                                        <input name="nomor_telepon" id="parentProfilePhone" type="tel"
                                            value="{{ $parent?->nomor_telepon }}" maxlength="20">
                                    </label>

                                </div>

                                <div class="gp-profile-form-actions">

                                    <button type="button" class="gp-profile-outline" id="parentProfileCancelEdit">
                                        Batal
                                    </button>

                                    <button type="submit" class="gp-profile-primary" id="parentProfileSave">
                                        Simpan Perubahan
                                    </button>

                                </div>

                                <p class="gp-profile-message" id="parentProfileEditMessage" role="status"></p>

                            </form>

                        </article>

                        {{-- INFORMASI ANAK --}}
                        <article class="gp-profile-card">

                            <div class="gp-profile-card-header">
                                <h2>Informasi Anak</h2>
                            </div>

                            @if ($child)
                                <div class="gp-profile-info-grid">

                                    <div class="gp-profile-info-item">
                                        <span>Nama Anak</span>
                                        <strong>{{ $child->name }}</strong>
                                    </div>

                                    <div class="gp-profile-info-item">
                                        <span>NISN</span>
                                        <strong>{{ $child->nisn ?: '-' }}</strong>
                                    </div>

                                    <div class="gp-profile-info-item">
                                        <span>Kelas</span>
                                        <strong>{{ $className }}</strong>
                                    </div>

                                    <div class="gp-profile-info-item">
                                        <span>Sekolah</span>
                                        <strong>{{ $schoolName }}</strong>
                                    </div>

                                </div>
                            @else
                                <p class="gp-profile-message">
                                    Belum ada hubungan anak yang disetujui.
                                    Data siswa tidak ditampilkan.
                                </p>
                            @endif

                        </article>

                        {{-- PENGATURAN AKUN --}}
                        <article class="gp-profile-card gp-profile-settings-card" id="parentProfileSettings">

                            <div class="gp-profile-card-header">
                                <h2>Pengaturan Akun</h2>
                            </div>

                            <div class="gp-profile-password-summary">

                                <div>
                                    <span>Kata Sandi</span>
                                    <strong>••••••••••</strong>
                                </div>

                                <button type="button" class="gp-profile-outline" id="parentProfilePasswordButton">
                                    Ubah Kata Sandi
                                </button>

                            </div>

                            <form id="parentProfilePasswordForm" class="gp-profile-form" hidden>

                                <div class="gp-profile-form-grid">

                                    <label>
                                        Password Saat Ini
                                        <input name="current_password" type="password"
                                            autocomplete="current-password" required>
                                    </label>

                                    <label>
                                        Password Baru
                                        <input name="password" type="password" minlength="8"
                                            autocomplete="new-password" required>
                                    </label>

                                    <label>
                                        Konfirmasi Password Baru
                                        <input name="password_confirmation" type="password" minlength="8"
                                            autocomplete="new-password" required>
                                    </label>

                                </div>

                                <div class="gp-profile-password-actions">

                                    <button type="button" class="gp-profile-outline"
                                        id="parentProfilePasswordCancel">
                                        Batal
                                    </button>

                                    <button type="submit" class="gp-profile-primary" id="parentProfilePasswordSave">
                                        Simpan Password
                                    </button>

                                </div>

                                <p class="gp-profile-message" id="parentProfilePasswordMessage" role="status"></p>

                            </form>

                        </article>


                        {{-- AKSI PROFIL ORANG TUA --}}
                        <div class="parent-profile-bottom-actions">

                            <form action="{{ route('logout') }}" method="POST" data-gd-logout-form>
                                @csrf

                                <button type="submit" class="parent-profile-logout-button" data-gd-logout-trigger>
                                    Keluar dari Akun
                                </button>
                            </form>

                            <button type="button" class="parent-profile-home-button" data-parent-page="beranda">
                                Kembali ke Beranda
                            </button>

                        </div>

                    </div>

                </section>
