<?php

namespace Database\Seeders;

use App\Models\Enclosure;
use Illuminate\Database\Seeder;

class EnclosuresSeeder extends Seeder
{
    public function run(): void
    {
        $enclosures = [
            'JB-12',
            'JB-24',
            'JB-48',
            'JB-96',
            'MiniJB-4',
            'MiniJB-8',
            'Razdjelnik R-8',
            'Razdjelnik R-16',
            'Razdjelnik R-24',
            'Distribucijski kabinetTC-12',
            'Distribucijski kabinetTC-24',
        ];

        foreach ($enclosures as $name) {
            Enclosure::firstOrCreate(['name' => $name]);
        }
    }
}
