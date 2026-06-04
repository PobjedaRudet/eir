<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->dropForeign(['work_order_item_id']);
        });

        Schema::table('service_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('work_order_item_id')->nullable()->change();
            $table->string('resource_type', 20)->nullable()->after('work_order_item_id');
            $table->unsignedBigInteger('resource_id')->nullable()->after('resource_type');
            $table->string('resource_name', 200)->nullable()->after('resource_id');
            $table->string('resource_unit', 20)->nullable()->after('resource_name');
            $table->string('source_label', 200)->nullable()->after('resource_unit');
        });

        Schema::table('service_orders', function (Blueprint $table) {
            $table->foreign('work_order_item_id')->references('id')->on('work_order_items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->dropForeign(['work_order_item_id']);
        });

        Schema::table('service_orders', function (Blueprint $table) {
            $table->dropColumn(['resource_type', 'resource_id', 'resource_name', 'resource_unit', 'source_label']);
            $table->unsignedBigInteger('work_order_item_id')->nullable(false)->change();
        });

        Schema::table('service_orders', function (Blueprint $table) {
            $table->foreign('work_order_item_id')->references('id')->on('work_order_items')->cascadeOnDelete();
        });
    }
};
