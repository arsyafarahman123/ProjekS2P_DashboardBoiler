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

    <div class="bg-panel rounded-lg p-4">
      <div class="flex items-center justify-between mb-1 flex-wrap gap-3">
        <div class="text-xs font-bold tracking-wide">
          DATA PENGUKURAN — {{ strtoupper($area->name) }} ({{ $filledCount }} DARI {{ $area->tube_count }} PIPA TERISI)
        </div>
        <a href="{{ route('tube.mapping') }}" class="text-[11px] text-[#8fb4d6] hover:text-accent">Lihat di Tube Mapping &rarr;</a>
      </div>
      <div class="text-[11px] text-slate-400 mb-4">
        Isi nilai pipa-pipa di bawah, lalu klik <span class="text-slate-200 font-semibold">SIMPAN SEMUA</span> di bagian bawah —
        satu tanggal ukur berlaku untuk semua pipa. Kolom yang dikosongkan tidak akan mengubah data yang sudah tersimpan.
        Titik ukur area ini: <span class="text-slate-200 font-semibold">{{ implode(', ', $points) }}</span>
        (atur lewat <a href="{{ route('input-data.titik', ['unit' => $unit, 'section' => $area->name]) }}" class="text-accent hover:underline font-semibold">Add/Delete Titik</a>).
      </div>

      <div id="grid-ukur" class="overflow-x-auto overflow-y-auto" style="max-height:65vh;">
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
                <td class="py-1.5 pr-2 border-t border-white/5">
                  <input class="field-input" style="min-width:86px;padding:5px 8px;"
                         data-tube="{{ $i }}" data-field="initial"
                         type="number" step="0.01" min="0" max="1000" placeholder="mm"
                         value="{{ $r['initial'] ?? '' }}">
                </td>
                @foreach ($points as $p)
                  <td class="py-1.5 pr-2 border-t border-white/5">
                    <input class="field-input" style="min-width:76px;padding:5px 8px;"
                           data-tube="{{ $i }}" data-field="point" data-point="{{ $p }}"
                           type="number" step="0.01" min="0" max="1000" placeholder="mm"
                           value="{{ $r['points'][$p] ?? '' }}">
                  </td>
                @endforeach
                <td class="py-1.5 text-right whitespace-nowrap border-t border-white/5">
                  @if ($r)
                    <button type="submit" form="del-{{ $i }}" class="text-critical hover:text-red-300 font-semibold text-[10px]">HAPUS</button>
                  @endif
                </td>
              </tr>
            @endfor
          </tbody>
        </table>
      </div>

      {{-- Satu tanggal ukur + satu tombol simpan untuk seluruh grid --}}
      <form id="bulk-form" method="POST" action="{{ route('input-data.ukur.store') }}"
            class="mt-4 pt-4 border-t border-white/10 flex flex-wrap items-end justify-end gap-4">
        @csrf
        <input type="hidden" name="unit" value="{{ $unit }}">
        <input type="hidden" name="section" value="{{ $area->name }}">
        <input type="hidden" name="payload" id="payload">
        <div>
          <label class="field-label" for="measured_at">TANGGAL UKUR (BERLAKU SEMUA PIPA)</label>
          <input class="field-input" id="measured_at" name="measured_at" type="date"
                 value="{{ old('measured_at', $measuredAtDefault) }}" style="width:180px;">
        </div>
        <button type="submit" class="btn-gold font-bold text-xs px-8 py-2.5 rounded whitespace-nowrap">SIMPAN SEMUA</button>
      </form>
    </div>

    {{-- Form hapus per pipa (terhubung lewat atribut form="...") --}}
    @for ($i = 1; $i <= $area->tube_count; $i++)
      @if ($rows[$i] ?? null)
        <form id="del-{{ $i }}" method="POST" action="{{ route('input-data.ukur.destroy', $i) }}"
              onsubmit="return confirm('Hapus semua data pipa #{{ $i }} di area {{ $area->name }}?')">
          @csrf
          @method('DELETE')
          <input type="hidden" name="unit" value="{{ $unit }}">
          <input type="hidden" name="section" value="{{ $area->name }}">
        </form>
      @endif
    @endfor

    <script>
      // Kumpulkan semua nilai yang terisi menjadi satu paket JSON saat submit
      document.getElementById('bulk-form').addEventListener('submit', function (e) {
        const rows = {};
        document.querySelectorAll('#grid-ukur input[data-tube]').forEach(function (inp) {
          const v = inp.value.trim();
          if (v === '') return;
          const t = inp.dataset.tube;
          rows[t] = rows[t] || { tube: parseInt(t, 10), points: {} };
          if (inp.dataset.field === 'initial') {
            rows[t].initial = v;
          } else {
            rows[t].points[inp.dataset.point] = v;
          }
        });

        const list = Object.values(rows);
        if (list.length === 0) {
          e.preventDefault();
          alert('Belum ada nilai yang diisi.');
          return;
        }
        document.getElementById('payload').value = JSON.stringify(list);
      });
    </script>

  @endif

@endsection
