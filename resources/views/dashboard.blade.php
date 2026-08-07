@extends('layouts.dashboard')

@section('title', 'S2P PLTU Cilacap — Boiler 3D Digital Twin')

@push('head')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.plot.ly/plotly-2.32.0.min.js"></script>
@endpush

@push('styles')
<style>
    * { box-sizing: border-box; }
    html { scroll-behavior: smooth; }
    body {
        font-family: 'Inter', sans-serif; margin:0;
        background:
            radial-gradient(ellipse 1000px 560px at 12% -8%, #e0a94022, transparent 60%),
            radial-gradient(ellipse 900px 560px at 100% 0%, #1a4a7a3d, transparent 60%),
            radial-gradient(ellipse 700px 500px at 50% 110%, #0d3a5c40, transparent 60%),
            #050b1a;
        background-attachment: fixed;
        position: relative;
    }
    /* Outer .main padding/background is now the shared class in
       dashboard-shared.css — single source of truth across all pages. */
    .section-block {
        position: relative;
        padding: 18px 0;
        border-bottom: 1px solid rgba(255,255,255,0.08);
    }
    .section-block:last-child { border-bottom: none; }
    .header-title-bar {
        display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:14px; position:relative; z-index:1;
    }
    .header-title-bar .title-left {
        display:flex; align-items:center; gap:10px;
    }
    .header-title-bar .accent-bar {
        width:4px; height:20px; background:#e0a940; border-radius:2px; flex-shrink:0;
    }
    .header-title-bar h2 {
        color:#f0b94a; margin:0; font-weight:700; font-size:20px; letter-spacing:1.5px;
        text-transform:uppercase;
        background:none; -webkit-text-fill-color:#f0b94a;
    }
    .header-logo-corner {
        width:70px; flex-shrink:0;
    }
    .header-logo-corner .logo-box{
        width:70px; height:70px; overflow:hidden; display:flex; align-items:center; justify-content:center;
    }
    .header-logo-corner .logo-box img{
        width:100%; height:100%; object-fit:contain; display:block;
    }
    .glow-orb { position:absolute; border-radius:50%; filter: blur(60px); pointer-events:none; z-index:0; }
    .brand-badge { background: linear-gradient(135deg,#e0a940,#a97e1f); border:1px solid #f0c46a; border-radius:10px; padding:6px 14px;
        color:#2a1a00; font-weight:800; font-size:11px; letter-spacing:1px; box-shadow: 0 3px 12px #e0a94044; }
    select.filter-select {
        min-width:160px; border-radius:3px; background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.12); color:#eef2f6;
        padding:6px 30px 6px 12px; font-size:13px; font-weight:600; cursor:pointer;
        appearance:none; -webkit-appearance:none;
        background-image:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='10' height='6'><path d='M0 0l5 6 5-6z' fill='%239fb0c3'/></svg>");
        background-repeat:no-repeat;
        background-position:right 12px center;
    }
    select.filter-select option{ color:#1a1a1a; background:#ffffff; font-weight:600; }
    select.filter-select:hover, select.filter-select:focus { border-color:#e0a940; outline:none; }
    .status-chip { display:inline-flex; align-items:center; gap:6px; padding:4px 12px; border-radius:999px; font-size:11px; font-weight:700; }
    .panel-title { color:white; font-weight:700; font-size:12px; letter-spacing:1.5px; margin-bottom:10px; font-family:inherit; }
    .risk-row { display:flex; align-items:center; gap:10px; padding:8px 6px; border-bottom:1px solid #16324a; cursor:pointer; border-radius:6px; }
    .risk-row:hover { background: rgba(245,179,1,.06); }
    .risk-row.active { background: rgba(245,179,1,.12); box-shadow: inset 0 0 0 1px #e0a94088; }
    .area-del-btn {
        margin-left:auto; flex-shrink:0; background:none; border:none; cursor:pointer;
        color:#6d7f96; font-size:13px; font-weight:700; padding:2px 7px; border-radius:4px;
        font-family:inherit; line-height:1;
    }
    .area-del-btn:hover { color:#fff; background:#e5484d; }
    details > summary { list-style:none; cursor:pointer; }
    details > summary::-webkit-details-marker { display:none; }
    .detail-panel { margin-bottom:12px; border:1px solid #1c3a5c; border-radius:18px; box-shadow: 0 4px 18px rgba(0,0,0,0.35); padding:16px 20px;
        background: linear-gradient(160deg, #132f57 0%, #0d2140 65%); }
    .detail-panel.focused { border-color:#7fd4e8; box-shadow: 0 0 0 2px #7fd4e855; }
    .stat-chip { text-align:center; min-width:80px; }
    .stat-chip .val { font-size:20px; font-weight:800; }
    .stat-chip .lbl { color:#9fb0c3; font-size:10.5px; font-weight:700; letter-spacing:.5px; }
    table.tube-table { width:100%; border-collapse:collapse; }
    table.tube-table th { padding:8px 10px; color:#9fb0c3; font-size:11px; text-align:left; border-bottom:1px solid #1c3a5c; position:sticky; top:0; background:#0d2140; }
    table.tube-table td { padding:7px 10px; color:white; font-size:12.5px; border-bottom:1px solid #16324a; }
    .table-scroll { max-height:360px; overflow-y:auto; border-radius:8px; }
    #risk-bar, #comparison-fig { width:100%; max-width:100%; }
    /* Field PDF mengikuti proporsi halaman PDF (2384 x 3370 pt) supaya seluruh gambar terlihat */
    #boiler-pdf {
        width:100%; max-width:100%;
        aspect-ratio: 2384 / 3370;
        border:0; border-radius:4px; display:block; background:#0d2140;
    }
</style>
@endpush

@section('content')
    <main class="main">
        <div class="section-block" style="padding-top:2px;">
            <div class="glow-orb" style="width:260px;height:260px;background:#e0a940;opacity:.05;top:-100px;right:-60px;"></div>
            <div class="glow-orb" style="width:220px;height:220px;background:#7fd4e8;opacity:.06;bottom:-90px;left:-40px;"></div>

            <div class="header-title-bar">
                <div class="title-left">
                    <span class="accent-bar"></span>
                    <h2>GLOBAL VIEW: BOILER UNIT {{ $defaultUnit }} STATUS & RISK MAP</h2>
                </div>
                <div class="header-logo-corner">
                    <div class="logo-box">
                        <img src="{{ asset('images/logo.png') }}" alt="S2P Logo">
                    </div>
                </div>
            </div>

            <div style="display:flex;justify-content:flex-start;align-items:center;margin-bottom:18px;flex-wrap:wrap;gap:22px;position:relative;z-index:1;background:rgba(255,255,255,0.03);padding:10px 16px;border-radius:4px;width:100%;">
                <div style="display:flex;align-items:center;gap:8px;">
                    <span style="color:#f0b94a;font-size:12px;font-weight:700;letter-spacing:1px;">UNIT:</span>
                    <select id="unit-dd" class="filter-select" style="width:130px;">
                        @foreach($units as $u)
                            <option value="{{ $u }}" @selected($u === $defaultUnit)>{{ $u }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <span style="color:#f0b94a;font-size:12px;font-weight:700;letter-spacing:1px;">TAHUN:</span>
                    <select id="year-dd" class="filter-select" style="width:120px;">
                        @foreach($years as $y)
                            <option value="{{ $y }}" @selected($y == $defaultYear)>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="margin-left:auto;display:flex;align-items:center;gap:14px;">
                    @auth
                        <span style="color:#9fb0c3;font-size:11.5px;font-weight:600;">👤 {{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                            @csrf
                            <button type="submit" style="background:none;border:1px solid rgba(255,255,255,0.18);border-radius:3px;color:#9fb0c3;font-size:11px;font-weight:700;letter-spacing:1px;padding:5px 12px;cursor:pointer;font-family:inherit;">LOGOUT</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" style="color:#8fb4d6;font-size:11px;font-weight:700;letter-spacing:1px;text-decoration:none;border:1px solid rgba(255,255,255,0.18);border-radius:3px;padding:6px 12px;">ADMIN LOGIN</a>
                    @endauth
                </div>
            </div>

            <div id="error-box" style="position:relative;z-index:1;"></div>
        </div>

        <div class="section-block">
            <div style="display:flex;gap:16px;align-items:flex-start;flex-wrap:wrap;position:relative;z-index:1;">
                <div style="flex:1.4;min-width:420px;background:linear-gradient(180deg, rgba(0,26,87,0.25) 0%, rgba(14,32,56,1) 100%);border:1px solid rgba(255,255,255,0.06);border-radius:5px;padding:14px;box-shadow:0 6px 20px rgba(0,0,0,.3);">
                    <iframe id="boiler-pdf"
                        src="{{ asset('images/'.rawurlencode('F2092S-J0203-05 R1 SECTION VIEW DRAWING OF BOILER HOUSE.pdf')) }}#toolbar=0&navpanes=0&view=Fit"
                        title="Section View Drawing of Boiler House"></iframe>
                    <div id="drawing-empty" style="display:none;align-items:center;justify-content:center;min-height:320px;color:#5d7590;font-size:12.5px;letter-spacing:1px;"></div>
                </div>
                <div style="flex:1;min-width:340px;background:linear-gradient(180deg, rgba(0,26,87,0.25) 0%, rgba(14,32,56,1) 100%);border:1px solid rgba(255,255,255,0.06);border-radius:5px;padding:14px;box-shadow:0 6px 20px rgba(0,0,0,.3);">
                    <div class="panel-title" style="display:flex;align-items:center;justify-content:space-between;gap:10px;">
                        <span>📊 Risk Summary by Section</span>
                        @auth
                        <button id="add-area-btn" style="background:linear-gradient(135deg,#e0a940,#a97e1f);border:none;border-radius:3px;color:#2a1a00;font-size:11px;font-weight:800;letter-spacing:.5px;padding:5px 12px;cursor:pointer;font-family:inherit;">＋ ADD AREA</button>
                        @endauth
                    </div>
                    @auth
                    <div id="add-area-form" style="display:none;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:10px;background:rgba(224,169,64,.07);border:1px solid #e0a94055;border-radius:6px;padding:10px;">
                        <input id="add-area-name" type="text" maxlength="100" placeholder="Nama area baru untuk unit terpilih..."
                            style="flex:1;min-width:180px;background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.15);border-radius:3px;color:#eef2f6;font-size:12.5px;padding:7px 10px;font-family:inherit;">
                        <button id="add-area-save" style="background:#e0a940;border:none;border-radius:3px;color:#2a1a00;font-size:11.5px;font-weight:800;padding:7px 14px;cursor:pointer;font-family:inherit;">SIMPAN</button>
                        <div id="add-area-msg" style="width:100%;color:#fda4af;font-size:11.5px;"></div>
                    </div>
                    @endauth
                    <div id="risk-list" style="margin-bottom:8px;color:#9fb0c3;font-size:12px;">Loading data...</div>
                    <div id="risk-bar"></div>
                    <div style="margin-top:12px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px;
                        padding:14px 22px; background:#0e2a55; border-radius:999px; border:1px solid #1c3f6e;">
                        <span style="display:flex;align-items:center;gap:9px;">
                            <span style="width:14px;height:14px;border-radius:4px;background:{{ $statusColor['Safe'] }};flex-shrink:0;"></span>
                            <span style="color:{{ $statusColor['Safe'] }};font-weight:800;font-size:12.5px;letter-spacing:.3px;">SAFE &lt;100%-75%</span>
                        </span>
                        <span style="display:flex;align-items:center;gap:9px;">
                            <span style="width:14px;height:14px;border-radius:4px;background:{{ $statusColor['Warning'] }};flex-shrink:0;"></span>
                            <span style="color:{{ $statusColor['Warning'] }};font-weight:800;font-size:12.5px;letter-spacing:.3px;">WARNING 75%-70%</span>
                        </span>
                        <span style="display:flex;align-items:center;gap:9px;">
                            <span style="width:14px;height:14px;border-radius:4px;background:{{ $statusColor['Critical'] }};flex-shrink:0;"></span>
                            <span style="color:{{ $statusColor['Critical'] }};font-weight:800;font-size:12.5px;letter-spacing:.3px;">CRITICAL &gt;70%</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="section-block" style="padding-bottom:2px;border-bottom:none;">
            <div style="background:linear-gradient(180deg, rgba(0,26,87,0.25) 0%, rgba(14,32,56,1) 100%);border:1px solid rgba(255,255,255,0.06);border-radius:5px;padding:16px 18px;">
                <div class="panel-title" style="display:flex;align-items:center;gap:10px;text-transform:uppercase;font-size:15px;letter-spacing:.4px;margin-bottom:0;">
                    <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="#3fdc84" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><polyline points="2 17 9 10 13 14 22 4"></polyline><polyline points="16 4 22 4 22 10"></polyline></svg>
                    5-Year Comparison
                </div>
                <div id="comparison-legend" style="display:flex;flex-wrap:wrap;gap:26px;margin:14px 4px 16px 4px;color:#9fb0c3;font-size:12px;">Loading data...</div>
                <div id="comparison-fig"></div>
            </div>
        </div>
    </div>
    </main>

@endsection

@push('scripts')
<script>
// ================= Konfigurasi dari server =================
const STATUS_COLOR = @json($statusColor);
const INITIAL_PAYLOAD = @json($initialPayload);
// Hanya unit ini yang punya gambar section drawing; unit lain panelnya dibiarkan kosong
const DRAWING_UNIT = @json($drawingUnit);
// Status login admin (menentukan tombol hapus area di daftar Risk Summary)
const IS_ADMIN = @json(auth()->check());
const AREA_BASE_URL = @json(url('/admin/areas'));
// Section yang sedang difokuskan lewat klik di daftar Risk Summary (null = tampilkan semua)
let focusedSection = null;

// ================= Chart 2D (port build_risk_summary_fig & build_comparison_fig) =================
function buildRiskBar(sectionSummary){
    const statuses = ['Safe','Warning','Critical'];
    const textColorMap = { Safe:'#06281a', Warning:'#2b1d00', Critical:'#ffffff' };
    const traces = statuses.map(status => {
        const key = status.toLowerCase();
        const y = sectionSummary.map(s => s[key]);
        return {
            type:'bar', x: sectionSummary.map(s => s.code), y, name: status,
            customdata: sectionSummary.map(s => s.section),
            hovertemplate: '%{customdata}<br>' + status + ': %{y}<extra></extra>',
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
        bargap: sectionSummary.length <= 2 ? 0.65 : 0.35,
    };
    return {traces, layout};
}

const COMPARISON_PALETTE = ['#e0a940', '#7fd4e8', '#e5484d', '#3fdc84', '#a78bfa'];

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
        height:280, autosize:true, paper_bgcolor:'transparent', plot_bgcolor:'transparent',
        font:{color:'#cfe3f5', family:'Inter, sans-serif'},
        margin:{l:34,r:20,t:20,b:60},
        xaxis:{title:{text:'Tahun', standoff:18}, showgrid:false, dtick:1, tickformat:'d'},
        yaxis:{showgrid:true, gridcolor:'#1c3a5c'},
        showlegend:false, hovermode:'x unified',
    };
    return {traces, layout};
}

// ================= Render DOM (port risk-list) =================
function renderRiskList(sectionSummary){
    const el = document.getElementById('risk-list');
    el.innerHTML = sectionSummary.map(s => `
        <div class="risk-row ${s.section === focusedSection ? 'active' : ''}" data-section="${s.section}" title="Klik untuk tampilkan section ini saja di grafik (klik lagi untuk semua)">
            <div style="width:10px;height:10px;border-radius:3px;background:${s.color};flex-shrink:0;"></div>
            <div style="flex:1;">
                <span style="color:white;font-weight:700;font-size:12.5px;">${escapeHtml(s.section.toUpperCase())}</span>
                <span style="color:#8fb4d6;font-size:12px;"> — ${s.critical} critical tubes, ${s.watch} watch</span>
            </div>
            ${IS_ADMIN ? `<button class="area-del-btn" data-area-id="${s.id}" title="Hapus area ini">✕</button>` : ''}
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
    const el = document.getElementById('error-box');
    el.innerHTML = `<div style="background:#3a1414;border:1px solid #e5484d;border-radius:12px;padding:16px;color:#fecaca;font-size:13px;">
        <strong style="color:#e5484d;">⚠️ Dashboard gagal load data</strong><br>${html}
    </div>`;
}

// Render ulang chart Risk Summary sesuai section yang difokuskan (semua kalau tidak ada)
function renderRiskBar(payload){
    const summary = focusedSection
        ? payload.section_summary.filter(s => s.section === focusedSection)
        : payload.section_summary;
    const riskBar = buildRiskBar(summary);
    Plotly.newPlot('risk-bar', riskBar.traces, riskBar.layout, {displaylogo:false, responsive:true});
}

function render(payload){
    document.getElementById('error-box').innerHTML = '';

    // Gambar section drawing hanya tampil untuk DRAWING_UNIT; unit lain panelnya kosong
    const hasDrawing = payload.unit === DRAWING_UNIT;
    document.getElementById('boiler-pdf').style.display = hasDrawing ? 'block' : 'none';
    document.getElementById('drawing-empty').style.display = hasDrawing ? 'none' : 'flex';

    renderRiskBar(payload);

    renderRiskList(payload.section_summary);

    renderComparisonLegend(payload.comparison);
    const comp = buildComparisonChart(payload.comparison);
    Plotly.newPlot('comparison-fig', comp.traces, comp.layout, {displaylogo:false, responsive:true});

    // Pastikan semua chart pas dengan lebar container-nya (fix chart yang kepotong/meluber)
    requestAnimationFrame(() => {
        ['risk-bar','comparison-fig'].forEach(id => {
            const el = document.getElementById(id);
            if (el) Plotly.Plots.resize(el);
        });
    });
}

window.addEventListener('resize', () => {
    ['risk-bar','comparison-fig'].forEach(id => {
        const el = document.getElementById(id);
        if (el && el.data) Plotly.Plots.resize(el);
    });
});

document.getElementById('unit-dd').addEventListener('change', loadData);
document.getElementById('year-dd').addEventListener('change', loadData);

// ================= Admin: Add Area (hanya dirender kalau login) =================
const addAreaBtn = document.getElementById('add-area-btn');
if (addAreaBtn) {
    const formEl = document.getElementById('add-area-form');
    const nameEl = document.getElementById('add-area-name');
    const msgEl = document.getElementById('add-area-msg');

    async function submitArea(){
        const name = nameEl.value.trim();
        if (!name) { msgEl.textContent = 'Nama area tidak boleh kosong.'; return; }
        const unit = document.getElementById('unit-dd').value;
        try {
            const res = await fetch(@json(route('areas.store')), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({unit, name}),
            });
            const body = await res.json().catch(() => ({}));
            if (!res.ok) {
                msgEl.textContent = body.message || `Gagal menambah area (status ${res.status}).`;
                return;
            }
            nameEl.value = '';
            msgEl.textContent = '';
            formEl.style.display = 'none';
            loadData(); // refresh daftar & chart supaya area baru langsung tampil
        } catch (err) {
            msgEl.textContent = 'Gagal menambah area: ' + err.message;
        }
    }

    addAreaBtn.addEventListener('click', () => {
        const open = formEl.style.display !== 'none';
        formEl.style.display = open ? 'none' : 'flex';
        if (!open) nameEl.focus();
    });
    document.getElementById('add-area-save').addEventListener('click', submitArea);
    nameEl.addEventListener('keydown', e => { if (e.key === 'Enter') submitArea(); });
}

// Hapus area (khusus admin): konfirmasi dulu, data tube area itu ikut terhapus
async function deleteArea(areaId){
    if (!lastPayload) return;
    const s = lastPayload.section_summary.find(x => x.id === areaId);
    if (!s) return;
    const extra = s.total > 0 ? `\nSemua data tube area ini (${s.total} tube) ikut terhapus.` : '';
    if (!confirm(`Hapus area "${s.section}" dari ${lastPayload.unit}?${extra}`)) return;
    try {
        const res = await fetch(`${AREA_BASE_URL}/${areaId}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });
        if (!res.ok) {
            const body = await res.json().catch(() => ({}));
            alert(body.message || `Gagal menghapus area (status ${res.status}).`);
            return;
        }
        if (focusedSection === s.section) focusedSection = null;
        loadData(); // refresh daftar & chart
    } catch (err) {
        alert('Gagal menghapus area: ' + err.message);
    }
}

// Klik baris section di Risk Summary -> grafik hanya menampilkan section itu.
// Klik section yang sama sekali lagi -> kembali menampilkan semua section.
// Klik tombol ✕ (admin) -> hapus area tersebut.
document.getElementById('risk-list').addEventListener('click', e => {
    const delBtn = e.target.closest('.area-del-btn');
    if (delBtn) { deleteArea(Number(delBtn.dataset.areaId)); return; }
    const row = e.target.closest('.risk-row');
    if (!row || !lastPayload) return;
    focusedSection = (focusedSection === row.dataset.section) ? null : row.dataset.section;
    renderRiskList(lastPayload.section_summary);
    renderRiskBar(lastPayload);
});

lastPayload = INITIAL_PAYLOAD;
render(INITIAL_PAYLOAD);
</script>
@endpush