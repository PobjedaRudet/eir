<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gradiliste_equipment', function (Blueprint $table) {
            $table->foreignId('gradiliste_id')->constrained('gradilista')->cascadeOnDelete();
            $table->foreignId('equipment_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->primary(['gradiliste_id', 'equipment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gradiliste_equipment');
    }
};
