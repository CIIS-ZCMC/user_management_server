<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Services\HR\ActiveEmployeesService;
use App\Services\HR\EmployeesWithNoBiometricService;
use App\Services\HR\EmployeesWithNoLoginTransactionService;
use App\Http\Resources\HR\EmployeesReportByStatusResource;
use Illuminate\Http\Request;
use PDF;

ini_set('memory_limit', config('app.memory_limit'));

class EmployeesReportByStatusController extends Controller
{
    public function __construct(
        private ActiveEmployeesService $activeEmployeesService,
        private EmployeesWithNoBiometricService $employeesWithNoBiometricService,
        private EmployeesWithNoLoginTransactionService $employeesWithNoLoginTransactionService
    ){}

    public function activeEmployees(Request $request)
    {
        return EmployeesReportByStatusResource::collection($this->activeEmployeesService->getActiveEmployees($request->regularOnly))
            ->additional([
                'message' => "Successfully retrieved active employees"
            ]);
    }

    public function employeesWithNoBiometric(Request $request)
    {
        return EmployeesReportByStatusResource::collection($this->employeesWithNoBiometricService->getEmployeesWithNoBiometric($request->regularOnly))
            ->additional([
                'message' => "Successfully retrieved employees with no biometric"
            ]);
    }

    public function employeesWithNoLoginTransaction(Request $request)
    {
        return EmployeesReportByStatusResource::collection($this->employeesWithNoLoginTransactionService->getEmployeesWithNoLoginTransaction($request->regularOnly))
            ->additional([
                'message' => "Successfully retrieved employees with no login transaction"
            ]);
    }

    public function totalNumberOfEmployeesPerStatus(Request $request)
    {
        return response()->json([
            'active' => $this->activeEmployeesService->getActiveEmployees($request->regularOnly)->count(),
            'employees_with_no_biometric' => $this->employeesWithNoBiometricService->getEmployeesWithNoBiometric($request->regularOnly)->count(),
            'employees_with_no_login_transaction' => $this->employeesWithNoLoginTransactionService->getEmployeesWithNoLoginTransaction($request->regularOnly)->count()
        ]);
    }

    public function downloadPdf(Request $request)
    {
        $activeEmployees = $this->activeEmployeesService->getActiveEmployees($request->regularOnly);

        $employees = count($activeEmployees);

        $employeesNoBiometric = $this->employeesWithNoBiometricService->getEmployeesWithNoBiometric($request->regularOnly);

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

        $employeesNoLogin = $this->employeesWithNoLoginTransactionService->getEmployeesWithNoLoginTransaction($request->regularOnly);

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
        return $pdf->download('Employee Biometric Enrollment Status Report.pdf');
    }
}