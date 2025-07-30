<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Services\HR\ActiveEmployeesService;
use App\Services\HR\EmployeesWithNoBiometricService;
use App\Services\HR\EmployeesWithNoLoginTransactionService;
use App\Http\Resources\HR\EmployeesReportByStatusResource;
use App\Services\HR\EmployeeSummaryReportService;
use Illuminate\Http\Request;
use PDF;

ini_set('memory_limit', config('app.memory_limit'));

class EmployeesReportByStatusController extends Controller
{
    public function __construct(
        private ActiveEmployeesService $activeEmployeesService,
        private EmployeesWithNoBiometricService $employeesWithNoBiometricService,
        private EmployeesWithNoLoginTransactionService $employeesWithNoLoginTransactionService,
        private EmployeeSummaryReportService $employeeSummaryReportService
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
        return $this->employeeSummaryReportService->handle($request);
    }
}