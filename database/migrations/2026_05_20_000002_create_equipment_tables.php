<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category'); // masina, wc, kontejner, ostalo
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('project_equipment', function (Blueprint $table) {
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->primary(['project_id', 'equipment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_equipment');
        Schema::dropIfExists('equipment');
    }
};
