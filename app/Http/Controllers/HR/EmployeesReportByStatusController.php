<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Services\HR\ActiveEmployeesService;
use App\Services\HR\EmployeesWithNoBiometricService;
use App\Services\HR\EmployeesWithNoLoginTransactionService;
use App\Http\Resources\HR\EmployeesReportByStatusResource;
use PDF;

ini_set('memory_limit', config('app.memory_limit'));

class EmployeesReportByStatusController extends Controller
{
    public function __construct(
        private ActiveEmployeesService $activeEmployeesService,
        private EmployeesWithNoBiometricService $employeesWithNoBiometricService,
        private EmployeesWithNoLoginTransactionService $employeesWithNoLoginTransactionService
    ){}

    public function activeEmployees()
    {
        return EmployeesReportByStatusResource::collection($this->activeEmployeesService->getActiveEmployees())
            ->additional([
                'message' => "Successfully retrieved active employees"
            ]);
    }

    public function employeesWithNoBiometric()
    {
        return EmployeesReportByStatusResource::collection($this->employeesWithNoBiometricService->getEmployeesWithNoBiometric())
            ->additional([
                'message' => "Successfully retrieved employees with no biometric"
            ]);
    }

    public function employeesWithNoLoginTransaction()
    {
        return EmployeesReportByStatusResource::collection($this->employeesWithNoLoginTransactionService->getEmployeesWithNoLoginTransaction())
            ->additional([
                'message' => "Successfully retrieved employees with no login transaction"
            ]);
    }

    public function totalNumberOfEmployeesPerStatus()
    {
        return response()->json([
            'active' => $this->activeEmployeesService->getActiveEmployees()->count(),
            'employees_with_no_biometric' => $this->employeesWithNoBiometricService->getEmployeesWithNoBiometric()->count(),
            'employees_with_no_login_transaction' => $this->employeesWithNoLoginTransactionService->getEmployeesWithNoLoginTransaction()->count()
        ]);
    }

    public function downloadPdf()
    {
        $activeEmployees = $this->activeEmployeesService->getActiveEmployees();

        $employees = count($activeEmployees);

        $employeesNoBiometric = $this->employeesWithNoBiometricService->getEmployeesWithNoBiometric();

        $employees_no_biometric = [];

        $employeesNoBiometric->each(function ($employee) use (&$employees_no_biometric) {
            $employees_no_biometric[] = [
                'employee_id' => $employee->employee_id,
                'name' => $employee->name(),
                'email' => $employee->personalInformation->contact->email_address,
                'date_hired' => $employee->date_hired,
                'area' => $employee->assignedArea?->findDetails()['details']['name'] ?? 'Not Assigned',
                'created_at' => $employee->created_at,
                'updated_at' => $employee->updated_at,
            ];
        });

        $employeesNoLogin = $this->employeesWithNoLoginTransactionService->getEmployeesWithNoLoginTransaction();

        $employees_no_login = [];

        $employeesNoLogin->each(function ($employee) use (&$employees_no_login) {
            $employees_no_login[] = [
                'employee_id' => $employee->employee_id,
                'name' => $employee->name(),
                'email' => $employee->personalInformation->contact->email_address,
                'date_hired' => $employee->date_hired,
                'area' => $employee->assignedArea?->findDetails()['details']['name'] ?? 'Not Assigned',
                'created_at' => $employee->created_at,
                'updated_at' => $employee->updated_at,
            ];
        });

        $pdf = PDF::loadView('report.employees_list_by_status', compact('employees', 'employees_no_biometric', 'employees_no_login'));
        return $pdf->download('active_employees_list.pdf');
    }
}