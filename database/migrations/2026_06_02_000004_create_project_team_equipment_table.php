<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_team_equipment', function (Blueprint $table) {
            $table->foreignId('project_team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->primary(['project_team_id', 'equipment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_team_equipment');
    }
};
