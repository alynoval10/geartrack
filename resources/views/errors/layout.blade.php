<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') | GearTrack</title>
    <style>
        * { box-sizing: border-box; }
        body { display: grid; min-height: 100vh; margin: 0; padding: 24px; place-items: center; background: #f1f5f9; color: #0f172a; font-family: Inter, system-ui, sans-serif; }
        main { width: min(100%, 520px); padding: 32px; border: 1px solid #e2e8f0; border-radius: 20px; background: white; box-shadow: 0 18px 45px rgb(15 23 42 / .08); text-align: center; }
        .code { color: #0369a1; font-size: 13px; font-weight: 800; letter-spacing: .12em; }
        h1 { margin: 10px 0; font-size: clamp(26px, 7vw, 38px); }
        p { margin: 0 auto; color: #64748b; line-height: 1.65; }
        nav { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; margin-top: 24px; }
        a { display: inline-flex; min-height: 42px; align-items: center; justify-content: center; padding: 0 16px; border-radius: 10px; background: #0369a1; color: white; font-weight: 700; text-decoration: none; }
        a.secondary { border: 1px solid #cbd5e1; background: white; color: #334155; }
    </style>
</head>
<body>
    <main>
        <div class="code">@yield('code')</div>
        <h1>@yield('heading')</h1>
        <p>@yield('message')</p>
        <nav aria-label="Pilihan pemulihan">
            <a href="{{ url('/') }}">Kembali ke GearTrack</a>
            @hasSection('secondaryUrl')
                <a class="secondary" href="@yield('secondaryUrl')">@yield('secondaryLabel')</a>
            @endif
        </nav>
    </main>
</body>
</html>
