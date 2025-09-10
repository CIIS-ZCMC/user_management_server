<?php

namespace App\Services\HR;

use App\Repositories\HR\EmployeeRepository;

class EmployeesWithNoBiometricService
{
    public function __construct(
        private EmployeeRepository $employeeRepository
    ){}

    public function getEmployeesWithNoBiometric($filter = null)
    {
        return $this->employeeRepository->getEmployeesWithNoBiometric($filter);
    }

    public function getMedicalDoctorsWithNoBiometric()
    {
        return $this->employeeRepository->getMedicalDoctorsWithNoBiometric();
    }
}
