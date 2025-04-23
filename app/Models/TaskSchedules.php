<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskSchedules extends Model
{
    use HasFactory;

    protected $table = 'task_schedules';

    public $fillable = [
        'action',
        'effective_at',
        'end_at',
        'employee_profile_id',
        'candidate_employee',
        'task_run',
        'task_complete'
    ];

    public $timestamps = TRUE;

    public function employee()
    {
        return $this->belongsTo(EmployeeProfile::class);
    }

    public function candidate()
    {
        return $this->belongsTo(EmployeeProfile::class, 'candidate_employee');
    }

    public function start()
    {
        if ($this->action === 'OIC') {
            $head = $this->employee;
            $assigned_area = $head->findDetails();
            $candidate = $this->candidate;

            switch ($assigned_area['sector']) {
                case "Division":
                    $division = Division::find($assigned_area['details']->id);
                    if (!$this->end_at->isSameAs(Carbon::now())) {
                        $this->registerTrail(['division_id' => $division->id]);
                        return true;
                    }
                    $division->update([
                        'oic_employee_profile_id' => $candidate->id,
                        'oic_effective_at' => $this->effective_at,
                        'oic_end_at' => $this->end_at
                    ]);
                    return true;
                case "Department":
                    $department = Department::find($assigned_area['details']->id);
                    if (!$this->end_at->isSameAs(Carbon::now())) {
                        $this->registerTrail(['department_id' => $department->id]);
                        return true;
                    }
                    $department->update([
                        'oic_employee_profile_id' => $candidate->id,
                        'oic_effective_at' => $this->effective_at,
                        'oic_end_at' => $this->end_at
                    ]);
                    return true;
                case "Section":
                    $section = Section::find($assigned_area['details']->id);
                    if (!$this->end_at->isSameAs(Carbon::now())) {
                        $this->registerTrail(['section_id' => $section->id]);
                        return true;
                    }
                    $section->update([
                        'oic_employee_profile_id' => $candidate->id,
                        'oic_effective_at' => $this->effective_at,
                        'oic_end_at' => $this->end_at
                    ]);
                    return true;
                case "Unit":
                    $unit = Unit::find($assigned_area['details']->id);
                    if (!$this->end_at->isSameAs(Carbon::now())) {
                        $this->registerTrail(['unit_id' => $unit->id]);
                        return true;
                    }
                    $unit->update([
                        'oic_employee_profile_id' => $candidate->id,
                        'oic_effective_at' => $this->effective_at,
                        'oic_end_at' => $this->end_at
                    ]);
                    return true;
            }
        }

        return false;
    }

    public function end()
    {
        if ($this->action === 'OIC') {
            $head = $this->employee;
            $assigned_area = $head->findDetails();
            $candidate = $this->candidate;
            $role = null;

            switch ($assigned_area['sector']) {
                case "Division":
                    $division = Division::find($assigned_area['details']->id);
                    $division->update([
                        'oic_employee_profile_id' => null,
                        'oic_effective_at' => null,
                        'oic_end_at' => null
                    ]);
                    $this->registerTrail(['division_id' => $division->id]);
                    $role = Role::where('code', 'OMCC-01')->first();
                    break;
                case "Department":
                    $department = Department::find($assigned_area['details']->id);
                    $department->update([
                        'oic_employee_profile_id' => null,
                        'oic_effective_at' => null,
                        'oic_end_at' => null
                    ]);
                    $this->registerTrail(['department_id' => $department->id]);

                    $role = Role::where('code', 'DEPT-HEAD-01')->first();
                    break;
                case "Section":
                    $section = Section::find($assigned_area['details']->id);
                    $section->update([
                        'oic_employee_profile_id' => null,
                        'oic_effective_at' => null,
                        'oic_end_at' => null
                    ]);
                    $this->registerTrail(['section_id' => $section->id]);

                    if ($section->area_id === 'HOPPS-HRMO-DE-001') {
                        $role = Role::where('code', 'HRMO-HEAD-01')->first();
                        break;
                    }

                    $role = Role::where('code', 'SECTION-HEAD-01')->first();
                    break;
                case "Unit":
                    $unit = Unit::find($assigned_area['details']->id);
                    $unit->update([
                        'oic_employee_profile_id' => null,
                        'oic_effective_at' => null,
                        'oic_end_at' => null
                    ]);
                    $this->registerTrail(['unit_id' => $unit->id]);
                    $role = Role::where('code', 'UNIT-HEAD-01')->first();
                    break;
            }

            $system_role = SystemRole::where('role_id', $role->id)->first();
            $special_access = SpecialAccessRole::where('system_role_id', $system_role->id)
                ->where('employee_profile_id', $candidate->id)->first();

            $special_access->delete();
            return true;
        }

        return false;
    }

    public function registerTrail($area)
    {
        OfficerInChargeTrail::create([
            'employee_profile_id' => $this->candidate_employee,
            ...$area,
            'started_at' => $this->effective_at,
            'ended_at' => $this->end_at
        ]);
    }
}
