<?php

namespace App\Console\Commands;

use App\Models\AssignAreaTrail;
use App\Models\PlantillaNumber;
use Illuminate\Console\Command;

class PlantillaNumberReAssignTask extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:plantilla-number-re-assign-task:to-run {--taskId} {--area} {--sector}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $taskId = $this->option('taskId');

        // Check if taskId is provided
        if (!$taskId) {
            $this->error('Task ID not provided.');
            return;
        }

        $area = $this->option('area');
        $sector = $this->option('sector');
        $key = null;
        
        switch($sector){
            case 'Division':
                $key = 'division_id';
                break;
            case 'Department':
                $key = 'department_id';
                break;
            case 'Section':
                $key = 'section_id';
                break;
            case 'Unit':
                $key = 'unit_id';
                break;
        }

        $new_area_assign_details = [];
        $areas = ['division_id', 'department_id', 'section_id', 'unit'];

        foreach($areas as $area){
            $new_area_assign_details[$area] = $key !== $area? null: $area;
        }

        $plantilla_number = PlantillaNumber::find($taskId);
        $employee_previous_assign_area = $plantilla_number->employeeProfile->assignedArea;

        $plantilla_number
            ->employeeProfile
            ->assignedArea
            ->update([
                ...$new_area_assign_details, 
                'effective_at' => $plantilla_number->assignedArea->effective_at]);

        $employee_previous_assign_area['started_at'] = $employee_previous_assign_area->effective_at;
        $employee_previous_assign_area['end_at'] = $employee_previous_assign_area->now();

        AssignAreaTrail::create($employee_previous_assign_area);
    }
}
