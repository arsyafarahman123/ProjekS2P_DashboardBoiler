@extends('layouts.dashboard')

@section('title', 'Maintenance - S2P Boiler Dashboard')

@push('head')
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
@endpush

@push('styles')
<style>
  body { font-family: ui-sans-serif, system-ui, sans-serif; }
  .bg-panel{
    background:linear-gradient(180deg, rgba(0,26,87,0.25) 0%, rgba(14,32,56,1) 100%) !important;
    border:1px solid rgba(255,255,255,0.06);
  }
  .rounded-lg{ border-radius:5px !important; }
  .accent-bar { width:4px; height:20px; background:#e0a940; border-radius:2px; flex-shrink:0; display:inline-block; }

  .header-logo-box{
    width:70px;
    height:70px;
    overflow:hidden;
    display:flex;
    align-items:center;
    justify-content:center;
    flex-shrink:0;
  }
</style>
@endpush

@section('body-class', 'bg-bgnavy text-slate-200 min-h-screen')

@section('content')
  <main class="main">

    <div class="flex items-center justify-between mb-5">
      <div class="flex items-center gap-[10px]">
        <span class="accent-bar"></span>
        <h1 class="text-[#f0b94a] text-[20px] font-bold tracking-[1.5px] m-0">MAINTENANCE</h1>
      </div>
      <div class="header-logo-box">
        <img src="{{ asset('images/logo.png') }}" alt="S2P logo" class="w-full h-full object-contain">
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
      <a href="{{ route('tube.mapping') }}" class="mt-2 text-xs font-semibold text-accent hover:underline">
        &larr; Kembali ke Tube Mapping
      </a>
    </div>

  </main>
@endsection