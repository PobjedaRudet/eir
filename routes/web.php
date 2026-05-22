<?php

use App\Http\Controllers\Api\MpmApiController;
use App\Http\Controllers\Api\NabavkaApiController;
use App\Http\Controllers\Api\NotificationsApiController;
use App\Http\Controllers\Api\RadnikApiController;
use App\Http\Controllers\Api\VodjaApiController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    // Notifications (shared â€” all roles)
    Route::prefix('api/notifications')->group(function () {
        Route::get('/', [NotificationsApiController::class, 'index']);
        Route::post('/read-all', [NotificationsApiController::class, 'markAllRead']);
        Route::post('/{notification}/read', [NotificationsApiController::class, 'markRead']);
    });
});

// VoÄ‘a projekta
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

// MenadĹľer projekata (MPM)
Route::middleware(['auth', 'verified', 'role:mpm'])->group(function () {
    // Backward-compatibility aliases for old MPM URLs.
    Route::redirect('mpm/portal', 'pm/portal', 301);
    Route::redirect('mpm/novi-projekat', 'pm/novi-projekat', 301);
    Route::redirect('mpm/projekti', 'pm/projekti', 301);
    Route::redirect('mpm/oprema', 'pm/oprema', 301);
    Route::redirect('mpm/odobrenja', 'pm/odobrenja', 301);
    Route::redirect('mpm/izvjestaj', 'pm/izvjestaj', 301);
    Route::get('mpm/projekti/{project}/radnici', fn ($project) => redirect("/pm/projekti/{$project}/radnici", 301));
    Route::get('mpm/projekti/{project}/plan', fn ($project) => redirect("/pm/projekti/{$project}/plan", 301));

    Route::view('pm/portal', 'pages.mpm.portal')->name('pm.portal');
    Route::view('pm/novi-projekat', 'pages.mpm.novi-projekat')->name('pm.novi-projekat');
    Route::view('pm/projekti', 'pages.mpm.projekti')->name('pm.projekti');
    Route::view('pm/projekti/{project}/radnici', 'pages.mpm.radnici')->name('pm.radnici');
    Route::view('pm/projekti/{project}/plan', 'pages.mpm.plan')->name('pm.plan');
    Route::view('pm/oprema', 'pages.mpm.oprema')->name('pm.oprema');
    Route::view('pm/odobrenja', 'pages.mpm.odobrenja')->name('pm.odobrenja');
    Route::view('pm/izvjestaj', 'pages.mpm.izvjestaj')->name('pm.izvjestaj');

    Route::prefix('api/pm')->group(function () {
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
        // Excel export
        Route::get('projects/{project}/export', [MpmApiController::class, 'exportProject']);
        // Project status toggle
        Route::patch('projects/{project}/toggle-status', [MpmApiController::class, 'toggleProjectStatus']);
        // Report (shared with vodja)
        Route::get('report', [VodjaApiController::class, 'report']);
        Route::get('report-projects', [VodjaApiController::class, 'projects']);
    });
});

// Nabavka (procurement)
Route::middleware(['auth', 'verified', 'role:nabavka'])->group(function () {
    Route::view('nabavka/dashboard', 'pages.nabavka.dashboard')->name('nabavka.dashboard');

    Route::prefix('api/nabavka')->group(function () {
        Route::get('work-orders', [NabavkaApiController::class, 'workOrders']);
        Route::get('purchase-orders', [NabavkaApiController::class, 'index']);
        Route::post('purchase-orders', [NabavkaApiController::class, 'createPurchaseOrder']);
        Route::post('purchase-orders/{purchaseOrder}/order', [NabavkaApiController::class, 'markOrdered']);
        Route::post('purchase-orders/{purchaseOrder}/send-to-supplier', [NabavkaApiController::class, 'sendToSupplier']);
        Route::get('purchase-orders/{purchaseOrder}/pdf', [NabavkaApiController::class, 'downloadPdf']);
        Route::post('purchase-orders/{purchaseOrder}/deliver', [NabavkaApiController::class, 'markDelivered']);
    });
});

require __DIR__.'/settings.php';
