<?php

use App\Http\Controllers\DashboardController;
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