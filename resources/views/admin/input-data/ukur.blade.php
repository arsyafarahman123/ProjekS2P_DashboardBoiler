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
  @elseif ($tubeCount < 1)
    <div class="bg-panel rounded-lg p-6 text-center text-xs text-slate-400">
      Area <span class="text-white font-bold">{{ $area->name }}</span> belum punya pipa.
      Tambahkan dulu lewat menu
      <a href="{{ route('input-data.pipa', ['unit' => $unit, 'section' => $area->name]) }}" class="text-accent font-bold hover:underline">Add/Delete Pipa</a>.
    </div>
  @else

    {{-- Form input satu-per-satu: pilih pipa #, isi NILAI TITIK dan/atau NILAI UKUR --}}
    <div class="bg-panel rounded-lg p-5 mb-5">
      <div class="text-xs font-bold tracking-wide mb-1">
        INPUT NILAI &mdash; {{ strtoupper($area->name) }} ({{ $filledCount }} DARI {{ $tubeCount }} PIPA TERISI)
      </div>
      <div class="text-[11px] text-slate-400 mb-4">
        Pilih pipa, lalu isi <span class="text-slate-200 font-semibold">NILAI TITIK</span> (per titik A/B/C/D)
        dan/atau <span class="text-slate-200 font-semibold">NILAI UKUR</span> (nilai umum per pipa).
        Boleh isi sebagian saja &mdash; satu kali Simpan Data per pengisian.
        Atur titik ukur lewat <a href="{{ route('input-data.titik', ['unit' => $unit, 'section' => $area->name]) }}" class="text-accent hover:underline font-semibold">Add/Delete Titik</a>.
      </div>

      <form method="POST" action="{{ route('input-data.ukur.store') }}" class="grid sm:grid-cols-2 lg:grid-cols-6 gap-4 items-end" id="form-ukur">
        @csrf
        <input type="hidden" name="unit" value="{{ $unit }}">
        <input type="hidden" name="section" value="{{ $area->name }}">

        <div>
          <label class="field-label" for="tube_number">1. PILIH PIPA #</label>
          <select class="field-input" id="tube_number" name="tube_number" required>
            <option value="">&mdash; pilih &mdash;</option>
            @for ($i = 1; $i <= $tubeCount; $i++)
              <option value="{{ $i }}" @selected(old('tube_number') == $i)>
                #{{ $i }}@if ($rows[$i] ?? null) &nbsp;(sudah ada data)@endif
              </option>
            @endfor
          </select>
        </div>

        {{-- NILAI TITIK untuk SEMUA titik sekaligus (A/B/C/D) --}}
        @foreach ($points as $p)
          <div>
            <label class="field-label" for="nilai_{{ $p }}">2.{{ $loop->iteration }}. NILAI TITIK {{ $p }} (MM)</label>
            <input class="field-input" id="nilai_{{ $p }}" name="nilai_{{ $p }}" type="number" step="0.01" min="0" max="1000"
                   placeholder="titik {{ $p }}" value="{{ old('nilai_' . $p) }}"
                   data-old-value="{{ old('nilai_' . $p) }}">
          </div>
        @endforeach

        <div>
          <label class="field-label" for="measured_mm">3. NILAI UKUR (MM)</label>
          <input class="field-input" id="measured_mm" name="measured_mm" type="number" step="0.01" min="0" max="1000"
                 placeholder="nilai independen per pipa" value="{{ old('measured_mm') }}"
                 data-old-value="{{ old('measured_mm') }}">
        </div>

        <div>
          <label class="field-label" for="nilai_awal">4. NILAI AWAL (MM)</label>
          <input class="field-input" id="nilai_awal" name="nilai_awal" type="number" step="0.01" min="0" max="1000"
                 placeholder="otomatis terisi dari database" value="{{ old('nilai_awal') }}">
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
        <div class="flex items-center gap-3">
          <span class="text-[10px] text-slate-500">Tahun aktif: <span class="text-slate-200 font-bold">{{ $activeYear }}</span></span>
          <a href="{{ route('tube.mapping', ['unit' => $unit, 'section' => $area->name, 'year' => $activeYear]) }}"
             class="text-[11px] text-[#8fb4d6] hover:text-accent">Lihat di Tube Mapping &rarr;</a>
        </div>
      </div>

      <div id="grid-ukur" class="overflow-x-auto overflow-y-auto" style="max-height:55vh;">
        <table class="w-full text-[11px] border-separate border-spacing-0">
          <thead class="text-slate-400">
            <tr class="text-left">
              <th class="font-normal py-2 pr-2 sticky top-0 bg-[#101f3a] z-10">PIPA #</th>
              <th class="font-normal py-2 pr-2 sticky top-0 bg-[#101f3a] z-10">NILAI AWAL (MM)</th>
              <th class="font-normal py-2 pr-2 sticky top-0 bg-[#101f3a] z-10">NILAI UKUR (MM)</th>
              @foreach ($points as $p)
                <th class="font-normal py-2 pr-2 sticky top-0 bg-[#101f3a] z-10">TITIK {{ $p }} (MM)</th>
              @endforeach
              <th class="font-normal py-2 sticky top-0 bg-[#101f3a] z-10 text-right">AKSI</th>
            </tr>
          </thead>
          <tbody class="text-slate-200">
            @for ($i = 1; $i <= $tubeCount; $i++)
              @php $r = $rows[$i] ?? null; @endphp
              <tr id="pipa-{{ $i }}" class="{{ $r ? 'bg-white/[0.03]' : '' }}">
                <td class="py-1.5 pr-2 font-bold text-accent whitespace-nowrap border-t border-white/5">
                  #{{ $i }}
                  @if ($r)<span class="text-safe text-[9px] font-semibold ml-1" title="sudah ada data">&#9679;</span>@endif
                </td>
                <td class="py-1.5 pr-2 border-t border-white/5">{{ $r['initial'] ?? '—' }}</td>
                <td class="py-1.5 pr-2 border-t border-white/5">
                  @if (! empty($r['measured_mm']))
                    <span class="font-bold text-accent">{{ $r['measured_mm'] }}</span>
                  @else
                    <span class="text-slate-600">—</span>
                  @endif
                </td>
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

    {{-- Pengaturan tanggal ukur untuk sesi input (berlaku untuk semua submit) --}}
    <div class="bg-panel rounded-lg p-4 mt-5">
      <div class="text-xs font-bold tracking-wide mb-1">TANGGAL UKUR (SESI INPUT)</div>
      <div class="text-[10px] text-slate-500 mb-3">
        Isi sekali di awal sesi &mdash; tanggal ini otomatis dipakai untuk semua data yang disimpan, tanpa perlu isi ulang per titik.
      </div>
      <div class="flex flex-wrap items-end gap-4">
        <div class="flex-1 min-w-[220px]">
          <input class="field-input" id="session_date" type="date" value="{{ old('measured_at', $measuredAtDefault) }}">
        </div>
        <div class="flex items-center gap-2 pb-0.5">
          <button type="button" id="btn_set_date_today"
                  class="text-[11px] font-semibold px-4 py-2.5 rounded bg-white/5 border border-white/10 hover:bg-white/10 text-slate-200">
            Terapkan Tanggal untuk Semua Data Hari Ini
          </button>
        </div>
      </div>
      <div id="session_date_status" class="text-[11px] text-safe font-semibold mt-2 hidden">
        &#10003; Tanggal ukur diset ke <span id="session_date_value"></span> &mdash; berlaku untuk semua data yang akan disimpan di sesi ini.
      </div>
    </div>

  @endif

@endsection

@push('scripts')
<script>
(function () {
  'use strict';

  // ============================================
  // 1. AUTO-FILL:
  //    - Pilih pipa -> isi NILAI AWAL, NILAI UKUR, dan SEMUA field
  //      NILAI TITIK (A/B/C/D) sekaligus dari database (AJAX).
  //    - NILAI UKUR tetap menampilkan nilai independen dari tabel
  //      (measured_mm) — angka di form selalu sama dengan tabel.
  // ============================================
  var tubeSelect = document.getElementById('tube_number');
  var initialInput = document.getElementById('nilai_awal');
  var measuredInput = document.getElementById('measured_mm');
  var unit = '{{ $unit }}';
  var section = '{{ $area?->name ?? '' }}';
  var tubeDataUrl = '{{ $area ? route('input-data.ukur.tube-data', ['tubeNumber' => '__TUBE__']) : '' }}';

  // Daftar nama titik area ini (A/B/C/D) — dipakai untuk mengisi field
  // nilai_A, nilai_B, dst.
  var pointNames = @json($points);

  // Data pipa yang paling terakhir diambil dari server (auto-fill).
  var lastTubeData = null;

  if (tubeSelect && tubeDataUrl) {
    var lastRequest = null;

    tubeSelect.addEventListener('change', function () {
      var no = tubeSelect.value;

      // Reset dulu SEMUA field titik + NILAI UKUR saat ganti pipa
      resetPointValues();
      if (measuredInput) measuredInput.value = oldMeasuredValue();

      lastTubeData = null;
      if (! no) return;

      // Abort request sebelumnya supaya tidak ada race condition
      if (lastRequest) lastRequest.abort();

      lastRequest = new XMLHttpRequest();
      var url = tubeDataUrl.replace('__TUBE__', no) +
                '?unit=' + encodeURIComponent(unit) +
                '&section=' + encodeURIComponent(section);
      lastRequest.open('GET', url);
      lastRequest.setRequestHeader('Accept', 'application/json');
      lastRequest.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

      lastRequest.onload = function () {
        if (lastRequest.status === 200) {
          try {
            var data = JSON.parse(lastRequest.responseText);
            lastTubeData = data;

            // Isi NILAI AWAL kalau pipa sudah punya baseline
            if (initialInput && data.initial !== null && data.initial !== undefined) {
              initialInput.value = data.initial;
            }

            // Isi NILAI UKUR (independen per pipa) dari baseline
            if (measuredInput && data.measured_mm !== null && data.measured_mm !== undefined) {
              measuredInput.value = data.measured_mm;
            }

            // Isi SEMUA field NILAI TITIK (A/B/C/D) sekaligus dari
            // nilai tersimpan masing-masing titik.
            fillAllPointValues(data);
          } catch (e) {
            // Response bukan JSON -> biarkan field kosong
          }
        }
        lastRequest = null;
      };

      lastRequest.onerror = function () {
        lastRequest = null;
      };

      lastRequest.send();
    });
  }

  // Isi SEMUA field NILAI TITIK (A/B/C/D) sekaligus dari data pipa.
  // Field yang tidak punya nilai tersimpan dikosongkan.
  function fillAllPointValues(data) {
    if (! data || ! data.points) return;
    pointNames.forEach(function (pt) {
      var input = document.getElementById('nilai_' + pt);
      if (! input) return;
      var v = data.points[pt];
      if (v !== undefined && v !== null) {
        input.value = v;
      } else {
        input.value = oldPointValue(pt);
      }
    });
  }

  // Reset semua field NILAI TITIK ke nilai lama (setelah validasi gagal)
  function resetPointValues() {
    pointNames.forEach(function (pt) {
      var input = document.getElementById('nilai_' + pt);
      if (input) input.value = oldPointValue(pt);
    });
  }

  // Nilai lama field titik (dari data-old-value) — biar tidak terhapus
  function oldPointValue(pt) {
    var input = document.getElementById('nilai_' + pt);
    if (input && input.hasAttribute('data-old-value')) {
      return input.getAttribute('data-old-value');
    }
    return '';
  }

  function oldMeasuredValue() {
    if (measuredInput && measuredInput.hasAttribute('data-old-value')) {
      return measuredInput.getAttribute('data-old-value');
    }
    return '';
  }

  // ============================================
  // 2. TANGGAL UKUR SESI: cukup isi sekali,
  //    berlaku untuk semua submit di sesi ini.
  //    Tanpa JS sekalipun, controller tetap pakai
  //    tanggal yang dikirim form per-submit.
  // ============================================
  var sessionDate = document.getElementById('session_date');
  var form = document.getElementById('form-ukur');

  // Field tanggal di form utama dibuat hidden alias —
  // nilainya diambil dari session_date saat submit.
  var hiddenDate = document.createElement('input');
  hiddenDate.type = 'hidden';
  hiddenDate.name = 'measured_at';
  hiddenDate.value = sessionDate ? sessionDate.value : '';
  if (form && sessionDate) {
    form.appendChild(hiddenDate);

    // Selalu sinkron tanggal sesi ke field hidden saat user mengubahnya
    sessionDate.addEventListener('change', function () {
      hiddenDate.value = sessionDate.value;
    });

    // Saat submit, pastikan nilai terbaru ikut terkirim
    form.addEventListener('submit', function () {
      hiddenDate.value = sessionDate.value;
    });
  }

  // Tombol "Terapkan Tanggal untuk Semua Data Hari Ini"
  var btnToday = document.getElementById('btn_set_date_today');
  var dateStatus = document.getElementById('session_date_status');
  var dateValue = document.getElementById('session_date_value');
  if (btnToday && sessionDate && dateStatus && dateValue) {
    btnToday.addEventListener('click', function () {
      var today = new Date();
      var yyyy = today.getFullYear();
      var mm = String(today.getMonth() + 1).padStart(2, '0');
      var dd = String(today.getDate()).padStart(2, '0');
      sessionDate.value = yyyy + '-' + mm + '-' + dd;
      if (hiddenDate) hiddenDate.value = sessionDate.value;

      dateValue.textContent = sessionDate.value;
      dateStatus.classList.remove('hidden');
    });
  }
})();
</script>
@endpush