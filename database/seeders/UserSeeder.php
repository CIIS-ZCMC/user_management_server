<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Profile;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::create([
            'email' => 'ciis_zcmc@gmail.com',
            'password' => Hash::make('4dm1n'),
        ]);

        Profile::create([
            "first_name" => "center",
            "middle_name" => "innovation and",
            "last_name" => "information System",
            "extension_name" => "CIIS",
            "dob" => date('Y-m-d'),
            "sex" => "gay",
            "FK_user_ID" => $user->id
        ]);
    }
}
