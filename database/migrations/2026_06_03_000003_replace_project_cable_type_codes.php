<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('projects')->where('cable_type', 'direct_buried')->update(['cable_type' => '8Y0001_1']);
        DB::table('projects')->where('cable_type', 'duct_cable')->update(['cable_type' => '8Y0001_2']);
        DB::table('projects')->where('cable_type', 'micro_cable')->update(['cable_type' => '8Y0001_3']);
    }

    public function down(): void
    {
        DB::table('projects')->where('cable_type', '8Y0001_1')->update(['cable_type' => 'direct_buried']);
        DB::table('projects')->where('cable_type', '8Y0001_2')->update(['cable_type' => 'duct_cable']);
        DB::table('projects')->where('cable_type', '8Y0001_3')->update(['cable_type' => 'micro_cable']);
    }
};
