{{-- =====================================================
     SALUANG — HALAMAN PROFIL GURU
     File: resources/views/guru/partials/profil.blade.php
     Desain mengikuti Profil Siswa
     ===================================================== --}}

@php
    $guru = auth()->user();
    $fotoProfil = $guru?->profile_photo_path;
@endphp

<section id="guruProfilePage" class="gp-profile-page" data-guru-section="profil" hidden>

    {{-- 01. NAVIGASI ATAS --}}
    <div class="gd-page-navigation">
        <button type="button" class="gd-nav-btn" data-guru-previous>
            <i class="bi bi-chevron-left"></i>
            Previous
        </button>
    </div>

    <div class="gp-profile-container">

        {{-- 02. JUDUL --}}
        <div class="gp-profile-heading">
            <h1>Profil Saya</h1>
            <p>Kelola informasi pribadi dan pengaturan akun Guru SALUANG.</p>
        </div>

        {{-- 03. KARTU IDENTITAS --}}
        <article class="gp-profile-card gp-profile-identity">

            <div class="gp-profile-avatar" id="gpProfileAvatar">

                @if ($fotoProfil)
                    <img id="gpProfileAvatarImage" src="{{ asset('storage/' . $fotoProfil) }}" alt="Foto profil Guru">
                @else
                    <span id="gpProfileAvatarInitial">
                        {{ strtoupper(mb_substr($guru?->name ?? 'G', 0, 1)) }}
                    </span>
                @endif

            </div>

            <div class="gp-profile-identity-info">

                <h2 id="gpProfileDisplayName">
                    {{ $guru?->name ?? 'Guru SALUANG' }}
                </h2>

                <p id="gpProfileIdentitySubtitle">
                    {{ $guru?->sekolah ?: 'Sekolah belum diisi' }}
                    ·
                    {{ $guru?->mata_pelajaran ?: 'Mata pelajaran belum diisi' }}
                </p>

                <div class="gp-profile-photo-actions">

                    <button type="button" class="gp-profile-outline" id="gpProfilePhotoButton" disabled
                        title="Fitur unggah foto akan dihubungkan ke server">
                        Ubah Foto Profil
                    </button>

                    <input type="file" id="gpProfilePhotoInput" accept="image/png,image/jpeg,image/webp" hidden>

                    <button type="button" class="gp-profile-delete-photo" id="gpProfileDeletePhotoButton" disabled
                        @if (!$fotoProfil) hidden @endif>
                        Hapus Foto
                    </button>

                    <span class="gp-profile-photo-filename" id="gpProfilePhotoFilename">
                        Belum ada file dipilih
                    </span>

                </div>
            </div>

        </article>

        {{-- 04. INFORMASI PRIBADI --}}
        <article class="gp-profile-card">

            <div class="gp-profile-card-header">
                <h2>Informasi Pribadi</h2>

                <button type="button" class="gp-profile-outline" id="gpProfileEditButton">
                    Edit Profil
                </button>
            </div>

            {{-- INFORMASI YANG DITAMPILKAN --}}
            <div id="gpProfileInformation">

                <div class="gp-profile-info-grid">

                    <div class="gp-profile-info-item">
                        <span>Nama Lengkap</span>
                        <strong id="gpProfileInfoName">
                            {{ $guru?->name ?: 'Belum diisi' }}
                        </strong>
                    </div>

                    <div class="gp-profile-info-item">
                        <span>Username</span>
                        <strong id="gpProfileInfoUsername">
                            {{ $guru?->username ?: 'Belum diisi' }}
                        </strong>
                    </div>

                    <div class="gp-profile-info-item">
                        <span>Email</span>
                        <strong id="gpProfileInfoEmail">
                            {{ $guru?->email ?: 'Belum diisi' }}
                        </strong>
                    </div>

                    <div class="gp-profile-info-item">
                        <span>NIP/NIPK</span>
                        <strong id="gpProfileInfoNip">
                            {{ $guru?->nip_nipk ?: 'Belum diisi' }}
                        </strong>
                    </div>

                    <div class="gp-profile-info-item">
                        <span>Mata Pelajaran</span>
                        <strong id="gpProfileInfoSubject">
                            {{ $guru?->mata_pelajaran ?: 'Belum diisi' }}
                        </strong>
                    </div>

                    <div class="gp-profile-info-item">
                        <span>Sekolah</span>
                        <strong id="gpProfileInfoSchool">
                            {{ $guru?->sekolah ?: 'Belum diisi' }}
                        </strong>
                    </div>

                    <div class="gp-profile-info-item">
                        <span>Nomor Telepon</span>
                        <strong id="gpProfileInfoPhone">
                            {{ $guru?->nomor_telepon ?: 'Belum diisi' }}
                        </strong>
                    </div>

                </div>
            </div>

            {{-- FORM EDIT PROFIL --}}
            <form id="gpProfileEditForm" class="gp-profile-form" hidden>

                <div class="gp-profile-form-grid">

                    <label for="gpProfileEditName">
                        Nama Lengkap
                        <input type="text" id="gpProfileEditName" value="{{ $guru?->name ?? '' }}" maxlength="255"
                            required>
                    </label>

                    <label for="gpProfileEditUsername">
                        Username
                        <input type="text" id="gpProfileEditUsername" value="{{ $guru?->username ?? '' }}"
                            maxlength="50" required>
                    </label>

                    <label for="gpProfileEditEmail">
                        Email
                        <input type="email" id="gpProfileEditEmail" value="{{ $guru?->email ?? '' }}" maxlength="255"
                            required>
                    </label>

                    <label for="gpProfileEditNip">
                        NIP/NIPK
                        <input type="text" id="gpProfileEditNip" value="{{ $guru?->nip_nipk ?? '' }}" readonly>
                    </label>

                    <label for="gpProfileEditSubject">
                        Mata Pelajaran
                        <input type="text" id="gpProfileEditSubject" value="{{ $guru?->mata_pelajaran ?? '' }}"
                            maxlength="255">
                    </label>

                    <label for="gpProfileEditSchool">
                        Sekolah
                        <input type="text" id="gpProfileEditSchool" value="{{ $guru?->sekolah ?? '' }}"
                            maxlength="255">
                    </label>

                    <label for="gpProfileEditPhone">
                        Nomor Telepon
                        <input type="tel" id="gpProfileEditPhone" value="{{ $guru?->nomor_telepon ?? '' }}"
                            maxlength="30" placeholder="Contoh: 08xxxxxxxxxx">
                    </label>

                </div>

                <div class="gp-profile-form-actions">

                    <button type="button" class="gp-profile-outline" id="gpProfileCancelEdit">
                        Kembali ke Profil
                    </button>

                    <button type="submit" class="gp-profile-primary" id="gpProfileSaveButton" disabled
                        title="Fitur simpan akan dihubungkan ke server">
                        Simpan Perubahan
                    </button>

                </div>

                <p class="gp-profile-message" id="gpProfileEditMessage" role="status"></p>

            </form>

        </article>

        {{-- 05. PENGATURAN AKUN --}}
        <article class="gp-profile-card gp-profile-settings-card" id="gpProfileSettingsCard">

            <div class="gp-profile-card-header">
                <h2>Pengaturan Akun</h2>
            </div>

            <div class="gp-profile-password-summary">

                <div>
                    <span>Kata Sandi</span>
                    <strong>••••••••••</strong>
                </div>

                <button type="button" class="gp-profile-outline" id="gpProfilePasswordButton">
                    Ubah Kata Sandi
                </button>

            </div>

            {{-- FORM UBAH PASSWORD --}}
            <form id="gpProfilePasswordForm" class="gp-profile-form gp-profile-password-form" hidden>

                <div class="gp-profile-form-grid">

                    <button type="button" class="gp-profile-outline gp-profile-password-back"
                        id="gpProfilePasswordBack">
                        Kembali ke Profil
                    </button>

                    <label for="gpProfileCurrentPassword">
                        Password Saat Ini
                        <input type="password" id="gpProfileCurrentPassword" autocomplete="current-password"
                            required>
                    </label>

                    <label for="gpProfileNewPassword">
                        Password Baru
                        <input type="password" id="gpProfileNewPassword" autocomplete="new-password" minlength="8"
                            required>
                    </label>

                    <label for="gpProfileConfirmPassword">
                        Konfirmasi Password Baru
                        <input type="password" id="gpProfileConfirmPassword" autocomplete="new-password"
                            minlength="8" required>
                    </label>

                </div>

                <div class="gp-profile-password-actions">
                    <button type="submit" class="gp-profile-primary" id="gpProfileSavePassword" disabled
                        title="Fitur ubah password akan dihubungkan ke server">
                        Simpan Password
                    </button>
                </div>

                <p class="gp-profile-message" id="gpProfilePasswordMessage" role="status"></p>

            </form>

        </article>

    </div>

    {{-- 06. NAVIGASI BAWAH --}}
    <div class="gd-page-navigation gd-navigation-bottom">

        <button type="button" class="gd-nav-btn" data-guru-previous>
            <i class="bi bi-chevron-left"></i>
            Previous
        </button>

    </div>

</section>
