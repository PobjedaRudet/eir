<?php

use App\Http\Controllers\Api\RadnikApiController;
use App\Http\Controllers\Api\VodjaApiController;
use Illuminate\Support\Facades\Route;

// Radnik API
Route::middleware(['auth', 'verified', 'role:radnik'])->prefix('radnik')->group(function () {
    Route::get('/entries', [RadnikApiController::class, 'entries']);
    Route::get('/form-config', [RadnikApiController::class, 'formConfig']);
    Route::post('/entries', [RadnikApiController::class, 'storeEntry']);
});

// Vodja API
Route::middleware(['auth', 'verified', 'role:vodja'])->prefix('vodja')->group(function () {
    Route::get('/projects', [VodjaApiController::class, 'projects']);
    Route::get('/project-form-config', [VodjaApiController::class, 'projectFormConfig']);
    Route::get('/cities/{cityId}/streets', [VodjaApiController::class, 'streetsByCity']);
    Route::post('/projects', [VodjaApiController::class, 'storeProject']);
    Route::get('/report', [VodjaApiController::class, 'report']);
});
