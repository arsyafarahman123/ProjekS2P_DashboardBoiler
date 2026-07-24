<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login — S2P PLTU Cilacap</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
<style>
    * { box-sizing: border-box; }
    body {
        font-family: 'Inter', sans-serif; margin:0; min-height:100vh;
        display:flex; align-items:center; justify-content:center;
        background:
            radial-gradient(ellipse 1000px 560px at 12% -8%, #f5b30122, transparent 60%),
            radial-gradient(ellipse 900px 560px at 100% 0%, #1a4a7a3d, transparent 60%),
            radial-gradient(ellipse 700px 500px at 50% 110%, #0d3a5c40, transparent 60%),
            #050b1a;
        background-attachment: fixed;
        position: relative;
        overflow: hidden;
    }

    .bg-strip {
        position: fixed; top: 0; left: 0; right: 0; height: 3px; z-index: 5;
        background: linear-gradient(90deg, #061948, #f5b301 35%, #3ba7ff 65%, #061948);
        background-size: 300% 100%;
        animation: strip-flow 8s linear infinite;
    }
    @keyframes strip-flow { 0% {background-position: 0% 0;} 100% {background-position: 300% 0;} }

    .login-wrap { position: relative; z-index: 2; width: 100%; max-width: 400px; margin: 20px; }

    .login-card {
        position: relative;
        background: linear-gradient(120deg, #64798f 0%, #586C82 45%, #46586c 100%);
        border: 1px solid #6f849a; border-radius: 18px;
        padding: 36px 34px 30px 34px;
        box-shadow: 0 20px 60px rgba(0,0,0,.5), inset 0 1px 0 rgba(255,255,255,0.05);
        overflow: hidden;
    }
    .login-card::before {
        content:""; position:absolute; top:0; left:0; right:0; height:3px;
        background: linear-gradient(90deg, #f5b301, #ffd873, #f5b301);
    }
    .glow-orb { position:absolute; border-radius:50%; filter: blur(50px); pointer-events:none; z-index:0; }

    .login-content { position: relative; z-index: 1; }

    .logo-row { display:flex; align-items:center; gap:12px; margin-bottom:26px; }
    .logo-box {
        width:52px; height:40px; background:#0e2037; border-top:2px solid #f5b301; border-bottom:2px solid #f5b301;
        border-radius:2px; overflow:hidden; display:flex; align-items:center; justify-content:center; flex-shrink:0;
    }
    .logo-box img { width:100%; height:100%; object-fit:contain; display:block; }
    .logo-box .logo-text { font-family:'Poppins',sans-serif; font-weight:800; font-size:15px; color:#f5b301; line-height:1; }
    .brand-name { font-family:'Poppins',sans-serif; font-weight:800; font-size:15px; color:#f5b301; letter-spacing:.5px; line-height:1.3; }
    .brand-sub { color:#9fc0dc; font-size:10.5px; font-weight:600; letter-spacing:.8px; }

    .status-badge {
        display:inline-flex; align-items:center; gap:6px; padding:4px 12px; border-radius:999px;
        background:rgba(34,197,94,.12); border:1px solid rgba(34,197,94,.3);
        color:#4ade80; font-size:10px; font-weight:700; letter-spacing:.6px; margin-bottom:18px;
    }
    .pulse-dot { display:inline-block; width:7px; height:7px; border-radius:50%; background:#22c55e; animation: pulse-glow 1.8s infinite; }
    @keyframes pulse-glow {
        0% { box-shadow: 0 0 0 0 rgba(34,197,94,.6); } 70% { box-shadow: 0 0 0 7px rgba(34,197,94,0); } 100% { box-shadow: 0 0 0 0 rgba(34,197,94,0); }
    }

    .title-row { display:flex; align-items:center; gap:10px; margin-bottom:6px; }
    .title-row .accent-bar { width:4px; height:20px; background:#f5b301; border-radius:2px; flex-shrink:0; }
    .login-card h1 { color:#fff; font-size:19px; font-weight:800; margin:0; letter-spacing:.4px; font-family:'Poppins',sans-serif; }
    .login-card p.sub { color:#d7e4f0; font-size:12.5px; line-height:1.5; margin:0 0 26px 0; }

    .field-group { margin-bottom:18px; }
    label { display:flex; align-items:center; gap:6px; color:#f5b301; font-size:10.5px; font-weight:800; letter-spacing:1px; margin-bottom:7px; }
    label svg { flex-shrink:0; }

    .input-wrap { position: relative; }
    .input-wrap svg.input-icon {
        position:absolute; left:13px; top:50%; transform:translateY(-50%); pointer-events:none; color:#9fc0dc;
    }
    input[type=text], input[type=password] {
        width:100%; padding:11px 14px 11px 40px; border-radius:10px;
        background:#2c3f54; border:1px solid #4a6178;
        color:#eef2f6; font-size:13.5px; font-family:inherit;
        transition: border-color .15s ease, background .15s ease;
    }
    input[type=text]:focus, input[type=password]:focus {
        outline:none; border-color:#f5b301; background:#33475d;
    }
    input::placeholder { color:#6d84a0; }

    button[type=submit] {
        width:100%; padding:13px; border:none; border-radius:10px; cursor:pointer; margin-top:6px;
        background:linear-gradient(135deg,#f5b301,#c9860f); color:#2a1a00;
        font-weight:800; font-size:13.5px; letter-spacing:1.2px; font-family:inherit;
        box-shadow: 0 6px 18px rgba(245,179,1,.25);
        transition: filter .15s ease, transform .15s ease;
    }
    button[type=submit]:hover { filter:brightness(1.08); transform: translateY(-1px); }
    button[type=submit]:active { transform: translateY(0); }

    .error-msg {
        display:flex; align-items:center; gap:8px;
        background:#3a1414; border:1px solid #ef4444; border-radius:8px;
        padding:10px 13px; color:#fecaca; font-size:12.5px; margin-bottom:18px;
    }

    .divider { height:1px; background:rgba(255,255,255,0.08); margin:22px 0 16px 0; }

    a.back-link {
        display:flex; align-items:center; gap:6px; color:#9fc0dc; font-size:12px; font-weight:600; text-decoration:none;
        transition: color .15s ease;
    }
    a.back-link:hover { color:#f5b301; }

    .footer-note { text-align:center; margin-top:22px; color:#5b86ac; font-size:10.5px; letter-spacing:.3px; }
</style>
</head>
<body>
<div class="bg-strip"></div>

<div class="login-wrap">
    <div class="login-card">
        <div class="glow-orb" style="width:220px;height:220px;background:#f5b301;opacity:.10;top:-90px;right:-70px;"></div>
        <div class="glow-orb" style="width:200px;height:200px;background:#3ba7ff;opacity:.12;bottom:-80px;left:-60px;"></div>

        <div class="login-content">
            <div class="logo-row">
                <div class="logo-box">
                    <img src="{{ asset('images/logo.png') }}" alt="S2P Logo">
                </div>
                <div>
                    <div class="brand-name">S2P PLTU CILACAP</div>
                    <div class="brand-sub">BOILER DIGITAL TWIN</div>
                </div>
            </div>

            <div class="status-badge">
                <span class="pulse-dot"></span>
                SYSTEM ONLINE
            </div>

            <div class="title-row">
                <span class="accent-bar"></span>
                <h1>ADMIN LOGIN</h1>
            </div>
            <p class="sub">Masuk untuk mengelola area boiler &amp; input data titik ukur pipa.</p>

            @if ($errors->any())
                <div class="error-msg">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="#ef4444" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;"><path d="M12 3 L22.5 21 H1.5 Z"></path><line x1="12" y1="9.5" x2="12" y2="14.5"></line><circle cx="12" cy="17.5" r="0.6" fill="#ef4444" stroke="none"></circle></svg>
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="field-group">
                    <label for="email">
                        <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="#f5b301" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"></circle><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"></path></svg>
                        USERNAME
                    </label>
                    <div class="input-wrap">
                        <svg class="input-icon" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"></circle><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"></path></svg>
                        <input id="email" type="text" name="email" value="{{ old('email') }}" placeholder="Masukkan username" required autofocus>
                    </div>
                </div>

                <div class="field-group">
                    <label for="password">
                        <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="#f5b301" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="9" rx="2"></rect><path d="M8 11 V7a4 4 0 0 1 8 0 v4"></path></svg>
                        PASSWORD
                    </label>
                    <div class="input-wrap">
                        <svg class="input-icon" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="9" rx="2"></rect><path d="M8 11 V7a4 4 0 0 1 8 0 v4"></path></svg>
                        <input id="password" type="password" name="password" placeholder="Masukkan password" required>
                    </div>
                </div>

                <button type="submit">MASUK</button>
            </form>

            <div class="divider"></div>

            <a class="back-link" href="{{ route('global-view') }}">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                Kembali ke dashboard
            </a>
        </div>
    </div>

    <div class="footer-note">© 2026 PT S2P — PLTU Cilacap Boiler Monitoring System</div>
</div>
</body>
</html>