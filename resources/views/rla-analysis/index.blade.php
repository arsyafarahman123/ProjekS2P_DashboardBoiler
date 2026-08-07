@extends('layouts.dashboard')

@section('title', 'RLA Analysis - S2P Boiler Dashboard')

@push('styles')
<style>
  :root{
    --bg-deep:#0a1729;
    --bg-page-1:#16324f;
    --bg-page-2:#5c7086;
    --sidebar-bg:#0a1729;
    --card-bg:#2c4569;
    --card-bg-2:#233a5f;
    --card-border:rgba(255,255,255,0.06);
    --gold:#e0a940;
    --gold-dark:#a97e1f;
    --gold-text:#f0b94a;
    --green:#3fdc84;
    --cyan:#7fd4e8;
    --red:#e5484d;
    --text-light:#eef2f6;
    --text-dim:#9fb0c3;
    --text-faint:#6d7f96;
  }
  *{box-sizing:border-box; margin:0; padding:0;}
  html{ scroll-behavior:smooth; }
  body{
    font-family: ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Arial, Helvetica, sans-serif;
    margin:0;
    background:#586C82;
    color:var(--text-light);
  }

  /* RLA-specific: stack this page's sections (title row, filter bar,
     content grid, bottom row) with a consistent gap. The outer padding
     and background for .main itself now live in dashboard-shared.css. */
  .main{
    display:flex;
    flex-direction:column;
    gap:14px;
    min-width:0;
  }

  .title-row{
    display:flex;
    align-items:center;
    justify-content:space-between;
  }
  .title-row .title-left{
    display:flex;
    align-items:center;
    gap:10px;
  }
  .title-row .accent-bar{
    width:4px;
    height:20px;
    background:#e0a940;
    border-radius:2px;
    flex-shrink:0;
  }
  .title-row h1{
    font-size:20px;
    letter-spacing:1.5px;
    color:var(--gold-text);
    font-weight:700;
    margin:0;
  }
  .header-logo{
    width:70px;
    height:70px;
    overflow:hidden;
    display:flex;
    align-items:center;
    justify-content:center;
    flex-shrink:0;
  }
  .header-logo img{ width:100%; height:100%; object-fit:contain; display:block; 
  }

  /* FILTER BAR */
.filter-bar{
  display:inline-flex;
  align-items:center;
  gap:26px;
  background:rgba(255,255,255,0.03);
  padding:10px 16px;
  border-radius:4px;
  flex-wrap:wrap;
}
  .filter-bar label{
    font-size:12px;
    font-weight:700;
    letter-spacing:1px;
    color:var(--gold-text);
    margin-right:10px;
  }
  .filter-bar select{
    background:rgba(255,255,255,0.08);
    border:1px solid rgba(255,255,255,0.12);
    color:var(--text-light);
    font-size:13px;
    font-weight:600;
    padding:6px 30px 6px 12px;
    border-radius:3px;
    min-width:160px;
    appearance:none;
    -webkit-appearance:none;
    background-image:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='10' height='6'><path d='M0 0l5 6 5-6z' fill='%239fb0c3'/></svg>");
    background-repeat:no-repeat;
    background-position:right 12px center;
  }
  .filter-bar select option{
    color:#1a1a1a;
    background:#ffffff;
    font-weight:600;
  }

  /* CONTENT GRID */
  .content-grid{
    display:grid;
    grid-template-columns:1.7fr 1fr;
    gap:14px;
    flex:1;
    min-height:0;
  }
  .content-grid > .panel{
    display:flex;
    flex-direction:column;
    min-height:0;
  }
  .content-grid > .panel .chart-wrap{
    flex:1;
    display:flex;
    flex-direction:column;
    justify-content:center;
    min-height:0;
  }
  .content-grid > .panel .chart-wrap svg{
    aspect-ratio: 800 / 400;
    height:auto;
  }
.panel{
  background:linear-gradient(180deg, rgba(0,26,87,0.25) 0%, rgba(14,32,56,1) 100%);
  border:1px solid var(--card-border);
  border-radius:5px;
  padding:16px 18px;
}
  .panel-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:10px;
  }
  .panel-header h2{
    font-size:12px;
    letter-spacing:1.5px;
    color:var(--text-light);
    font-weight:700;
  }
  .panel-header .tag{
    font-size:10px;
    color:var(--text-faint);
    letter-spacing:1px;
  }

  /* chart */
  .chart-wrap{ width:100%; position:relative; }
  .rl-tooltip{
    position:absolute;
    display:none;
    pointer-events:none;
    z-index:20;
    min-width:170px;
    background:linear-gradient(160deg, var(--card-bg) 0%, #1b3050 100%);
    border:1px solid rgba(255,255,255,0.15);
    border-left:3px solid var(--gold);
    border-radius:5px;
    padding:8px 12px;
    box-shadow:0 6px 16px rgba(0,0,0,0.4);
  }
  .rl-tooltip .rl-period{
    font-size:10.5px;
    letter-spacing:0.5px;
    color:var(--text-dim);
    font-weight:700;
    margin-bottom:4px;
  }
  .rl-tooltip .rl-value{
    font-size:13px;
    font-weight:700;
    color:var(--gold-text);
    margin-bottom:2px;
  }
  .rl-tooltip .rl-eta{
    font-size:11px;
    color:var(--cyan);
  }
  .hover-zone{ fill:transparent; cursor:crosshair; }
  .legend-lines{
    display:flex;
    justify-content:center;
    gap:22px;
    margin-top:10px;
    flex-wrap:wrap;
    font-size:11.5px;
    color:var(--text-dim);
  }
  .legend-lines span{ display:inline-flex; align-items:center; gap:6px; line-height:1; }
  .legend-lines img{ width:28px; height:28px; display:block; vertical-align:middle; align-self:center; object-fit:contain; }
  .legend-boxes{
    display:flex;
    gap:18px;
    margin-top:8px;
    font-size:11.5px;
    color:var(--text-dim);
    font-weight:600;
  }
  .legend-boxes span{ display:inline-flex; align-items:center; gap:6px; }
  .legend-boxes i{ width:12px; height:12px; border-radius:2px; display:inline-block; }

  /* Recommendations */
  .right-col{ display:flex; flex-direction:column; gap:14px; height:100%; }
  .rec-panel{ flex:1; display:flex; flex-direction:column; padding:22px 24px; }
  .rec-panel h2{ color:var(--gold-text); font-size:15px; }
  .rec-sub{ font-size:11.5px; color:var(--text-dim); font-weight:700; letter-spacing:1px; margin-bottom:16px; }
  .rec-panel .priority-list{ display:flex; flex-direction:column; flex:1; justify-content:space-evenly; }
  .priority{
    border-left:4px solid;
    border-radius:3px;
    padding:14px 18px;
    margin-bottom:8px;
  }
  .priority .p-title{ font-size:13px; font-weight:700; margin-bottom:5px; }
  .priority .p-desc{ font-size:13.5px; color:#e6ebf1; line-height:1.5; }
  .priority.p1{ border-color:#e5484d; background:linear-gradient(90deg, rgba(229,72,77,0.28), rgba(229,72,77,0.08)); }
  .priority.p1 .p-title{ color:#ff8a8e; }
  .priority.p2{ border-color:#e08a3c; background:linear-gradient(90deg, rgba(224,138,60,0.22), rgba(224,138,60,0.06)); }
  .priority.p2 .p-title{ color:#f0a860; }
  .priority.p3{ border-color:#e0c23c; background:linear-gradient(90deg, rgba(224,194,60,0.2), rgba(224,194,60,0.05)); }
  .priority.p3 .p-title{ color:#e8d268; }
  .priority.p4{ border-color:#3fa9c9; background:linear-gradient(90deg, rgba(63,169,201,0.28), rgba(63,169,201,0.1)); }
  .priority.p4 .p-title{ color:#7fd4e8; }

  /* creep chart */
  .creep-chart{ width:100%; margin-top:6px; }

  /* RUL table panel */
  table.rul-table{
    width:100%;
    border-collapse:collapse;
    font-size:12px;
    margin-top:4px;
  }
  table.rul-table th{
    text-align:left;
    color:var(--text-dim);
    font-weight:600;
    font-size:10.5px;
    letter-spacing:0.5px;
    padding-bottom:8px;
  }
  table.rul-table td{
    padding:7px 4px;
    color:var(--text-light);
    border-top:1px solid rgba(255,255,255,0.06);
  }
  table.rul-table td.tube-id{ color:var(--cyan); font-weight:600; }
  .rul-badge{
    display:inline-block;
    padding:2px 9px;
    border-radius:10px;
    font-size:10px;
    font-weight:700;
    letter-spacing:0.5px;
  }
  .rul-badge.critical{ background:rgba(229,72,77,0.18); color:#ff8a8e; }
  .rul-badge.watch{ background:rgba(224,194,60,0.18); color:#e8d268; }
  .rul-badge.safe{ background:rgba(63,220,132,0.18); color:#3fdc84; }

  /* bottom row */
  .bottom-row{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:14px;
  }
  .bottom-row .panel h2{ margin-bottom:10px; }
  table.ndt{
    width:100%;
    border-collapse:collapse;
    font-size:12.5px;
  }
  table.ndt th{
    text-align:left;
    color:var(--text-dim);
    font-weight:600;
    font-size:11px;
    padding-bottom:8px;
  }
  table.ndt td{
    padding:5px 0;
    color:var(--text-light);
  }
  table.ndt td.tube-id{ color:var(--cyan); }
  table.ndt td.creep{ color:var(--gold-text); font-weight:700; }

  .search-box{
    display:flex;
    align-items:center;
    gap:8px;
    background:#e9ecef;
    border-radius:3px;
    padding:9px 12px;
    margin:8px 0 14px;
  }
  .search-box input{
    border:none;
    outline:none;
    background:transparent;
    font-size:13px;
    width:100%;
    color:#333;
  }
  .search-box svg{ flex-shrink:0; }

  .export-btn{
    display:flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    background:linear-gradient(135deg, #c9982f 0%, #8a6520 100%);
    color:#fff;
    font-size:12.5px;
    font-weight:700;
    letter-spacing:0.5px;
    padding:11px;
    border-radius:3px;
    border:none;
    width:100%;
    cursor:pointer;
  }

  @media (max-width: 1000px){
    .content-grid{ grid-template-columns:1fr; }
    .bottom-row{ grid-template-columns:1fr; }
  }
</style>
@endpush

@section('content')
  <main class="main">

    <div class="title-row">
      <div class="title-left">
        <span class="accent-bar"></span>
        <h1>REMAINING LIFE ASSESSMENT ANALYSIS: {{ strtoupper($selectedSection) }}</h1>
      </div>
      <div class="header-logo"><img src="{{ asset('images/logo.png') }}" alt="S2P logo"></div>
    </div>

    <form method="GET" class="filter-bar">
      <div><label>BOILER SECTION:</label>
        <select name="section" onchange="this.form.submit()">
          @foreach($boilerSections as $s)
            <option value="{{ $s }}" @selected($s === $selectedSection)>{{ strtoupper($s) }}</option>
          @endforeach
        </select>
      </div>
      <div><label>UNIT:</label>
        <select name="unit" onchange="this.form.submit()">
          @foreach($units as $u)
            <option value="{{ $u }}" @selected($u === $selectedUnit)>{{ $u }}</option>
          @endforeach
        </select>
      </div>
      <div><label>TAHUN:</label>
        <select name="year" onchange="this.form.submit()">
          @foreach($years as $y)
            <option value="{{ $y }}" @selected($y == $selectedYear)>{{ $y }}</option>
          @endforeach
        </select>
      </div>
    </form>

    <div class="content-grid">
      <div class="panel">
        <div class="panel-header">
          <h2>THICKNESS PER TUBE &mdash; {{ strtoupper($selectedSection) }}</h2>
          <span class="tag">RLA-01</span>
        </div>
        <div class="chart-wrap">
          @php
            $tc = $data['thickness_chart'];
            $tubeNumbers = $tc['tube_numbers'];
            $n = count($tubeNumbers);

            // Area plot (koordinat px di dalam viewBox 0 0 900 400)
            $chartX0 = 60; $chartX1 = 870;
            $chartY0 = 10;  $chartY1 = 340; // y0 = atas (nilai besar), y1 = bawah (0)

            // Skala Y: bulatin ke atas ke kelipatan 1mm, minimal 6mm biar ada ruang
            $allVals = array_merge($tc['a'], $tc['b'], $tc['c'], $tc['d'], [$tc['mwt']]);
            $yMax = max(6, ceil(max($allVals)) + 1);
            $yMin = 0;

            $xStep = $n > 1 ? ($chartX1 - $chartX0) / ($n - 1) : 0;
            $yToPx = fn($v) => $chartY1 - (($v - $yMin) / ($yMax - $yMin)) * ($chartY1 - $chartY0);

            $buildPoints = fn($series) => collect($series)
                ->map(fn($v, $i) => round($chartX0 + $i * $xStep, 1) . ',' . round($yToPx($v), 1))
                ->implode(' ');

            $mwtY = round($yToPx($tc['mwt']), 1);
          @endphp
          <svg viewBox="0 0 900 400" width="100%" style="overflow:visible">
            <!-- horizontal gridlines tiap 1mm -->
            <g stroke="rgba(127,212,232,0.14)" stroke-width="1">
              @for ($gv = $yMin; $gv <= $yMax; $gv++)
                <line x1="{{ $chartX0 }}" y1="{{ round($yToPx($gv), 1) }}" x2="{{ $chartX1 }}" y2="{{ round($yToPx($gv), 1) }}"/>
              @endfor
            </g>
            <!-- axis lines -->
            <g stroke="rgba(255,255,255,0.15)" stroke-width="1">
              <line x1="{{ $chartX0 }}" y1="{{ $chartY1 }}" x2="{{ $chartX1 }}" y2="{{ $chartY1 }}"/>
            </g>
            <!-- y labels -->
            <g font-size="11" fill="#8b9cb3">
              @for ($gv = $yMin; $gv <= $yMax; $gv++)
                <text x="30" y="{{ round($yToPx($gv), 1) + 4 }}">{{ number_format($gv, 2) }}</text>
              @endfor
            </g>
            <!-- axis titles -->
            <text x="-175" y="16" transform="rotate(-90)" font-size="11" fill="#8b9cb3" text-anchor="middle">Thickness (mm)</text>

            <!-- MWT dashed -->
            <line x1="{{ $chartX0 }}" y1="{{ $mwtY }}" x2="{{ $chartX1 }}" y2="{{ $mwtY }}" stroke="#ff6b6b" stroke-dasharray="6,5" stroke-width="1.3"/>
            <text x="{{ $chartX1 - 90 }}" y="{{ $mwtY - 6 }}" font-size="10" fill="#ff6b6b" font-weight="700">MWT ({{ number_format($tc['mwt'], 2) }}mm)</text>

            <!-- titik A -->
            <polyline fill="none" stroke="#3fdc84" stroke-width="1.8" stroke-linejoin="round" stroke-linecap="round"
              points="{{ $buildPoints($tc['a']) }}"/>
            <!-- titik B -->
            <polyline fill="none" stroke="#7fd4e8" stroke-width="1.8" stroke-linejoin="round" stroke-linecap="round"
              points="{{ $buildPoints($tc['b']) }}"/>
            <!-- titik C -->
            <polyline fill="none" stroke="#e0c23c" stroke-width="1.8" stroke-linejoin="round" stroke-linecap="round"
              points="{{ $buildPoints($tc['c']) }}"/>
            <!-- titik D -->
            <polyline fill="none" stroke="#a78bfa" stroke-width="1.8" stroke-linejoin="round" stroke-linecap="round"
              points="{{ $buildPoints($tc['d']) }}"/>

            <!-- x labels: nomor tube (di-sampling), dirotasi biar muat -->
            <g font-size="8.5" fill="#8b9cb3">
              @foreach ($tubeNumbers as $i => $no)
                <text x="{{ round($chartX0 + $i * $xStep, 1) }}" y="352" text-anchor="end" transform="rotate(-60 {{ round($chartX0 + $i * $xStep, 1) }} 352)">{{ $no }}</text>
              @endforeach
            </g>
            <text x="{{ ($chartX0 + $chartX1) / 2 }}" y="392" font-size="11" fill="#8b9cb3" text-anchor="middle">Tube Number</text>
          </svg>

          <div class="legend-lines">
            <span><i style="display:inline-block;width:14px;height:3px;background:#3fdc84;vertical-align:middle;margin-right:4px;"></i>Titik A</span>
            <span><i style="display:inline-block;width:14px;height:3px;background:#7fd4e8;vertical-align:middle;margin-right:4px;"></i>Titik B</span>
            <span><i style="display:inline-block;width:14px;height:3px;background:#e0c23c;vertical-align:middle;margin-right:4px;"></i>Titik C</span>
            <span><i style="display:inline-block;width:14px;height:3px;background:#a78bfa;vertical-align:middle;margin-right:4px;"></i>Titik D</span>
            <span><i style="display:inline-block;width:14px;height:0;border-top:2px dashed #ff6b6b;vertical-align:middle;margin-right:4px;"></i>MWT</span>
          </div>
          <div class="legend-boxes" style="margin-top:6px;">
            <span style="color:#8b9cb3;font-size:11px;">
              Secara umum nilai ketebalan {{ strtolower($selectedSection) }} masih normal dan berada di atas MWT, rataan nilai cukup homogen
              &mdash; area bend cenderung sedikit lebih rendah daripada area straight.
            </span>
          </div>
        </div>
      </div>

      <div class="right-col">
        <div class="panel">
          <div class="panel-header">
            <h2>TOP 5 REMAINING USEFUL LIFE (RUL)</h2>
            <span class="tag">RUL-01</span>
          </div>
          <table class="rul-table">
            <tr><th>TUBE ID</th><th>SECTION</th><th>RUL</th><th>STATUS</th></tr>
            @forelse ($data['rul_table'] as $row)
              <tr>
                <td class="tube-id">{{ $row['tube_id'] }}</td>
                <td>{{ $row['section'] }}</td>
                <td>{{ $row['rul_months'] }} mo</td>
                <td><span class="rul-badge {{ $row['badge'] }}">{{ strtoupper($row['status']) }}</span></td>
              </tr>
            @empty
              <tr><td colspan="4" style="color:var(--text-faint); text-align:center; padding:14px 0;">Belum ada data tube untuk tahun {{ $selectedYear }}.</td></tr>
            @endforelse
          </table>
        </div>

        <div class="panel rec-panel">
          <h2>RISK MITIGATION OPTIONS &amp; RECOMMENDATIONS</h2>
          <div class="rec-sub">PRIORITIZE LIST</div>

      <div class="priority-list">
          @forelse ($data['priorities'] as $i => $pr)
          <div class="priority p{{ $i + 1 }}">
          <div class="p-title">{{ $pr['level'] }}:</div>
          <div class="p-desc">{{ $pr['text'] }}</div>
      </div>
          @empty
          <div style="color:var(--text-faint); text-align:center; padding:14px 0;">Belum ada data pengukuran untuk {{ $selectedSection }} pada kombinasi Unit/Tahun ini.</div>
          @endforelse
      </div>
        </div>
      </div>
    </div>

<div class="bottom-row">
      <div class="panel">
        <h2>HISTORICAL NDT</h2>
        <table class="ndt">
          <tr><th>Date</th><th>Tube ID</th><th>Creep %</th></tr>
          @forelse ($data['historical_ndt'] as $row)
            <tr>
              <td>{{ $row['date'] }}</td>
              <td class="tube-id">{{ $row['tube_id'] }}</td>
              <td class="creep">{{ number_format($row['creep_pct'], 1) }}%</td>
            </tr>
          @empty
            <tr><td colspan="3" style="color:var(--text-faint); text-align:center; padding:14px 0;">Belum ada riwayat NDT untuk {{ $selectedSection }} — {{ $selectedUnit }}.</td></tr>
          @endforelse
        </table>
      </div>

      <div class="panel">
        <h2>SEARCH / FILTER</h2>
        <div class="search-box">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#666" stroke-width="2"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input type="text" placeholder="Filter Tube ID...">
        </div>
        <button class="export-btn"><img src="{{ asset('images/download.png') }}" alt="" style="width:16px;height:16px;filter:brightness(0) invert(1);"> EXPORT RLA REPORT (PDF/EXCEL)</button>
      </div>
    </div>

    {{-- Dokumen RLA Terupload (dari menu Input Data) --}}
    <div class="panel" style="margin-top:0;">
      <div class="panel-header">
        <h2>RLA DOCUMENTS</h2>
        <span class="tag">RLA-DOCS</span>
      </div>

      @if ($documents->isEmpty())
        <div style="padding:20px 0; text-align:center; font-size:12px; color:var(--text-faint);">
          Belum ada dokumen RLA. Silakan upload melalui menu <strong>Input Data &rarr; Upload Data RLA</strong>.
        </div>
      @else
        {{-- Gambar ditampilkan sebagai thumbnail inline --}}
        @php $imageDocs = $documents->filter(fn($d) => $d->isImage()); @endphp
        @if ($imageDocs->isNotEmpty())
          <div style="display:flex; flex-wrap:wrap; gap:14px; margin-bottom:16px;">
            @foreach ($imageDocs as $doc)
              <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:4px; padding:12px; width:360px;">
                <a href="{{ $doc->fileUrl() }}" target="_blank" title="Klik untuk lihat fullsize">
                  <img src="{{ $doc->fileUrl() }}" alt="{{ $doc->nama_file }}"
                       style="width:100%; height:auto; max-height:420px; object-fit:contain; border-radius:3px; display:block;"
                       loading="lazy">
                </a>
                <div style="margin-top:8px; font-size:11px; color:var(--text-dim); line-height:1.5;">
                  <div style="font-weight:700; color:var(--gold-text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="{{ $doc->unit }}">
                    {{ strtoupper($doc->unit) }}
                  </div>
                  <div style="font-weight:600; color:var(--text-light);">{{ $doc->tanggal->format('d M Y') }}</div>
                  <div style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="{{ $doc->nama_file }}">
                    {{ $doc->nama_file }}
                  </div>
                   <a href="{{ $doc->downloadUrl() }}" download
                      style="display:inline-block; margin-top:4px; font-size:9px; font-weight:700; color:var(--cyan); border:1px solid rgba(127,212,232,0.4); border-radius:3px; padding:2px 8px; text-decoration:none;"
                      onmouseover="this.style.backgroundColor='rgba(127,212,232,0.15)'"
                      onmouseout="this.style.backgroundColor='transparent'">
                     DOWNLOAD
                   </a>
                 </div>
               </div>
             @endforeach
           </div>
         @endif

         {{-- PDF ditampilkan sebagai preview iframe --}}
         @php $pdfDocs = $documents->filter(fn($d) => $d->isPdf()); @endphp
         @if ($pdfDocs->isNotEmpty())
           <div style="display:flex; flex-wrap:wrap; gap:14px; margin-bottom:16px;">
             @foreach ($pdfDocs as $doc)
               <div style="background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); border-radius:4px; padding:12px; width:420px;">
                 <div style="background:#eef2f6; border-radius:3px; overflow:hidden; height:340px;">
                   <iframe src="{{ $doc->fileUrl() }}#toolbar=0&navpanes=0"
                           width="100%"
                           height="100%"
                           style="border:none; display:block;"
                           title="{{ $doc->nama_file }}">
                   </iframe>
                 </div>
                 <div style="margin-top:8px; font-size:11px; color:var(--text-dim); line-height:1.5;">
                   <div style="font-weight:700; color:var(--gold-text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="{{ $doc->unit }}">
                     {{ strtoupper($doc->unit) }}
                   </div>
                   <div style="font-weight:600; color:var(--text-light);">{{ $doc->tanggal->format('d M Y') }}</div>
                   <div style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="{{ $doc->nama_file }}">
                     {{ $doc->nama_file }}
                   </div>
                   <div style="margin-top:6px; display:flex; gap:8px;">
                     <a href="{{ $doc->fileUrl() }}" target="_blank"
                        style="display:inline-block; font-size:9px; font-weight:700; color:var(--gold-text); border:1px solid rgba(224,169,64,0.5); border-radius:3px; padding:3px 10px; text-decoration:none;"
                        onmouseover="this.style.backgroundColor='rgba(224,169,64,0.15)'"
                        onmouseout="this.style.backgroundColor='transparent'">
                       LIHAT FULLSIZE
                     </a>
                     <a href="{{ $doc->downloadUrl() }}" download
                        style="display:inline-block; font-size:9px; font-weight:700; color:var(--cyan); border:1px solid rgba(127,212,232,0.4); border-radius:3px; padding:3px 10px; text-decoration:none;"
                        onmouseover="this.style.backgroundColor='rgba(127,212,232,0.15)'"
                        onmouseout="this.style.backgroundColor='transparent'">
                       DOWNLOAD
                     </a>
                   </div>
                 </div>
               </div>
             @endforeach
           </div>
         @endif

         {{-- Tabel untuk file non-gambar & non-PDF (Excel, CSV, dll) --}}
         @php $nonImageNonPdfDocs = $documents->filter(fn($d) => !$d->isImage() && !$d->isPdf()); @endphp
         @if ($nonImageNonPdfDocs->isNotEmpty())
           <div style="overflow-x:auto; margin-top:4px;">
             <table style="width:100%; border-collapse:collapse; font-size:11.5px;">
               <thead>
                 <tr style="text-align:left; color:var(--text-dim); font-size:10.5px; letter-spacing:0.5px;">
                   <th style="padding:8px 6px; border-bottom:1px solid rgba(255,255,255,0.08);">UNIT</th>
                   <th style="padding:8px 6px; border-bottom:1px solid rgba(255,255,255,0.08);">TANGGAL</th>
                   <th style="padding:8px 6px; border-bottom:1px solid rgba(255,255,255,0.08);">NAMA FILE</th>
                   <th style="padding:8px 6px; border-bottom:1px solid rgba(255,255,255,0.08);">DIUPLOAD</th>
                   <th style="padding:8px 6px; border-bottom:1px solid rgba(255,255,255,0.08); text-align:right;">AKSI</th>
                 </tr>
               </thead>
               <tbody>
                 @foreach ($nonImageNonPdfDocs as $doc)
                   <tr>
                     <td style="padding:7px 6px; border-bottom:1px solid rgba(255,255,255,0.05); color:var(--gold-text); font-weight:700;">
                       {{ strtoupper($doc->unit) }}
                     </td>
                     <td style="padding:7px 6px; border-bottom:1px solid rgba(255,255,255,0.05); font-weight:600;">
                       {{ $doc->tanggal->format('d M Y') }}
                     </td>
                     <td style="padding:7px 6px; border-bottom:1px solid rgba(255,255,255,0.05); max-width:280px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"
                         title="{{ $doc->nama_file }}">
                       {{ $doc->nama_file }}
                     </td>
                     <td style="padding:7px 6px; border-bottom:1px solid rgba(255,255,255,0.05); color:var(--text-dim);">
                       {{ $doc->created_at->format('d M Y H:i') }}
                     </td>
                     <td style="padding:7px 6px; border-bottom:1px solid rgba(255,255,255,0.05); text-align:right;">
                       <a href="{{ $doc->downloadUrl() }}" download
                         style="display:inline-block; font-size:10px; font-weight:700; color:var(--cyan); border:1px solid rgba(127,212,232,0.4); border-radius:3px; padding:4px 10px; text-decoration:none;"
                         onmouseover="this.style.backgroundColor='rgba(127,212,232,0.15)'"
                         onmouseout="this.style.backgroundColor='transparent'">
                        DOWNLOAD
                      </a>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      @endif
    </div>

  </main>

@endsection

