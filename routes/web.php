<?php

use App\Http\Controllers\Api\MpmApiController;
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
    Route::view('vodja/izvjestaj', 'pages.vodja.izvjestaj')->name('vodja.izvjestaj');
    Route::view('vodja/projekti/{project}/resursi', 'pages.vodja.resursi')->name('vodja.resursi');
    Route::view('vodja/projekti/{project}/servis', 'pages.vodja.servis')->name('vodja.servis');
        Route::view('vodja/servisni-nalozi', 'pages.vodja.svi-servisni-nalozi')->name('vodja.servisni-nalozi');
    Route::prefix('api/vodja')->group(function () {
        Route::get('projects', [VodjaApiController::class, 'projects']);
        // Resource Plans
        Route::get('projects/{project}/plans', [VodjaApiController::class, 'projectPlans']);
        Route::post('projects/{project}/plans', [VodjaApiController::class, 'createPlan']);
        Route::get('plans/{plan}', [VodjaApiController::class, 'planDetail']);
        Route::post('plans/{plan}/items', [VodjaApiController::class, 'addPlanItem']);
        Route::patch('plans/{plan}/items/{item}', [VodjaApiController::class, 'updatePlanItem']);
        Route::delete('plans/{plan}/items/{item}', [VodjaApiController::class, 'removePlanItem']);
        Route::post('plans/{plan}/submit', [VodjaApiController::class, 'submitPlan']);
        Route::delete('plans/{plan}', [VodjaApiController::class, 'discardPlan']);
        // Catalog (for order items)
        Route::get('catalog', [VodjaApiController::class, 'catalog']);
        // Work orders
        Route::get('projects/{project}/orders', [VodjaApiController::class, 'projectOrders']);
        Route::post('projects/{project}/orders', [VodjaApiController::class, 'createOrder']);
        Route::delete('orders/{order}', [VodjaApiController::class, 'deleteOrder']);
        Route::post('orders/{order}/items', [VodjaApiController::class, 'addOrderItem']);
        Route::delete('orders/{order}/items/{orderItem}', [VodjaApiController::class, 'removeOrderItem']);
        Route::post('orders/{order}/submit', [VodjaApiController::class, 'submitOrder']);
        Route::get('report', [VodjaApiController::class, 'report']);
        // Service orders
        Route::get('projects/{project}/service-orders', [VodjaApiController::class, 'projectServiceOrders']);
        Route::get('service-orders', [VodjaApiController::class, 'allServiceOrders']);
        Route::post('service-orders', [VodjaApiController::class, 'createServiceOrder']);
        Route::post('service-orders/{serviceOrder}/return', [VodjaApiController::class, 'returnServiceOrder']);
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

// Menadžer projekata (MPM)
Route::middleware(['auth', 'verified', 'role:mpm'])->group(function () {
    Route::view('mpm/portal', 'pages.mpm.portal')->name('mpm.portal');
    Route::view('mpm/novi-projekat', 'pages.mpm.novi-projekat')->name('mpm.novi-projekat');
    Route::view('mpm/projekti', 'pages.mpm.projekti')->name('mpm.projekti');
    Route::view('mpm/projekti/{project}/radnici', 'pages.mpm.radnici')->name('mpm.radnici');
    Route::view('mpm/projekti/{project}/plan', 'pages.mpm.plan')->name('mpm.plan');
    Route::view('mpm/oprema', 'pages.mpm.oprema')->name('mpm.oprema');
    Route::view('mpm/odobrenja', 'pages.mpm.odobrenja')->name('mpm.odobrenja');

    Route::prefix('api/mpm')->group(function () {
        Route::get('projects', [MpmApiController::class, 'projects']);
        Route::get('project-form-config', [MpmApiController::class, 'projectFormConfig']);
        Route::get('cities/{cityId}/streets', [MpmApiController::class, 'streetsByCity']);
        Route::post('projects', [MpmApiController::class, 'storeProject']);
        Route::get('projects/{project}/workers', [MpmApiController::class, 'projectWorkers']);
        Route::put('projects/{project}/workers', [MpmApiController::class, 'syncProjectWorkers']);
        // Equipment catalog
        Route::get('equipment', [MpmApiController::class, 'equipmentList']);
        Route::post('equipment', [MpmApiController::class, 'storeEquipment']);
        Route::put('equipment/{equipment}', [MpmApiController::class, 'updateEquipment']);
        Route::delete('equipment/{equipment}', [MpmApiController::class, 'destroyEquipment']);
        // Materials catalog
        Route::get('materials', [MpmApiController::class, 'materialList']);
        Route::post('materials', [MpmApiController::class, 'storeMaterial']);
        Route::put('materials/{material}', [MpmApiController::class, 'updateMaterial']);
        Route::delete('materials/{material}', [MpmApiController::class, 'destroyMaterial']);
        // Services catalog
        Route::get('services', [MpmApiController::class, 'serviceList']);
        Route::post('services', [MpmApiController::class, 'storeService']);
        Route::put('services/{projectService}', [MpmApiController::class, 'updateService']);
        Route::delete('services/{projectService}', [MpmApiController::class, 'destroyService']);
        // Resource Plan approvals
        Route::get('plans/pending', [MpmApiController::class, 'pendingPlans']);
        Route::post('plans/{plan}/approve', [MpmApiController::class, 'approvePlan']);
        Route::post('plans/{plan}/reject', [MpmApiController::class, 'rejectPlan']);
        // MPM Radni plan creation for projects
        Route::get('projects/{project}/plan', [MpmApiController::class, 'projectPlan']);
        Route::post('projects/{project}/plan', [MpmApiController::class, 'createProjectPlan']);
        Route::put('plans/{plan}/teams', [MpmApiController::class, 'syncPlanTeams']);
        // Work order approvals
        Route::get('orders/pending', [MpmApiController::class, 'pendingOrders']);
        Route::post('orders/{order}/approve', [MpmApiController::class, 'approveOrder']);
        Route::post('orders/{order}/reject', [MpmApiController::class, 'rejectOrder']);
    });
});

require __DIR__.'/settings.php';
