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
    Route::post('signin', 'UserController@signIn');
    Route::post('send-otp', 'UserController@sendOTPEmail');
    Route::post('validate-otp', 'UserController@validateOTP');
    Route::post('reset-password', 'UserController@resetPassword');
});

Route::middleware('auth.cookie')->group(function(){
    Route::namespace('App\Http\Controllers')->group(function(){
        Route::post('authenticity-check', 'UserController@isAuthenticated');

        /**
         * User Module
         */
        Route::middleware('auth.permission::user view')->group(function(){
            Route::get('users', 'UserController@index');
        });

        Route::middleware('auth.permission::user create')->group(function(){
            Route::post('user', 'UserController@store');
        });

        Route::middleware('auth.permission::user view')->group(function(){
            Route::get('user/{id}', 'UserController@show');
        });

        Route::middleware('auth.permission::user update')->group(function(){
            Route::put('user/{id}', 'UserController@update');
        });

        Route::middleware('auth.permission::user delete')->group(function(){
            Route::delete('user/{id}', 'UserController@destroy');
        });

        /**
         * Employee Module
         */
        Route::middleware('auth.permission::employee view')->group(function(){
            Route::get('employee-profiles', 'EmployeeProfileController@index');
        });

        Route::middleware('auth.permission::employee create')->group(function(){
            Route::post('employee-profile', 'EmployeeProfileController@store');
        });

        Route::middleware('auth.permission::employee view')->group(function(){
            Route::get('employee-profile/{id}', 'EmployeeProfileController@show');
        });

        Route::middleware('auth.permission::employee update')->group(function(){
            Route::put('employee-profile/{id}', 'EmployeeProfileController@update');
        });

        Route::middleware('auth.permission::employee delete')->group(function(){
            Route::delete('employee-profile/{id}', 'EmployeeProfileController@destroy');
        });

        /**
         * Module without authorization needed
         */
        Route::delete('signout', 'UserController@signOut');
    });
});