<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Tube Mapping - S2P Boiler Dashboard</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
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
  * { box-sizing: border-box; }
  body {
    font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
    margin: 0;
    background:
        radial-gradient(ellipse 1000px 560px at 12% -8%, #e0a94022, transparent 60%),
        radial-gradient(ellipse 900px 560px at 100% 0%, #1a4a7a3d, transparent 60%),
        radial-gradient(ellipse 700px 500px at 50% 110%, #0d3a5c40, transparent 60%),
        linear-gradient(120deg, #64798f 0%, #586C82 45%, #46586c 100%);
    background-attachment: fixed;
  }
  .panel-title { color:white; font-weight:700; font-size:12px; letter-spacing:1.5px; }
  #boiler-pdf-tm {
    width:100%; max-width:100%;
    aspect-ratio: 2384 / 3370;
    border:0; border-radius:4px; display:block; background:#0d2140;
  }
  .cell { transition: transform .12s ease; }
  .cell:hover { transform: scale(1.15); z-index: 10; position: relative; }
  .tube-grid{
    display:grid;
    grid-template-columns:repeat(auto-fill, minmax(34px, 1fr));
    gap:4px;
  }
  .tube-cell{
    height:32px;
    display:flex;
    align-items:center;
    justify-content:center;
    border-radius:3px;
    font-weight:600;
    font-size:11px;
    cursor:pointer;
  }
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
    padding-top:14px;
    padding-bottom:14px;
    gap:20px;
    position:sticky;
    top:0;
    align-self:flex-start;
    height:100vh;
    overflow-y:auto;
    overflow-x:hidden;
    scrollbar-width:thin;
    scrollbar-color:#2a4a6e transparent;
  }
  .sidebar::-webkit-scrollbar{ width:4px; }
  .sidebar::-webkit-scrollbar-thumb{ background:#2a4a6e; border-radius:4px; }
  .sidebar .logo-box{
    width:52px;
    height:52px;
    overflow:hidden;
    display:flex;
    align-items:center;
    justify-content:center;
    flex-shrink:0;
  }
  .sidebar-nav{
    display:flex;
    flex-direction:column;
    gap:18px;
    margin-top:8px;
    align-items:center;
    padding-bottom:12px;
  }
  .sidebar-nav .nav-item{
    writing-mode:vertical-rl;
    transform:rotate(180deg);
    font-size:10px;
    letter-spacing:1.5px;
    font-weight:600;
    color:#6d7f96;
    position:relative;
    padding:4px 8px 4px 0;
    background:none;
    border:none;
    border-right:3px solid transparent;
    cursor:pointer;
  }
  .sidebar-nav .nav-item:hover{
    color:#cbd5e1;
  }
  .sidebar-nav .nav-item.active{
    color:#fff;
    border-right-color:#e0a940;
  }

  .header-logo-box{
    width:70px;
    height:70px;
    overflow:hidden;
    display:flex;
    align-items:center;
    justify-content:center;
    flex-shrink:0;
  }
  body > div.flex{ min-height:100vh; }
</style>
</head>
<body class="text-slate-200 min-h-screen" x-data="tubeDashboard()">

<div class="flex">

  <aside class="sidebar">
    <div class="logo-box">
      <img src="{{ asset('images/logo.png') }}" alt="S2P logo" class="w-full h-full object-contain">
    </div>
    <nav class="sidebar-nav">
      <a href="{{ route('global-view') }}" class="nav-item">GLOBAL VIEW</a>
      <a href="{{ route('tube.mapping') }}" class="nav-item active">TUBE MAPPING</a>
      <a href="{{ route('rla-analysis') }}" class="nav-item">RLA ANALYSIS</a>
      <a href="{{ route('maintenance') }}" class="nav-item">MAINTENANCE</a>
      <a href="{{ route('input-data.index') }}" class="nav-item">INPUT DATA</a>
    </nav>
  </aside>

  <main class="flex-1 p-6">

    <div class="flex items-center justify-between mb-5">
      <div class="flex items-center gap-[10px]">
        <span class="accent-bar"></span>
        <h1 class="text-[20px] font-bold tracking-[1.5px] text-[#f0b94a] m-0">
          TUBE MAPPING: {{ strtoupper($unit) }} — {{ strtoupper($section) }} &mdash; TAHUN {{ $year }}
        </h1>
      </div>
      <div class="header-logo-box">
        <img src="{{ asset('images/logo.png') }}" alt="S2P logo" class="w-full h-full object-contain">
      </div>
    </div>

    <form method="GET" class="flex flex-wrap gap-6 items-center mb-5 text-xs w-full bg-white/[0.03] rounded-[4px] px-4 py-2.5">
      <div>
        <label class="text-[#f0b94a] mr-2 font-bold tracking-wide">BOILER SECTION:</label>
        <select name="section" onchange="this.form.submit()" class="bg-white/[0.08] border border-white/[0.12] rounded-[3px] px-3 py-2 pr-8 text-slate-100 font-semibold appearance-none" style="background-image:url('data:image/svg+xml;utf8,<svg xmlns=%27http://www.w3.org/2000/svg%27 width=%2710%27 height=%276%27><path d=%27M0 0l5 6 5-6z%27 fill=%27%239fb0c3%27/></svg>');background-repeat:no-repeat;background-position:right 12px center;">
          @foreach($sections as $s)
            <option value="{{ $s }}" @selected($s===$section)>{{ strtoupper($s) }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="text-[#f0b94a] mr-2 font-bold tracking-wide">UNIT:</label>
        <select name="unit" onchange="this.form.submit()" class="bg-white/[0.08] border border-white/[0.12] rounded-[3px] px-3 py-2 pr-8 text-slate-100 font-semibold appearance-none" style="background-image:url('data:image/svg+xml;utf8,<svg xmlns=%27http://www.w3.org/2000/svg%27 width=%2710%27 height=%276%27><path d=%27M0 0l5 6 5-6z%27 fill=%27%239fb0c3%27/></svg>');background-repeat:no-repeat;background-position:right 12px center;">
          @foreach($units as $u)
            <option value="{{ $u }}" @selected($u===$unit)>{{ strtoupper($u) }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="text-[#f0b94a] mr-2 font-bold tracking-wide">TAHUN:</label>
        <select name="year" onchange="this.form.submit()" class="bg-white/[0.08] border border-white/[0.12] rounded-[3px] px-3 py-2 pr-8 text-slate-100 font-semibold appearance-none" style="background-image:url('data:image/svg+xml;utf8,<svg xmlns=%27http://www.w3.org/2000/svg%27 width=%2710%27 height=%276%27><path d=%27M0 0l5 6 5-6z%27 fill=%27%239fb0c3%27/></svg>');background-repeat:no-repeat;background-position:right 12px center;">
          @foreach($years as $y)
            <option value="{{ $y }}" @selected($y==$year)>{{ $y }}</option>
          @endforeach
        </select>
      </div>
    </form>

    <div class="grid grid-cols-3 gap-5 mb-5">

      <div class="col-span-2 bg-panel rounded-lg p-4 relative">
        <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
           <div class="text-xs font-bold tracking-wide">{{ strtoupper($section) }} TUBE MAP — {{ strtoupper($unit) }} (TUBE 1&ndash;{{ $tubeCount }})</div>
          <div class="flex items-center gap-3 text-[10px] text-slate-400">
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-safe"></span> SAFE</span>
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-watch"></span> WARNING</span>
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-critical"></span> CRITICAL</span>
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-white/10 border border-white/20"></span> BELUM ADA DATA</span>
          </div>
        </div>

        <div class="tube-grid select-none">
           @for($i=1;$i<=$tubeCount;$i++)
            @php
              $status = $statusByTubeNumber[$i] ?? null;
              $cellClass = match ($status) {
                  'Safe' => 'bg-safe/25 text-safe border-safe/50 hover:bg-safe/40',
                  'Watch', 'Warning' => 'bg-watch/25 text-watch border-watch/50 hover:bg-watch/40',
                  'Critical' => 'bg-critical/25 text-critical border-critical/50 hover:bg-critical/40',
                  default => 'bg-white/[0.07] text-slate-500 border-white/10',
              };
            @endphp
            <button type="button"
              @click="selectPshTube({{ $i }}, $event)"
              class="cell tube-cell border {{ $cellClass }}">
              {{ $i }}
            </button>
          @endfor
        </div>

        <div x-show="selected" x-cloak @click.outside="selected=null"
             :style="popupStyle"
             class="absolute w-80 bg-[#0d1830] border border-white/10 rounded-lg shadow-2xl p-4 text-xs z-20">
          <div class="flex justify-between items-start mb-2">
            <div class="font-bold text-slate-200">
              TUBE #<span x-text="selected?.no"></span>
              <span class="text-slate-400" x-text="'(' + selected?.id + ')'"></span>
            </div>
            <button @click="selected=null" class="text-slate-400 hover:text-white">X</button>
          </div>

          <div class="flex items-center justify-between mb-3 text-slate-300">
            <span>STATUS:
              <span class="font-bold" :class="statusClass()" x-text="statusText()"></span>
            </span>
            <span>RATA-RATA: <span class="font-semibold" :class="avgPointPctClass()" x-text="avgPointPctText()"></span></span>
          </div>

          <div class="text-[10px] font-bold tracking-wide text-slate-400 mb-1">
            WALL THICKNESS 5 TAHUN ({{ min(\App\Models\BoilerTube::YEARS) }}&ndash;{{ max(\App\Models\BoilerTube::YEARS) }})
          </div>
          <div x-show="!hasThicknessStats()" class="text-slate-500 pb-2">
            Belum ada data dummy untuk tube ini.
          </div>
          <div x-show="hasThicknessStats()" class="grid grid-cols-3 gap-2 pb-2">
            <div>
              <div class="text-[10px] text-slate-500">MIN</div>
              <div class="font-semibold" :class="minClass()" x-text="thicknessStat('min')"></div>
            </div>
            <div>
              <div class="text-[10px] text-slate-500">MAX</div>
              <div class="font-semibold text-safe" x-text="thicknessStat('max')"></div>
            </div>
            <div>
              <div class="text-[10px] text-slate-500">AVG</div>
              <div class="font-semibold text-white" x-text="thicknessStat('avg')"></div>
            </div>
          </div>
          <div x-show="hasThicknessStats()" class="text-[10.5px] text-slate-400 pb-3 mb-3 border-b border-white/10">
            Batas minimum aman (min. allowable): <span class="font-semibold text-white" x-text="minAllowableText()"></span>.
            MIN <span :class="minClass()" class="font-semibold">merah/kuning</span> artinya titik paling tipis udah dekat/lewat batas ini.
          </div>

          <div class="text-[10px] font-bold tracking-wide text-slate-400 mb-1">TITIK PENGUKURAN (KETEBALAN PER TITIK A&ndash;D)</div>
          <div class="space-y-1 text-slate-300">
            <template x-for="p in pointList()" :key="p">
              <div>TITIK <span x-text="p"></span>:
                <span class="font-semibold text-white" x-text="pointValue(p)"></span>
              </div>
            </template>
            <div x-show="!hasPointData()" class="text-slate-500 pt-1">
              Belum ada data titik ukur untuk tube ini.
            </div>
          </div>
        </div>
      </div>

       <div class="flex flex-col gap-5">
        <div class="bg-panel rounded-lg p-4">
          <div class="flex items-center justify-between mb-3">
            <div class="text-xs font-bold tracking-wide">BOILER 3D STRUCTURE</div>
            <div class="text-[9px] text-slate-500">{{ strtoupper($unit) }}</div>
          </div>
          @if(isset($boilerImages) && $boilerImages->isNotEmpty())
            @php $latestImg = $boilerImages->first(); $ext = strtolower(pathinfo($latestImg->nama_file, PATHINFO_EXTENSION)); @endphp
            @if($ext === 'pdf')
              <iframe id="boiler-pdf-tm"
                src="{{ route('input-data.image.file', $latestImg) }}#toolbar=0&navpanes=0&view=Fit"
                title="{{ $latestImg->nama_file }}"></iframe>
            @else
              <img src="{{ route('input-data.image.file', $latestImg) }}" alt="Boiler 3D {{ strtoupper($unit) }}"
                   class="w-full rounded border border-white/10 object-contain" style="max-height:500px;">
            @endif
            <div class="text-[10px] text-slate-400 mt-2 truncate">{{ $latestImg->nama_file }}</div>
          @elseif($unit === \App\Models\BoilerTube::DEFAULT_UNIT)
            <iframe id="boiler-pdf-tm"
              src="{{ asset('images/'.rawurlencode('F2092S-J0203-05 R1 SECTION VIEW DRAWING OF BOILER HOUSE.pdf')) }}#toolbar=0&navpanes=0&view=Fit"
              title="Section View Drawing of Boiler House"></iframe>
          @else
            <div class="rounded bg-[#0d1830] flex items-center justify-center text-[11px] text-slate-500" style="min-height:320px;">
              Gambar section drawing belum tersedia untuk {{ strtoupper($unit) }}.
            </div>
          @endif
        </div>
        <div class="bg-panel rounded-lg p-4 flex-1">
          <div class="flex justify-between items-center mb-2">
            <div class="text-xs font-bold tracking-wide">CREEP PERCENTAGE 5 TAHUN</div>
          </div>
          <canvas id="creepChart" height="140"></canvas>
        </div>
      </div>
    </div>

    <div class="bg-panel rounded-lg p-4 mb-5">
      <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
         <div class="text-xs font-bold tracking-wide">KETEBALAN PER TITIK (JENIS PIPA A&ndash;D) &mdash; TUBE 1&ndash;{{ $tubeCount }}</div>
        <div class="flex items-center gap-3 text-[10px] text-slate-400">
          <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-safe"></span> 100%&ndash;75% AMAN</span>
          <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-watch"></span> <75%&ndash;70% WARNING</span>
          <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-critical"></span> <70% CRITICAL</span>
        </div>
      </div>
      <div class="overflow-x-auto" style="max-height:420px; overflow-y:auto;">
        <table class="w-full text-[11px]">
          <thead class="text-slate-400 sticky top-0 bg-[#0e2038]">
            <tr class="text-left">
              <th class="font-normal pb-2 pr-3">TUBE #</th>
              @foreach($pointNames as $p)
                <th class="font-normal pb-2 pr-3">TITIK {{ $p }}</th>
              @endforeach
              <th class="font-normal pb-2">STATUS</th>
            </tr>
          </thead>
          <tbody class="text-slate-200">
             @for($i=1; $i<=$tubeCount; $i++)
              @php
                $row = $pointsTable[$i] ?? null;
                $rowStatusClass = match ($row['status'] ?? null) {
                    'critical' => 'text-critical',
                    'warning' => 'text-watch',
                    'safe' => 'text-safe',
                    default => 'text-slate-500',
                };
              @endphp
              <tr id="point-row-{{ $i }}" class="border-t border-white/5 cursor-pointer hover:bg-white/[0.04]"
                  @click="selectPshTube({{ $i }}, $event); $el.scrollIntoView({block:'nearest'})">
                <td class="py-1.5 pr-3 font-semibold">{{ $i }}</td>
                @foreach($pointNames as $p)
                  @php
                    $pct = $row['pct'][$p] ?? null;
                    $cellClass = $pct === null ? 'text-slate-500'
                        : ($pct < 70 ? 'text-critical font-semibold' : ($pct < 75 ? 'text-watch font-semibold' : 'text-safe'));
                  @endphp
                  <td class="py-1.5 pr-3 {{ $cellClass }}">{{ $pct !== null ? $pct.'%' : '—' }}</td>
                @endforeach
                <td class="py-1.5 font-semibold {{ $rowStatusClass }}">{{ strtoupper($row['status'] ?? 'N/A') }}</td>
              </tr>
            @endfor
          </tbody>
        </table>
      </div>
    </div>

    <div class="grid grid-cols-3 gap-5 mb-5">
      <div class="bg-panel rounded-lg p-4">
        <div class="text-xs font-bold tracking-wide mb-3">LEGENDA STATUS</div>
        <div class="space-y-2 text-xs">
          <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-sm bg-safe"></span> SAFE ({{ $summary["safe_pct"] }}%)</div>
          <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-sm bg-watch"></span> WARNING ({{ $summary["watch_pct"] }}%)</div>
          <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-sm bg-critical"></span> CRITICAL ({{ $summary["critical_pct"] }}%)</div>
        </div>
      </div>

      <div class="col-span-2 bg-panel rounded-lg p-4">
        <div class="text-xs font-bold tracking-wide mb-3">HISTORICAL NDT {{ strtoupper($unit) }} / {{ strtoupper($section) }}</div>
        <table class="w-full text-[11px]">
          <thead class="text-slate-400">
            <tr class="text-left">
              <th class="font-normal pb-2">TUBE ID</th>
              <th class="font-normal pb-2">CREEP %</th>
              <th class="font-normal pb-2">SCAN DATE</th>
              <th class="font-normal pb-2">STATUS</th>
              <th class="font-normal pb-2">REKOMENDASI</th>
            </tr>
          </thead>
          <tbody class="text-slate-200">
            @foreach($historicalNdt as $h)
              <tr class="border-t border-white/5">
                <td class="py-1.5">{{ $h->tube_id }}</td>
                <td class="py-1.5">{{ $h->creep_pct }}%</td>
                <td class="py-1.5">{{ $h->scan_date->format("Y-m-d") }}</td>
                <td class="py-1.5">
                  <span class="{{ $h->status === "Critical" ? "text-critical" : (($h->status === "Watch" || $h->status === "Warning") ? "text-watch" : "text-safe") }} font-semibold">
                    {{ strtoupper($h->status) }}
                  </span>
                </td>
                <td class="py-1.5">{{ $h->recommended_action }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>

    <div class="bg-panel rounded-lg p-4 mb-5">
      <div class="text-xs font-bold tracking-wide mb-3">SEARCH / FILTER</div>
      <div class="flex flex-wrap gap-3 items-center">
        <div class="relative flex-1 min-w-[220px]">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" class="absolute left-3 top-1/2 -translate-y-1/2"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input x-model="filterTubeId" type="text" placeholder="Filter Tube ID..."
                 class="w-full bg-[#fffff] border border-white/10 rounded pl-9 pr-3 py-2 text-xs text-slate-200 placeholder-slate-500 focus:outline-none focus:border-accent">
        </div>
        <a href="#" class="font-bold text-xs px-4 py-2.5 rounded flex items-center justify-center gap-2 whitespace-nowrap" style="background:linear-gradient(135deg, #c9982f 0%, #8a6520 100%);color:#fff;letter-spacing:0.5px;">
          <img src="{{ asset('images/download.png') }}" alt="" style="width:16px;height:16px;filter:brightness(0) invert(1);">
          EXPORT REPORT (PDF/EXCEL)
        </a>
      </div>
    </div>

    <div class="bg-panel rounded-lg p-4">
      <div class="text-xs font-bold tracking-wide mb-3">TOP 5 MAINTENANCE PRIORITY</div>
      <div class="space-y-2">
        @foreach($topPriority as $p)
          <div class="flex items-center justify-between text-xs border-b border-white/5 pb-2">
            <div><span class="text-accent font-bold mr-2">#{{ $p["rank"] }}</span> {{ $p["tube_id"] }} <span class="text-slate-500">({{ $p["unit"] }})</span></div>
            <span class="bg-critical/20 text-critical font-semibold px-2 py-0.5 rounded">{{ $p["risk"] }} risk</span>
          </div>
        @endforeach
      </div>
    </div>

    <div x-show="toast" x-cloak x-text="toast"
         class="fixed bottom-6 right-6 bg-panel border border-white/10 text-slate-200 text-xs px-4 py-2 rounded shadow-lg z-50"></div>
  </main>
</div>

<script>
// MIN/MAX/AVG wall thickness 5 tahun (2021-2025) per nomor tube, + angka
// asli tiap tahun (by_year) — dihitung server-side langsung dari
// tube_dummy_2021_2025.csv (data dummy Unit 3A), bukan data karangan.
const TUBE_THICKNESS_STATS = @json($tubeThicknessStats);
// Data titik ukur A-D per tube (persen ketebalan sisa vs baseline) — dari
// tube_measurements + tube_baselines per tahun yang sedang difilter.
const POINTS_TABLE = @json($pointsTable);
// Status & creep terkini (tahun yang lagi difilter) per nomor tube.
const STATUS_BY_TUBE = @json($statusByTubeNumber);
const CREEP_BY_TUBE = @json($creepByTubeNumber);
// Daftar nama titik (A, B, C, D atau sesuai konfigurasi area)
const POINT_NAMES = @json($pointNames);
// Kode section aktif (mis. FBS, PSH, SSH) buat bikin Tube ID yang benar
// sesuai section yang lagi dibuka, bukan selalu "PSH".
const SECTION_CODE = @json($sectionCode);
const ACTIVE_UNIT = @json($unit);

function tubeDashboard() {
  return {
    selected: null,
    popupStyle: '',
    toast: null,
    filterTubeId: '',
    hasThicknessStats() {
      return !!TUBE_THICKNESS_STATS[this.selected?.no];
    },
    hasPointData() {
      return !!POINTS_TABLE[this.selected?.no];
    },
    pointList() {
      return POINT_NAMES;
    },
    pointValue(p) {
      const row = POINTS_TABLE[this.selected?.no];
      if (!row || row.pct[p] == null) return '—';
      return Number(row.pct[p]).toFixed(1) + '%';
    },
    thicknessStat(kind) {
      const stats = TUBE_THICKNESS_STATS[this.selected?.no];
      if (!stats) return '-';
      return Number(stats[kind]).toFixed(2) + ' mm';
    },
    minAllowableText() {
      const stats = TUBE_THICKNESS_STATS[this.selected?.no];
      return stats ? Number(stats.min_allowable).toFixed(2) + ' mm' : '-';
    },
    minClass() {
      const stats = TUBE_THICKNESS_STATS[this.selected?.no];
      if (!stats) return 'text-slate-400';
      // Seberapa dekat MIN ke batas minimum allowable, dibanding jarak MAX ke batas itu
      // (dipakai sebagai "margin aman" tube ini) — makin dekat/lewat batas, makin merah.
      const margin = stats.max - stats.min_allowable;
      const used = stats.max - stats.min; // seberapa banyak sudah terpakai dari margin
      const pctUsed = margin > 0 ? (used / margin) * 100 : 100;
      if (stats.min <= stats.min_allowable || pctUsed >= 80) return 'text-critical';
      if (pctUsed >= 40) return 'text-watch';
      return 'text-safe';
    },
    statusText() {
      const s = STATUS_BY_TUBE[this.selected?.no];
      if (!s) return 'BELUM ADA DATA';
      // Tampilkan sebagai WARNING (bukan Watch) untuk konsistensi label
      if (s === 'Watch' || s === 'Warning') return 'WARNING';
      return String(s).toUpperCase();
    },
    statusClass() {
      const s = STATUS_BY_TUBE[this.selected?.no];
      if (s === 'Safe') return 'text-safe';
      if (s === 'Watch' || s === 'Warning') return 'text-watch';
      if (s === 'Critical') return 'text-critical';
      return 'text-slate-500';
    },
    avgPointPct() {
      const row = POINTS_TABLE[this.selected?.no];
      if (!row || !row.pct) return { text: '—', cls: 'text-slate-500' };
      const vals = Object.values(row.pct).filter(v => v != null);
      if (!vals.length) return { text: '—', cls: 'text-slate-500' };
      const avg = vals.reduce((a, b) => a + b, 0) / vals.length;
      const text = Number(avg).toFixed(1) + '%';
      const cls = avg < 70 ? 'text-critical' : (avg < 75 ? 'text-watch' : 'text-safe');
      return { text, cls };
    },
    avgPointPctText() { return this.avgPointPct().text; },
    avgPointPctClass() { return this.avgPointPct().cls; },
    creepText() {
      const c = CREEP_BY_TUBE[this.selected?.no];
      return c == null ? '-' : Number(c).toFixed(2) + '%';
    },
    selectPshTube(no, event) {
      // Tube ID format: KODE-UNIT-TUBENUMBER (e.g. FBS-U3A-01)
      const unitCode = ACTIVE_UNIT.replace(/\s+/g, '').toUpperCase();
      this.selected = { no, id: SECTION_CODE + '-' + unitCode + '-' + String(no).padStart(2, '0') };

      // Sorot baris data titik A-D punya tube ini di tabel bawah.
      document.querySelectorAll('.point-row-active').forEach(el => el.classList.remove('point-row-active', 'bg-white/10'));
      const row = document.getElementById('point-row-' + no);
      if (row) {
        row.classList.add('point-row-active', 'bg-white/10');
        row.scrollIntoView({ block: 'center', behavior: 'smooth' });
      }

      const btn = event.currentTarget;
      const container = btn.closest('.relative');
      if (!container) return;

      const btnRect = btn.getBoundingClientRect();
      const containerRect = container.getBoundingClientRect();
      const popupWidth = 320;   // sesuai w-80
      const popupHeightEstimate = 160;

      let left = (btnRect.left - containerRect.left) + (btnRect.width / 2) - (popupWidth / 2);
      left = Math.max(8, Math.min(left, container.clientWidth - popupWidth - 8));

      let top = (btnRect.bottom - containerRect.top) + 8;
      if (top + popupHeightEstimate > container.clientHeight) {
        top = (btnRect.top - containerRect.top) - popupHeightEstimate - 8;
      }
      top = Math.max(8, top);

      this.popupStyle = `top:${top}px; left:${left}px;`;
    }
  }
}

document.addEventListener("DOMContentLoaded", function () {
  const ctx = document.getElementById("creepChart");
  if (!ctx) return;
  
   const tubeCount = {{ $tubeCount }};
  const labels = [];
  const data = [];
   for (let i = 1; i <= tubeCount; i++) {
    labels.push(i);
    const stat = TUBE_THICKNESS_STATS[i];
    data.push(stat ? stat.avg : null);
  }

  new Chart(ctx, {
    type: "line",
    data: {
      labels: labels,
      datasets: [{
        data: data,
        borderColor: "#f87171",
        backgroundColor: "rgba(56,102,163,0.35)",
        fill: true,
        tension: 0.2,
        pointRadius: 0,
        spanGaps: true,
      }]
    },
    options: {
      plugins: { legend: { display: false } },
      scales: {
        x: {
           title: { display: true, text: "NOMOR TUBE (1-" + tubeCount + ")", color: "#94a3b8" },
          ticks: { color: "#94a3b8", maxTicksLimit: 10 },
          grid: { color: "rgba(255,255,255,0.05)" },
        },
        y: {
          title: { display: true, text: "KETEBALAN (MM)", color: "#94a3b8" },
          ticks: { color: "#94a3b8" },
          grid: { color: "rgba(255,255,255,0.05)" },
        },
      }
    }
  });
});
</script>

</body>
</html>
