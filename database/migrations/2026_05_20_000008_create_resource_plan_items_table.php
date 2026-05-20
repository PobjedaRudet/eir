<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resource_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('resource_plans')->cascadeOnDelete();
            // equipment | material | service
            $table->string('resource_type', 20);
            $table->unsignedBigInteger('resource_id');
            // denormalized — preserves name even if resource is later deleted
            $table->string('resource_name', 100);
            $table->decimal('quantity', 10, 2)->default(1);
            $table->string('unit', 20)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_plan_items');
    }
};
