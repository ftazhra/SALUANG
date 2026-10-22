
@extends('layouts.auth')

@section('title', 'Lupa Kata Sandi — SALUANG')

@section('content')
<div class="sl-page">

    {{-- ILUSTRASI LAHAN BASAH --}}
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

    {{-- PANEL KANAN --}}
    <main class="sl-right sl-forgot-page">
        <div class="sl-content sl-forgot-content">

            {{-- LOGO ASLI SALUANG --}}
            <a href="{{ route('beranda') }}" class="sl-logo">
                <img
                    src="{{ asset('images/saluang-logo-transparent.png') }}"
                    alt="Logo SALUANG"
                >
            </a>

            <div class="sl-forgot-form-area">

                <div class="sl-heading">
                    <h2>Lupa Kata Sandi</h2>

                    <p>
                        Masukkan alamat email yang terdaftar
                        pada akun SALUANG untuk mengajukan
                        pemulihan kata sandi.
                    </p>
                </div>

                {{-- PESAN BERHASIL --}}
                @if (session('status'))
                    <div class="sl-success" role="status">
                        {{ session('status') }}
                    </div>
                @endif

                {{-- PESAN VALIDASI --}}
                @if ($errors->any())
                    <div class="sl-alert" role="alert">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form
                    action="{{ route('password.request.store') }}"
                    method="POST"
                >
                    @csrf

                    {{-- EMAIL SEMUA PERAN --}}
                    <div class="sl-field">
                        <label for="recovery-email">
                            Email
                        </label>

                        <div class="sl-input">

                            <span class="sl-input-icon" aria-hidden="true">
                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <rect
                                        x="3"
                                        y="5"
                                        width="18"
                                        height="14"
                                        rx="2"
                                    />
                                    <path d="m3 7 9 6 9-6"/>
                                </svg>
                            </span>

                            <input
                                type="email"
                                id="recovery-email"
                                name="email"
                                value="{{ old('email') }}"
                                placeholder="Masukkan alamat email"
                                autocomplete="email"
                                maxlength="255"
                                required
                            >

                        </div>
                    </div>

                    <button type="submit" class="sl-submit">
                        Kirim Permintaan
                    </button>

                </form>

                <a
                    href="{{ route('login') }}"
                    class="sl-back sl-forgot-back"
                >
                    Kembali ke Login
                </a>

            </div>
        </div>
    </main>

</div>
@endsection