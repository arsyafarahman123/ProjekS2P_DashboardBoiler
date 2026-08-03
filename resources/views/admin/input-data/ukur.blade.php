@extends('admin.input-data.layout')

@section('title', 'Input Data Pengukuran')
@section('page-title')
  INPUT DATA PENGUKURAN: {{ strtoupper($unit) }}{{ $area ? ' — ' . strtoupper($area->name) : '' }}
@endsection

@section('content')

  <a href="{{ route('input-data.index', ['unit' => $unit, 'section' => $area?->name]) }}"
     class="inline-block text-[11px] text-[#8fb4d6] hover:text-accent font-semibold mb-4">&larr; Kembali ke menu Input Data</a>

  @include('admin.input-data._filter')

  @if (! $area)
    <div class="bg-panel rounded-lg p-6 text-center text-xs text-slate-400">
      Unit ini belum punya area. Tambahkan area lewat tombol <span class="text-accent font-bold">ADD AREA</span> di Global View dulu.
    </div>
  @elseif ($area->tube_count < 1)
    <div class="bg-panel rounded-lg p-6 text-center text-xs text-slate-400">
      Area <span class="text-white font-bold">{{ $area->name }}</span> belum punya pipa.
      Tambahkan dulu lewat menu
      <a href="{{ route('input-data.pipa', ['unit' => $unit, 'section' => $area->name]) }}" class="text-accent font-bold hover:underline">Add/Delete Pipa</a>.
    </div>
  @else

    {{-- Form input satu-per-satu: pilih pipa # + titik dulu, baru isi nilainya --}}
    <div class="bg-panel rounded-lg p-5 mb-5">
      <div class="text-xs font-bold tracking-wide mb-1">
        INPUT NILAI &mdash; {{ strtoupper($area->name) }} ({{ $filledCount }} DARI {{ $area->tube_count }} PIPA TERISI)
      </div>
      <div class="text-[11px] text-slate-400 mb-4">
        Pilih dulu nomor pipa dan titik ukur (A/B/C/D), baru isi nilainya. Satu form = satu titik.
        Titik ukur area ini: <span class="text-slate-200 font-semibold">{{ implode(', ', $points) }}</span>
        (atur lewat <a href="{{ route('input-data.titik', ['unit' => $unit, 'section' => $area->name]) }}" class="text-accent hover:underline font-semibold">Add/Delete Titik</a>).
      </div>

      <form method="POST" action="{{ route('input-data.ukur.store') }}" class="grid sm:grid-cols-2 lg:grid-cols-6 gap-4 items-end">
        @csrf
        <input type="hidden" name="unit" value="{{ $unit }}">
        <input type="hidden" name="section" value="{{ $area->name }}">

        <div>
          <label class="field-label" for="tube_number">1. PILIH PIPA #</label>
          <select class="field-input" id="tube_number" name="tube_number" required>
            <option value="">&mdash; pilih &mdash;</option>
            @for ($i = 1; $i <= $area->tube_count; $i++)
              <option value="{{ $i }}" @selected(old('tube_number') == $i)>
                #{{ $i }}@if ($rows[$i] ?? null) &nbsp;(sudah ada data)@endif
              </option>
            @endfor
          </select>
        </div>

        <div>
          <label class="field-label" for="point">2. PILIH TITIK</label>
          <select class="field-input" id="point" name="point" required>
            <option value="">&mdash; pilih &mdash;</option>
            @foreach ($points as $p)
              <option value="{{ $p }}" @selected(old('point') === $p)>TITIK {{ $p }}</option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="field-label" for="nilai">3. NILAI UKUR (MM)</label>
          <input class="field-input" id="nilai" name="nilai" type="number" step="0.01" min="0" max="1000"
                 required placeholder="mis. 6.35" value="{{ old('nilai') }}">
        </div>

        <div>
          <label class="field-label" for="nilai_awal">NILAI AWAL (MM) — OPSIONAL</label>
          <input class="field-input" id="nilai_awal" name="nilai_awal" type="number" step="0.01" min="0" max="1000"
                 placeholder="isi sekali per pipa" value="{{ old('nilai_awal') }}">
        </div>

        <div>
          <label class="field-label" for="measured_at">TANGGAL UKUR</label>
          <input class="field-input" id="measured_at" name="measured_at" type="date"
                 value="{{ old('measured_at', $measuredAtDefault) }}">
        </div>

        <div>
          <button type="submit" class="btn-gold font-bold text-xs px-6 py-2.5 rounded whitespace-nowrap w-full">SIMPAN DATA</button>
        </div>
      </form>
    </div>

    {{-- Rekap data yang sudah tersimpan --}}
    <div class="bg-panel rounded-lg p-4">
      <div class="flex items-center justify-between mb-3 flex-wrap gap-3">
        <div class="text-xs font-bold tracking-wide">REKAP DATA TERSIMPAN &mdash; {{ strtoupper($area->name) }}</div>
        <a href="{{ route('tube.mapping') }}" class="text-[11px] text-[#8fb4d6] hover:text-accent">Lihat di Tube Mapping &rarr;</a>
      </div>

      <div id="grid-ukur" class="overflow-x-auto overflow-y-auto" style="max-height:55vh;">
        <table class="w-full text-[11px] border-separate border-spacing-0">
          <thead class="text-slate-400">
            <tr class="text-left">
              <th class="font-normal py-2 pr-2 sticky top-0 bg-[#101f3a] z-10">PIPA #</th>
              <th class="font-normal py-2 pr-2 sticky top-0 bg-[#101f3a] z-10">NILAI AWAL (MM)</th>
              @foreach ($points as $p)
                <th class="font-normal py-2 pr-2 sticky top-0 bg-[#101f3a] z-10">TITIK {{ $p }} (MM)</th>
              @endforeach
              <th class="font-normal py-2 sticky top-0 bg-[#101f3a] z-10 text-right">AKSI</th>
            </tr>
          </thead>
          <tbody class="text-slate-200">
            @for ($i = 1; $i <= $area->tube_count; $i++)
              @php $r = $rows[$i] ?? null; @endphp
              <tr id="pipa-{{ $i }}" class="{{ $r ? 'bg-white/[0.03]' : '' }}">
                <td class="py-1.5 pr-2 font-bold text-accent whitespace-nowrap border-t border-white/5">
                  #{{ $i }}
                  @if ($r)<span class="text-safe text-[9px] font-semibold ml-1" title="sudah ada data">&#9679;</span>@endif
                </td>
                <td class="py-1.5 pr-2 border-t border-white/5">{{ $r['initial'] ?? '—' }}</td>
                @foreach ($points as $p)
                  <td class="py-1.5 pr-2 border-t border-white/5">
                    @if (isset($r['points'][$p]))
                      <span class="font-semibold">{{ $r['points'][$p] }}</span>
                      <form method="POST" action="{{ route('input-data.ukur.destroy.point', [$i, $p]) }}"
                            class="inline" onsubmit="return confirm('Hapus nilai titik {{ $p }} pipa #{{ $i }}?')">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="unit" value="{{ $unit }}">
                        <input type="hidden" name="section" value="{{ $area->name }}">
                        <button type="submit" class="text-critical hover:text-red-300 text-[9px] font-semibold ml-1" title="hapus titik ini">&times;</button>
                      </form>
                    @else
                      <span class="text-slate-600">—</span>
                    @endif
                  </td>
                @endforeach
                <td class="py-1.5 text-right whitespace-nowrap border-t border-white/5">
                  @if ($r)
                    <form method="POST" action="{{ route('input-data.ukur.destroy', $i) }}"
                          onsubmit="return confirm('Hapus semua data pipa #{{ $i }} di area {{ $area->name }}?')">
                      @csrf
                      @method('DELETE')
                      <input type="hidden" name="unit" value="{{ $unit }}">
                      <input type="hidden" name="section" value="{{ $area->name }}">
                      <button type="submit" class="text-critical hover:text-red-300 font-semibold text-[10px]">HAPUS SEMUA</button>
                    </form>
                  @endif
                </td>
              </tr>
            @endfor
          </tbody>
        </table>
      </div>
    </div>

  @endif

@endsection
