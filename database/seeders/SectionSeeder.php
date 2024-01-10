<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Division;
use App\Models\Section;

class SectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $division = Division::where('code', 'HOPPS')->first();

        Section::create([
            'name' => 'Material Management Section',
            'code' => 'MMS',
            'division_id' => $division->id
        ]);
    }
}
