<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_entry_id')->constrained()->cascadeOnDelete();
            $table->string('kind')->default('iskop'); // 'iskop' | 'upuhivanje'

            // Iskop fields (nullable – not used for upuhivanje)
            $table->string('excavation_type')->nullable(); // iskop, iskop_flaster, iskop_asfalt, raketa
            $table->string('dimensions')->nullable();      // 15x45, 15x60, 30x45, 30x60
            $table->decimal('meterage', 8, 2)->nullable();
            $table->json('sub_operations')->nullable();

            // Upuhivanje kabla fields (nullable – not used for iskop)
            $table->string('address')->nullable();
            $table->boolean('splajsovano')->default(false);
            $table->boolean('aktivirano')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operations');
    }
};
