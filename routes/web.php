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
        Route::get('/ftchdtrfrmdvc', 'Fetch_DTR_from_Device')->name('fetchdtrfromdevice');
        Route::get('fetchUserDTR', 'FetchUser_DTR')->name('fetchuserdtr');
        Route::get('generateDtr', 'Generate_DTR')->name('generateDtr');
        Route::get('/getHolidays', 'Get_Holidays')->name('getHolidays');
        Route::get('/setHolidays', 'Set_Holidays')->name('setHolidays');
        Route::get('/modifyHolidays', 'Modify_Holidays')->name('modifyHolidays');
        Route::get('/viewdtr', 'ViewDTR')->name('viewdtr');
        Route::get('/dtrutotreport', 'DTR_UTOT_Report')->name('dtrutotreport');
        Route::get('/testtest', 'test')->name('testtest');

        Route::get('/setHolidays', 'Set_Holidays')->name('setHolidays');
    }
);



Route::controller(App\Http\Controllers\DTR\BioController::class)->group(
    function () {

        Route::get('/newRegistration', 'Register_Bio')->name('newregistration');
        //  Route::get('/pulldatafromdevice', 'FetchAllData_FromDevice')->name('pullingdata');
        Route::get('/pushuserdatatodevice', 'Fetch_User_ToDevice')->name('pushinguserdata');
        Route::get('/setuserasadmin', 'Set_User_SuperAdmin')->name('setuserasadmin');
        Route::get('/pulluserdatafromdevice', 'Fetch_User_FromDevice')->name('pullinguserdata');
        Route::get('/fetchallbio', 'Fetch_BIO_To_Device')->name('fetchallbio');
        Route::get('/deleteall', 'Delete_AllBIO_From_Device')->name('deletealldatafromdevice');
        Route::get('/delete', 'Delete_SpecificBIO_From_Device')->name('deleteuserdatafromdevice');
        Route::get('/synctime', 'SyncTime')->name('synctdateandtime');
        Route::get('/enable/disable', 'Enable_OR_Disable')->name('enabledisable');
        Route::get('/restart/exit', 'Restart_OR_Shutdown')->name('resshut');
        Route::get('/setTime', 'settime')->name('setTime');
    }
);



Route::controller(App\Http\Controllers\DTR\BioMSController::class)->group(
    function () {
        Route::get('/alldevice', 'index')->name('alldevice');
        Route::get('/registerDevice', 'add_device')->name('registerDevice');
        Route::get('/testdevice', 'test_device_connection')->name('testdevice');
        Route::get('/deleteDevice', 'Delete_device')->name('deleteDevice');
        Route::get('/updateDevice', 'Update_device')->name('updateDevice');
    }
);

Route::controller(App\Http\Controllers\DTR\MailController::class)->group(
    function () {
        Route::get('/testemail', 'testemail')->name('testemail');
    }
);


Route::get('/', function () {
    return view('welcome');
});

Route::post('print-leave-form/{id}', [LeaveApplicationController::class, 'storprintLeaveForme']);
