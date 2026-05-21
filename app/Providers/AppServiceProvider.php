<?php

namespace App\Providers;

use App\Models\PurchaseOrder;
use App\Models\ResourcePlan;
use App\Models\ResourcePlanItem;
use App\Models\ServiceOrder;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        Route::model('plan', ResourcePlan::class);
        Route::model('item', ResourcePlanItem::class);
        Route::model('order', WorkOrder::class);
        Route::model('orderItem', WorkOrderItem::class);
        Route::model('serviceOrder', ServiceOrder::class);
        Route::model('purchaseOrder', PurchaseOrder::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Schema::defaultStringLength(191);

        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
