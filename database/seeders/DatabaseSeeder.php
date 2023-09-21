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
        $this->call([DivisionSeeder::class]);
        $this->call([DepartmentSeeder::class]);
        $this->call([StationSeeder::class]);
        $this->call([SalaryGradeSeeder::class]);
        $this->call([JobPositionSeeder::class]);
        $this->call([SystemSeeder::class]);
        $this->call([SystemRoleSeeder::class]);
        $this->call([SystemRolePermisionSeeder::class]);
        $this->call([PositionSystemRoleSeeder::class]);
        $this->call([UserSeeder::class]);
    }
}
