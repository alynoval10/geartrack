@extends('student.layouts.app', ['title' => 'Ganti Kata Sandi'])

@section('content')
    <section class="student-card" style="max-width: 560px; margin: 30px auto 0;">
        <div class="student-card-head">
            <p class="student-eyebrow">Keamanan Akun</p>
            <h1 class="student-title" style="font-size: 28px;">{{ auth()->guard('student')->user()->must_change_password ? 'Buat Kata Sandi Baru' : 'Ganti Kata Sandi' }}</h1>
            <p class="student-subtitle">Gunakan minimal 8 karakter dan jangan gunakan NIS sebagai kata sandi.</p>
        </div>
        <div class="student-card-body">
            <form class="student-form" method="POST" action="{{ route('student.password.update') }}">
                @csrf
                <div class="student-field">
                    <label for="current_password">Kata sandi saat ini</label>
                    <input id="current_password" name="current_password" type="password" autocomplete="current-password" required>
                </div>
                <div class="student-field">
                    <label for="password">Kata sandi baru</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" required>
                </div>
                <div class="student-field">
                    <label for="password_confirmation">Ulangi kata sandi baru</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required>
                </div>
                <button class="student-button" type="submit">Simpan Kata Sandi</button>
            </form>
        </div>
    </section>
@endsection
