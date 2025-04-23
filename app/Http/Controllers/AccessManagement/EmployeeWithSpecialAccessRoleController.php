<?php

namespace App\Http\Controllers\AccessManagement;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeWithSpecialAccessRoleResource;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\EmployeeProfile;
use Illuminate\Http\Request;

class EmployeeWithSpecialAccessRoleController extends Controller
{
    public function index(Request $request): JsonResource
    {
        $per_page = $request->query('per_page') ?? 10;
        $page = $request->query('page') ?? 1;
        $search = $request->query('search');

        $employees = EmployeeProfile::whereNotNull('employee_id')
            ->whereHas("specialAccessRole")
            ->with(['personalInformation', 'assignedArea.designation', 'specialAccessRole'])
            ->when($search, function ($q) use ($search) {
                $q->whereHas('personalInformation', function ($q) use ($search) {
                    $q->where('last_name', 'like', "$search%")->orWhere('first_name', 'like', "$search%");
                });
            })->paginate(perPage: $per_page, page: $page);

        return EmployeeWithSpecialAccessRoleResource::collection($employees)
            ->additional([
                "meta" => [
                    "methods" => "[GET, POST, PUT, DELETE]"
                ],
                "message" => "Successfully retrieve list of employees with special access role."
            ]);
    } 
}
