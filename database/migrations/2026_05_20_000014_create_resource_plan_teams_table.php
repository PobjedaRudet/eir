<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('resource_plan_workers');

        Schema::create('resource_plan_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('resource_plans')->cascadeOnDelete();
            $table->string('name', 200);
            $table->timestamps();
        });

        Schema::create('resource_plan_team_workers', function (Blueprint $table) {
            $table->foreignId('team_id')->constrained('resource_plan_teams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->primary(['team_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_plan_team_workers');
        Schema::dropIfExists('resource_plan_teams');

        Schema::create('resource_plan_workers', function (Blueprint $table) {
            $table->foreignId('plan_id')->constrained('resource_plans')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->primary(['plan_id', 'user_id']);
        });
    }
};
