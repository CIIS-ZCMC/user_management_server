<?php

namespace App\Repositories\HR;

use App\Models\EmployeeProfile;

class EmployeeRepository
{
    public function getEmployee($id)
    {
        return EmployeeProfile::find($id);
    }

    public function getActiveEmployees()
    {
        return EmployeeProfile::with('biometric')
            ->whereHas('biometric', function($q) {
                $q->where('biometric','!=', 'NOT_YET_REGISTERED');
            })
            ->get();
    }

    public function getEmployeesWithNoBiometric()
    {
        return EmployeeProfile::with('biometric')
            ->where(function($query) {
                $query->whereNull('biometric_id')
                    ->orWhereHas('biometric', function($q) {
                        $q->where('biometric', 'NOT_YET_REGISTERED');
                    });
            })
            ->get();
    }

    public function getEmployeesWithNoLoginTransaction()
    {
        return EmployeeProfile::with('loginTrails')
        ->where(function ($query) {
            $query->whereNull('authorization_pin')
                  ->orWhereDoesntHave('loginTrails');
        })
        ->get();
    }
}