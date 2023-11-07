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
        $this->call([SystemSeeder::class]);
        $this->call([SystemModuleSeeder::class]);
        $this->call([DefaultPasswordSeeder::class]);
        $this->call([SalaryGradeSeeder::class]);
        $this->call([DesignationSeeder::class]);
        $this->call([DivisionSeeder::class]);
        $this->call([EmploymentTypeSeeder::class]);
        $this->call([LegalInformationQuestionSeeder::class]);
        // $this->call([DepartmentSeeder::class]);
        // $this->call([PermissionSeeder::class]);
        // $this->call([SystemSeeder::class]);
        // $this->call([SystemRoleSeeder::class]);
        // $this->call([SystemRolePermisionSeeder::class]);
        // $this->call([PositionSystemRoleSeeder::class]);
        // $this->call([UserSeeder::class]);
        // $this->call([DefaultPasswordSeeder::class]);
    }
}
