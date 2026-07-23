@extends('admin.input-data.layout')

@section('title', 'Add/Delete Pipa')
@section('page-title')
  ADD / DELETE PIPA: {{ strtoupper($unit) }}{{ $area ? ' — ' . strtoupper($area->name) : '' }}
@endsection

@section('content')

  <a href="{{ route('input-data.index', ['unit' => $unit, 'section' => $area?->name]) }}"
     class="inline-block text-[11px] text-[#8fb4d6] hover:text-accent font-semibold mb-4">&larr; Kembali ke menu Input Data</a>

  @include('admin.input-data._filter')

  @if (! $area)
    <div class="bg-panel rounded-lg p-6 text-center text-xs text-slate-400">
      Unit ini belum punya area. Tambahkan area lewat tombol <span class="text-accent font-bold">ADD AREA</span> di Global View dulu.
    </div>
  @else
    <div class="grid md:grid-cols-3 gap-5">

      <div class="bg-panel rounded-lg p-5">
        <div class="text-xs font-bold tracking-wide mb-3">JUMLAH PIPA SAAT INI</div>
        <div class="text-[34px] font-bold text-white leading-none mb-2">{{ $area->tube_count }}</div>
        <div class="text-xs text-slate-400">
          pipa pada area {{ $area->name }}@if($area->tube_count > 0), nomor 1&ndash;{{ $area->tube_count }}@endif.<br>
          <span class="text-slate-300 font-semibold">{{ $withData }}</span> pipa sudah punya data pengukuran.
        </div>
      </div>

      <div class="bg-panel rounded-lg p-5">
        <div class="text-xs font-bold tracking-wide mb-3 text-safe">TAMBAH PIPA</div>
        <form method="POST" action="{{ route('input-data.pipa.add') }}" class="flex gap-3 items-end">
          @csrf
          <input type="hidden" name="unit" value="{{ $unit }}">
          <input type="hidden" name="section" value="{{ $area->name }}">
          <div class="flex-1">
            <label class="field-label" for="jumlah-tambah">JUMLAH YANG DITAMBAH</label>
            <input class="field-input" id="jumlah-tambah" name="jumlah" type="number" min="1" max="2000" required placeholder="mis. 10">
          </div>
          <button type="submit" class="btn-gold font-bold text-xs px-5 py-2.5 rounded whitespace-nowrap">TAMBAH</button>
        </form>
        <div class="text-[11px] text-slate-500 mt-3">
          Pipa baru ditambahkan setelah nomor terakhir (misal dari {{ $area->tube_count }} menjadi {{ $area->tube_count + 10 }} bila menambah 10).
        </div>
      </div>

      <div class="bg-panel rounded-lg p-5">
        <div class="text-xs font-bold tracking-wide mb-3 text-critical">KURANGI PIPA</div>
        <form method="POST" action="{{ route('input-data.pipa.reduce') }}" class="flex gap-3 items-end"
              onsubmit="return confirm('Kurangi pipa area {{ $area->name }}? Pipa dihapus dari nomor paling akhir dan data pengukurannya ikut terhapus.')">
          @csrf
          <input type="hidden" name="unit" value="{{ $unit }}">
          <input type="hidden" name="section" value="{{ $area->name }}">
          <div class="flex-1">
            <label class="field-label" for="jumlah-kurang">JUMLAH YANG DIKURANGI</label>
            <input class="field-input" id="jumlah-kurang" name="jumlah" type="number" min="1" max="{{ max(1, $area->tube_count) }}" required
                   placeholder="mis. 5" @disabled($area->tube_count < 1)>
          </div>
          <button type="submit" class="font-bold text-xs px-5 py-2.5 rounded whitespace-nowrap bg-critical/80 hover:bg-critical text-white"
                  @disabled($area->tube_count < 1)>KURANGI</button>
        </form>
        <div class="text-[11px] text-slate-500 mt-3">
          Pipa dikurangi dari nomor paling akhir. Data pengukuran pipa yang terhapus ikut dibersihkan.
        </div>
      </div>

    </div>
  @endif

@endsection
