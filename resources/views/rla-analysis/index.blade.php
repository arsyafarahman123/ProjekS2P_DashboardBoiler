<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>RLA Analysis - S2P Boiler Dashboard</title>
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

  .layout{ display:flex; min-height:100vh; font-family:'Inter', 'Segoe UI', Arial, Helvetica, sans-serif; }

  /* SIDEBAR */
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
  .sidebar .logo-box img{ 
    width:100%; 
    height:100%;
    object-fit:contain;
    display:block; 
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
  color:var(--text-faint);
  position:relative;
  padding:4px 8px 4px 0;
  border-right:3px solid transparent;
}
.sidebar-nav .nav-item.active{
  color:#fff;
  border-right-color:var(--gold);
}

  /* MAIN */
  .main{
    flex:1;
    position:relative;
    min-height:100vh;
    background:linear-gradient(120deg, #64798f 0%, #586C82 45%, #46586c 100%);
    padding:22px 26px;
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
  .rul-badge.warning{ background:rgba(224,194,60,0.18); color:#e8d268; }
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
</head>
<body>

<div class="layout">

  <div class="sidebar">
    <div class="logo-box"><img src="{{ asset('images/logo.png') }}" alt="S2P logo"></div>
    <div class="sidebar-nav">
      <a href="{{ route('global-view') }}" class="nav-item" style="text-decoration:none;">GLOBAL VIEW</a>
      <a href="{{ route('tube.mapping') }}" class="nav-item" style="text-decoration:none;">TUBE MAPPING</a>
      <a href="{{ route('rla-analysis') }}" class="nav-item active" style="text-decoration:none;">RLA ANALYSIS</a>
      <a href="{{ route('maintenance') }}" class="nav-item" style="text-decoration:none;">MAINTENANCE</a>
      <a href="{{ route('input-data.index') }}" class="nav-item" style="text-decoration:none;">INPUT DATA</a>
    </div>
  </div>

  <div class="main">

    <div class="title-row">
      <div class="title-left">
        <span class="accent-bar"></span>
        <h1>RLA ANALYSIS: {{ strtoupper($selectedSection) }}</h1>
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
          <h2>REMAINING LIFE PREDICTION</h2>
          <span class="tag">RLA-01</span>
        </div>
        <div class="chart-wrap">
          <svg viewBox="0 0 800 400" width="100%" style="overflow:visible">
            <!-- vertical gridlines -->
            <g stroke="rgba(127,212,232,0.14)" stroke-width="1">
              <line x1="60" y1="10" x2="60" y2="340"/>
              <line x1="140" y1="10" x2="140" y2="340"/>
              <line x1="220" y1="10" x2="220" y2="340"/>
              <line x1="300" y1="10" x2="300" y2="340"/>
              <line x1="380" y1="10" x2="380" y2="340"/>
              <line x1="460" y1="10" x2="460" y2="340"/>
              <line x1="540" y1="10" x2="540" y2="340"/>
              <line x1="620" y1="10" x2="620" y2="340"/>
              <line x1="700" y1="10" x2="700" y2="340"/>
            </g>
            <!-- axis lines -->
            <g stroke="rgba(255,255,255,0.15)" stroke-width="1">
              <line x1="60" y1="340" x2="740" y2="340"/>
            </g>
            <!-- y labels left -->
            <g font-size="11" fill="#8b9cb3">
              <text x="30" y="16">200</text>
              <text x="38" y="97">150</text>
              <text x="38" y="178">100</text>
              <text x="46" y="259">50</text>
              <text x="46" y="340">0</text>
            </g>
            <!-- y labels right -->
            <g font-size="11" fill="#8b9cb3">
              <text x="750" y="16">2</text>
              <text x="750" y="97">1.5</text>
              <text x="750" y="178">1</text>
              <text x="750" y="259">0.5</text>
              <text x="750" y="340">0</text>
            </g>
            <!-- axis titles -->
            <text x="-175" y="16" transform="rotate(-90)" font-size="11" fill="#8b9cb3" text-anchor="middle">Remaining Life (Months)</text>
            <text x="-175" y="792" transform="rotate(-90)" font-size="11" fill="#8b9cb3" text-anchor="middle">Thinning (mm)</text>

            <!-- critical limit dashed -->
            <line x1="60" y1="256" x2="740" y2="256" stroke="#ff6b6b" stroke-dasharray="6,5" stroke-width="1.3"/>
            <text x="650" y="250" font-size="10" fill="#ff6b6b" font-weight="700">CRITICAL LIMIT</text>

            <!-- thinning rate (cyan) -->
            <polyline fill="none" stroke="#7fd4e8" stroke-width="2.2" stroke-linejoin="round" stroke-linecap="round"
              points="60,325 115,300 170,275 225,255 280,235 335,222 390,205 445,175 500,145 555,110 610,80 665,55 700,42"/>

            <!-- watch tubes (green) -->
            <polyline fill="none" stroke="#3fdc84" stroke-width="2.4" stroke-linejoin="round" stroke-linecap="round"
              points="60,58 115,70 170,88 225,100 280,108 335,118 390,133 445,155 500,180 555,205 610,225 665,242 700,252"/>

            <!-- watch avg (yellow) -->
            <polyline fill="none" stroke="#e0c23c" stroke-width="2.4" stroke-linejoin="round" stroke-linecap="round"
              points="60,63 115,78 170,100 225,120 280,135 335,148 390,168 445,192 500,218 555,245 610,268 665,285 700,295"/>

            <!-- selected tube (red) -->
            <polyline fill="none" stroke="#ff4d4f" stroke-width="2.6" stroke-linejoin="round" stroke-linecap="round"
              points="60,68 115,105 170,150 225,192 280,225 335,255 390,285 445,312 500,330 555,338 610,340 665,340 700,340"/>

            <!-- x labels -->
            <g font-size="10" fill="#8b9cb3">
              <text x="45" y="358">2023-Q4</text>
              <text x="125" y="358">2024-Q3</text>
              <text x="205" y="358">2025-Q2</text>
              <text x="285" y="358">2026-Q1</text>
              <text x="365" y="358">2026-Q4</text>
              <text x="445" y="358">2027-Q3</text>
              <text x="525" y="358">2028-Q2</text>
              <text x="605" y="358">2029-Q1</text>
            </g>

            <!-- remaining life hover zones (invisible, driven by JS below) -->
            <g id="rlHoverZones">
              <rect class="hover-zone" x="20"  y="10" width="80"  height="330" data-period="2023-Q4" data-months="168" data-eta="2037-Q4"/>
              <rect class="hover-zone" x="100" y="10" width="80"  height="330" data-period="2024-Q3" data-months="132" data-eta="2035-Q3"/>
              <rect class="hover-zone" x="180" y="10" width="80"  height="330" data-period="2025-Q2" data-months="94"  data-eta="2033-Q1"/>
              <rect class="hover-zone" x="260" y="10" width="80"  height="330" data-period="2026-Q1" data-months="64"  data-eta="2031-Q2"/>
              <rect class="hover-zone" x="340" y="10" width="80"  height="330" data-period="2026-Q4" data-months="37"  data-eta="2029-Q4"/>
              <rect class="hover-zone" x="420" y="10" width="80"  height="330" data-period="2027-Q3" data-months="14"  data-eta="2028-Q4"/>
              <rect class="hover-zone" x="500" y="10" width="80"  height="330" data-period="2028-Q2" data-months="3"   data-eta="2028-Q3"/>
              <rect class="hover-zone" x="580" y="10" width="160" height="330" data-period="2029-Q1" data-months="0"   data-eta="2029-Q1"/>
            </g>
          </svg>

          <div class="rl-tooltip" id="rlTooltip">
            <div class="rl-period" id="rlPeriod"></div>
            <div class="rl-value" id="rlValue"></div>
            <div class="rl-eta" id="rlEta"></div>
          </div>

          <div class="legend-lines">
            <span><img src="{{ asset('images/tube_merah.png') }}" alt=""> Selected Tube</span>
            <span><img src="{{ asset('images/tube_kuning.png') }}" alt=""> Watch Avg</span>
            <span><img src="{{ asset('images/tube_hijau.png') }}" alt=""> Watch Tubes</span>
            <span><img src="{{ asset('images/tube_abu.png') }}" alt=""> Design Limit</span>
            <span><img src="{{ asset('images/tube_navy.png') }}" alt=""> Thinning Rate</span>
          </div>
          <div class="legend-boxes">
            <span><i style="background:#3fdc84"></i>SAFE</span>
            <span><i style="background:#e0c23c"></i>WARNING</span>
            <span><i style="background:#ff4d4f"></i>CRITICAL</span>
          </div>
        </div>
      </div>

      <div class="right-col">
        <div class="panel">
          <div class="panel-header">
            <h2>REMAINING USEFUL LIFE (RUL)</h2>
            <span class="tag">RUL-01</span>
          </div>
          <table class="rul-table">
            <tr><th>TUBE ID</th><th>SECTION</th><th>RUL</th><th>%</th><th>GRADE</th><th>STATUS</th></tr>
            @foreach($data['rul_table'] as $row)
            <tr>
              <td class="tube-id">{{ $row['tube_id'] }}</td>
              <td>{{ $row['section'] }}</td>
              <td>{{ $row['rul_months'] }} mo</td>
              <td>{{ $row['pct'] }}%</td>
              <td>{{ $row['grade'] }}</td>
              <td><span class="rul-badge {{ $row['status'] }}">{{ strtoupper($row['status']) }}</span></td>
            </tr>
            @endforeach
          </table>
        </div>

        <div class="panel rec-panel">
          <h2>RISK MITIGATION OPTIONS &amp; RECOMMENDATIONS</h2>
          <div class="rec-sub">PRIORITIZE LIST</div>

          <div class="priority-list">
            <div class="priority p1">
              <div class="p-title">PRIORITY 1 (CRITICAL):</div>
              <div class="p-desc">Replace Tube ID # SH-2-R12-T18 (Selected Tube) at Next Outage.</div>
            </div>
            <div class="priority p2">
              <div class="p-title">PRIORITY 2:</div>
              <div class="p-desc">Increase Sootblowing Frequency in Secondary Superheater.</div>
            </div>
            <div class="priority p3">
              <div class="p-title">PRIORITY 3:</div>
              <div class="p-desc">Adjust Burner 4 for Flame Distribution optimization.</div>
            </div>
            <div class="priority p4">
              <div class="p-title">PRIORITY 4:</div>
              <div class="p-desc">Conduct specific chemical cleaning on Economizer tubes during next annual shutdown.</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="bottom-row">
      <div class="panel">
        <h2>HISTORICAL NDT</h2>
        <table class="ndt">
          <tr><th>Date</th><th>Tube ID</th><th>Creep %</th></tr>
          @foreach($data['historical_ndt'] as $ndt)
          <tr><td>{{ $ndt['date'] }}</td><td class="tube-id">{{ $ndt['tube_id'] }}</td><td class="creep">{{ $ndt['creep'] }}%</td></tr>
          @endforeach
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

  </div>

</div>
  <script>
    (function(){
      const wrap = document.querySelector('.chart-wrap');
      const tooltip = document.getElementById('rlTooltip');
      const periodEl = document.getElementById('rlPeriod');
      const valueEl = document.getElementById('rlValue');
      const etaEl = document.getElementById('rlEta');
      const zones = document.querySelectorAll('.hover-zone');

      zones.forEach(zone => {
        zone.addEventListener('mousemove', (e) => {
          const period = zone.getAttribute('data-period');
          const months = zone.getAttribute('data-months');
          const eta = zone.getAttribute('data-eta');

          periodEl.textContent = 'PER ' + period;
          valueEl.textContent = 'Remaining Life: ~' + months + ' bulan';
          etaEl.textContent = months == 0
            ? 'Estimasi sudah mencapai batas kritis'
            : 'Perkiraan habis: ' + eta;

          const wrapRect = wrap.getBoundingClientRect();
          let left = e.clientX - wrapRect.left + 14;
          let top = e.clientY - wrapRect.top - 46;

          // keep tooltip inside the chart-wrap box
          const maxLeft = wrapRect.width - 190;
          if (left > maxLeft) left = e.clientX - wrapRect.left - 190;
          if (top < 0) top = e.clientY - wrapRect.top + 16;

          tooltip.style.left = left + 'px';
          tooltip.style.top = top + 'px';
          tooltip.style.display = 'block';
        });

        zone.addEventListener('mouseleave', () => {
          tooltip.style.display = 'none';
        });
      });
    })();
  </script>

</body>
</html>