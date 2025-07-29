<?php

namespace App\Services\HR;

use App\Repositories\HR\EmployeeRepository;

class ActiveEmployeesService
{
    public function __construct(
        private EmployeeRepository $employeeRepository
    ){}

    public function getActiveEmployees()
    {
        return $this->employeeRepository->getActiveEmployees();
    }
}
