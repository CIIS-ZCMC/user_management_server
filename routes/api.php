<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::namespace('App\Http\Controllers')->group(function () {
    Route::post('login', 'UserController@authenticate');
    Route::post('send-otp', 'UserController@sendOTPEmail');
    Route::post('validate-otp', 'UserController@validateOTP');
    Route::post('reset-password', 'UserController@resetPassword');
});

/**
 * Validate Request from other system.
 */
// Route::middleware(['auth.api', 'abilitiesCheck:validate_token'])->group(function () {
//     Route::post('/validate', [UserController::class, 'validateRequest']);
// });

// Route::middleware(['auth.api', 'abilitiesCheck:create_users'])->group(function () {
//     Route::post('/users', [YourController::class, 'createUser']);
// });

// Route::middleware(['auth.api', 'abilitiesCheck:edit_users,delete_users'])->group(function () {
//     Route::put('/users/{id}', [YourController::class, 'updateUser']);
//     Route::delete('/users/{id}', [YourController::class, 'deleteUser']);
// });


Route::middleware([AuthenticateWithCookie::class.':1'])->group(function(){
    Route::namespace('App\Http\Controllers')->group(function(){
        Route::get('profiles', 'ProfileController@index');
    });
});