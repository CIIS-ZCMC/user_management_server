<?php

namespace App\Services\HR;

use App\Repositories\HR\EmployeeRepository;

class EmployeesWithNoLoginTransactionService
{
    public function __construct(
        private EmployeeRepository $employeeRepository
    ){}

    public function getEmployeesWithNoLoginTransaction($regularOnly)
    {
        return $this->employeeRepository->getEmployeesWithNoLoginTransaction($regularOnly);
    }
}