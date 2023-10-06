<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\DefaultPassword;

class DefaultPasswordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DefaultPassword::create([
            'password' => 'ZcmcUmis2023@',
            'employee_profile_id' => 1,
            'status' => TRUE
        ]);
    }
}
