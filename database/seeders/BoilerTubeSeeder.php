<?php

namespace Database\Seeders;

use App\Models\BoilerTube;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class BoilerTubeSeeder extends Seeder
{
    // Perkiraan sisa umur (bulan) dari creep %, dipakai karena kolom
    // "Remaining Life" tidak ada di excel dummy — creep tinggi = sisa
    // umur makin pendek. 0% creep ≈ 120 bulan, 100% creep ≈ 0 bulan.
    private function remainingLifeFromCreep(float $creep): int
    {
        return (int) max(0, round(120 - ($creep / 100 * 120)));
    }

    public function run(): void
    {
        // Kosongkan semua data lama dulu supaya tidak ada sisa section/nilai dummy sebelumnya
        BoilerTube::truncate();

        $unit = BoilerTube::DEFAULT_UNIT;
        $csvPath = database_path('seeders/data/tube_dummy_2021_2025.csv');

        if (! file_exists($csvPath)) {
            $this->command?->error("File CSV tidak ditemukan: {$csvPath}");

            return;
        }

        $handle = fopen($csvPath, 'r');
        $header = fgetcsv($handle); // buang baris header

        $codeMap = BoilerTube::SECTION_CODES;
        $rows = [];

        // Baca langsung dari data dummy excel (tube_dummy_2021_2025.csv):
        // creep_pct per tube per tahun diambil apa adanya dari sana, tapi
        // STATUS dihitung ULANG dari creep_pct pakai BoilerTube::statusFromCreep()
        // (bukan dipakai mentah dari kolom 'status' CSV, karena kolom itu
        // banyak yang tidak konsisten dengan creep_pct-nya sendiri).
        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row);

            $section = $data['section'];
            $tubeNumber = (int) $data['tube_number'];
            $year = (int) $data['year'];
            $creepPct = round((float) $data['creep_pct'], 2);
            // PENTING: status dihitung ULANG dari creep_pct pakai rumus resmi
            // aplikasi (BoilerTube::statusFromCreep), BUKAN dipakai mentah dari
            // kolom 'status' di CSV. Kolom 'status' bawaan CSV ternyata banyak
            // yang kontradiksi sama creep_pct-nya sendiri (mis. creep 46.9%
            // ditulis 'Warning', padahal aturan >30% = Critical) — kejadian di
            // ~47% baris. Kalau dipakai mentah, status yang tampil di Global
            // View & tabel Historical NDT jadi tidak sinkron dengan angka
            // creep %-nya sendiri.
            $status = BoilerTube::statusFromCreep($creepPct);
            $code = $codeMap[$section] ?? strtoupper(substr($section, 0, 3));
            $tubeId = sprintf('%s-U3A-%02d', $code, $tubeNumber);

            $rows[] = [
                'unit' => $unit,
                'year' => $year,
                'section' => $section,
                'tube_id' => $tubeId,
                'creep_pct' => $creepPct,
                'remaining_life_months' => $this->remainingLifeFromCreep($creepPct),
                'status' => $status,
                'recommended_action' => BoilerTube::actionFromStatus($status),
                'scan_date' => Carbon::parse($data['inspected_at'])->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Insert per-batch supaya query tidak terlalu besar sekali jalan
            if (count($rows) >= 500) {
                BoilerTube::insert($rows);
                $rows = [];
            }
        }
        fclose($handle);

        if (! empty($rows)) {
            BoilerTube::insert($rows);
        }

        $this->command?->info('boiler_tubes sudah diisi langsung dari tube_dummy_2021_2025.csv (semua tahun & 200 tube/section).');
    }
}
