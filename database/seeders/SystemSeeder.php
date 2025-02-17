<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;

use App\Models\System;

class SystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        System::create([
            'name' => 'User Management Information System',
            'code' => Cache::get('system_abbreviation'),
            'domain' => Crypt::encrypt(env('CLIENT_DOMAIN')),
            'status' => 1
        ]);
    }
}
