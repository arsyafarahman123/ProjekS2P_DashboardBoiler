<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>RLA Report — {{ $section }}</title>
<style>
  body{ font-family: 'Helvetica', Arial, sans-serif; font-size: 11px; color:#1a1a1a; }
  h1{ font-size:16px; margin-bottom:2px; }
  .meta{ color:#555; margin-bottom:16px; }
  h2{ font-size:13px; margin-top:22px; margin-bottom:6px; border-bottom:1px solid #ccc; padding-bottom:4px; }
  table{ width:100%; border-collapse:collapse; margin-bottom:6px; }
  th, td{ border:1px solid #ddd; padding:4px 6px; text-align:left; font-size:10.5px; }
  th{ background:#f2f2f2; }
  .empty{ color:#888; font-style:italic; padding:8px 0; }
  .p1{ border-left:4px solid #e5484d; padding-left:6px; }
  .p2{ border-left:4px solid #e08a3c; padding-left:6px; }
  .p3{ border-left:4px solid #e0c23c; padding-left:6px; }
  .p4{ border-left:4px solid #3fa9c9; padding-left:6px; }
  .priority-row td{ padding:6px; }
</style>
</head>
<body>

  <h1>Remaining Life Assessment (RLA) Report</h1>
  <div class="meta">
    Section: <strong>{{ $section }}</strong> &nbsp;|&nbsp;
    Unit: <strong>{{ $unit }}</strong> &nbsp;|&nbsp;
    Tahun: <strong>{{ $year }}</strong> &nbsp;|&nbsp;
    Dicetak: {{ now()->format('d M Y H:i') }}
  </div>

  <h2>Thickness per Tube (Titik A–D)</h2>
  @if (empty($data['thickness_chart']['tube_numbers']))
    <div class="empty">Belum ada data ketebalan untuk kombinasi Unit/Section/Tahun ini.</div>
  @else
    <table>
      <tr><th>Tube No</th><th>Titik A</th><th>Titik B</th><th>Titik C</th><th>Titik D</th><th>MWT (mm)</th></tr>
      @foreach ($data['thickness_chart']['tube_numbers'] as $i => $no)
        <tr>
          <td>{{ $no }}</td>
          <td>{{ $data['thickness_chart']['a'][$i] ?? '-' }}</td>
          <td>{{ $data['thickness_chart']['b'][$i] ?? '-' }}</td>
          <td>{{ $data['thickness_chart']['c'][$i] ?? '-' }}</td>
          <td>{{ $data['thickness_chart']['d'][$i] ?? '-' }}</td>
          <td>{{ $data['thickness_chart']['mwt'] ?? '-' }}</td>
        </tr>
      @endforeach
    </table>
  @endif

  <h2>Top 5 Remaining Useful Life (RUL)</h2>
  @if ($data['rul_table']->isEmpty())
    <div class="empty">Belum ada data tube untuk tahun {{ $year }}.</div>
  @else
    <table>
      <tr><th>Tube ID</th><th>Section</th><th>RUL</th><th>Status</th></tr>
      @foreach ($data['rul_table'] as $row)
        <tr>
          <td>{{ $row['tube_id'] }}</td>
          <td>{{ $row['section'] }}</td>
          <td>{{ $row['rul_months'] }} mo</td>
          <td>{{ strtoupper($row['status']) }}</td>
        </tr>
      @endforeach
    </table>
  @endif

  <h2>Risk Mitigation Options &amp; Recommendations</h2>
  @if (empty($data['priorities']))
    <div class="empty">Belum ada data pengukuran untuk {{ $section }} pada kombinasi Unit/Tahun ini.</div>
  @else
    <table>
      @foreach ($data['priorities'] as $pr)
        <tr class="priority-row">
          <td class="p{{ $loop->iteration }}" style="width:180px;"><strong>{{ $pr['level'] }}</strong></td>
          <td>{{ $pr['text'] }}</td>
        </tr>
      @endforeach
    </table>
  @endif

  <h2>Historical NDT</h2>
  @if ($data['historical_ndt']->isEmpty())
    <div class="empty">Belum ada riwayat NDT untuk {{ $section }} — {{ $unit }}.</div>
  @else
    <table>
      <tr><th>Date</th><th>Tube ID</th><th>Creep %</th></tr>
      @foreach ($data['historical_ndt'] as $row)
        <tr>
          <td>{{ $row['date'] }}</td>
          <td>{{ $row['tube_id'] }}</td>
          <td>{{ number_format($row['creep_pct'], 1) }}%</td>
        </tr>
      @endforeach
    </table>
  @endif

</body>
</html>
