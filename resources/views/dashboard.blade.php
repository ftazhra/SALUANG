
@extends('layouts.app')

@section('title', 'Dashboard — SALUANG')

@section('content')
<div class="auth-page">
    <div class="auth-card">
        <h1>Dashboard SALUANG</h1>

        <p>Selamat datang, <strong>{{ $user->name }}</strong>!</p>

        <p>
            Peran:
            <strong>
                {{ match($user->role) {
                    'siswa' => 'Siswa',
                    'guru' => 'Guru',
                    'orang_tua' => 'Orang Tua/Wali',
                    default => 'Tidak diketahui'
                } }}
            </strong>
        </p>

        <p>Login menggunakan database MySQL dan session Laravel berhasil.</p>

        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="auth-submit">Keluar</button>
        </form>
    </div>
</div>
@endsection
