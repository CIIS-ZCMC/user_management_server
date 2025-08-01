<?php

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),
    'memory_limit' => env('MEMORY_LIMIT', '512M'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    'app_key' => env('APP_KEY'),
    'server_domain' => env('SERVER_DOMAIN'),
    'session_domain' => env('SESSION_DOMAIN'),
    'client_domain' => env('CLIENT_DOMAIN'),
    'socket_server_domain' => env('SOCKET_SERVER_DOMAIN'),

    'cookie_name' => env('COOKIE_NAME'),
    'system_abbreviation' => env('SYSTEM_ABBREVIATION'),
    'data_storing_key' => env('DATA_STORING_ENCRYPTION_KEY'),
    'encrypt_decrypt_algorithm' => env('ENCRYPT_DECRYPT_ALGORITHM'),
    'database_encryption_key' => env('DATABASE_ENCRYPTION_KEY'),
    'salt_value' => env('SALT_VALUE'),
    'data_key_encryption' => env('DATA_KEY_ENCRYPTION'),

    'google_api_client_id' => env('GOOGLE_API_CLIENT_ID'),
    'google_api_client_secret' => env('GOOGLE_API_CLIENT_SECRET'),
    'system_email_token' => env('SYSTEM_EMAIL_TOKEN'),
    'system_email' => env('SYSTEM_EMAIL'),
    'system_name' => env('SYSTEM_NAME'),

    'alloted_valid_time_for_firstentry' => env('ALLOTED_VALID_TIME_FOR_FIRSTENTRY'),
    'alloted_dtr_interval' => env('ALLOTED_DTR_INTERVAL'),
    'required_working_hours' => env('REQUIRED_WORKING_HOURS'),
    'firstin' => env('FIRSTIN'),
    'firstout' => env('FIRSTOUT'),
    'secondin' => env('SECONDIN'),
    'secondout' => env('SECONDOUT'),
    'max_allowed_entry_oncall' => env('MAX_ALLOWED_ENTRY_ONCALL'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |   
    */

    'url' => env('APP_URL', 'http://localhost'),

    'asset_url' => env('ASSET_URL'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We have gone
    | ahead and set this to a sensible default for you out of the box.
    |
    */

    'timezone' => 'Asia/Manila',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    */

    'locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available. You may change the value to correspond to any of
    | the language folders that are provided through your application.
    |
    */

    'fallback_locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Faker Locale
    |--------------------------------------------------------------------------
    |
    | This locale will be used by the Faker PHP library when generating fake
    | data for your database seeds. For example, this will be used to get
    | localized telephone numbers, street address information and more.
    |
    */

    'faker_locale' => 'en_US',

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => 'file',
        // 'store'  => 'redis',
    ],

    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Feel free to add your own services to
    | this array to grant expanded functionality to your applications.
    |
    */

    'providers' => ServiceProvider::defaultProviders()->merge([
        /*
        * Package Service Providers...
        */
        Maatwebsite\Excel\ExcelServiceProvider::class,  // <-- Add this line

        /*
         * Application Service Providers...
         */

        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        // App\Providers\BroadcastServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\RouteServiceProvider::class
    ])->toArray(),

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | This array of class aliases will be registered when this application
    | is started. However, feel free to register as many as you wish as
    | the aliases are "lazy" loaded so they don't hinder performance.
    |
    */

    'aliases' => Facade::defaultAliases()->merge([

       'Excel' => Maatwebsite\Excel\Facades\Excel::class,
    ])->toArray(),

];
