<?php

use App\Http\Controllers\AreaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RlaAnalysisController;
use App\Http\Controllers\InputDataController;
use App\Http\Controllers\TubeMappingController;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Global View Boiler Dashboard
|--------------------------------------------------------------------------
*/


// Halaman awal redirect ke Global View
Route::get('/', function () {
    return redirect()->route('global-view');
});


// ================================
// GLOBAL VIEW
// ================================
Route::get('/global-view', [DashboardController::class, 'index'])
    ->name('global-view');


// API data dashboard
Route::get('/api/boiler-data', [DashboardController::class, 'data'])
    ->name('boiler.data');


// ================================
// TUBE MAPPING
// ================================

Route::get('/tube-mapping', [TubeMappingController::class, 'index'])
    ->name('tube.mapping');

Route::get('/tube-mapping/tube/{tubeId}', [TubeMappingController::class, 'show'])
    ->name('tube-mapping.show');


// ================================
// RLA ANALYSIS
// ================================
Route::get('/rla-analysis', [RlaAnalysisController::class, 'index'])
    ->name('rla-analysis');


// ================================
// MAINTENANCE
// ================================
Route::get('/maintenance', function () {
    return view('maintenance.index');
})->name('maintenance');


// ================================
// ADMIN (login + kelola area)
// ================================
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::post('/admin/areas', [AreaController::class, 'store'])
    ->name('areas.store')
    ->middleware('auth');

Route::delete('/admin/areas/{area}', [AreaController::class, 'destroy'])
    ->name('areas.destroy')
    ->middleware('auth');

// Menu Input Data (khusus admin): add/delete pipa, add/delete titik,
// input pengukuran, upload RLA, dan upload gambar boiler.
Route::middleware('auth')->prefix('input_data')->name('input-data.')->group(function () {
    Route::get('/', [InputDataController::class, 'index'])->name('index');

    Route::get('/pipa', [InputDataController::class, 'pipa'])->name('pipa');
    Route::post('/pipa/tambah', [InputDataController::class, 'pipaAdd'])->name('pipa.add');
    Route::post('/pipa/kurangi', [InputDataController::class, 'pipaReduce'])->name('pipa.reduce');

    Route::get('/titik', [InputDataController::class, 'titik'])->name('titik');
    Route::post('/titik/tambah', [InputDataController::class, 'titikAdd'])->name('titik.add');
    Route::delete('/titik', [InputDataController::class, 'titikDelete'])->name('titik.delete');

    Route::get('/ukur', [InputDataController::class, 'ukur'])->name('ukur');
    Route::post('/ukur', [InputDataController::class, 'ukurStore'])->name('ukur.store');
    Route::delete('/ukur/{tubeNumber}', [InputDataController::class, 'ukurDestroy'])
        ->name('ukur.destroy')
        ->whereNumber('tubeNumber');

    Route::get('/rla', [InputDataController::class, 'rla'])->name('rla');
    Route::post('/rla', [InputDataController::class, 'rlaStore'])->name('rla.store');
    Route::delete('/rla/{document}', [InputDataController::class, 'rlaDestroy'])->name('rla.destroy');

    Route::get('/image', [InputDataController::class, 'image'])->name('image');
    Route::post('/image', [InputDataController::class, 'imageStore'])->name('image.store');
    Route::delete('/image/{image}', [InputDataController::class, 'imageDestroy'])->name('image.destroy');
});

// RLA file access (public — agar user biasa bisa lihat gambar/dokumen di RLA Analysis)
Route::prefix('input_data')->name('input-data.')->group(function () {
    Route::get('/rla/file/{document}', [InputDataController::class, 'rlaFile'])->name('rla.file');
    Route::get('/rla/download/{document}', [InputDataController::class, 'rlaDownload'])->name('rla.download');
    Route::get('/image/file/{image}', [InputDataController::class, 'imageFile'])->name('image.file');
});
