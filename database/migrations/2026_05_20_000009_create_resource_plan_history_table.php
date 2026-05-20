<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Immutable audit log — insert only, no updates
        Schema::create('resource_plan_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('resource_plans')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            // created | submitted | approved | rejected | item_added | item_updated | item_removed
            $table->string('action', 50);
            $table->json('data')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_plan_history');
    }
};
