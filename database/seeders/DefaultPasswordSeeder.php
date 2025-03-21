<?php

namespace Database\Seeders;

use Carbon\Carbon;
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
        $sixty_days_expiration = Carbon::now()->addMonths(3);

        DefaultPassword::create([
            'password' => 'ZcmcUmis2023@',
            'status' => TRUE,
            'effective_at' => now(),
            'end_at' => $sixty_days_expiration
        ]);
    }
}
