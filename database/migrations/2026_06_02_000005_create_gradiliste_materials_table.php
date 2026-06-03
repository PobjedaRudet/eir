<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gradiliste_materials', function (Blueprint $table) {
            $table->foreignId('gradiliste_id')->constrained('gradilista')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 10, 2)->default(1);
            $table->primary(['gradiliste_id', 'material_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gradiliste_materials');
    }
};
