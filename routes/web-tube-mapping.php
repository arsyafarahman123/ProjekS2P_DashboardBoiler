<?php

use App\Http\Controllers\TubeMappingController;
use Illuminate\Support\Facades\Route;


Route::get('/tube-mapping', [TubeMappingController::class, 'index'])
    ->name('tube-mapping.index');


Route::get('/tube-mapping/tube/{tubeId}', [TubeMappingController::class, 'show'])
    ->name('tube-mapping.show');