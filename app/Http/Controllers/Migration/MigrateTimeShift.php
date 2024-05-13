<?php

namespace App\Http\Controllers\Migration;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\TimeShift;
use Exception;

class MigrateTimeShift extends Controller
{
    /**
     * Display a listing of the resource.
     */

    //Inser to timeshift
    private function insertTimeShift($sched)
    {
        TimeShift::create([
            'first_in' => $sched->firstIn,
            'first_out' => $sched->firstOut,
            'second_in' => $sched->secondIn,
            'second_out' => $sched->secondOut,
            'total_hours' => $sched->totalHours,
            'color' => $sched->color,
            // Add other columns as needed
        ]);
    }

    public function index()
    {
        //
        try {
            // Attempt to establish the connection
            $RGB = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 'a', 'b', 'c', 'd', 'e', 'f'];
            $scheds = DB::connection('sqlsrv')->table('timeshift')->get();


            //sum of the total hours
            foreach ($scheds as $sched) {
                $noLunchBreak = false;
                //Generate Bright Colors
                $Red = $RGB[mt_rand(8, 14)] . $RGB[mt_rand(4, 14)];
                $Green = $RGB[mt_rand(8, 14)] . $RGB[mt_rand(4, 14)];
                $Blue = $RGB[mt_rand(8, 14)] . $RGB[mt_rand(4, 14)];

                $Color = "#" . $Red . $Green . $Blue;

                if ($sched->out1st == null) {
                    $noLunchBreak = true;
                }
                if ($noLunchBreak) {
                    if (explode(':', $sched->in1st)[0] < explode(':', $sched->out2nd)[0]) {
                        $totalMinutes = (explode(':', $sched->out2nd)[0] - explode(':', $sched->in1st)[0]) * 60 + (explode(':', $sched->out2nd)[1] + explode(':', $sched->in1st)[1]);
                        $totalHours = $totalMinutes / 60 + $totalMinutes % 60;

                        $this->insertTimeShift((object) [
                            "firstIn" => $sched->in1st,
                            "firstOut" => $sched->out2nd,
                            "secondIn" => null,
                            "secondOut" => null,
                            "totalHours" => $totalHours,
                            "color" => $Color,
                        ]);
                    } else {
                        $totalMinutes = ((24 - explode(':', $sched->in1st)[0]) + explode(':', $sched->out2nd)[0]) * 60 + (explode(':', $sched->in1st)[1] + explode(':', $sched->out2nd)[1]);
                        $totalHours = $totalMinutes / 60 + $totalMinutes % 60;
                        $this->insertTimeShift((object)[
                            "firstIn" => $sched->in1st,
                            "firstOut" => $sched->out2nd,
                            "secondIn" => null,
                            "secondOut" => null,
                            "totalHours" => $totalHours,
                            "color" => $Color,
                        ]);
                    }
                } else {
                    $totalHours = explode(':', $sched->out1st)[0] - explode(':', $sched->in1st)[0] + explode(':', $sched->out2nd)[0] - explode(':', $sched->in2nd)[0];
                    $this->insertTimeShift((object)[
                        "firstIn" => $sched->in1st,
                        "firstOut" => $sched->out1st,
                        "secondIn" => $sched->in2nd,
                        "secondOut" => $sched->out2nd,
                        "totalHours" => $totalHours,
                        "color" => $Color,
                    ]);

                    // echo $sched->in1st." ". $sched->out1st. " ". $sched->in2nd ." ". $sched->out2nd. "=" .explode(':', $sched->out1st)[0] - explode(':', $sched->in1st)[0]+explode(':', $sched->out2nd)[0] - explode(':', $sched->in2nd)[0] . "<br>" ;
                }
            }
            // If no exceptions are thrown, the connection is successful
            return 'done';
        } catch (Exception $e) {
            // If a QueryException is thrown, there was an issue with the connection
            echo "Connection failed: " . $e->getMessage();
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
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
