<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_equipment', function (Blueprint $table) {
            $table->dropColumn('quantity');
            $table->date('assigned_date')->nullable()->after('equipment_id');
            $table->string('status', 20)->default('aktivna')->after('assigned_date');
        });
    }

    public function down(): void
    {
        Schema::table('project_equipment', function (Blueprint $table) {
            $table->dropColumn(['assigned_date', 'status']);
            $table->unsignedSmallInteger('quantity')->default(1)->after('equipment_id');
        });
    }
};
