<?php

namespace App\Services\HR;

use App\Repositories\HR\EmployeeRepository;
use App\Services\HR\ActiveEmployeesService;
use App\Services\HR\EmployeesWithNoBiometricService;
use App\Services\HR\EmployeesWithNoLoginTransactionService;
use App\Http\Resources\HR\EmployeesReportByStatusResource;
use PDF;

class EmployeeSummaryReportService
{
    public function __construct(
        private EmployeeRepository $employeeRepository,
        private ActiveEmployeesService $activeEmployeesService,
        private EmployeesWithNoBiometricService $employeesWithNoBiometricService,
    ){}

    public function handle($request)
    {
        $overAll = $this->overAllEmployee($request);
        $regular = $this->regularEmployee($request);
        $jobOrder = $this->jobOrderEmployee($request);
        $medicalDoctors = $this->medicalDoctorsEmployee($request);

        $pdf = PDF::loadView('report.employees_list_by_status', compact('overAll', 'regular', 'jobOrder', 'medicalDoctors'));
        return $pdf->download('Employee Biometric Enrollment Status Report.pdf');
    }

    protected function overAllEmployee($request)
    {
        $employees = $this->employeeRepository->activeEmployee()->count();
        $employeesWithBiometric = $this->activeEmployeesService->getActiveEmployees()->count();
        $employeesNoBiometric = $this->employeesWithNoBiometricService->getEmployeesWithNoBiometric();

        $employees_no_biometric = $this->sortEmployeesNoBiometric($employeesNoBiometric, $request);
        $totalRegisteredAndNoneRegisteredEmployees = $this->employeeRepository->getTotalRegisteredAndNoneRegisteredEmployees();

        $total_with_no_biometric = $totalRegisteredAndNoneRegisteredEmployees->where('has_biometric', 'No')->first();
        $total_with_biometric = $totalRegisteredAndNoneRegisteredEmployees->where('has_biometric', 'Yes')->first();

        return [
            'employees' => $employees,
            'employeesWithBiometric' => $employeesWithBiometric,
            'employeesNoBiometric' => $employees_no_biometric,
            'total_with_no_biometric' => $total_with_no_biometric,
            'total_with_biometric' => $total_with_biometric
        ];
    }

    protected function regularEmployee($request)
    {
        $employees = $this->employeeRepository->activeEmployee('regular')->count();
        $employeesWithBiometric = $this->activeEmployeesService->getActiveEmployees('regular')->count();
        $employeesNoBiometric = $this->employeesWithNoBiometricService->getEmployeesWithNoBiometric('regular');

        $employees_no_biometric = $this->sortEmployeesNoBiometric($employeesNoBiometric, $request);
        $totalRegisteredAndNoneRegisteredEmployees = $this->employeeRepository->getTotalRegisteredAndNoneRegisteredEmployees('regular');

        $total_with_no_biometric = $totalRegisteredAndNoneRegisteredEmployees->where('has_biometric', 'No')->first();
        $total_with_biometric = $totalRegisteredAndNoneRegisteredEmployees->where('has_biometric', 'Yes')->first();

        return [
            'employees' => $employees,
            'employeesWithBiometric' => $employeesWithBiometric,
            'employeesNoBiometric' => $employees_no_biometric,
            'total_with_no_biometric' => $total_with_no_biometric,
            'total_with_biometric' => $total_with_biometric
        ];
    }

    protected function medicalDoctorsEmployee($request)
    {
        $employees = $this->employeeRepository->activeEmployee('medical_doctors')->count();
        $employeesWithBiometric = $this->activeEmployeesService->getActiveEmployees('medical_doctors')->count();
        $employeesNoBiometric = $this->employeesWithNoBiometricService->getMedicalDoctorsWithNoBiometric();

        $employees_no_biometric = $this->sortEmployeesNoBiometric($employeesNoBiometric, $request);
        $totalRegisteredAndNoneRegisteredEmployees = $this->employeeRepository->getTotalRegisteredAndNoneRegisteredEmployees('medical_doctors');
        
        \Log::info(json_encode($employees_no_biometric, JSON_PRETTY_PRINT));

        $total_with_no_biometric = $totalRegisteredAndNoneRegisteredEmployees->where('has_biometric', 'No')->first();
        $total_with_biometric = $totalRegisteredAndNoneRegisteredEmployees->where('has_biometric', 'Yes')->first();

        return [
            'employees' => $employees,
            'employeesWithBiometric' => $employeesWithBiometric,
            'employeesNoBiometric' => $employees_no_biometric,
            'total_with_no_biometric' => $total_with_no_biometric,
            'total_with_biometric' => $total_with_biometric
        ];
    }

    protected function jobOrderEmployee($request)
    {
        $employees = $this->employeeRepository->activeEmployee('job_order')->count();
        $employeesWithBiometric = $this->activeEmployeesService->getActiveEmployees('job_order')->count();
        $employeesNoBiometric = $this->employeesWithNoBiometricService->getEmployeesWithNoBiometric('job_order');

        $employees_no_biometric = $this->sortEmployeesNoBiometric($employeesNoBiometric, $request);
        $totalRegisteredAndNoneRegisteredEmployees = $this->employeeRepository->getTotalRegisteredAndNoneRegisteredEmployees('job_order');

        $total_with_no_biometric = $totalRegisteredAndNoneRegisteredEmployees->where('has_biometric', 'No')->first();
        $total_with_biometric = $totalRegisteredAndNoneRegisteredEmployees->where('has_biometric', 'Yes')->first();

        return [
            'employees' => $employees,
            'employeesWithBiometric' => $employeesWithBiometric,
            'employeesNoBiometric' => $employees_no_biometric,
            'total_with_no_biometric' => $total_with_no_biometric,
            'total_with_biometric' => $total_with_biometric
        ];
    }

    protected function sortEmployeesNoBiometric($employeesNoBiometric, $request)
    {
        $employees_no_biometric = EmployeesReportByStatusResource::collection($employeesNoBiometric)->toArray($request);

        $employees_no_biometric = collect($employees_no_biometric)
            ->sortBy('area', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();   

        return $employees_no_biometric;
    }

    protected function totalEmployeeWithLoginTransaction()
    {
        
    }
}
