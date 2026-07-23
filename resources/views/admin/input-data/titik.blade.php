@extends('admin.input-data.layout')

@section('title', 'Add/Delete Titik')
@section('page-title')
  ADD / DELETE TITIK: {{ strtoupper($unit) }}{{ $area ? ' — ' . strtoupper($area->name) : '' }}
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

    <div class="bg-panel rounded-lg p-4">
      <div class="flex items-center justify-between mb-1 flex-wrap gap-3">
        <div class="text-xs font-bold tracking-wide">
          TITIK UKUR AREA {{ strtoupper($area->name) }} ({{ $points->count() }} TITIK)
        </div>
        <form method="POST" action="{{ route('input-data.titik.add') }}">
          @csrf
          <input type="hidden" name="unit" value="{{ $unit }}">
          <input type="hidden" name="section" value="{{ $area->name }}">
          <button type="submit" class="btn-gold font-bold text-xs px-4 py-2 rounded whitespace-nowrap">+ TAMBAH TITIK</button>
        </form>
      </div>
      <div class="text-[11px] text-slate-400 mb-4">
        Susunan titik berlaku untuk <span class="text-slate-200 font-semibold">semua pipa</span> di area ini
        (bawaan A&ndash;D). Titik baru memakai huruf berikutnya yang kosong.
        Menghapus titik akan menghapus nilai ukur titik itu di seluruh pipa area.
      </div>

      <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
        @foreach ($points as $p)
          <div class="flex items-center justify-between bg-white/[0.05] border border-white/10 rounded px-3 py-2.5">
            <div class="text-xs">
              <span class="font-bold text-white">TITIK {{ $p['point'] }}</span>
              <span class="text-slate-400 ml-2">
                {{ $p['filled'] > 0 ? 'terisi di ' . $p['filled'] . ' pipa' : 'belum ada nilai' }}
              </span>
            </div>
            <form method="POST" action="{{ route('input-data.titik.delete') }}"
                  onsubmit="return confirm('Hapus titik {{ $p['point'] }} dari area {{ $area->name }}? Nilai ukur titik ini di semua pipa ikut terhapus.')">
              @csrf
              @method('DELETE')
              <input type="hidden" name="unit" value="{{ $unit }}">
              <input type="hidden" name="section" value="{{ $area->name }}">
              <input type="hidden" name="point" value="{{ $p['point'] }}">
              <button type="submit" class="text-critical hover:text-red-300 font-semibold text-[11px]"
                      @disabled($points->count() <= 1)>HAPUS</button>
            </form>
          </div>
        @endforeach
      </div>
    </div>

  @endif

@endsection
