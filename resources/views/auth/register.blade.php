@extends('layouts.auth')

@section('title', 'Daftar — SALUANG')

@section('content')
    <div class="sl-page">

        {{-- ILUSTRASI KIRI --}}
        <aside class="sl-art">
            <div class="sl-art-text">
                <h1>
                    Belajar dari Alam,<br>
                    <span>Menjaga untuk</span><br>
                    Masa Depan.
                </h1>

                <div class="sl-art-line"></div>

                <p>
                    Bersama SALUANG, kita belajar mengenal
                    kehidupan di lahan basah dan ikut
                    menjaganya untuk generasi mendatang.
                </p>
            </div>
        </aside>

        {{-- PANEL DAFTAR KANAN --}}
        <main class="sl-right sl-right-register">

            <div class="sl-content">

                {{-- LOGO SALUANG ASLI --}}
                <a href="{{ route('beranda') }}" class="sl-logo">
                    <img src="{{ asset('images/saluang-logo-transparent.png') }}" alt="Logo SALUANG">
                </a>

                {{-- JUDUL --}}
                <div class="sl-heading">
                    <h2>Buat Akun SALUANG</h2>
                    <p>
                        Daftar dan mulai perjalanan belajarmu
                        bersama SALUANG.
                    </p>
                </div>

                {{-- PESAN ERROR --}}
                @if ($errors->any())
                    <div class="sl-alert" role="alert">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- FORM REGISTRASI --}}
                <form action="{{ route('register.store') }}" method="POST" data-sl-form="register">
                    @csrf

                    {{-- PILIH PERAN --}}
                    <div class="sl-roles" role="group" aria-label="Pilih peran">

                        {{-- SISWA --}}
                        <label class="sl-role">
                            <input type="radio" name="role" value="siswa" @checked(old('role', 'siswa') === 'siswa')>

                            <span class="sl-role-card">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="m2 9 10-5 10 5-10 5L2 9Z" />
                                    <path d="M6 12v5c3 2 9 2 12 0v-5" />
                                    <path d="M22 9v7" />
                                </svg>
                                <span>Siswa</span>
                            </span>
                        </label>

                        {{-- GURU --}}
                        <label class="sl-role">
                            <input type="radio" name="role" value="guru" @checked(old('role') === 'guru')>

                            <span class="sl-role-card">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <rect x="5" y="2.5" width="14" height="19" rx="1" />
                                    <path d="M8 17.5h8" />
                                </svg>
                                <span>Guru</span>
                            </span>
                        </label>

                        {{-- ORANG TUA --}}
                        <label class="sl-role">
                            <input type="radio" name="role" value="orang_tua" @checked(old('role') === 'orang_tua')>

                            <span class="sl-role-card">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <circle cx="9" cy="8" r="3" />
                                    <circle cx="18" cy="9" r="2" />
                                    <path d="M3 20v-2a6 6 0 0 1 12 0v2" />
                                    <path d="M16.5 15a4.5 4.5 0 0 1 4.5 4.5" />
                                </svg>
                                <span>Orang Tua</span>
                            </span>
                        </label>

                    </div>

                    {{-- NAMA LENGKAP: SEMUA PERAN --}}
                    <div class="sl-field">
                        <label for="name">Nama Lengkap</label>

                        <div class="sl-input">
                            <span class="sl-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round">
                                    <circle cx="12" cy="8" r="3" />
                                    <path d="M5 20v-2a7 7 0 0 1 14 0v2" />
                                </svg>
                            </span>

                            <input type="text" id="name" name="name" value="{{ old('name') }}"
                                placeholder="Masukkan nama lengkap" autocomplete="name" required>
                        </div>
                    </div>

                    {{-- NISN: KHUSUS SISWA --}}
                    <div class="sl-field" data-sl-only="siswa">
                        <label for="nisn">NISN</label>

                        <div class="sl-input">
                            <span class="sl-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round">
                                    <circle cx="12" cy="8" r="3" />
                                    <path d="M5 20v-2a7 7 0 0 1 14 0v2" />
                                </svg>
                            </span>

                            <input type="text" id="nisn" name="nisn" value="{{ old('nisn') }}"
                                inputmode="numeric" maxlength="10" placeholder="Masukkan NISN">
                        </div>
                    </div>

                    {{-- NIPK: KHUSUS GURU --}}
                    <div class="sl-field" data-sl-only="guru" hidden>
                        <label for="nip_nipk">NIPK</label>

                        <div class="sl-input">
                            <span class="sl-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round">
                                    <circle cx="12" cy="8" r="3" />
                                    <path d="M5 20v-2a7 7 0 0 1 14 0v2" />
                                </svg>
                            </span>

                            <input type="text" id="nip_nipk" name="nip_nipk" value="{{ old('nip_nipk') }}"
                                placeholder="Masukkan NIPK">
                        </div>
                    </div>

                    {{-- NO HP: KHUSUS ORANG TUA --}}
                    <div class="sl-field" data-sl-only="orang_tua" hidden>
                        <label for="nomor_telepon">No. HP</label>

                        <div class="sl-input">
                            <span class="sl-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="7" y="2" width="10" height="20" rx="2" />
                                    <path d="M11 18h2" />
                                </svg>
                            </span>

                            <input type="tel" id="nomor_telepon" name="nomor_telepon"
                                value="{{ old('nomor_telepon') }}" placeholder="Masukkan No. HP">
                        </div>
                    </div>

                    {{-- NISN ANAK: KHUSUS ORANG TUA --}}
                    <div class="sl-field" data-sl-only="orang_tua" hidden>
                        <label for="register_nisn_anak">NISN Anak</label>

                        <div class="sl-input">
                            <span class="sl-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m2 9 10-5 10 5-10 5L2 9Z" />
                                    <path d="M6 12v4c3 2 9 2 12 0v-4" />
                                </svg>
                            </span>

                            <input type="text" id="register_nisn_anak" name="nisn_anak"
                                value="{{ old('nisn_anak') }}" inputmode="numeric" maxlength="10"
                                placeholder="Masukkan NISN anak">
                        </div>
                    </div>


                    {{-- USERNAME: SEMUA PERAN --}}
                    <div class="sl-field">
                        <label for="username">Username</label>

                        <div class="sl-input">
                            <span class="sl-input-icon" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10" />
                                    <path d="M16 8v5a3 3 0 0 0 6 0v-1a10 10 0 1 0-3.5 7.6" />
                                    <circle cx="12" cy="12" r="4" />
                                </svg>
                            </span>

                            <input type="text" id="username" name="username" value="{{ old('username') }}"
                                placeholder="Buat username" autocomplete="username" required>
                        </div>
                    </div>


                    {{-- EMAIL: KHUSUS SISWA --}}
                    <div class="sl-field" data-sl-only="siswa">
                        <label for="register_email">Email</label>

                        <div class="sl-input">
                            <span class="sl-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="5" width="18" height="14" rx="2" />
                                    <path d="m3 7 9 6 9-6" />
                                </svg>
                            </span>

                            <input type="email" id="register_email" name="email" value="{{ old('email') }}"
                                maxlength="255" placeholder="Masukkan email aktif" autocomplete="email">
                        </div>
                    </div>

                    {{-- KELAS: KHUSUS SISWA --}}
                    <div class="sl-field" data-sl-only="siswa">
                        <label for="register_kelas">Kelas</label>

                        <div class="sl-input">
                            <span class="sl-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="4" y="4" width="16" height="16" rx="2" />
                                    <path d="M8 9h8M8 13h8M8 17h5" />
                                </svg>
                            </span>

                            <input type="text" id="register_kelas" name="kelas" value="{{ old('kelas') }}"
                                maxlength="20" placeholder="Contoh: VII A">
                        </div>
                    </div>

                    {{-- SEKOLAH: KHUSUS SISWA --}}
                    <div class="sl-field" data-sl-only="siswa">
                        <label for="register_sekolah">Sekolah</label>

                        <div class="sl-input">
                            <span class="sl-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 21h18M5 21V7l7-4 7 4v14" />
                                    <path d="M9 10h2M13 10h2M9 14h2M13 14h2" />
                                    <path d="M10 21v-4h4v4" />
                                </svg>
                            </span>

                            <input type="text" id="register_sekolah" name="sekolah" value="{{ old('sekolah') }}"
                                maxlength="255" placeholder="Contoh: SMP Negeri 21 Banjarmasin">
                        </div>
                    </div>


                    {{-- KATA SANDI: SEMUA PERAN --}}
                    <div class="sl-field">
                        <label for="register-password">Kata Sandi</label>

                        <div class="sl-input">
                            <span class="sl-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round">
                                    <rect x="5" y="10" width="14" height="11" rx="2" />
                                    <path d="M8 10V7a4 4 0 0 1 8 0v3" />
                                </svg>
                            </span>

                            <input type="password" id="register-password" name="password" minlength="8"
                                placeholder="Buat kata sandi" autocomplete="new-password" required>

                            <button type="button" class="sl-eye" data-sl-eye="register-password"
                                aria-label="Tampilkan kata sandi">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- KONFIRMASI SANDI: SEMUA PERAN --}}
                    <div class="sl-field">
                        <label for="password_confirmation">
                            Konfirmasi Kata Sandi
                        </label>

                        <div class="sl-input">
                            <span class="sl-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round">
                                    <rect x="5" y="10" width="14" height="11" rx="2" />
                                    <path d="M8 10V7a4 4 0 0 1 8 0v3" />
                                </svg>
                            </span>

                            <input type="password" id="password_confirmation" name="password_confirmation"
                                minlength="8" placeholder="Masukkan kembali kata sandi" autocomplete="new-password"
                                required>

                            <button type="button" class="sl-eye" data-sl-eye="password_confirmation"
                                aria-label="Tampilkan konfirmasi kata sandi">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- TOMBOL DAFTAR --}}
                    <button type="submit" id="sl-register-button" class="sl-submit">
                        Daftar
                    </button>

                </form>

                <p class="sl-switch">
                    Sudah punya akun?
                    <a href="{{ route('login') }}">Masuk</a>
                </p>

                <a href="{{ route('beranda') }}" class="sl-back">
                    Kembali ke Beranda
                </a>

            </div>
        </main>
    </div>
@endsection
