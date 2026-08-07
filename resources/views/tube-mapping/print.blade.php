<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Tube Mapping Report - {{ $unit }} - {{ $section }} - {{ $year }}</title>
<style>
  @page { size: A4 landscape; margin: 14mm; }
  * { box-sizing: border-box; }
  body { font-family: Arial, Helvetica, sans-serif; color: #111; margin: 0; padding: 0 0 24px; }
  .header { display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 3px solid #c9982f; padding-bottom: 10px; margin-bottom: 14px; }
  .header h1 { font-size: 18px; margin: 0 0 4px; }
  .header .meta { font-size: 11px; color: #444; }
  .brand { font-weight: bold; font-size: 14px; color: #c9982f; }
  .structure-box { margin-bottom: 14px; page-break-inside: avoid; }
  .structure-box .label { font-size: 11px; font-weight: bold; color: #333; margin-bottom: 6px; text-transform: uppercase; letter-spacing: .5px; }
  .structure-box img { max-width: 100%; max-height: 260mm; display: block; margin: 0 auto; border: 1px solid #ccc; }
  .structure-box embed { width: 100%; height: 260mm; border: 1px solid #ccc; }
  .structure-box .caption { font-size: 9px; color: #666; margin-top: 4px; text-align: center; }
  table { width: 100%; border-collapse: collapse; font-size: 10px; }
  th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; }
  th { background: #101f3a; color: #fff; font-weight: 600; }
  tr:nth-child(even) td { background: #f7f7f7; }
  .status-safe { color: #16794f; font-weight: bold; }
  .status-warning { color: #a9770e; font-weight: bold; }
  .status-critical { color: #b0221b; font-weight: bold; }
  .status-unknown { color: #888; }
  .no-print { margin: 16px 0; }
  .no-print button { font-size: 13px; padding: 8px 16px; border-radius: 4px; border: none; background: #c9982f; color: #fff; font-weight: bold; cursor: pointer; }
  @media print {
    .no-print { display: none; }
  }
</style>
</head>
<body>

  <div class="no-print">
    <button onclick="window.print()">Cetak / Simpan sebagai PDF</button>
  </div>

  <div class="header">
    <div>
      <h1>Tube Mapping Report</h1>
      <div class="meta">Unit: <strong>{{ strtoupper($unit) }}</strong> &nbsp;|&nbsp;
        Boiler Section: <strong>{{ strtoupper($section) }}</strong> &nbsp;|&nbsp;
        Tahun: <strong>{{ $year }}</strong> &nbsp;|&nbsp;
        Dicetak: {{ now()->format('d/m/Y H:i') }}</div>
    </div>
    <div class="brand">S2P BOILER DASHBOARD</div>
  </div>

  <table>
    <thead>
      <tr>
        <th>Tube #</th>
        <th>Nilai Awal (mm)</th>
        <th>Nilai Ukur (mm)</th>
        @foreach ($pointNames as $p)
          <th>Titik {{ $p }} (mm)</th>
        @endforeach
        <th>Min (mm)</th>
        <th>Max (mm)</th>
        <th>Avg (mm)</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      @for ($i = 1; $i <= $tubeCount; $i++)
        @php
          $row = $pointsTable[$i] ?? null;
          $statusClass = match ($row['status'] ?? null) {
              'critical' => 'status-critical',
              'warning' => 'status-warning',
              'safe' => 'status-safe',
              default => 'status-unknown',
          };
        @endphp
        <tr>
          <td>{{ $i }}</td>
          <td>{{ $row['baseline'] ?? '—' }}</td>
          <td>{{ $row['measured_mm'] ?? '—' }}</td>
          @foreach ($pointNames as $p)
            <td>{{ $row['mm'][$p] ?? '—' }}</td>
          @endforeach
          <td>{{ $row['min_mm'] ?? '—' }}</td>
          <td>{{ $row['max_mm'] ?? '—' }}</td>
          <td>{{ $row['avg_mm'] ?? '—' }}</td>
          <td class="{{ $statusClass }}">{{ $row ? strtoupper($row['status']) : 'BELUM ADA DATA' }}</td>
        </tr>
      @endfor
    </tbody>
  </table>

  <script>
    // Otomatis buka dialog Print begitu halaman + tabel selesai render,
    // supaya user tinggal pilih "Save as PDF" di dialog print browser.
    window.addEventListener('load', function () {
      setTimeout(function () { window.print(); }, 300);
    });
  </script>
</body>
</html>
