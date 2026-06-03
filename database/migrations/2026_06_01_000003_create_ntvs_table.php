<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ntvs', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->timestamps();
        });

        Schema::create('project_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name', 200);
            $table->timestamps();
        });

        Schema::create('project_ntvs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ntv_id')->constrained('ntvs')->cascadeOnDelete();
            $table->foreignId('project_team_id')->nullable()->constrained('project_teams')->nullOnDelete();
            $table->timestamps();
            $table->unique(['project_id', 'ntv_id']);
        });

        Schema::create('project_ntv_streets', function (Blueprint $table) {
            $table->foreignId('project_ntv_id')->constrained('project_ntvs')->cascadeOnDelete();
            $table->foreignId('street_id')->constrained()->cascadeOnDelete();
            $table->primary(['project_ntv_id', 'street_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_ntv_streets');
        Schema::dropIfExists('project_ntvs');
        Schema::dropIfExists('project_teams');
        Schema::dropIfExists('ntvs');
    }
};
