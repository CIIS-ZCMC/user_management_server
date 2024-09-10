<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Religion;

class ReligionSeeder extends Seeder
{
    public function run()
    {
        $religions = [
            ['name' => 'Roman Catholic'],
            ['name' => 'Islam'],
            ['name' => 'Evangelical'],
            ['name' => 'Iglesia ni Cristo'],
            ['name' => 'Aglipayan'],
            ['name' => 'Buddhism'],
            ['name' => 'Jehovah’s Witnesses'],
            ['name' => 'Seventh-day Adventist'],
            ['name' => 'Baptist'],
            ['name' => 'Methodist'],
            ['name' => 'Lutheran'],
            ['name' => 'Other Christian Denominations'],
            ['name' => 'Other Non-Christian Religions'],
        ];

        foreach ($religions as $religion) {
            Religion::create($religion);
        }
    }
}
