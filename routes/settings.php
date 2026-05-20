<?php

use App\Http\Controllers\Settings\SettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', [SettingsController::class, 'profile'])->name('profile.edit');
    Route::put('settings/profile', [SettingsController::class, 'updateProfile'])->name('profile.update');
    Route::delete('settings/user', [SettingsController::class, 'destroy'])->name('settings.user.destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('settings/appearance', [SettingsController::class, 'appearance'])->name('appearance.edit');

    Route::get('settings/security', [SettingsController::class, 'security'])
        ->middleware(['password.confirm'])
        ->name('security.edit');

    Route::put('settings/security/password', [SettingsController::class, 'updatePassword'])
        ->middleware(['password.confirm'])
        ->name('security.password.update');
});
