@extends('student.layouts.app', ['title' => 'Masuk Siswa'])

@section('content')
    <section class="student-card" style="max-width: 480px; margin: 48px auto 0;">
        <div class="student-card-head">
            <p class="student-eyebrow">Akses Siswa</p>
            <h1 class="student-title">Masuk ke GearTrack</h1>
            <p class="student-subtitle">Gunakan NIS dan kata sandi akun siswa Anda.</p>
        </div>
        <div class="student-card-body">
            <form class="student-form" method="POST" action="{{ route('student.login.store') }}">
                @csrf
                <div class="student-field">
                    <label for="nis">NIS</label>
                    <input id="nis" name="nis" type="text" value="{{ old('nis') }}" inputmode="numeric" autocomplete="username" required autofocus>
                </div>
                <div class="student-field">
                    <label for="password">Kata sandi</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required>
                    <p class="student-help">Kata sandi awal sama dengan NIS. Setelah masuk pertama kali, Anda diminta menggantinya.</p>
                </div>
                <button class="student-button" type="submit">Masuk</button>
                <p class="student-muted">Akun hanya tersedia bagi siswa aktif pada tahun ajaran berjalan. Jika tidak dapat masuk, hubungi guru atau administrator.</p>
            </form>
        </div>
    </section>
@endsection
