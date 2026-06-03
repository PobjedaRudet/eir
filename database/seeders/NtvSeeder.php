<?php

namespace Database\Seeders;

use App\Models\Ntv;
use Illuminate\Database\Seeder;

class NtvSeeder extends Seeder
{
    public function run(): void
    {
        $ntvs = [
            '8V8001',
            '8V8002',
            '8V8003',
            '8V8004',
            '8V8105',
            '8V8106',
            '8V8107',
            '8V8108',
            '8V8109',
        ];

        foreach ($ntvs as $name) {
            Ntv::firstOrCreate(['name' => $name]);
        }
    }
}
