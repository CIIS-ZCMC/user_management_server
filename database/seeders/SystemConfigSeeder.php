<?php

namespace Database\Seeders;

use App\Helpers\Helpers;
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

        do {
            $app_key = config('app.app_key');
            Cache::forget('app_key');
            if ($app_key !== null) Cache::forever('app_key', $app_key);
        } while ($app_key === null);

        do {
            $server_domain = config('app.server_domain');
            Cache::forget('server_domain');
            if ($server_domain !== null) Cache::forever('server_domain', $server_domain);
        } while ($server_domain === null);

        do {
            $cookie_name = config('app.cookie_name');
            Cache::forget('cookie_name');
            if ($cookie_name !== null) Cache::forever('cookie_name', $cookie_name);
        } while ($cookie_name === null);

        do {
            $system_abbreviation = config('app.system_abbreviation');
            Cache::forget('system_abbreviation');
            if ($system_abbreviation !== null) Cache::forever('system_abbreviation', $system_abbreviation);
        } while ($system_abbreviation === null);

        do {
            $data_storing_key = config('app.data_storing_key');
            Cache::forget('data_storing_key');
            if ($data_storing_key !== null) Cache::forever('data_storing_key', $data_storing_key);
        } while ($data_storing_key === null);

        do {
            $encrypt_decrypt_algorithm = config('app.encrypt_decrypt_algorithm');
            Cache::forget('encrypt_decrypt_algorithm');
            if ($encrypt_decrypt_algorithm !== null) Cache::forever('encrypt_decrypt_algorithm', $encrypt_decrypt_algorithm);
        } while ($encrypt_decrypt_algorithm === null);

        do {
            $database_encryption_key = config('app.database_encryption_key');
            Cache::forget('database_encryption_key');
            if ($database_encryption_key !== null) Cache::forever('database_encryption_key', $database_encryption_key);
        } while ($database_encryption_key === null);

        do {
            $salt_value = config('app.salt_value');
            Cache::forget('salt_value');
            if ($salt_value !== null) Cache::forever('salt_value', $salt_value);
        } while ($salt_value === null);

        do {
            $data_key_encryption = config('app.data_key_encryption');
            Cache::forget('data_key_encryption');
            if ($data_key_encryption !== null) Cache::forever('data_key_encryption', $data_key_encryption);
        } while ($data_key_encryption === null);

        do {
            $google_api_client_id = config('app.google_api_client_id');
            Cache::forget('google_api_client_id');
            if ($google_api_client_id !== null) Cache::forever('google_api_client_id', $google_api_client_id);
        } while ($google_api_client_id === null);

        do {
            $google_api_client_secret = config('app.google_api_client_secret');
            Cache::forget('google_api_client_secret');
            if ($google_api_client_secret !== null) Cache::forever('google_api_client_secret', $google_api_client_secret);
        } while ($google_api_client_secret === null);

        do {
            $system_email_token = config('app.system_email_token');
            Cache::forget('system_email_token');
            if ($system_email_token !== null) Cache::forever('system_email_token', $system_email_token);
        } while ($system_email_token === null);

        do {
            $system_email = config('app.system_email');
            Cache::forget('system_email');
            if ($system_email !== null) Cache::forever('system_email', $system_email);
        } while ($system_email === null);

        do {
            $system_name = config('app.system_name');
            Cache::forget('system_name');
            if ($system_name !== null) Cache::forever('system_name', $system_name);
        } while ($system_name === null);
    }
}
