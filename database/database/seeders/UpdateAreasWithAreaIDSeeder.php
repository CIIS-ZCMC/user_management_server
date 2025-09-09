<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Division;
use App\Models\Section;
use App\Models\Unit;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UpdateAreasWithAreaIDSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $exclude_code = 'XX -';

        $divisions = Division::where('name', 'NOT LIKE', "%{$exclude_code}%")->get();

        foreach ($divisions as $division) {
            $total_divisions_with_area_id = Division::whereNotNull('area_id')->count();
            
            $area_id = sprintf("%s-%s-%03d", $division->code, 'DI', $total_divisions_with_area_id + 1);
            $division->update(['area_id' => $area_id]);
        }

        $departments = Department::where('name', 'NOT LIKE', "%{$exclude_code}%")->get();

        foreach ($departments as $department) {
            $total_departments_with_area_id = Department::whereNotNull('area_id')->count();
            
            $area_id = sprintf("%s-%s-%03d", $department->code, 'DE', $total_departments_with_area_id + 1);
            $department->update(['area_id' => $area_id]);
        }
        
        $sections = Section::where('name', 'NOT LIKE', "%{$exclude_code}%")->get();

        foreach ($sections as $section) {
            $total_sections_with_area_id = Section::whereNotNull('area_id')->count();
            
            $area_id = sprintf("%s-%s-%03d", $section->code, 'DE', $total_sections_with_area_id + 1);
            $section->update(['area_id' => $area_id]);
        }
        
        $units = Unit::where('name', 'NOT LIKE', "%{$exclude_code}%")->get();

        foreach ($units as $unit) {
            $total_units_with_area_id = Unit::whereNotNull('area_id')->count();
            
            $area_id = sprintf("%s-%s-%03d", $unit->code, 'DE', $total_units_with_area_id + 1);
            $unit->update(['area_id' => $area_id]);
        }
    }
}
