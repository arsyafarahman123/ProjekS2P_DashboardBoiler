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
            radial-gradient(ellipse 1000px 560px at 12% -8%, #e0a94022, transparent 60%),
            radial-gradient(ellipse 900px 560px at 100% 0%, #1a4a7a3d, transparent 60%),
            #050b1a;
    }
    .login-card {
        width:100%; max-width:380px; margin:20px;
        background:linear-gradient(180deg, rgba(0,26,87,0.25) 0%, rgba(14,32,56,1) 100%);
        border:1px solid rgba(255,255,255,0.08); border-radius:8px;
        padding:32px 30px; box-shadow:0 10px 40px rgba(0,0,0,.45);
    }
    .login-card .logo { display:flex; align-items:center; gap:12px; margin-bottom:22px; }
    .login-card .logo img { width:46px; height:auto; display:block; }
    .login-card .logo .brand {
        font-family:'Poppins',sans-serif; font-weight:800; font-size:16px; color:#e0a940; letter-spacing:.5px;
    }
    .login-card h1 { color:#fff; font-size:17px; font-weight:700; margin:0 0 4px 0; letter-spacing:.5px; }
    .login-card p.sub { color:#9fb0c3; font-size:12.5px; margin:0 0 22px 0; }
    label { display:block; color:#f0b94a; font-size:11px; font-weight:700; letter-spacing:1px; margin-bottom:6px; }
    input[type=email], input[type=password] {
        width:100%; padding:10px 12px; margin-bottom:16px; border-radius:4px;
        background:rgba(255,255,255,0.07); border:1px solid rgba(255,255,255,0.14);
        color:#eef2f6; font-size:13.5px; font-family:inherit;
    }
    input:focus { outline:none; border-color:#e0a940; }
    button[type=submit] {
        width:100%; padding:11px; border:none; border-radius:4px; cursor:pointer;
        background:linear-gradient(135deg,#e0a940,#a97e1f); color:#2a1a00;
        font-weight:800; font-size:13px; letter-spacing:1px; font-family:inherit;
    }
    button[type=submit]:hover { filter:brightness(1.08); }
    .error-msg {
        background:#3a1414; border:1px solid #e5484d; border-radius:6px;
        padding:10px 12px; color:#fecaca; font-size:12.5px; margin-bottom:16px;
    }
    a.back-link { display:inline-block; margin-top:18px; color:#8fb4d6; font-size:12px; text-decoration:none; }
    a.back-link:hover { color:#e0a940; }
</style>
</head>
<body>
<div class="login-card">
    <div class="logo">
        <img src="{{ asset('images/logo.png') }}" alt="S2P Logo">
        <span class="brand">S2P PLTU CILACAP</span>
    </div>
    <h1>ADMIN LOGIN</h1>
    <p class="sub">Masuk untuk mengelola area boiler &amp; input data titik ukur pipa.</p>

    @if ($errors->any())
        <div class="error-msg">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <label for="email">EMAIL</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>

        <label for="password">PASSWORD</label>
        <input id="password" type="password" name="password" required>

        <button type="submit">MASUK</button>
    </form>

    <a class="back-link" href="{{ route('global-view') }}">&larr; Kembali ke dashboard</a>
</div>
</body>
</html>
