<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aktivasi Administrator | GearTrack</title>
    <style>
        * { box-sizing: border-box; }
        body { display: grid; min-height: 100vh; margin: 0; padding: 24px; place-items: center; background: #f1f5f9; color: #0f172a; font-family: Inter, system-ui, sans-serif; }
        main { width: min(100%, 560px); padding: 32px; border: 1px solid #e2e8f0; border-radius: 18px; background: #fff; box-shadow: 0 18px 45px rgb(15 23 42 / .08); }
        .brand { margin: 0; color: #0369a1; font-size: 13px; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; }
        h1 { margin: 8px 0 6px; font-size: clamp(25px, 6vw, 32px); }
        .intro, .help { color: #64748b; line-height: 1.6; }
        .intro { margin: 0 0 24px; }
        label { display: block; margin: 16px 0 6px; font-size: 14px; font-weight: 650; }
        input { width: 100%; min-height: 44px; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 9px; color: #0f172a; font: inherit; }
        input:focus { outline: 2px solid #0284c7; outline-offset: 1px; }
        .help { margin: 6px 0 0; font-size: 13px; }
        .error { margin: 6px 0 0; color: #b91c1c; font-size: 13px; }
        .alert { margin: 0 0 16px; padding: 12px 14px; border: 1px solid #fecaca; border-radius: 9px; background: #fef2f2; color: #991b1b; font-size: 14px; }
        button { width: 100%; min-height: 46px; margin-top: 24px; border: 0; border-radius: 9px; background: #0369a1; color: #fff; font: inherit; font-weight: 700; cursor: pointer; }
        button:hover { background: #075985; }
        @media (max-width: 520px) { main { padding: 24px; } }
    </style>
</head>
<body>
    <main>
        <p class="brand">GearTrack</p>
        <h1>Pengaturan awal GearTrack</h1>
        <p class="intro">Buat akun Administrator utama untuk instalasi ini. Setelah akun dibuat, halaman ini tidak dapat digunakan kembali.</p>

        @if ($errors->any())
            <div class="alert" role="alert">{{ $errors->first('setup') ?: 'Periksa kembali data yang diisi.' }}</div>
        @endif

        <form method="POST" action="{{ route('initial-admin.store') }}" autocomplete="on">
            @csrf
            <label for="name">Nama Administrator Utama</label>
            <input id="name" name="name" value="{{ old('name') }}" required maxlength="255" autocomplete="name">
            @error('name') <p class="error">{{ $message }}</p> @enderror

            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email">
            @error('email') <p class="error">{{ $message }}</p> @enderror

            <label for="password">Kata sandi</label>
            <input id="password" name="password" type="password" required minlength="12" maxlength="255" autocomplete="new-password">
            <p class="help">Gunakan kata sandi minimal 12 karakter.</p>
            @error('password') <p class="error">{{ $message }}</p> @enderror

            <label for="password_confirmation">Konfirmasi kata sandi</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required minlength="12" maxlength="255" autocomplete="new-password">

            <button type="submit">Simpan dan lanjutkan</button>
        </form>
    </main>
</body>
</html>
