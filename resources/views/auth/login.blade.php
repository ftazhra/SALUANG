@extends('layouts.auth')

@section('title', 'Masuk — SALUANG')

@section('content')
    <div class="sl-page">

        <!-- GAMBAR KIRI -->
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

        <!-- FORM KANAN -->
        <main class="sl-right">
            <div class="sl-content">

                <a href="{{ route('beranda') }}" class="sl-logo">
                    <img src="{{ asset('images/saluang-logo-transparent.png') }}" alt="Logo SALUANG">
                </a>

                <div class="sl-heading">
                    <h2>Selamat Datang di SALUANG!</h2>
                    <p>Masuk untuk melanjutkan perjalanan belajarmu.</p>
                </div>

                @if ($errors->any())
                    <div class="sl-alert">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form action="{{ route('login.store') }}" method="POST" data-sl-form="login">
                    @csrf

                    <!-- PILIHAN PERAN -->
                    <div class="sl-roles" aria-label="Pilih peran">

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

                    <!-- NISN / NIPK / NOMOR HP -->
                    <div class="sl-field">
                        <label for="identifier" id="sl-identifier-label">
                            NISN
                        </label>

                        <div class="sl-input">
                            <span class="sl-input-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round" aria-hidden="true">
                                    <circle cx="12" cy="8" r="3" />
                                    <path d="M5 20v-2a7 7 0 0 1 14 0v2" />
                                </svg>
                            </span>

                            <input type="text" name="identifier" id="identifier" placeholder="Masukkan NISN"
                                value="{{ old('identifier') }}" autocomplete="username" required>
                        </div>
                    </div>

                    <!-- KHUSUS ORANG TUA -->
                    <div class="sl-field" id="sl-child-field" hidden>
                        <label for="nisn_anak">NISN Anak</label>

                        <div class="sl-input">
                            <span class="sl-input-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="m2 9 10-5 10 5-10 5L2 9Z" />
                                    <path d="M6 12v4c3 2 9 2 12 0v-4" />
                                </svg>
                            </span>

                            <input type="text" name="nisn_anak" id="nisn_anak" inputmode="numeric" maxlength="10"
                                value="{{ old('nisn_anak') }}" placeholder="Masukkan NISN anak">
                        </div>
                    </div>

                    <!-- PASSWORD -->
                    <div class="sl-field">
                        <label for="login-password">Kata Sandi</label>

                        <div class="sl-input">
                            <span class="sl-input-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <rect x="5" y="10" width="14" height="11" rx="2" />
                                    <path d="M8 10V7a4 4 0 0 1 8 0v3" />
                                </svg>
                            </span>

                            <input type="password" name="password" id="login-password" placeholder="Masukkan kata sandi"
                                autocomplete="current-password" required>

                            <button type="button" class="sl-eye" data-sl-eye="login-password"
                                aria-label="Tampilkan kata sandi">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="sl-forgot">
                        <a href="{{ route('password.request') }}">
                            Lupa kata sandi?
                        </a>
                    </div>

                    <button type="submit" class="sl-submit">
                        Masuk
                    </button>

                </form>

                <p class="sl-switch">
                    Belum punya akun?
                    <a href="{{ route('register') }}">Daftar sekarang</a>
                </p>

                <a href="{{ route('beranda') }}" class="sl-back">
                    Kembali ke Beranda
                </a>

            </div>
        </main>
    </div>
@endsection
