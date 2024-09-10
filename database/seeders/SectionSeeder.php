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
        $division = Division::where('code', 'HOPSS')->first();

        Section::create([
            'name' => 'Data Protection Unit',
            'code' => 'DPU',
            'division_id' => $division->id
        ]);

        Section::create([
            'name' => 'Engineering and Facilities Management',
            'code' => 'EFM',
            'division_id' => $division->id
        ]);
        
        Section::create([
            'name' => 'Human Resource Management Office',
            'code' => 'HRMO',
            'division_id' => $division->id
        ]);

        Section::create([
            'name' => 'Material Management Section',
            'code' => 'MMS',
            'division_id' => $division->id
        ]);

        Section::create([
            'name' => 'Procurement Section',
            'code' => 'PROC',
            'division_id' => $division->id
        ]);

        Section::create([
            'name' => 'Security Unit',
            'code' => 'SEKYU',
            'division_id' => $division->id
        ]);

        $division = Division::where('code', 'FINANCE')->first();

        Section::create([
            'name' => 'Budget Section',
            'code' => 'BUDGET',
            'division_id' => $division->id
        ]);

        Section::create([
            'name' => 'Accounting Section',
            'code' => 'ACCOUNTING',
            'division_id' => $division->id
        ]);

        Section::create([
            'name' => 'Billing and Claims Section',
            'code' => 'BILLING',
            'division_id' => $division->id
        ]);

        Section::create([
            'name' => 'Cash Operations Section',
            'code' => 'CASH',
            'division_id' => $division->id
        ]);
    }
}
