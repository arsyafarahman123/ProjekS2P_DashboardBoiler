# Dashboard Boiler — S2P

Dashboard monitoring kondisi tube boiler berbasis Laravel, dibuat sebagai alat bantu analisis Remaining Life Assessment (RLA) dan pemetaan tube per unit/section.

## Fitur

- **Global View** — ringkasan status seluruh section boiler (Critical / Watch / Safe) per unit dan tahun, dengan data yang diambil secara dinamis lewat endpoint API internal.
- **Tube Mapping** — visualisasi peta tube per section, lengkap dengan detail per tube (`/tube-mapping/tube/{tubeId}`).
- **RLA Analysis** — halaman analisis remaining life assessment tube boiler.
- **Maintenance** — halaman informasi/riwayat maintenance.

## Tech Stack

- [Laravel 13](https://laravel.com) (PHP 8.3+)
- SQLite (default, bisa diganti MySQL/PostgreSQL lewat `.env`)
- Vite + Tailwind CSS 4

## Instalasi

```bash
git clone <repo-url>
cd ProjekS2P_DashboardBoiler
composer install
npm install

cp .env.example .env
php artisan key:generate
php artisan migrate

npm run build   # atau: npm run dev
php artisan serve
```

Aplikasi bisa diakses di `http://localhost:8000`, otomatis redirect ke halaman **Global View**.

## Struktur Utama

```
app/Http/Controllers/    # DashboardController, TubeMappingController, RlaAnalysisController
app/Models/               # BoilerTube, TubeScan
database/migrations/      # skema tabel boiler_tubes & tube_scans
resources/views/          # dashboard, tube-mapping, rla-analysis, maintenance
routes/web.php            # definisi seluruh route
```

## Routes

| Route | Deskripsi |
|---|---|
| `GET /` | Redirect ke Global View |
| `GET /global-view` | Halaman utama dashboard |
| `GET /api/boiler-data` | Endpoint data (JSON) untuk global view |
| `GET /tube-mapping` | Peta tube |
| `GET /tube-mapping/tube/{tubeId}` | Detail satu tube |
| `GET /rla-analysis` | Analisis RLA |
| `GET /maintenance` | Halaman maintenance |

## Lisensi

Proyek internal — sesuaikan lisensi sesuai kebutuhan tim/organisasi.
