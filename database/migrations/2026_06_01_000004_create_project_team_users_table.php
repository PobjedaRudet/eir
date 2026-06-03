<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_team_users', function (Blueprint $table) {
            $table->foreignId('project_team_id')->constrained('project_teams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['project_team_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_team_users');
    }
};
