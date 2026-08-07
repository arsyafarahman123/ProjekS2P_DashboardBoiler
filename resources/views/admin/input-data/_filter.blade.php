{{-- Pemilih unit + area: wajib dipilih dulu sebelum semua aksi input data --}}
<div class="bg-panel rounded-lg p-4 mb-5">
  <form method="GET" action="{{ url()->current() }}" class="flex flex-wrap gap-6 items-end text-xs">
    <div>
      <label class="field-label">1. PILIH UNIT:</label>
      <select name="unit" onchange="this.form.submit()" class="field-input" style="min-width:140px;">
        @foreach($units as $u)
          <option value="{{ $u }}" @selected($u === $unit)>{{ strtoupper($u) }}</option>
        @endforeach
      </select>
    </div>
    <div>
      <label class="field-label">2. PILIH AREA PADA UNIT INI:</label>
      <select name="section" onchange="this.form.submit()" class="field-input" style="min-width:240px;">
        @forelse($areas as $a)
          <option value="{{ $a->name }}" @selected($area && $a->name === $area->name)>{{ strtoupper($a->name) }}</option>
        @empty
          <option value="">(BELUM ADA AREA)</option>
        @endforelse
      </select>
    </div>
    @if (isset($years))
      <div>
        <label class="field-label">3. TAHUN:</label>
        <select name="year" onchange="this.form.submit()" class="field-input" style="min-width:100px;">
          @foreach($years as $y)
            <option value="{{ $y }}" @selected((int) ($activeYear ?? 0) === (int) $y)>{{ $y }}</option>
          @endforeach
        </select>
      </div>
    @endif
    @if ($area)
      <div class="pb-1 text-slate-400">
        Pipa tersedia di area ini:
        @if ($area->tube_count > 0)
          <span class="text-white font-bold text-[15px]">{{ $area->tube_count }}</span> pipa (no. 1&ndash;{{ $area->tube_count }})
        @else
          <span class="text-watch font-bold">BELUM ADA</span> &mdash; tambahkan lewat menu Add/Delete Pipa
        @endif
      </div>
    @endif
  </form>
</div>
