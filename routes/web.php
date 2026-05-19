<?php

use App\Http\Controllers\Api\RadnikApiController;
use App\Http\Controllers\Api\VodjaApiController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

// Vođa projekta
Route::middleware(['auth', 'verified', 'role:vodja'])->group(function () {
    Route::view('vodja/projekti', 'pages.vodja.projekti')->name('vodja.projekti');
    Route::view('vodja/novi-projekat', 'pages.vodja.novi-projekat')->name('vodja.novi-projekat');
    Route::view('vodja/izvjestaj', 'pages.vodja.izvjestaj')->name('vodja.izvjestaj');

    Route::prefix('api/vodja')->group(function () {
        Route::get('projects', [VodjaApiController::class, 'projects']);
        Route::get('project-form-config', [VodjaApiController::class, 'projectFormConfig']);
        Route::get('cities/{cityId}/streets', [VodjaApiController::class, 'streetsByCity']);
        Route::post('projects', [VodjaApiController::class, 'storeProject']);
        Route::get('report', [VodjaApiController::class, 'report']);
    });
});

// Radnik na terenu
Route::middleware(['auth', 'verified', 'role:radnik'])->group(function () {
    Route::view('radnik/unosi', 'pages.radnik.unosi')->name('radnik.unosi');
    Route::view('radnik/novi-unos', 'pages.radnik.novi-unos')->name('radnik.novi-unos');

    Route::prefix('api/radnik')->group(function () {
        Route::get('entries', [RadnikApiController::class, 'entries']);
        Route::get('form-config', [RadnikApiController::class, 'formConfig']);
        Route::post('entries', [RadnikApiController::class, 'storeEntry']);
    });
});

require __DIR__.'/settings.php';
