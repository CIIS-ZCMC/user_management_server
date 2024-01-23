<?php

namespace App\Http\Controllers\LeaveAndOvertime;

use App\Http\Resources\OfficialBusinessResource;
use App\Http\Requests\OfficialBusinessRequest;
use App\Helpers\Helpers;

use App\Models\Department;
use App\Models\Division;
use App\Models\OfficialBusiness;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Http\Response;


class OfficialBusinessController extends Controller
{
    private $CONTROLLER_NAME = 'Official Business';
    private $PLURAL_MODULE_NAME = 'official businesses';
    private $SINGULAR_MODULE_NAME = 'official business';

    /**
     * Display a listing of the resource.   
     */
    public function index(Request $request)
    {
        try {
            
            return response()->json(['data' => OfficialBusinessResource::collection(OfficialBusiness::all())], Response::HTTP_OK);

        } catch (\Throwable $th) {
            
            Helpers::errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        try {

            $user = $request->user;
            $sql = OfficialBusiness::where('employee_profile_id', $user->id)->get();
            return response()->json(['data' => OfficialBusinessResource::collection($sql)], Response::HTTP_OK);

        } catch (\Throwable $th) {
            
            Helpers::errorLog($this->CONTROLLER_NAME,'index', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(OfficialBusinessRequest $request)
    {
        try {
            $cleanData = [];

            foreach ($request->all() as $key => $value) {
                if (empty($value)) {
                    $cleanData[$key] = $value;
                    continue;
                }

                if (is_int($value)) {
                    $cleanData[$key] = $value;
                    continue;
                }

                if ($request->hasFile($key)) {
                    $file = $request->file($key);   
                    $cleanData[$key] = $file;
                    continue;
                }

                $cleanData[$key] = strip_tags($value);
            }
           
            $user                   = $request->user;
            $assigned_area          = $user->assignedArea->findDetails();
            $approving_officer      = Division::where('code', 'OMCC')->first()->chief_employee_profile_id;
            $recommending_officer   = null;

            switch($assigned_area['sector']){
                case 'Division':
                    // If employee is Division head
                    if(Division::find($assigned_area['details']['id'])->chief_employee_profile_id === $user->id){
                        $recommending_officer = $user->id;
                        break;
                    }
                    $recommending_officer = Division::find($assigned_area['details']['id'])->chief_employee_profile_id;
                    break;
                case 'Department':
                    // If employee is Department head
                    if(Department::find($assigned_area['details']['id'])->head_employee_profile_id === $user->id){
                        $recommending_officer = Department::find($assigned_area['details']['id'])->division->chief_employee_profile_id;
                        break;
                    }
                    $recommending_officer = Department::find($assigned_area['details']['id'])->head_employee_profile_id;
                    break;
                case 'Section':
                    // If employee is Section head
                    $section = Section::find($assigned_area['details']['id']);
                    if($section->supervisor_employee_profile_id === $user->id){
                        if($section->division_id !== null){
                            $recommending_officer = Division::find($section->division_id)->chief_employee_profile_id;
                            break;
                        }
                        $recommending_officer = Department::find($section->department_id)->head_employee_profile_id;
                        break;
                    }
                    $recommending_officer = $section->supervisor_employee_profile_id;
                    break;
                case 'Unit':
                    // If employee is Unit head
                    $section = Unit::find($assigned_area->details->id)->section;
                    $recommending_officer = $section->supervisor_employee_profile_id;
                    break;
                default:
                    return response()->json(['message' => 'Invalid request'], Response::HTTP_BAD_REQUEST);
            }

            $data = new OfficialBusiness;

            $data->employee_profile_id              = $user->id;
            $data->date_from                        = $cleanData['date_from'];
            $data->date_to                          = $cleanData['date_to'];
            $data->time_from                        = $cleanData['time_from'];
            $data->time_to                          = $cleanData['time_to'];
            $data->purpose                          = $cleanData['purpose'];
            $data->personal_order_file              = $cleanData['personal_order_file']->getClientOriginalName();;
            $data->personal_order_path              = $cleanData['personal_order_file']->store('public/official_business');
            $data->personal_order_size              = $cleanData['personal_order_file']->getSize();
            $data->certificate_of_appearance        = $cleanData['certificate_of_appearance']->getClientOriginalName();;
            $data->certificate_of_appearance_path   = $cleanData['certificate_of_appearance']->store('public/official_business');
            $data->certificate_of_appearance_size   = $cleanData['certificate_of_appearance']->getSize();
            $data->approving_officer                = $approving_officer;
            $data->recommending_officer             = $recommending_officer;
            $data->save();

            Helpers::registerSystemLogs($request, $data->id, true, 'Success in storing '.$this->PLURAL_MODULE_NAME.'.');
            Helpers::registerOfficialBusinessLogs($data->id, $user['id'], 'store');
            return response()->json(['data' =>OfficialBusinessResource::collection(OfficialBusiness::where('id', $data->id)->get())], Response::HTTP_OK);

        } catch (\Throwable $th) {

            Helpers::errorLog($this->CONTROLLER_NAME,'store', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {            
            $data = OfficialBusiness::findOrFail($id);

            if(!$data) {
                return response()->json(['message' => 'No record found.'], Response::HTTP_NOT_FOUND);
            }

            foreach ($request->all() as $key => $value) {
                if (empty($value)) {
                    $cleanData[$key] = $value;
                    continue;
                }

                $cleanData[$key] = strip_tags($value);
            }

            $user   = $request->user;
            $status = null;

            if ($cleanData['status'] === 'declined') {
                $status = 'declined';
            } else {
                switch ($data->status) {
                    case 'applied':
                    $status = 'for recommending approval';
                    break;

                    case 'for recommending approval':
                        $status = 'for approving approval';
                    break;

                    case 'for approving approval':
                        $status = 'approved';
                    break;
                    
                    default:
                        $status = 'declined';
                    break;
                }
            }

            $data->update(['status' => $status]);

            Helpers::registerSystemLogs($request, $id, true, 'Success in updating '.$this->SINGULAR_MODULE_NAME.'.');
            Helpers::registerOfficialBusinessLogs($data->id, $user['id'], 'update');
            return response()->json(['data' =>OfficialBusinessResource::collection(OfficialBusiness::where('id', $data->id)->get())], Response::HTTP_OK);

        } catch (\Throwable $th) {
            //throw $th;
            
            Helpers::errorLog($this->CONTROLLER_NAME,'update', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
