<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Services\HR\ActiveEmployeesService;
use App\Services\HR\EmployeesWithNoBiometricService;
use App\Services\HR\EmployeesWithNoLoginTransactionService;
use App\Http\Resources\HR\EmployeesReportByStatusResource;

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
}