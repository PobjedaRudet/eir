<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('vodja', 'radnik', 'mpm') NOT NULL DEFAULT 'radnik'");
        } else {
            // SQLite stores enum as varchar; change to string to allow 'mpm'
            Schema::table('users', function (Blueprint $table) {
                $table->string('role', 20)->default('radnik')->change();
            });
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('vodja', 'radnik') NOT NULL DEFAULT 'radnik'");
        }
    }
};
