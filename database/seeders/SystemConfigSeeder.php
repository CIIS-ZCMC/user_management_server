<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class SystemConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {   
        Cache::flush();

        do{
            $server_domain = config('app.server_domain');
            if($server_domain !== null) Cache::rememberForever('server_domain', function () use ($server_domain) {
                return $server_domain;
            });
        }while($server_domain === null);

        do{
            $cookie_name = config('app.cookie_name');
            if($cookie_name !== null) Cache::rememberForever('cookie_name', function () use ($cookie_name) {
                return $cookie_name;
            });
        }while($cookie_name === null);

        do{
            $system_abbreviation = config('app.system_abbreviation');
            if($system_abbreviation !== null) Cache::rememberForever('system_abbreviation', function () use ($system_abbreviation) {
                return $system_abbreviation;
            });
        }while($system_abbreviation === null);

        do{
            $data_storing_key = config('app.data_storing_key');
            if($data_storing_key !== null) Cache::rememberForever('data_storing_key', function () use ($data_storing_key) {
                return $data_storing_key;
            });
        }while($data_storing_key === null);


        do{
            $encrypt_decrypt_algorithm = config('app.encrypt_decrypt_algorithm');
            if($encrypt_decrypt_algorithm !== null) Cache::rememberForever('encrypt_decrypt_algorithm', function () use ($encrypt_decrypt_algorithm) {
                return $encrypt_decrypt_algorithm;
            });
        }while($encrypt_decrypt_algorithm === null);

        do{
            $database_encryption_key = config('app.database_encryption_key');
            if($database_encryption_key !== null) Cache::rememberForever('database_encryption_key', function () use ($database_encryption_key) {
                return $database_encryption_key;
            });
        }while($database_encryption_key === null);

        do{
            $salt_value = config('app.salt_value');
            if($salt_value !== null) Cache::rememberForever('salt_value', function () use ($salt_value) {
                return $salt_value;
            });
        }while($salt_value === null);

        do{
            $data_key_encryption = config('app.data_key_encryption');
            if($data_key_encryption !== null) Cache::rememberForever('data_key_encryption', function () use ($data_key_encryption) {
                return $data_key_encryption;
            });
        }while($data_key_encryption === null);
    }
}
