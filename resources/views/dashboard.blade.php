<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>S2P PLTU Cilacap — Boiler 3D Digital Twin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.plot.ly/plotly-2.32.0.min.js"></script>
<style>
    * { box-sizing: border-box; }
    html { scroll-behavior: smooth; }
    body {
        font-family: 'Inter', sans-serif; margin:0;
        background:
            radial-gradient(ellipse 1000px 560px at 12% -8%, #f5b30122, transparent 60%),
            radial-gradient(ellipse 900px 560px at 100% 0%, #1a4a7a3d, transparent 60%),
            radial-gradient(ellipse 700px 500px at 50% 110%, #0d3a5c40, transparent 60%),
            #050b1a;
        background-attachment: fixed;
        position: relative;
    }
    .layout { display:flex; background:#050b1a; min-height:100vh; font-family:'Inter',sans-serif; }
    /* SIDEBAR */
    .sidebar{
        width:72px;
        min-width:72px;
        background:linear-gradient(180deg, #0a1729 0%, #0d2038 100%);
        display:flex;
        flex-direction:column;
        align-items:center;
        padding-top:16px;
        gap:36px;
        position:sticky; top:0; height:100vh;
    }
    .logo-box{
        width:52px;
        height:40px;
        background:#0e2037;
        border:2px solid #f0f0f0;
        border-radius:2px;
        overflow:hidden;
        display:flex; align-items:center; justify-content:center;
    }
    .logo-box .logo-text{
        font-family:'Poppins',sans-serif;
        font-weight:800;
        font-size:15px;
        letter-spacing:.5px;
        color:#f5b301;
        line-height:1;
    }
    .logo-box img{ width:100%; height:100%; object-fit:contain; display:block; }
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
        color:#7fa3c9;
        position:relative;
        padding:4px 0;
        cursor:pointer;
    }
    .sidebar-nav .nav-item.active{
        color:#fff;
        border-left:3px solid #f5b301;
        padding-left:10px;
        margin-left:-13px;
    }
    .main { flex:1; padding:0; }
    .page-panel {
        position: relative;
        min-height: 100vh;
        background: linear-gradient(120deg, #64798f 0%, #586C82 45%, #46586c 100%);
        padding: 22px 26px;
        overflow: hidden;
    }
    .section-block {
        position: relative;
        padding: 18px 0;
        border-bottom: 1px solid rgba(255,255,255,0.08);
    }
    .section-block:last-child { border-bottom: none; }
    .header-title-bar {
        display:flex; align-items:center; gap:10px; margin-bottom:14px; position:relative; z-index:1;
    }
    .header-title-bar .accent-bar {
        width:4px; height:18px; background:#f5b301; border-radius:2px; flex-shrink:0;
    }
    .header-title-bar h2 {
        color:#f5b301; margin:0; font-weight:800; font-size:15px; letter-spacing:.6px;
        font-family:'Poppins',sans-serif; text-transform:uppercase;
        background:none; -webkit-text-fill-color:#f5b301;
    }
    .header-logo-corner {
        width:56px; position:absolute; top:14px; right:18px; z-index:2;
    }
    .header-logo-corner .logo-box{
        width:56px; height:40px; background:#0e2037; border-top:2px solid #f5b301; border-bottom:2px solid #f5b301;
        border-radius:2px; overflow:hidden; display:flex; align-items:center; justify-content:center;
    }
    .glow-orb { position:absolute; border-radius:50%; filter: blur(60px); pointer-events:none; z-index:0; }
    .stat-card { transition: transform .18s ease, background .18s ease, box-shadow .18s ease; border-radius:10px; position:relative;
        background: linear-gradient(90deg,#16283f 0%,#1c3350 55%,#274361 100%);
        border:1px solid #24425f; padding:14px 22px; color:white; min-height:64px;
        display:flex; flex-direction:row; align-items:center; gap:14px; }
    .stat-card:hover { transform: translateY(-3px); box-shadow: 0 6px 18px rgba(0,0,0,.35); }
    .stat-icon { flex-shrink:0; display:flex; align-items:center; justify-content:center; opacity:.95; }
    .stat-content { display:flex; flex-direction:column; }
    .stat-label { color:#7fa3c9; font-size:10.5px; font-weight:700; letter-spacing:.8px; margin-bottom:5px; display:flex; align-items:center; }
    .stat-value { font-size:19px; font-weight:800; font-family:'Poppins',sans-serif; display:flex; align-items:center; }
    @keyframes pulse-glow {
        0% { box-shadow: 0 0 0 0 rgba(34,197,94,.6); } 70% { box-shadow: 0 0 0 9px rgba(34,197,94,0); } 100% { box-shadow: 0 0 0 0 rgba(34,197,94,0); }
    }
    .pulse-dot { display:inline-block; width:9px; height:9px; border-radius:50%; background:#22c55e; margin-right:6px; animation: pulse-glow 1.8s infinite; }
    .brand-badge { background: linear-gradient(135deg,#f5b301,#c9860f); border:1px solid #ffd873; border-radius:10px; padding:6px 14px;
        color:#2a1a00; font-weight:800; font-size:11px; letter-spacing:1px; box-shadow: 0 3px 12px #f5b30144; }
    ::-webkit-scrollbar { width: 8px; height:8px; }
    ::-webkit-scrollbar-thumb { background:#2a5580; border-radius:6px; }
    ::-webkit-scrollbar-thumb:hover { background:#f5b30188; }
    select.filter-select {
        width:170px; border-radius:8px; background:#3c4e62; border:1px solid #7690a8; color:white;
        padding:8px 10px; font-family:'Inter',sans-serif; font-size:13px; cursor:pointer;
    }
    select.filter-select:hover, select.filter-select:focus { border-color:#f5b301; outline:none; }
    .status-chip { display:inline-flex; align-items:center; gap:6px; padding:4px 12px; border-radius:999px; font-size:11px; font-weight:700; }
    .panel-title { color:white; font-weight:800; font-size:13px; margin-bottom:10px; font-family:'Poppins',sans-serif; }
    .risk-row { display:flex; align-items:center; gap:10px; padding:8px 6px; border-bottom:1px solid #16324a; cursor:pointer; border-radius:6px; }
    .risk-row:hover { background: rgba(245,179,1,.06); }
    details > summary { list-style:none; cursor:pointer; }
    details > summary::-webkit-details-marker { display:none; }
    .detail-panel { margin-bottom:12px; border:1px solid #1c3a5c; border-radius:18px; box-shadow: 0 4px 18px rgba(0,0,0,0.35); padding:16px 20px;
        background: linear-gradient(160deg, #132f57 0%, #0d2140 65%); }
    .detail-panel.focused { border-color:#3ba7ff; box-shadow: 0 0 0 2px #3ba7ff55; }
    .stat-chip { text-align:center; min-width:80px; }
    .stat-chip .val { font-size:20px; font-weight:800; }
    .stat-chip .lbl { color:#7fa3c9; font-size:10.5px; font-weight:700; letter-spacing:.5px; }
    table.tube-table { width:100%; border-collapse:collapse; }
    table.tube-table th { padding:8px 10px; color:#7fa3c9; font-size:11px; text-align:left; border-bottom:1px solid #1c3a5c; position:sticky; top:0; background:#0d2140; }
    table.tube-table td { padding:7px 10px; color:white; font-size:12.5px; border-bottom:1px solid #16324a; }
    .table-scroll { max-height:360px; overflow-y:auto; border-radius:8px; }
    #boiler-3d, #risk-bar, #comparison-fig { width:100%; max-width:100%; }
</style>
</head>
<body>
<div class="layout">
    <div class="sidebar">
    <div class="logo-box">
        <img src="{{ asset('images/logo.png') }}" alt="S2P Logo">
    </div>
<div class="sidebar-nav">

    <a href="{{ route('global-view') }}" class="nav-item active">
        GLOBAL VIEW
    </a>

   <a href="{{ route('tube.mapping') }}" class="nav-item">
        TUBE MAPPING
     </a>

    <a href="#" class="nav-item">
        RLA ANALYSIS
    </a>

    <a href="#" class="nav-item">
        MAINTENANCE
    </a>

</div>
</div>

    <div class="main">
    <div class="page-panel">
        <div class="section-block" style="padding-top:2px;">
            <div class="glow-orb" style="width:260px;height:260px;background:#f5b301;opacity:.05;top:-100px;right:-60px;"></div>
            <div class="glow-orb" style="width:220px;height:220px;background:#3ba7ff;opacity:.06;bottom:-90px;left:-40px;"></div>

            <div class="header-logo-corner">
                <div class="logo-box">
                    <img src="{{ asset('images/logo.png') }}" alt="S2P Logo">
                </div>
            </div>

            <div class="header-title-bar">
                <span class="accent-bar"></span>
                <h2>GLOBAL VIEW: BOILER UNIT {{ $defaultUnit }} STATUS & RISK MAP</h2>
            </div>

            <div style="display:flex;justify-content:flex-start;align-items:center;margin-bottom:18px;flex-wrap:wrap;gap:22px;position:relative;z-index:1;">
                <div style="display:flex;align-items:center;gap:8px;">
                    <span style="color:#d7e4f0;font-size:11px;font-weight:700;letter-spacing:1px;">UNIT:</span>
                    <select id="unit-dd" class="filter-select" style="width:130px;">
                        @foreach($units as $u)
                            <option value="{{ $u }}" @selected($u === $defaultUnit)>{{ $u }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <span style="color:#d7e4f0;font-size:11px;font-weight:700;letter-spacing:1px;">TAHUN:</span>
                    <select id="year-dd" class="filter-select" style="width:120px;">
                        @foreach($years as $y)
                            <option value="{{ $y }}" @selected($y == $defaultYear)>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div id="status-cards" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:10px;position:relative;z-index:1;"></div>
        </div>

        <div class="section-block">
            <div style="display:flex;gap:16px;align-items:stretch;flex-wrap:wrap;position:relative;z-index:1;">
                <div style="flex:1.4;min-width:420px;background:linear-gradient(160deg,#122c52,#0d2140);border:1px solid #1e3a5f;border-radius:14px;padding:14px;box-shadow:0 6px 20px rgba(0,0,0,.3);">
                    <div id="boiler-3d"></div>
                </div>
                <div style="flex:1;min-width:340px;background:linear-gradient(160deg,#122c52,#0d2140);border:1px solid #1e3a5f;border-radius:14px;padding:14px;box-shadow:0 6px 20px rgba(0,0,0,.3);">
                    <div class="panel-title">📊 Risk Summary by Section</div>
                    <div id="risk-list" style="margin-bottom:8px;"></div>
                    <div id="risk-bar"></div>
                    <div style="margin-top:12px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px;
                        padding:14px 22px; background:#0e2a55; border-radius:999px; border:1px solid #1c3f6e;">
                        <span style="display:flex;align-items:center;gap:9px;">
                            <span style="width:14px;height:14px;border-radius:4px;background:{{ $statusColor['Safe'] }};flex-shrink:0;"></span>
                            <span style="color:{{ $statusColor['Safe'] }};font-weight:800;font-size:12.5px;letter-spacing:.3px;">SAFE &lt;40%</span>
                        </span>
                        <span style="display:flex;align-items:center;gap:9px;">
                            <span style="width:14px;height:14px;border-radius:4px;background:{{ $statusColor['Watch'] }};flex-shrink:0;"></span>
                            <span style="color:{{ $statusColor['Watch'] }};font-weight:800;font-size:12.5px;letter-spacing:.3px;">WATCH 40–80%</span>
                        </span>
                        <span style="display:flex;align-items:center;gap:9px;">
                            <span style="width:14px;height:14px;border-radius:4px;background:{{ $statusColor['Critical'] }};flex-shrink:0;"></span>
                            <span style="color:{{ $statusColor['Critical'] }};font-weight:800;font-size:12.5px;letter-spacing:.3px;">CRITICAL &gt;80%</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="section-block" style="padding-bottom:2px;">
            <div class="panel-title" style="display:flex;align-items:center;gap:10px;text-transform:uppercase;font-size:15px;letter-spacing:.4px;">
                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="#22c55e" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><polyline points="2 17 9 10 13 14 22 4"></polyline><polyline points="16 4 22 4 22 10"></polyline></svg>
                Perbandingan 5 Tahun Terakhir
            </div>
            <div id="comparison-legend" style="display:flex;flex-wrap:wrap;gap:26px;margin:14px 4px 16px 4px;"></div>
            <div id="comparison-fig"></div>
        </div>
    </div>
    </div>
</div>

<script>
// ================= Konfigurasi dari server =================
const SECTIONS = @json($sections);
const SECTION_LAYOUT = @json($sectionLayout);
const STATUS_COLOR = @json($statusColor);
const STEEL = '#5b7a99';
let focusedSection = null;

// ================= Helper vektor (port dari numpy) =================
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

// ================= Builder geometri 3D (port dari make_cylinder dkk) =================
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
        type:'mesh3d',
        x: verts.map(v=>v[0]), y: verts.map(v=>v[1]), z: verts.map(v=>v[2]),
        i: ii, j: jj, k: kk,
        color, opacity, name, flatshading:true, hoverinfo:'name',
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
    traces.push({type:'scatter3d', mode:'lines', x:sx, y:sy, z:sz, line:{color:'#0a1826', width:3}, hoverinfo:'skip', showlegend:false});
    return traces;
}

function makeDuct(p0, p1, radius=0.22){
    return makeCylinder(p0, p1, radius, radius, STEEL, 'Ducting', 10, 0.9, true);
}

function buildBoiler3D(colorsBySection){
    const L = SECTION_LAYOUT['Waterwall'], S = SECTION_LAYOUT['Superheater'], R = SECTION_LAYOUT['Reheater'],
          E = SECTION_LAYOUT['Economizer'], A = SECTION_LAYOUT['Air Preheater'];
    let traces = [];
    traces.push(makeCylinder([5.3,1.5,-0.35],[5.3,1.5,-0.3], 6.4, 6.4, '#1c3d5f', 'Ground', 24, 0.85, true));

    traces = traces.concat(makeWaterwall(L, colorsBySection['Waterwall'], 'Waterwall', 5, 0.32));
    traces.push(makeCylinder([L.x0-0.25,1.5,L.z1+0.6],[L.x1+0.25,1.5,L.z1+0.6], 0.55, 0.55, STEEL, 'Steam Drum', 14, 0.95, true));

    [['Superheater',S,3,3,0.4], ['Reheater',R,2,3,0.4], ['Economizer',E,2,3,0.34]].forEach(([name,layout,nc,nr,rad]) => {
        traces = traces.concat(makeTubeGrid(layout, colorsBySection[name], name, nc, nr, rad));
    });

    traces = traces.concat(makeAirPreheater(A, colorsBySection['Air Preheater'], 'Air Preheater'));

    traces.push(makeCylinder([10.6,1.5,0],[10.6,1.5,9.5], 0.55, 0.42, '#8a97a3', 'Cerobong', 16, 0.95, true));
    traces.push(makeCylinder([10.6,1.5,9.5],[10.6,1.5,9.9], 0.42, 0.5, '#6b7885', 'Cerobong Cap', 16, 0.95, true));

    const midZ = 5.5;
    traces.push(makeDuct([L.x1,1.5,midZ],[S.x0,1.5,midZ], 0.5));
    traces.push(makeDuct([S.x1,1.5,midZ],[R.x0,1.5,midZ], 0.45));
    traces.push(makeDuct([R.x1,1.5,midZ],[E.x0,1.5,midZ], 0.42));
    traces.push(makeDuct([E.x1,1.5,4.3],[A.x0,1.5,3.5], 0.4));
    traces.push(makeDuct([A.x1,1.5,3.5],[10.6,1.5,4], 0.42));

    Object.entries(SECTION_LAYOUT).forEach(([section, layout]) => {
        const cx = (layout.x0+layout.x1)/2;
        traces.push({type:'scatter3d', mode:'text', x:[cx], y:[layout.y1+0.4], z:[layout.z1+0.6],
            text:[section.toUpperCase()], textfont:{color:'white', size:11}, showlegend:false, hoverinfo:'skip'});
    });

    const layout = {
        scene:{
            xaxis:{title:'', showbackground:false, showticklabels:false, showgrid:false, zeroline:false},
            yaxis:{title:'', showbackground:false, visible:false},
            zaxis:{title:'Tinggi (m)', showbackground:false, gridcolor:'#1c3a5c', color:'#8fb4d6'},
            aspectmode:'manual', aspectratio:{x:1.9,y:1.0,z:1.3},
            camera:{eye:{x:0.75,y:0.75,z:0.45}}, bgcolor:'#0d2140',
        },
        margin:{l:0,r:0,t:10,b:0}, paper_bgcolor:'#0d2140', font:{color:'white'}, height:460, showlegend:false,
    };
    return {traces, layout};
}

// ================= Chart 2D (port build_risk_summary_fig & build_comparison_fig) =================
function buildRiskBar(sectionSummary){
    const statuses = ['Safe','Watch','Critical'];
    const textColorMap = { Safe:'#06281a', Watch:'#2b1d00', Critical:'#ffffff' };
    const traces = statuses.map(status => {
        const key = status.toLowerCase();
        const y = sectionSummary.map(s => s[key]);
        return {
            type:'bar', x: sectionSummary.map(s => s.section), y, name: status,
            marker:{color: STATUS_COLOR[status]},
            text: y.map(v => v>0 ? String(v) : ''), textposition:'inside', insidetextanchor:'middle',
            textangle:0, constraintext:'none',
            textfont:{color: textColorMap[status], size:12, family:'Inter, sans-serif', weight:700},
        };
    });
    const layout = {
        barmode:'stack', height:210, paper_bgcolor:'#0d2140', plot_bgcolor:'#0d2140',
        font:{color:'#cfe3f5', family:'Inter, sans-serif', size:11},
        margin:{l:34,r:14,t:14,b:36},
        legend:{orientation:'h', y:1.18, font:{size:10}},
        xaxis:{showgrid:false, tickfont:{color:'#d7e4f0', size:12, weight:700}, automargin:true},
        yaxis:{showgrid:true, gridcolor:'#1c3a5c', tickfont:{color:'#9fc0dc', size:11}, automargin:true},
        bargap:0.35,
    };
    return {traces, layout};
}

const COMPARISON_PALETTE = ['#f5b301', '#3ba7ff', '#ef4444', '#22c55e', '#a78bfa'];

function buildComparisonChart(comparison){
    const traces = Object.entries(comparison).map(([tubeId, rows], idx) => {
        const color = COMPARISON_PALETTE[idx % COMPARISON_PALETTE.length];
        return {
            type:'scatter', mode:'lines+markers', name: tubeId,
            x: rows.map(r => r.year), y: rows.map(r => r.creep_pct),
            line:{width:3, color}, marker:{size:7, color},
        };
    });
    const layout = {
        height:280, autosize:true, paper_bgcolor:'#0d2140', plot_bgcolor:'#0d2140',
        font:{color:'#cfe3f5', family:'Inter, sans-serif'},
        margin:{l:34,r:20,t:20,b:60},
        xaxis:{title:{text:'Tahun', standoff:18}, showgrid:false, dtick:1, tickformat:'d'},
        yaxis:{showgrid:true, gridcolor:'#1c3a5c'},
        showlegend:false, hovermode:'x unified',
    };
    return {traces, layout};
}

// ================= Render DOM (port html.Div cards / risk-list) =================
function renderCards(cards){
    const el = document.getElementById('status-cards');
    const alertColor = '#ef4444';

    const icons = {
        pulse: `<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="2 13 6 13 9 5 13 21 16 13 22 13"></polyline></svg>`,
        gauge: `<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15a8 8 0 1 1 16 0"></path><path d="M12 15 L16.5 9.5"></path><circle cx="12" cy="15" r="1" fill="currentColor" stroke="none"></circle></svg>`,
        warning: `<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 L22.5 21 H1.5 Z"></path><line x1="12" y1="9.5" x2="12" y2="14.5"></line><circle cx="12" cy="17.5" r="0.6" fill="currentColor" stroke="none"></circle></svg>`,
        bolt: `<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 4 14 11 14 9 22 20 10 13 10 13 2"></polygon></svg>`,
    };

    el.innerHTML = `
        <div class="stat-card">
            <div class="stat-icon" style="color:#22c55e;">${icons.pulse}</div>
            <div class="stat-content">
                <div class="stat-label">OPERATIONAL STATUS</div>
                <div class="stat-value" style="color:#22c55e;"><span class="pulse-dot"></span>ACTIVE</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="color:#3ba7ff;">${icons.gauge}</div>
            <div class="stat-content">
                <div class="stat-label">GLOBAL EFFICIENCY</div>
                <div class="stat-value" style="color:#5dc9d6;">${cards.efficiency}%</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="color:#f5b301;">${icons.warning}</div>
            <div class="stat-content">
                <div class="stat-label">ACTIVE ALERTS</div>
                <div class="stat-value" style="color:#f5b301;">${cards.critical_count + cards.watch_count} <span style="color:${alertColor};font-size:13px;font-weight:700;margin-left:6px;">(Critical: ${cards.critical_count})</span></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="color:#3ba7ff;">${icons.bolt}</div>
            <div class="stat-content">
                <div class="stat-label">BOILER LOAD</div>
                <div class="stat-value" style="color:#5dc9d6;">${cards.load}%</div>
            </div>
        </div>`;
}

function renderRiskList(sectionSummary){
    const el = document.getElementById('risk-list');
    el.innerHTML = sectionSummary.map(s => `
        <div class="risk-row" data-section="${s.section}">
            <div style="width:10px;height:10px;border-radius:3px;background:${s.color};flex-shrink:0;"></div>
            <div>
                <span style="color:white;font-weight:700;font-size:12.5px;">${s.section.toUpperCase()}</span>
                <span style="color:#8fb4d6;font-size:12px;"> — ${s.critical} critical tubes, ${s.watch} watch</span>
            </div>
        </div>`).join('');
}

function renderComparisonLegend(comparison){
    const el = document.getElementById('comparison-legend');
    const tubeIds = Object.keys(comparison);
    el.innerHTML = tubeIds.map((tubeId, idx) => {
        const color = COMPARISON_PALETTE[idx % COMPARISON_PALETTE.length];
        return `
        <div style="display:flex;align-items:center;gap:8px;">
            <span style="width:11px;height:11px;border-radius:50%;background:${color};flex-shrink:0;"></span>
            <span style="color:${color};font-weight:700;font-size:13px;letter-spacing:.3px;">${tubeId}</span>
        </div>`;
    }).join('');
}

// ================= Fetch & orkestrasi (port update_main) =================
let lastPayload = null;

async function loadData(){
    const unit = document.getElementById('unit-dd').value;
    const year = document.getElementById('year-dd').value;
    try {
        const res = await fetch(`/api/boiler-data?unit=${encodeURIComponent(unit)}&year=${encodeURIComponent(year)}`);
        if (!res.ok) {
            const text = await res.text();
            showError(`Server error (status ${res.status}) waktu ambil data unit=${unit} year=${year}.<br><br><pre style="white-space:pre-wrap;font-size:11px;">${escapeHtml(text.slice(0, 2000))}</pre>`);
            return;
        }
        const payload = await res.json();
        lastPayload = payload;
        try {
            render(payload);
        } catch (err) {
            showError(`Error saat render tampilan: ${err.message}<br><pre style="white-space:pre-wrap;font-size:11px;">${escapeHtml(err.stack || '')}</pre>`);
        }
    } catch (err) {
        showError(`Gagal fetch /api/boiler-data: ${err.message}`);
    }
}

function escapeHtml(str){
    return str.replace(/[&<>]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[c]));
}

function showError(html){
    const el = document.getElementById('status-cards');
    el.innerHTML = `<div style="grid-column:1/-1;background:#3a1414;border:1px solid #ef4444;border-radius:12px;padding:16px;color:#fecaca;font-size:13px;">
        <strong style="color:#ef4444;">⚠️ Dashboard gagal load data</strong><br>${html}
    </div>`;
}

function render(payload){
    renderCards(payload.cards);

    const colorsBySection = {};
    payload.section_summary.forEach(s => colorsBySection[s.section] = s.color);
    const boiler = buildBoiler3D(colorsBySection);
    Plotly.newPlot('boiler-3d', boiler.traces, boiler.layout, {displaylogo:false, responsive:true});

    const riskBar = buildRiskBar(payload.section_summary);
    Plotly.newPlot('risk-bar', riskBar.traces, riskBar.layout, {displaylogo:false, responsive:true});

    renderRiskList(payload.section_summary);

    renderComparisonLegend(payload.comparison);
    const comp = buildComparisonChart(payload.comparison);
    Plotly.newPlot('comparison-fig', comp.traces, comp.layout, {displaylogo:false, responsive:true});

    // Pastikan semua chart pas dengan lebar container-nya (fix chart yang kepotong/meluber)
    requestAnimationFrame(() => {
        ['boiler-3d','risk-bar','comparison-fig'].forEach(id => {
            const el = document.getElementById(id);
            if (el) Plotly.Plots.resize(el);
        });
    });
}

window.addEventListener('resize', () => {
    ['boiler-3d','risk-bar','comparison-fig'].forEach(id => {
        const el = document.getElementById(id);
        if (el && el.data) Plotly.Plots.resize(el);
    });
});

document.getElementById('unit-dd').addEventListener('change', loadData);
document.getElementById('year-dd').addEventListener('change', loadData);
loadData();
</script>
</body>
</html>