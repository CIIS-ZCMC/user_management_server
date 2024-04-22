<?php

namespace App\Http\Controllers\Schedule;

use App\Http\Resources\ScheduleResource;
use App\Models\EmployeeProfile;
use App\Helpers\Helpers;

use App\Models\EmployeeSchedule;
use App\Models\Schedule;
use App\Models\TimeShift;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EmployeeScheduleController extends Controller
{
    private $CONTROLLER_NAME = 'Employee Schedule';
    private $PLURAL_MODULE_NAME = 'employee schedules';
    private $SINGULAR_MODULE_NAME = 'employee schedule';

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
