<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('vodja', 'radnik', 'mpm', 'nabavka') NOT NULL DEFAULT 'radnik'");
        }
        // SQLite stores enum as varchar; already supports any string — no change needed
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('vodja', 'radnik', 'mpm') NOT NULL DEFAULT 'radnik'");
        }
    }
};
