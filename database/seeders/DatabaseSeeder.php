<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([SystemConfigSeeder::class]);
        // $this->call([LegalInformationQuestionSeeder::class]);
        $this->call([SystemSeeder::class]);
        $this->call([RoleSeeder::class]);
        $this->call([SystemModuleSeeder::class]);
        $this->call([PermissionSeeder::class]);
        $this->call([ModulePermissionSeeder::class]);
        $this->call([SalaryGradeSeeder::class]);
        $this->call([SystemRoleSeeder::class]);
        $this->call([DesignationSeeder::class]);
        $this->call([PositionSystemRoleSeeder::class]);
        $this->call([EmploymentTypeSeeder::class]);
        $this->call([DivisionSeeder::class]);
        // $this->call([DepartmentSeeder::class]);
        // $this->call([SectionSeeder::class]);
        // $this->call([UnitSeeder::class]);
        $this->call([LeaveTypeRequirementSeeder::class]);
        $this->call([LeaveTypeSeeder::class]);
        $this->call([PersonalInformationSeeder::class]);
        $this->call([SpecialAccessRoleSeeder::class]);
        $this->call([TimeShiftSeeder::class]);
        $this->call([DocumentNumberSeeder::class]);
    }
}
