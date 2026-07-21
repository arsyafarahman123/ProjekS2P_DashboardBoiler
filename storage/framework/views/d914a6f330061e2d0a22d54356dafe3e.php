<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Maintenance - S2P Boiler Dashboard</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    theme: {
      extend: {
        colors: {
          panel: "#1c355c",
          bgnavy: "#28425f",
          accent: "#f5a623",
          safe: "#22c55e",
          watch: "#eab308",
          critical: "#ef4444",
        }
      }
    }
  }
</script>
<style>
  body { font-family: ui-sans-serif, system-ui, sans-serif; }

  .sidebar{
    width:72px;
    min-width:72px;
    background:linear-gradient(180deg, #0a1729 0%, #0d2038 100%);
    display:flex;
    flex-direction:column;
    align-items:center;
    padding-top:16px;
    gap:36px;
    min-height:100vh;
  }
  .sidebar .logo-box{
    width:64px;
    height:44px;
    display:flex;
    align-items:center;
    justify-content:center;
    position:relative;
  }
  .sidebar .logo-box .logo-text{
    color:#f5a623;
    font-weight:800;
    font-size:15px;
    letter-spacing:1px;
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
    color:#64748b;
    position:relative;
    padding:4px 0;
    background:none;
    border:none;
    cursor:pointer;
    text-decoration:none;
  }
  .sidebar-nav .nav-item:hover{
    color:#cbd5e1;
  }
  .sidebar-nav .nav-item.active{
    color:#fff;
    border-left:3px solid #f5a623;
    padding-left:10px;
    margin-left:-13px;
  }

  .header-logo-box{
    width:64px;
    height:44px;
    display:flex;
    align-items:center;
    justify-content:center;
    position:relative;
  }
  .header-logo-box .logo-text{
    color:#f5a623;
    font-weight:800;
    font-size:15px;
    letter-spacing:1px;
  }
</style>
</head>
<body class="bg-bgnavy text-slate-200 min-h-screen">

<div class="flex">

  <aside class="sidebar">
    <div class="logo-box">
      <img src="<?php echo e(asset('images/logo.png')); ?>" alt="S2P logo" class="w-full h-full object-contain">
    </div>
    <nav class="sidebar-nav">
      <a href="<?php echo e(route('global-view')); ?>" class="nav-item">GLOBAL VIEW</a>
      <a href="<?php echo e(route('tube.mapping')); ?>" class="nav-item">TUBE MAPPING</a>
      <a href="<?php echo e(route('rla-analysis')); ?>" class="nav-item">RLA ANALYSIS</a>
      <a href="<?php echo e(route('maintenance')); ?>" class="nav-item active">MAINTENANCE</a>
    </nav>
  </aside>

  <main class="flex-1 p-6">

    <div class="flex items-center justify-between mb-5">
      <h1 class="text-lg font-bold tracking-wide">
        <span class="text-accent">|</span> MAINTENANCE
      </h1>
      <div class="header-logo-box">
        <img src="<?php echo e(asset('images/logo.png')); ?>" alt="S2P logo" class="w-full h-full object-contain">
      </div>
    </div>

    <div class="bg-panel rounded-lg p-10 flex flex-col items-center justify-center text-center gap-3" style="min-height:60vh;">
      <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#f5a623" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
      </svg>
      <div class="text-lg font-bold text-slate-100">Halaman Maintenance</div>
      <p class="text-sm text-slate-400 max-w-md">
        Modul Maintenance sedang dalam pengembangan. Halaman ini nantinya akan menampilkan jadwal
        perawatan, riwayat penggantian tube, dan rekomendasi aksi maintenance berdasarkan hasil
        analisis dari modul Tube Mapping &amp; RLA Analysis.
      </p>
      <a href="<?php echo e(route('tube.mapping')); ?>" class="mt-2 text-xs font-semibold text-accent hover:underline">
        &larr; Kembali ke Tube Mapping
      </a>
    </div>

  </main>
</div>

</body>
</html>
<?php /**PATH C:\Users\ASUS\Downloads\ProjekS2P_DashboardBoiler-fixed (3)\ProjekS2P_DashboardBoiler-main\resources\views/maintenance/index.blade.php ENDPATH**/ ?>