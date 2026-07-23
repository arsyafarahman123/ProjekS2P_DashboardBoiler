<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>@yield('title', 'Input Data') - S2P Boiler Dashboard</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    theme: {
      extend: {
        colors: {
          panel: "#0e2038",
          bgnavy: "#586C82",
          accent: "#e0a940",
          safe: "#3fdc84",
          watch: "#e0c23c",
          critical: "#e5484d",
        }
      }
    }
  }
</script>
<style>
  body { font-family: ui-sans-serif, system-ui, sans-serif; }
  input, select { color-scheme: dark; }
  select option{ color:#1a1a1a; background:#ffffff; font-weight:600; }
  .bg-panel{
    background:linear-gradient(180deg, rgba(0,26,87,0.25) 0%, rgba(14,32,56,1) 100%) !important;
    border:1px solid rgba(255,255,255,0.06);
  }
  .rounded-lg{ border-radius:5px !important; }
  .accent-bar { width:4px; height:20px; background:#e0a940; border-radius:2px; flex-shrink:0; display:inline-block; }

  .sidebar{
    width:72px;
    min-width:72px;
    background:linear-gradient(180deg, #0a1729 0%, #0d2038 100%);
    display:flex;
    flex-direction:column;
    align-items:center;
    padding-top:16px;
    gap:36px;
    position:sticky;
    top:0;
    align-self:flex-start;
    height:100vh;
  }
  .sidebar .logo-box{
    width:52px;
    background:#0e2037;
    border:2px solid #f0f0f0;
    border-radius:2px;
    overflow:hidden;
    display:flex;
    align-items:center;
    justify-content:center;
  }
  .sidebar-nav{
    display:flex;
    flex-direction:column;
    gap:44px;
    margin-top:20px;
    align-items:center;
  }
  .sidebar-nav .nav-item{
    writing-mode:vertical-rl;
    transform:rotate(180deg);
    font-size:11px;
    letter-spacing:2px;
    font-weight:600;
    color:#6d7f96;
    position:relative;
    padding:4px 8px 4px 0;
    background:none;
    border:none;
    border-right:3px solid transparent;
    cursor:pointer;
    text-decoration:none;
  }
  .sidebar-nav .nav-item:hover{ color:#cbd5e1; }
  .sidebar-nav .nav-item.active{
    color:#fff;
    border-right-color:#e0a940;
  }

  .header-logo-box{
    width:70px;
    border:2px solid #f0f0f0;
    border-radius:2px;
    overflow:hidden;
    display:flex;
    align-items:center;
    justify-content:center;
  }
  body > div.flex{ min-height:100vh; }

  .field-label{ display:block; color:#f0b94a; font-size:11px; font-weight:700; letter-spacing:1px; margin-bottom:6px; }
  .field-input{
    width:100%;
    background:rgba(255,255,255,0.08);
    border:1px solid rgba(255,255,255,0.12);
    border-radius:3px;
    padding:8px 10px;
    color:#eef2f6;
    font-size:13px;
    font-weight:600;
  }
  .field-input:focus{ outline:none; border-color:#e0a940; }
  .btn-gold{
    background:linear-gradient(135deg, #c9982f 0%, #8a6520 100%);
    color:#fff;
    letter-spacing:0.5px;
  }
</style>
</head>
<body class="bg-bgnavy text-slate-200 min-h-screen">

<div class="flex">

  <aside class="sidebar">
    <div class="logo-box">
      <img src="{{ asset('images/logo.png') }}" alt="S2P logo" class="w-full h-full object-contain">
    </div>
    <nav class="sidebar-nav">
      <a href="{{ route('global-view') }}" class="nav-item">GLOBAL VIEW</a>
      <a href="{{ route('tube.mapping') }}" class="nav-item">TUBE MAPPING</a>
      <a href="{{ route('rla-analysis') }}" class="nav-item">RLA ANALYSIS</a>
      <a href="{{ route('maintenance') }}" class="nav-item">MAINTENANCE</a>
      <a href="{{ route('input-data.index') }}" class="nav-item active">INPUT DATA</a>
    </nav>
  </aside>

  <main class="flex-1 p-6">

    <div class="flex items-center justify-between mb-5">
      <div class="flex items-center gap-[10px]">
        <span class="accent-bar"></span>
        <h1 class="text-[20px] font-bold tracking-[1.5px] text-[#f0b94a] m-0">
          @yield('page-title')
        </h1>
      </div>
      <div class="flex items-center gap-4">
        <span class="text-xs text-slate-400">Login sebagai <span class="text-slate-200 font-semibold">{{ auth()->user()->name }}</span></span>
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" class="text-[11px] font-bold tracking-wide text-slate-300 border border-white/20 rounded px-3 py-1.5 hover:text-white hover:border-white/40">LOGOUT</button>
        </form>
        <div class="header-logo-box">
          <img src="{{ asset('images/logo.png') }}" alt="S2P logo" class="w-full h-full object-contain">
        </div>
      </div>
    </div>

    @if (session('status'))
      <div class="mb-4 bg-safe/10 border border-safe/40 text-safe text-xs font-semibold rounded px-4 py-2.5">
        {{ session('status') }}
      </div>
    @endif

    @if ($errors->any())
      <div class="mb-4 bg-critical/10 border border-critical/40 text-red-300 text-xs rounded px-4 py-2.5">
        <ul class="list-disc list-inside space-y-0.5">
          @foreach ($errors->all() as $err)
            <li>{{ $err }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    @yield('content')

  </main>
</div>

</body>
</html>
