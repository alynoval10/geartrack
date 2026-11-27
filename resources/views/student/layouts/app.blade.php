<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Portal Siswa' }} · GearTrack</title>
    <style>
        :root { color-scheme: light; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; color: #0f172a; background: #f1f5f9; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; }
        a { color: #0369a1; }
        .student-shell { width: min(1120px, calc(100% - 32px)); margin: 0 auto; padding: 24px 0 48px; }
        .student-topbar { display: flex; align-items: center; justify-content: space-between; gap: 18px; margin-bottom: 28px; }
        .student-brand { display: flex; align-items: center; gap: 12px; color: #0f172a; text-decoration: none; font-weight: 800; font-size: 19px; }
        .student-brand-mark { display: grid; place-items: center; width: 42px; height: 42px; border-radius: 13px; background: #0369a1; color: #fff; }
        .student-nav { display: flex; align-items: center; justify-content: flex-end; flex-wrap: wrap; gap: 8px; }
        .student-nav a, .student-nav button { display: inline-flex; align-items: center; min-height: 40px; padding: 0 13px; border: 1px solid #dbe2ea; border-radius: 10px; background: #fff; color: #334155; font: inherit; font-size: 13px; font-weight: 650; text-decoration: none; cursor: pointer; }
        .student-nav a:hover, .student-nav button:hover { border-color: #0369a1; color: #0369a1; }
        .student-grid { display: grid; grid-template-columns: minmax(0, 1.15fr) minmax(280px, .85fr); gap: 20px; }
        .student-card { border: 1px solid #dce3eb; border-radius: 18px; background: #fff; box-shadow: 0 8px 25px #0f172a0a; overflow: hidden; }
        .student-card-head { padding: 20px 22px; border-bottom: 1px solid #e2e8f0; }
        .student-card-body { padding: 22px; }
        .student-eyebrow { margin: 0 0 8px; color: #0369a1; font-size: 11px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
        .student-title { margin: 0; font-size: clamp(24px, 4vw, 34px); letter-spacing: -.04em; }
        .student-subtitle { margin: 8px 0 0; color: #64748b; line-height: 1.6; }
        .student-card h2 { margin: 0; font-size: 18px; }
        .student-muted { color: #64748b; font-size: 14px; line-height: 1.55; }
        .student-alert { margin: 0 0 18px; padding: 13px 15px; border: 1px solid #a7f3d0; border-radius: 12px; background: #ecfdf5; color: #065f46; }
        .student-alert-error { border-color: #fecaca; background: #fef2f2; color: #991b1b; }
        .student-form { display: grid; gap: 16px; }
        .student-field { display: grid; gap: 7px; }
        .student-field label { font-size: 13px; font-weight: 700; }
        .student-field input, .student-field select, .student-field textarea { width: 100%; min-height: 44px; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px; background: #fff; color: #0f172a; font: inherit; }
        .student-field select[multiple] { min-height: 240px; padding: 8px; }
        .student-field select[multiple] option { padding: 8px; border-radius: 6px; }
        .student-field textarea { min-height: 100px; resize: vertical; }
        .student-field input:focus, .student-field select:focus, .student-field textarea:focus { outline: 3px solid #bae6fd; border-color: #0284c7; }
        .student-help { margin: 0; color: #64748b; font-size: 12px; line-height: 1.5; }
        .student-button { display: inline-flex; justify-content: center; align-items: center; min-height: 44px; padding: 0 16px; border: 0; border-radius: 10px; background: #0369a1; color: white; font: inherit; font-weight: 750; cursor: pointer; text-decoration: none; }
        .student-button:hover { background: #075985; }
        .student-button-secondary { border: 1px solid #cbd5e1; background: white; color: #334155; }
        .student-qr { display: grid; justify-items: center; gap: 12px; }
        .student-qr svg { display: block; width: min(100%, 230px); height: auto; }
        .student-info { display: grid; gap: 12px; margin-top: 18px; }
        .student-info-row { display: flex; justify-content: space-between; gap: 12px; border-bottom: 1px solid #eef2f6; padding-bottom: 10px; font-size: 13px; }
        .student-info-row span { color: #64748b; }
        .student-history { display: grid; gap: 12px; }
        .student-request { display: grid; gap: 10px; padding: 16px; border: 1px solid #e2e8f0; border-radius: 13px; }
        .student-request-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; }
        .student-status { display: inline-flex; align-items: center; min-height: 25px; padding: 0 9px; border-radius: 999px; background: #fef3c7; color: #92400e; font-size: 11px; font-weight: 800; white-space: nowrap; }
        .student-status-approved { background: #dcfce7; color: #166534; }
        .student-status-rejected { background: #fee2e2; color: #991b1b; }
        .student-error { margin: 0; color: #b91c1c; font-size: 12px; }
        .student-scan-panel { display: grid; gap: 10px; margin-top: 12px; padding: 12px; border: 1px solid #dbe2ea; border-radius: 12px; background: #f8fafc; }
        .student-selected-assets { display: flex; flex-wrap: wrap; gap: 6px; min-height: 42px; padding: 7px; border: 1px solid #cbd5e1; border-radius: 10px; background: white; }
        .student-selected-assets[hidden] { display: none; }
        .student-selected-chip { display: inline-flex; align-items: center; gap: 7px; max-width: 100%; min-height: 28px; padding: 4px 7px 4px 9px; border: 1px solid #bae6fd; border-radius: 7px; background: #f0f9ff; color: #075985; font-size: 12px; line-height: 1.35; }
        .student-selected-chip span { overflow-wrap: anywhere; }
        .student-selected-chip button { display: grid; flex: 0 0 18px; place-items: center; width: 18px; height: 18px; padding: 0; border: 0; border-radius: 50%; background: transparent; color: #64748b; font-size: 16px; line-height: 1; cursor: pointer; }
        .student-selected-chip button:hover { background: #dbeafe; color: #0c4a6e; }
        .student-scan-count { margin: 0; color: #64748b; font-size: 12px; }
        .student-scan-reader { width: 100%; max-width: 420px; margin: 0 auto; overflow: hidden; border-radius: 8px; background: #0f172a; }
        .student-scan-reader video { width: 100% !important; }
        .student-scan-status { margin: 0; color: #475569; font-size: 13px; line-height: 1.5; }
        .student-scan-status[data-state="error"] { color: #b91c1c; }
        .student-scan-status[data-state="success"] { color: #047857; }
        .student-scan-actions { display: flex; flex-wrap: wrap; gap: 8px; }
        .student-scan-actions [hidden] { display: none; }
        .student-scan-file-label { position: relative; overflow: hidden; }
        .student-scan-file-label input { position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; }
        @media (max-width: 760px) { .student-shell { width: min(100% - 22px, 560px); padding-top: 14px; } .student-topbar { align-items: flex-start; flex-direction: column; } .student-nav { justify-content: flex-start; } .student-grid { grid-template-columns: 1fr; } .student-card-body { padding: 18px; } }
    </style>
</head>
<body>
    <main class="student-shell">
        <header class="student-topbar">
            <a class="student-brand" href="{{ auth()->guard('student')->check() && ! auth()->guard('student')->user()->must_change_password ? route('student.dashboard') : route('student.login') }}">
                <span class="student-brand-mark">GT</span>
                <span>GearTrack <span class="student-muted">· Portal Siswa</span></span>
            </a>
            @if (auth()->guard('student')->check() && ! auth()->guard('student')->user()->must_change_password)
                <nav class="student-nav" aria-label="Navigasi siswa">
                    <a href="{{ route('student.dashboard') }}#qr">QR Saya</a>
                    <a href="{{ route('student.dashboard') }}#request">Ajukan Peminjaman</a>
                    <a href="{{ route('student.password.edit') }}">Ganti Kata Sandi</a>
                    <form method="POST" action="{{ route('student.logout') }}">
                        @csrf
                        <button type="submit">Keluar</button>
                    </form>
                </nav>
            @endif
        </header>

        @if (session('status'))
            <p class="student-alert" role="status">{{ session('status') }}</p>
        @endif

        @if ($errors->any())
            <div class="student-alert student-alert-error" role="alert">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @yield('content')
    </main>
    @stack('scripts')
</body>
</html>
