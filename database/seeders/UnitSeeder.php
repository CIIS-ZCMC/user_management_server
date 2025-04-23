<?php

namespace Database\Seeders;

use App\Models\Section;
use App\Models\Unit;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $section = Section::where('code', 'HOPSS-EFM')->first();

        Unit::create([
            'name' => 'Housekeeping Unit',
            'code' => 'HOUSE',
            'section_id' => $section->id
        ]);

        Unit::create([
            'name' => 'Biomedical Unit',
            'code' => 'BIOMED',
            'section_id' => $section->id
        ]);

        Unit::create([
            'name' => 'Linen and Laundry Unit',
            'code' => 'LINEN',
            'section_id' => $section->id
        ]);
    }
}
