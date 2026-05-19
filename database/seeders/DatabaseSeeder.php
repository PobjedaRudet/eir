<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name'  => 'Vodja Projekta',
            'email' => 'vodja@eir.ba',
            'role'  => 'vodja',
        ]);

        User::factory()->create([
            'name'  => 'Radnik Teren',
            'email' => 'radnik@eir.ba',
            'role'  => 'radnik',
        ]);

        $this->call([
            CitiesAndStreetsSeeder::class,
            EnclosuresSeeder::class,
        ]);
    }
}
