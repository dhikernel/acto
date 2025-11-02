<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LayerApiController;

Route::get('/', function () {
    return view('home');
});

Route::get('/debug', function () {
    return view('debug');
});

Route::get('/simple', function () {
    return view('simple-map');
});

Route::get('/test-api', function () {
    return view('test-api');
});

// API routes for layers
Route::prefix('api')->group(function () {
    Route::get('/layers', [LayerApiController::class, 'index']);
    Route::get('/layers/{layer}', [LayerApiController::class, 'show']);
    Route::get('/layers-stats', [LayerApiController::class, 'stats']);
});
