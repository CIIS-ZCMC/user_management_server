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
        $this->call([DepartmentSeeder::class]);
        $this->call([EmploymentPositionSeeder::class]);
        $this->call([SystemSeeder::class]);
        $this->call([SystemRoleSeeder::class]);
        $this->call([SystemRolePermisionSeeder::class]);
        $this->call([PositionSystemRoleSeeder::class]);
        $this->call([UserSeeder::class]);
    }
}
