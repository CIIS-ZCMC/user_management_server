<?php

namespace App\Services\HR;

use App\Repositories\HR\EmployeeRepository;

class EmployeesWithNoBiometricService
{
    public function __construct(
        private EmployeeRepository $employeeRepository
    ){}

    public function getEmployeesWithNoBiometric()
    {
        return $this->employeeRepository->getEmployeesWithNoBiometric();
    }
}
