<?php

use App\Http\Controllers\LeaveAndOverTime\LeaveApplicationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/


Route::controller(App\Http\Controllers\DTR\DTRcontroller::class)->group(
    function () {
        Route::get('/ftchdtrfrmdvc', 'fetchDTRFromDevice')->name('fetchdtrfromdevice');
        Route::get('fetchUserDTR', 'fetchUserDTR')->name('fetchuserdtr');
        Route::get('generateDtr', 'generateDTR')->name('generateDtr');
        Route::get('/getHolidays', 'getHolidays')->name('getHolidays');
        Route::get('/setHolidays', 'setHolidays')->name('setHolidays');
        Route::get('/modifyHolidays', 'modifyHolidays')->name('modifyHolidays');
        Route::get('/viewdtr', 'viewDTR')->name('viewdtr');
        Route::get('/dtrutotreport', 'dtrUTOTReport')->name('dtrutotreport');
        Route::get('/testtest', 'test')->name('testtest');

        Route::get('/setHolidays', 'Set_Holidays')->name('setHolidays');
    }
);



Route::controller(App\Http\Controllers\DTR\BioController::class)->group(
    function () {

        Route::get('/newRegistration', 'registerBio')->name('newregistration');
        //  Route::get('/pulldatafromdevice', 'FetchAllData_FromDevice')->name('pullingdata');
        Route::get('/pushuserdatatodevice', 'fetchUserToDevice')->name('pushinguserdata');
        Route::get('/setuserasadmin', 'setUserSuperAdmin')->name('setuserasadmin');
        Route::get('/pulluserdatafromdevice', 'fetchUserFromDevice')->name('pullinguserdata');
        Route::get('/fetchallbio', 'fetchBIOToDevice')->name('fetchallbio');
        Route::get('/deleteall', 'deleteAllBIOFromDevice')->name('deletealldatafromdevice');
        Route::get('/delete', 'deleteSpecificBIOFromDevice')->name('deleteuserdatafromdevice');
        Route::get('/synctime', 'syncTime')->name('synctdateandtime');
        Route::get('/enable/disable', 'enableORDisable')->name('enabledisable');
        Route::get('/restart/exit', 'restartORShutdown')->name('resshut');
        Route::get('/setTime', 'setTime')->name('setTime');
    }
);



Route::controller(App\Http\Controllers\DTR\BioMSController::class)->group(
    function () {
        Route::get('/alldevice', 'index')->name('alldevice');
        Route::get('/registerDevice', 'addDevice')->name('registerDevice');
        Route::get('/testdevice', 'testDeviceConnection')->name('testdevice');
        Route::get('/deleteDevice', 'deleteDevice')->name('deleteDevice');
        Route::get('/updateDevice', 'updateDevice')->name('updateDevice');
    }
);

Route::controller(App\Http\Controllers\DTR\MailController::class)->group(
    function () {
        Route::get('/sendOTP', 'sendOTP')->name('mail.sendOTP');
    }
);


Route::get('/welcome', function () {
    return view('welcome');
});

Route::controller(App\Http\Controllers\DTR\TwoFactorAuthController::class)->group(
    function () {
        Route::get('/verify', 'eVerification')->name('verify');
        Route::get('/verifyOtp', 'verifyOTP')->name('Verify_OTP');
    }
);

Route::get('/', function () {
    return view('mail.otp');
});


Route::namespace('App\Http\Controllers\Schedule')->group(function(){
    Route::get('/generate', 'ScheduleController@generate');
});

Route::get('/ot', function () {
    return view('overtime_authority');
});



Route::get('/lr', function () {
    return view('leave_report');
});
