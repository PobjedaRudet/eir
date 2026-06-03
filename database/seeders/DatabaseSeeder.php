<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\NtvSeeder;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'vodja@eir.ba'],
            [
                'name' => 'Vodja Projekta',
                'password' => 'password',
                'role' => 'vodja',
                'email_verified_at' => now(),
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'mpm@eir.ba'],
            [
                'name' => 'Menadžer Projekata',
                'password' => 'password',
                'role' => 'mpm',
                'email_verified_at' => now(),
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'radnik@eir.ba'],
            [
                'name' => 'Radnik Teren',
                'password' => 'password',
                'role' => 'radnik',
                'email_verified_at' => now(),
            ],
        );

        $this->call([
            CitiesAndStreetsSeeder::class,
            EnclosuresSeeder::class,
            NtvSeeder::class,
            EquipmentSeeder::class,
        ]);
    }
}
