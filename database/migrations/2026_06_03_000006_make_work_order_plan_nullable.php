<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
        });

        Schema::table('work_orders', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->change();
        });

        Schema::table('work_orders', function (Blueprint $table) {
            $table->foreign('plan_id')->references('id')->on('resource_plans')->nullOnDelete();
        });
    }

    public function down(): void
    {
        $fallbackPlanId = DB::table('resource_plans')->orderBy('id')->value('id');

        if ($fallbackPlanId === null) {
            DB::table('work_orders')->whereNull('plan_id')->delete();
        } else {
            DB::table('work_orders')->whereNull('plan_id')->update(['plan_id' => $fallbackPlanId]);
        }

        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
        });

        Schema::table('work_orders', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable(false)->change();
        });

        Schema::table('work_orders', function (Blueprint $table) {
            $table->foreign('plan_id')->references('id')->on('resource_plans')->cascadeOnDelete();
        });
    }
};
