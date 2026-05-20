<?php

namespace Database\Seeders;

use App\Models\Equipment;
use Illuminate\Database\Seeder;

class EquipmentSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            // Mašine
            ['name' => 'Bager',               'category' => 'masina'],
            ['name' => 'Mini bager',           'category' => 'masina'],
            ['name' => 'Kamion',               'category' => 'masina'],
            ['name' => 'Kiper',                'category' => 'masina'],
            ['name' => 'Utovarivač',           'category' => 'masina'],
            ['name' => 'Kompaktna bušilica',   'category' => 'masina'],
            ['name' => 'Valjak',               'category' => 'masina'],

            // WC
            ['name' => 'Mobilni WC',           'category' => 'wc'],

            // Kontejneri
            ['name' => 'Kontejner za alat',    'category' => 'kontejner'],
            ['name' => 'Kontejner za odlaganje','category' => 'kontejner'],
            ['name' => 'Kontejner-kancelarija','category' => 'kontejner'],

            // Ostalo
            ['name' => 'Agregat',              'category' => 'ostalo'],
            ['name' => 'Kompresor',            'category' => 'ostalo'],
            ['name' => 'Generator',            'category' => 'ostalo'],
            ['name' => 'Pumpa za vodu',        'category' => 'ostalo'],
        ];

        foreach ($items as $item) {
            Equipment::query()->firstOrCreate(
                ['name' => $item['name'], 'category' => $item['category']],
            );
        }
    }
}
