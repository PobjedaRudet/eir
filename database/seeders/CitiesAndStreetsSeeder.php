<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Street;
use Illuminate\Database\Seeder;

class CitiesAndStreetsSeeder extends Seeder
{
    public function run(): void
    {
        $cities = [
            'Sarajevo' => [
                'Titova ulica',
                'Ferhadija',
                'Obala Kulina bana',
                'Maršala Tita',
                'Zmaja od Bosne',
                'Alipašina',
                'Branilaca Sarajeva',
                'Džidžikovac',
            ],
            'Mostar' => [
                'Bulevar',
                'Kralja Tomislava',
                'Braće Fejića',
                'Maršala Tita',
                'Alekse Šantića',
            ],
            'Tuzla' => [
                'Ulica Oslobođenja',
                'Turalibegova',
                'Univerzitetska',
                'Slatina',
                'Albina Herljevića',
            ],
            'Banja Luka' => [
                'Veselina Masleše',
                'Srpska ulica',
                'Jovana Dučića',
                'Jevrejska',
                'Kralja Petra I',
            ],
            'Zenica' => [
                'Masarykova',
                'Prve Zeničke Brigade',
                'Kamberovića polje',
                'Londža',
            ],
        ];

        foreach ($cities as $cityName => $streets) {
            $city = City::firstOrCreate(['name' => $cityName]);

            foreach ($streets as $streetName) {
                Street::firstOrCreate([
                    'name' => $streetName,
                    'city_id' => $city->id,
                ]);
            }
        }
    }
}
