<?php

namespace App\Http\Controllers;

use App\Models\DigitalSignedLeave;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Helpers\Helpers;
use Symfony\Component\HttpFoundation\Response;

class DigitalSignedLeaveController extends Controller
{
    private string $CONTROLLER_NAME = 'DigitalSignedLeaveController';
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = DigitalSignedLeave::query();
            if ($request->has('employee_profile_id')) {
                $query->where('employee_profile_id', $request->input('employee_profile_id'));
            }

            if ($request->has('signer_type')) {
                $query->where('signer_type', $request->input('signer_type'));
            }

            if ($request->has('month_year')) {
                $query->where('month_year', $request->input('month_year'));
            }

            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }

            $documents = $query->orderBy('created_at', 'desc')->paginate(10);
        } catch (\Throwable $th) {
            Log::error('Error in index: ' . $th->getMessage());
            Helpers::errorLog($this->CONTROLLER_NAME, 'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
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
    public function show(DigitalSignedLeave $digitalSignedLeave)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DigitalSignedLeave $digitalSignedLeave)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DigitalSignedLeave $digitalSignedLeave)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DigitalSignedLeave $digitalSignedLeave)
    {
        //
    }
}
