<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Tube Mapping - S2P Boiler Dashboard</title>
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script src="https://cdn.plot.ly/plotly-2.32.0.min.js"></script>
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    theme: {
      extend: {
        colors: {
          panel: "#1c355c",
          bgnavy: "#586C82",
          accent: "#f5a623",
          safe: "#22c55e",
          watch: "#eab308",
          critical: "#ef4444",
        }
      }
    }
  }
</script>
<style>
  body { font-family: ui-sans-serif, system-ui, sans-serif; }
  .cell { transition: transform .12s ease; }
  .cell:hover { transform: scale(1.15); z-index: 10; position: relative; }

  .sidebar{
    width:72px;
    min-width:72px;
    background:linear-gradient(180deg, #0a1729 0%, #0d2038 100%);
    display:flex;
    flex-direction:column;
    align-items:center;
    padding-top:16px;
    gap:36px;
    min-height:100vh;
  }
  .sidebar .logo-box{
    width:64px;
    height:44px;
    display:flex;
    align-items:center;
    justify-content:center;
    position:relative;
  }
  .sidebar .logo-box .logo-text{
    color:#f5a623;
    font-weight:800;
    font-size:15px;
    letter-spacing:1px;
  }
  .sidebar-nav{
    display:flex;
    flex-direction:column;
    gap:44px;
    margin-top:20px;
    align-items:center;
  }
  .sidebar-nav .nav-item{
    writing-mode:vertical-rl;
    transform:rotate(180deg);
    font-size:11px;
    letter-spacing:2px;
    font-weight:600;
    color:#64748b;
    position:relative;
    padding:4px 0;
    background:none;
    border:none;
    cursor:pointer;
  }
  .sidebar-nav .nav-item:hover{
    color:#cbd5e1;
  }
  .sidebar-nav .nav-item.active{
    color:#fff;
    border-left:3px solid #f5a623;
    padding-left:10px;
    margin-left:-13px;
  }

  .header-logo-box{
    width:64px;
    height:44px;
    display:flex;
    align-items:center;
    justify-content:center;
    position:relative;
  }
  .header-logo-box .logo-text{
    color:#f5a623;
    font-weight:800;
    font-size:15px;
    letter-spacing:1px;
  }
</style>
</head>
<body class="bg-bgnavy text-slate-200 min-h-screen" x-data="tubeDashboard()">

<div class="flex">

  <aside class="sidebar">
    <div class="logo-box">
      <img src="<?php echo e(asset('images/logo.png')); ?>" alt="S2P logo" class="w-full h-full object-contain">
    </div>
    <nav class="sidebar-nav">
      <a href="<?php echo e(route('global-view')); ?>" class="nav-item">GLOBAL VIEW</a>
      <a href="<?php echo e(route('tube.mapping')); ?>" class="nav-item active">TUBE MAPPING</a>
      <a href="<?php echo e(route('rla-analysis')); ?>" class="nav-item">RLA ANALYSIS</a>
      <a href="<?php echo e(route('maintenance')); ?>" class="nav-item">MAINTENANCE</a>
    </nav>
  </aside>

  <main class="flex-1 p-6">

    <div class="flex items-center justify-between mb-5">
      <h1 class="text-lg font-bold tracking-wide">
        <span class="text-accent">|</span> TUBE MAPPING: <?php echo e(strtoupper($unit)); ?> — <?php echo e(strtoupper($section)); ?>

      </h1>
      <div class="header-logo-box">
        <img src="<?php echo e(asset('images/logo.png')); ?>" alt="S2P logo" class="w-full h-full object-contain">
      </div>
    </div>

    <form method="GET" class="flex flex-wrap gap-6 items-center mb-5 text-xs">
      <div>
        <label class="text-slate-400 mr-2 font-semibold">BOILER SECTION:</label>
        <select name="section" onchange="this.form.submit()" class="bg-panel border border-white/10 rounded px-3 py-1.5 text-slate-100">
          <?php $__currentLoopData = $sections; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($s); ?>" <?php if($s===$section): echo 'selected'; endif; ?>><?php echo e(strtoupper($s)); ?></option>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
      </div>
      <div>
        <label class="text-slate-400 mr-2 font-semibold">UNIT:</label>
        <select name="unit" onchange="this.form.submit()" class="bg-panel border border-white/10 rounded px-3 py-1.5 text-slate-100">
          <?php $__currentLoopData = $units; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($u); ?>" <?php if($u===$unit): echo 'selected'; endif; ?>><?php echo e(strtoupper($u)); ?></option>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
      </div>
      <div>
        <label class="text-slate-400 mr-2 font-semibold">TAHUN:</label>
        <select name="year" onchange="this.form.submit()" class="bg-panel border border-white/10 rounded px-3 py-1.5 text-slate-100">
          <?php $__currentLoopData = $years; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $y): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($y); ?>" <?php if($y==$year): echo 'selected'; endif; ?>><?php echo e($y); ?></option>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
      </div>
    </form>

    <div class="grid grid-cols-4 gap-4 mb-5">
      <div class="bg-panel rounded-lg px-4 py-3">
        <div class="text-[10px] tracking-wider text-slate-400 font-semibold mb-1">OPERATIONAL STATUS</div>
        <div class="flex items-center gap-2 text-safe font-bold text-lg">
          <img class="icon" src="<?php echo e(asset('images/SVgG.png')); ?>" alt="" width="18" height="18">
          <?php echo e($summary["operational_status"]); ?>

        </div>
      </div>
      <div class="bg-panel rounded-lg px-4 py-3">
        <div class="text-[10px] tracking-wider text-slate-400 font-semibold mb-1">GLOBAL EFFICIENCY</div>
        <div class="flex items-center gap-2 text-white font-bold text-lg">
          <img class="icon" src="<?php echo e(asset('images/thermo.png')); ?>" alt="" width="18" height="18">
          <?php echo e($summary["global_efficiency"]); ?>%
        </div>
      </div>
      <div class="bg-panel rounded-lg px-4 py-3">
        <div class="text-[10px] tracking-wider text-slate-400 font-semibold mb-1">ACTIVE ALERTS</div>
        <div class="flex items-center gap-2 font-bold text-lg">
          <img class="icon" src="<?php echo e(asset('images/pentung.png')); ?>" alt="" width="18" height="18">
          <span class="text-white"><?php echo e($summary["active_alerts"]); ?></span>
          <span class="text-critical text-sm font-medium">(Critical: <?php echo e($summary["critical_count"]); ?>)</span>
        </div>
      </div>
      <div class="bg-panel rounded-lg px-4 py-3">
        <div class="text-[10px] tracking-wider text-slate-400 font-semibold mb-1">BOILER LOAD</div>
        <div class="flex items-center gap-2 text-white font-bold text-lg">
          <img class="icon" src="<?php echo e(asset('images/listrik.png')); ?>" alt="" width="18" height="18">
          <?php echo e($summary["boiler_load"]); ?>%
        </div>
      </div>
    </div>

    <div class="grid grid-cols-3 gap-5 mb-5">

      <div class="col-span-2 bg-panel rounded-lg p-4 relative">
        <div class="text-xs font-bold tracking-wide mb-3"><?php echo e(strtoupper($section)); ?> TUBE INTERACTIVE GRID MAP</div>
        <div class="overflow-x-auto">
          <table class="border-collapse text-[10px] select-none">
            <thead>
              <tr>
                <th class="w-6"></th>
                <?php for($c=1;$c<=$maxCol;$c++): ?>
                  <th class="text-slate-500 font-normal px-1"><?php echo e($c); ?></th>
                <?php endfor; ?>
              </tr>
            </thead>
            <tbody>
              <?php for($r=1;$r<=$maxRow;$r++): ?>
                <tr>
                  <td class="text-slate-500 pr-1"><?php echo e($r); ?></td>
                  <?php for($c=1;$c<=$maxCol;$c++): ?>
                    <?php $t = $grid[$r][$c] ?? null; ?>
                    <?php if($t): ?>
                      <td class="p-[1px]">
                        <button
                          type="button"
                          @click="selectTube(<?php echo \Illuminate\Support\Js::from($t->tube_id)->toHtml() ?>, <?php echo \Illuminate\Support\Js::from($t->material)->toHtml() ?>, <?php echo e($t->current_thickness_mm); ?>, <?php echo e($t->min_design_thickness_mm); ?>, <?php echo e($t->remaining_life_months); ?>, <?php echo e($t->creep_pct); ?>, <?php echo \Illuminate\Support\Js::from($t->status)->toHtml() ?>, <?php echo e($t->corrosion_detected ? "true" : "false"); ?>)"
                          class="cell w-6 h-6 flex items-center justify-center rounded-sm font-semibold text-[10px] text-slate-900
                            <?php echo e($t->status === "Critical" ? "bg-critical text-white" : ($t->status === "Watch" ? "bg-watch" : "bg-safe")); ?>">
                          <?php echo e($c); ?>

                        </button>
                      </td>
                    <?php else: ?>
                      <td class="w-6 h-6"></td>
                    <?php endif; ?>
                  <?php endfor; ?>
                </tr>
              <?php endfor; ?>
            </tbody>
          </table>
        </div>

        <div x-show="selected" x-cloak @click.outside="selected=null"
             class="absolute top-24 left-16 w-80 bg-[#0d1830] border border-white/10 rounded-lg shadow-2xl p-4 text-xs z-20">
          <div class="flex justify-between items-start mb-2">
            <div class="font-bold text-slate-200">SELECTED TUBE: <span x-text="selected?.id"></span></div>
            <button @click="selected=null" class="text-slate-400 hover:text-white">X</button>
          </div>
          <div class="space-y-1 text-slate-300">
            <div>MATERIAL: <span class="font-semibold text-white" x-text="selected?.material"></span></div>
            <div>CURRENT THICKNESS: <span class="font-semibold text-white" x-text="selected?.thickness + 'mm'"></span></div>
            <div>MIN DESIGN THICKNESS: <span class="font-semibold text-white" x-text="selected?.minThickness + 'mm'"></span></div>
            <div>REMAINING LIFE: <span class="font-semibold text-white" x-text="selected?.life + ' month(s)'"></span></div>
            <div>CREEP:
              <span class="font-semibold" :class="selected?.status==='Critical' ? 'text-critical' : (selected?.status==='Watch' ? 'text-watch' : 'text-safe')"
                    x-text="selected?.creep + '% (' + selected?.status?.toUpperCase() + ')'"></span>
            </div>
            <div>NDT REPORT DETECTED CORROSION/PITTING:
              <span class="font-semibold text-white" x-text="selected?.corrosion ? 'YA' : 'TIDAK'"></span>
            </div>
          </div>
        </div>
      </div>

      <div class="flex flex-col gap-5">
        <div class="bg-panel rounded-lg p-4">
          <div class="flex items-center justify-between mb-3">
            <div class="text-xs font-bold tracking-wide">BOILER 3D STRUCTURE</div>
            <div class="text-[9px] text-slate-500">drag = putar &middot; scroll = zoom</div>
          </div>
          <div id="boiler3d" class="h-80 rounded bg-[#0d1830] overflow-hidden"></div>
        </div>
        <div class="bg-panel rounded-lg p-4 flex-1">
          <div class="flex justify-between items-center mb-2">
            <div class="text-xs font-bold tracking-wide">CREEP PERCENTAGE 5 TAHUN</div>
          </div>
          <canvas id="creepChart" height="140"></canvas>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-3 gap-5 mb-5">
      <div class="bg-panel rounded-lg p-4">
        <div class="text-xs font-bold tracking-wide mb-3">LEGENDA STATUS</div>
        <div class="space-y-2 text-xs">
          <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-sm bg-safe"></span> SAFE (<?php echo e($summary["safe_pct"]); ?>%)</div>
          <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-sm bg-watch"></span> WATCH (<?php echo e($summary["watch_pct"]); ?>%)</div>
          <div class="flex items-center gap-2"><span class="w-3 h-3 rounded-sm bg-critical"></span> CRITICAL (<?php echo e($summary["critical_pct"]); ?>%)</div>
        </div>
      </div>

      <div class="col-span-2 bg-panel rounded-lg p-4">
        <div class="text-xs font-bold tracking-wide mb-3">HISTORICAL NDT <?php echo e(strtoupper($unit)); ?> / <?php echo e(strtoupper($section)); ?></div>
        <table class="w-full text-[11px]">
          <thead class="text-slate-400">
            <tr class="text-left">
              <th class="font-normal pb-2">TUBE ID</th>
              <th class="font-normal pb-2">CREEP %</th>
              <th class="font-normal pb-2">SCAN DATE</th>
              <th class="font-normal pb-2">NDT REPORT CORROSION/PITTING</th>
            </tr>
          </thead>
          <tbody class="text-slate-200">
            <?php $__currentLoopData = $historicalNdt; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $h): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <tr class="border-t border-white/5">
                <td class="py-1.5"><?php echo e($h->tube_id); ?></td>
                <td class="py-1.5"><?php echo e($h->creep_pct); ?>%</td>
                <td class="py-1.5"><?php echo e($h->scan_date->format("Y-m-d")); ?></td>
                <td class="py-1.5"><?php echo e($h->corrosion_detected ? "Terdeteksi" : "Tidak"); ?></td>
              </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
                 class="w-full bg-[#0d1830] border border-white/10 rounded pl-9 pr-3 py-2 text-xs text-slate-200 placeholder-slate-500 focus:outline-none focus:border-accent">
        </div>
        <a href="#" class="bg-accent hover:bg-accent/90 text-[#2a1a00] font-bold text-xs px-4 py-2 rounded flex items-center gap-2 whitespace-nowrap">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#2a1a00" stroke-width="2"><path d="M12 3v12m0 0 4-4m-4 4-4-4M4 19h16"/></svg>
          EXPORT REPORT (PDF/EXCEL)
        </a>
      </div>
    </div>

    <div class="bg-panel rounded-lg p-4">
      <div class="text-xs font-bold tracking-wide mb-3">TOP 5 MAINTENANCE PRIORITY</div>
      <div class="space-y-2">
        <?php $__currentLoopData = $topPriority; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <div class="flex items-center justify-between text-xs border-b border-white/5 pb-2">
            <div><span class="text-accent font-bold mr-2">#<?php echo e($p["rank"]); ?></span> <?php echo e($p["tube_id"]); ?> <span class="text-slate-500">(<?php echo e($p["unit"]); ?>)</span></div>
            <span class="bg-critical/20 text-critical font-semibold px-2 py-0.5 rounded"><?php echo e($p["risk"]); ?> risk</span>
          </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </div>
    </div>

    <div x-show="toast" x-cloak x-text="toast"
         class="fixed bottom-6 right-6 bg-panel border border-white/10 text-slate-200 text-xs px-4 py-2 rounded shadow-lg z-50"></div>
  </main>
</div>

<script>
function tubeDashboard() {
  return {
    selected: null,
    toast: null,
    filterTubeId: '',
    selectTube(id, material, thickness, minThickness, life, creep, status, corrosion) {
      this.selected = { id, material, thickness, minThickness, life, creep, status, corrosion };
    }
  }
}

document.addEventListener("DOMContentLoaded", function () {
  const ctx = document.getElementById("creepChart");
  const labels = <?php echo json_encode($creepTrend->pluck("year"), 15, 512) ?>;
  const data = <?php echo json_encode($creepTrend->pluck("creep_pct"), 15, 512) ?>;

  new Chart(ctx, {
    type: "line",
    data: {
      labels: labels,
      datasets: [{
        data: data,
        borderColor: "#f87171",
        backgroundColor: "rgba(56,102,163,0.35)",
        fill: true,
        tension: 0.35,
        pointBackgroundColor: "#38bdf8",
        pointRadius: 4,
      }]
    },
    options: {
      plugins: { legend: { display: false } },
      scales: {
        x: { ticks: { color: "#94a3b8" }, grid: { color: "rgba(255,255,255,0.05)" } },
        y: { ticks: { color: "#94a3b8" }, grid: { color: "rgba(255,255,255,0.05)" } },
      }
    }
  });
});
</script>

<script>
const SECTION_LAYOUT = {
  "Waterwall":      {x0:0.5, x1:3.0, y0:0, y1:3.5, z0:0,   z1:2.0},
  "Superheater":    {x0:3.6, x1:4.6, y0:0, y1:3.0, z0:0.3, z1:1.7},
  "Reheater":       {x0:5.2, x1:6.0, y0:0, y1:2.8, z0:0.3, z1:1.7},
  "Economizer":     {x0:6.6, x1:7.4, y0:0, y1:2.5, z0:0.3, z1:1.7},
  "Air Preheater":  {x0:8.0, x1:9.5, y0:0, y1:2.2, z0:0.3, z1:1.7},
};
const SECTION_COLOR = {
  "Waterwall": "#22c55e", "Superheater": "#eab308", "Reheater": "#22c55e",
  "Economizer": "#22c55e", "Air Preheater": "#3ba7ff",
};
const STEEL = "#5b7a99";

function vsub(a,b){ return [a[0]-b[0], a[1]-b[1], a[2]-b[2]]; }
function vadd(a,b){ return [a[0]+b[0], a[1]+b[1], a[2]+b[2]]; }
function vscale(a,s){ return [a[0]*s, a[1]*s, a[2]*s]; }
function vnorm(a){ return Math.sqrt(a[0]*a[0]+a[1]*a[1]+a[2]*a[2]); }
function vcross(a,b){ return [a[1]*b[2]-a[2]*b[1], a[2]*b[0]-a[0]*b[2], a[0]*b[1]-a[1]*b[0]]; }
function vunit(a){ const n = vnorm(a) || 1e-9; return [a[0]/n, a[1]/n, a[2]/n]; }
function linspace(a,b,n,endpoint=true){
  if(n===1) return [a];
  const step = endpoint ? (b-a)/(n-1) : (b-a)/n;
  const out = [];
  for(let i=0;i<n;i++) out.push(a+step*i);
  return out;
}

function makeCylinder(p0,p1,r0,r1,color,name,nTheta=10,opacity=0.95,caps=true){
  const axis = vsub(p1,p0);
  let length = vnorm(axis);
  let u;
  if(length < 1e-9){ u=[0,0,1]; length=1e-6; } else { u = vscale(axis, 1/length); }
  const tmp = Math.abs(u[0]) < 0.9 ? [1,0,0] : [0,1,0];
  let v1 = vunit(vcross(u, tmp));
  let v2 = vcross(u, v1);
  const thetas = linspace(0, 2*Math.PI, nTheta, false);
  const dirs = thetas.map(t => [
    Math.cos(t)*v1[0] + Math.sin(t)*v2[0],
    Math.cos(t)*v1[1] + Math.sin(t)*v2[1],
    Math.cos(t)*v1[2] + Math.sin(t)*v2[2],
  ]);
  const bottom = dirs.map(d => vadd(p0, vscale(d, r0)));
  const top = dirs.map(d => vadd(p1, vscale(d, r1)));
  let verts = bottom.concat(top);
  const n = nTheta;
  let ii=[], jj=[], kk=[];
  for(let idx=0; idx<n; idx++){
    const a=idx, b=(idx+1)%n, c=idx+n, d=((idx+1)%n)+n;
    ii.push(a,a); jj.push(b,d); kk.push(d,c);
  }
  if(caps){
    const cb = verts.length, ct = verts.length+1;
    verts.push(p0, p1);
    for(let idx=0; idx<n; idx++){
      const a=idx, b=(idx+1)%n;
      ii.push(cb); jj.push(b); kk.push(a);
      const a2=idx+n, b2=((idx+1)%n)+n;
      ii.push(ct); jj.push(a2); kk.push(b2);
    }
  }
  return {
    type:"mesh3d",
    x: verts.map(v=>v[0]), y: verts.map(v=>v[1]), z: verts.map(v=>v[2]),
    i: ii, j: jj, k: kk,
    color, opacity, name, flatshading:true, hoverinfo:"name",
    lighting:{ambient:0.55, diffuse:0.7, specular:0.4, roughness:0.45, fresnel:0.1},
  };
}

function makeTubeGrid(l, color, name, nCols=3, nRows=4, radius=0.11){
  const traces = [];
  const xs = nCols>1 ? linspace(l.x0+(l.x1-l.x0)*0.15, l.x1-(l.x1-l.x0)*0.15, nCols) : [(l.x0+l.x1)/2];
  const zs = linspace(l.z0+(l.z1-l.z0)*0.08, l.z1-(l.z1-l.z0)*0.08, nRows);
  zs.forEach(zc => xs.forEach(xc => {
    traces.push(makeCylinder([xc,l.y0,zc],[xc,l.y1,zc], radius, radius, color, name, 8, 0.95, true));
  }));
  return traces;
}

function makeWaterwall(l, color, name, nPerSide=6, radius=0.055){
  const traces = [];
  let pts = [];
  linspace(l.x0, l.x1, nPerSide, false).forEach(t => pts.push([t, l.y0]));
  linspace(l.y0, l.y1, nPerSide, false).forEach(t => pts.push([l.x1, t]));
  linspace(l.x1, l.x0, nPerSide, false).forEach(t => pts.push([t, l.y1]));
  linspace(l.y1, l.y0, nPerSide, false).forEach(t => pts.push([l.x0, t]));
  pts.forEach(([px,py]) => {
    traces.push(makeCylinder([px,py,l.z0],[px,py,l.z1], radius, radius, color, name, 6, 0.95, false));
  });
  return traces;
}

function makeAirPreheater(l, color, name){
  const cx = (l.x0+l.x1)/2, cz = (l.z0+l.z1)/2;
  const r = Math.min(l.x1-l.x0, l.z1-l.z0)/2*0.92;
  const traces = [makeCylinder([cx,l.y0,cz],[cx,l.y1,cz], r, r, color, name, 18, 0.95, true)];
  let sx=[], sy=[], sz=[];
  [l.y0, l.y1].forEach(yc => {
    linspace(0, 2*Math.PI, 8, false).forEach(ang => {
      sx.push(cx, cx+r*Math.cos(ang), null);
      sy.push(yc, yc, null);
      sz.push(cz, cz+r*Math.sin(ang), null);
    });
  });
  traces.push({type:"scatter3d", mode:"lines", x:sx, y:sy, z:sz, line:{color:"#0a1826", width:3}, hoverinfo:"skip", showlegend:false});
  return traces;
}

function makeDuct(p0, p1, radius=0.22){
  return makeCylinder(p0, p1, radius, radius, STEEL, "Ducting", 10, 0.9, true);
}

function buildBoiler3D(){
  const L = SECTION_LAYOUT["Waterwall"], S = SECTION_LAYOUT["Superheater"], R = SECTION_LAYOUT["Reheater"],
        E = SECTION_LAYOUT["Economizer"], A = SECTION_LAYOUT["Air Preheater"];
  let traces = [];
  traces.push(makeCylinder([5.3,1.5,-0.35],[5.3,1.5,-0.3], 6.4, 6.4, "#1c3d5f", "Ground", 24, 0.85, true));

  traces = traces.concat(makeWaterwall(L, SECTION_COLOR["Waterwall"], "Waterwall", 5, 0.32));
  traces.push(makeCylinder([L.x0-0.25,1.5,L.z1+0.6],[L.x1+0.25,1.5,L.z1+0.6], 0.55, 0.55, STEEL, "Steam Drum", 14, 0.95, true));

  [["Superheater",S,3,3,0.4], ["Reheater",R,2,3,0.4], ["Economizer",E,2,3,0.34]].forEach(([name,layout,nc,nr,rad]) => {
    traces = traces.concat(makeTubeGrid(layout, SECTION_COLOR[name], name, nc, nr, rad));
  });

  traces = traces.concat(makeAirPreheater(A, SECTION_COLOR["Air Preheater"], "Air Preheater"));

  traces.push(makeCylinder([10.6,1.5,0],[10.6,1.5,9.5], 0.55, 0.42, "#8a97a3", "Cerobong", 16, 0.95, true));
  traces.push(makeCylinder([10.6,1.5,9.5],[10.6,1.5,9.9], 0.42, 0.5, "#6b7885", "Cerobong Cap", 16, 0.95, true));

  const midZ = 5.5;
  traces.push(makeDuct([L.x1,1.5,midZ],[S.x0,1.5,midZ], 0.5));
  traces.push(makeDuct([S.x1,1.5,midZ],[R.x0,1.5,midZ], 0.45));
  traces.push(makeDuct([R.x1,1.5,midZ],[E.x0,1.5,midZ], 0.42));
  traces.push(makeDuct([E.x1,1.5,4.3],[A.x0,1.5,3.5], 0.4));
  traces.push(makeDuct([A.x1,1.5,3.5],[10.6,1.5,4], 0.42));

  Object.entries(SECTION_LAYOUT).forEach(([section, layout]) => {
    const cx = (layout.x0+layout.x1)/2;
    traces.push({type:"scatter3d", mode:"text", x:[cx], y:[layout.y1+0.4], z:[layout.z1+0.6],
      text:[section.toUpperCase()], textfont:{color:"white", size:10}, showlegend:false, hoverinfo:"skip"});
  });

  const layout = {
    scene:{
      xaxis:{title:"", showbackground:false, showticklabels:false, showgrid:false, zeroline:false},
      yaxis:{title:"", showbackground:false, visible:false},
      zaxis:{title:"", showbackground:false, showticklabels:false, showgrid:false, zeroline:false},
      aspectmode:"manual", aspectratio:{x:1.9,y:1.0,z:1.3},
      camera:{eye:{x:0.75,y:0.75,z:0.45}}, bgcolor:"#0d1830",
    },
    margin:{l:0,r:0,t:0,b:0}, paper_bgcolor:"#0d1830", font:{color:"white"}, showlegend:false,
  };
  return {traces, layout};
}

document.addEventListener("DOMContentLoaded", function () {
  const container = document.getElementById("boiler3d");
  if (!container || typeof Plotly === "undefined") return;
  const boiler = buildBoiler3D();
  Plotly.newPlot("boiler3d", boiler.traces, boiler.layout, {displaylogo:false, responsive:true});
});
</script>
</body>
</html>
<?php /**PATH C:\Users\ASUS\Downloads\ProjekS2P_DashboardBoiler-fixed (3)\ProjekS2P_DashboardBoiler-main\resources\views/tube-mapping/index.blade.php ENDPATH**/ ?>