<?php

namespace App\Repositories\HR;

use App\Models\EmployeeProfile;

class EmployeeRepository
{
    public function getEmployee($id)
    {
        return EmployeeProfile::find($id);
    }

    public function getActiveEmployees($regularOnly = true)
    {
        return EmployeeProfile::with('biometric')
            ->whereHas('biometric', function($q) {
                $q->where('biometric','!=', 'NOT_YET_REGISTERED');
            })
            ->whereNotNull('employee_id')
            ->whereIn('employment_type_id', $regularOnly ? [1,2,3,4] : [5])
            ->get();
    }

    public function getEmployeesWithNoBiometric($regularOnly = true)
    {
        return EmployeeProfile::with('biometric')
            ->where(function($query) {
                $query->whereNull('biometric_id')
                    ->orWhereHas('biometric', function($q) {
                        $q->where('biometric', 'NOT_YET_REGISTERED');
                    });
            })
            ->whereNotNull('employee_id')
            ->whereIn('employment_type_id', $regularOnly ? [1,2,3,4] : [5])
            ->get();
    }

    public function getEmployeesWithNoLoginTransaction($regularOnly = true)
    {
        return EmployeeProfile::with('loginTrails')
            ->where(function ($query) {
                $query->whereNull('authorization_pin')
                    ->orWhereDoesntHave('loginTrails');
            })
            ->whereNotNull('employee_id')
            ->whereIn('employment_type_id', $regularOnly ? [1,2,3,4] : [5])
            ->get();
    }
}