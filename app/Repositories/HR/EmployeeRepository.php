<?php

namespace App\Repositories\HR;

use App\Models\EmployeeProfile;
use Illuminate\Support\Facades\DB;

class EmployeeRepository
{
    public function getEmployee($id)
    {
        return EmployeeProfile::find($id);
    }

    public function activeEmployee($filter = null)
    {
        if($filter == null){
            return EmployeeProfile::whereNotNull('employee_id')->get();
        }

        return EmployeeProfile::whereNotNull('employee_id')->whereIn('employment_type_id', $filter == 'regular' ? [1,2,3,4] : [5])->get();
    }

    public function getEmployeesWithBiometric($filter = null)
    {
        if($filter == null){
            return EmployeeProfile::with('biometric')
            ->withCount('loginTrails')
            ->whereHas('biometric', function($q) {
                $q->where('biometric', '!=', 'NOT_YET_REGISTERED');
            })
            ->whereNotNull('employee_id')
            ->get()
            ->map(function($employee) {
                $employee->has_login_history = $employee->loginTrails->count() > 0 ? 'Yes' : 'No';
                return $employee;
            });
        }

        return EmployeeProfile::with('biometric')
            ->withCount('loginTrails')
            ->whereHas('biometric', function($q) {
                $q->where('biometric', '!=', 'NOT_YET_REGISTERED');
            })
            ->whereNotNull('employee_id')
            ->whereIn('employment_type_id', $filter == 'regular' ? [1,2,3,4] : [5])
            ->get()
            ->map(function($employee) {
                $employee->has_login_history = $employee->loginTrails->count() > 0 ? 'Yes' : 'None';
                return $employee;
            });
    }

    public function getEmployeesWithNoBiometric($filter = null)
    {
        if($filter == null){
            return EmployeeProfile::with('loginTrails')
                ->withCount('loginTrails')
                ->leftJoin('biometrics as b', 'b.biometric_id', '=', 'employee_profiles.biometric_id')
                ->whereNotNull('employee_profiles.employee_id')
                ->whereNull('employee_profiles.deactivated_at')
                ->where('employee_profiles.biometric_id', '>', 0)
                ->where(function ($query) {
                    $query->whereNull('b.biometric') // no record in biometrics table
                        ->orWhere('b.biometric', '=', 'NOT_YET_REGISTERED'); // has record but not registered
                })
                ->select('employee_profiles.*')
                ->get()
                ->map(function ($employee) {
                    $employee->has_login_history = $employee->loginTrails->count() > 0 ? 'Yes' : 'No';
                    $employee->has_biometric = 'No';
                    return $employee;
                });
        }

        return EmployeeProfile::with('loginTrails')
            ->withCount('loginTrails')
            ->leftJoin('biometrics as b', 'b.biometric_id', '=', 'employee_profiles.biometric_id')
            ->whereNotNull('employee_profiles.employee_id')
            ->whereNull('employee_profiles.deactivated_at')
            ->where('employee_profiles.biometric_id', '>', 0)
            ->where(function ($query) {
                $query->whereNull('b.biometric') // no record in biometrics table
                    ->orWhere('b.biometric', '=', 'NOT_YET_REGISTERED'); // has record but not registered
            })
            ->select('employee_profiles.*')
            ->get()
            ->map(function ($employee) {
                $employee->has_login_history = $employee->loginTrails->count() > 0 ? 'Yes' : 'None';
                $employee->has_biometric = 'No';
                return $employee;
            });
    }

    public function getEmployeesWithNoLoginTransaction($regularOnly = null)
    {
        if($regularOnly == null){
            return EmployeeProfile::with('loginTrails')
            ->withCount('loginTrails')
            ->leftJoin('biometrics as b', 'b.biometric_id', '=', 'employee_profiles.biometric_id')
            ->whereNotNull('employee_profiles.employee_id')
            ->whereNull('employee_profiles.deactivated_at')
            ->where(function ($query) {
                $query->whereNull('b.biometric_id')
                    ->orWhere('b.biometric', '=', 'NOT_YET_REGISTERED');
            })
            ->select('employee_profiles.*')
            ->get()
            ->map(function ($employee) {
                $employee->has_login_history = $employee->loginTrails->count() > 0 ? 'Yes' : 'None';
                $employee->has_biometric = 'No';
                return $employee;
            });
        }

        return EmployeeProfile::with('loginTrails')
            ->withCount('loginTrails')
            ->leftJoin('biometrics as b', 'b.biometric_id', '=', 'employee_profiles.biometric_id')
            ->whereNotNull('employee_profiles.employee_id')
            ->whereNull('employee_profiles.deactivated_at')
            ->where(function ($query) {
                $query->whereNull('b.biometric_id')
                    ->orWhere('b.biometric', '=', 'NOT_YET_REGISTERED');
            })
            ->select('employee_profiles.*')
            ->get()
            ->map(function ($employee) {
                $employee->has_login_history = $employee->loginTrails->count() > 0 ? 'Yes' : 'None';
                $employee->has_biometric = 'No';
                return $employee;
            });
    }

    public function getTotalRegisteredAndNoneRegisteredEmployees($filter = null)
    {
        if($filter == null){
            return DB::table('employee_profiles as ep')
            ->leftJoin('biometrics as b', 'b.biometric_id', '=', 'ep.biometric_id')
            ->whereNotNull('ep.employee_id')
            ->whereNull('ep.deactivated_at')
            ->where('ep.biometric_id', '>', 0)
            ->selectRaw("CASE 
                WHEN b.biometric IS NOT NULL AND b.biometric != 'NOT_YET_REGISTERED' THEN 'Yes'
                ELSE 'No'
            END AS has_biometric,
            COUNT(*) AS total")
            ->groupBy('has_biometric')
            ->get();
        }

        return DB::table('employee_profiles as ep')
        ->leftJoin('biometrics as b', 'b.biometric_id', '=', 'ep.biometric_id')
        ->whereNotNull('ep.employee_id')
        ->whereNull('ep.deactivated_at')
        ->where('ep.biometric_id', '>', 0)
        ->whereIn('ep.employment_type_id', $filter == 'regular' ? [1,2,3,4] : [5])
        ->selectRaw("CASE 
                WHEN b.biometric IS NOT NULL AND b.biometric != 'NOT_YET_REGISTERED' THEN 'Yes'
                ELSE 'No'
            END AS has_biometric,
            COUNT(*) AS total")
        ->groupBy('has_biometric')
        ->get();
    }
}