<?php

namespace App\Helpers;

use App\Models\AssignArea;
use App\Models\CtoApplication;
use App\Models\Department;
use App\Models\Division;
use App\Models\EmployeeProfile;
use App\Models\EmployeeSchedule;
use App\Models\EmployeeScheduleLog;
use App\Models\ExchangeDutyLog;
use App\Models\Notifications;
use App\Models\LeaveApplication;
use App\Models\OfficialBusiness;
use App\Models\PullOutLog;
use App\Models\OfficialTimeLog;
use App\Models\Schedule;
use App\Models\Section;
use App\Models\SystemLogs;
use App\Models\TimeAdjustmentLog;
use App\Models\TimeShift;
use App\Models\OfficialBusinessLog;
use App\Models\OfficialTime;
use App\Models\Unit;
use Carbon\Carbon;
use DateTime;
use DateInterval;
use DatePeriod;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;


class Helpers
{
    public static function generatePassword()
    {
        $uppercase = self::randomCharacter('ABCDEFGHIJKLMNOPQRSTUVWXYZ');
        $lowercase = self::randomCharacter('abcdefghijklmnopqrstuvwxyz');
        $number = self::randomCharacter('0123456789');
        $special = self::randomCharacter('!@#$%^&*()_+{}[]|');
        $otherChars = self::randomCharacter('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+{}[]|');

        $password = $uppercase . $lowercase . $number . $special . $otherChars;
        $password = str_shuffle($password);

        if (strlen($password) < 8) {
            $password .= self::randomCharacter('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+{}[]|');
        }

        return $password;
    }

    /**
     * Generate a random character from the given string.
     *
     * @param string $characters
     * @return string
     */
    private static function randomCharacter($characters)
    {
        return mb_substr($characters, mt_rand(0, mb_strlen($characters) - 1), 1);
    }

    public static function getHrmoOfficer()
    {
        return Section::where('code', 'HRMO')->first()->supervisor_employee_profile_id;
    }

    public static function getChiefOfficer()
    {
        return Division::where('code', 'OMCC')->first()->chief_employee_profile_id;
    }

    public static function getDivHead($user_area)
    {
        switch ($user_area['sector']) {
            case 'Division':
                return Division::find($user_area['details']['id'])->chief_employee_profile_id;
            case 'Department':
                $department = Department::find($user_area['details']['id']);
                return $department->division->chief_employee_profile_id;
            case 'Section':
                $section = Section::find($user_area['details']['id']);
                if ($section->department_id !== null) {
                    return $section->department->division->chief_employee_profile_id;
                }
                return $section->division->chief_employee_profile_id;

            case 'Unit':
                $unit = Unit::find($user_area['details']['id']);
                if ($unit->section->department_id !== null) {
                    return $unit->section->department->division->chief_employee_profile_id;
                }
                return $unit->section->division->chief_employee_profile_id;
        }

        return null;
    }
    public static function generateMyOTP($employee_profile)
    {
        $otp_code = rand(100000, 999999);
        $otp_expiry = date('Y-m-d H:i:s', strtotime('+5 minutes'));

        $employee_profile->update(['otp' => $otp_code, 'otp_expiration' => $otp_expiry]);

        $body = view('mail.otp', ['otpcode' => $otp_code]);
        $data = [
            'Subject' => 'ONE TIME PIN',
            'To_receiver' => $employee_profile->personalinformation->contact->email_address,
            'Receiver_Name' => $employee_profile->personalInformation->name(),
            'Body' => $body
        ];

        return $data;
    }

    public static function validateOTP($otp, $employee_profile)
    {
        $otpExpirationMinutes = 5;
        $currentDateTime = Carbon::now();
        $employee_otp = $employee_profile->otp;
        $otp_expiration = Carbon::parse($employee_profile->otp_expiration);

        if ($employee_otp === null) {
            return "Please click resend OTP.";
        }

        if ((int) $otp !== $employee_otp) {
            return 'OTP provided is invalid';
        }

        if ($currentDateTime->diffInMinutes($otp_expiration) > $otpExpirationMinutes) {
            return 'OTP has expired.';
        }

        return null;
    }

    public static function getRecommendingAndApprovingOfficer($assigned_area, $employee_profile_id)
    {
        switch ($assigned_area['sector']) {
            case 'Division':
                // If employee is not Division head
                if (Division::find($assigned_area['details']->id)->chief_employee_profile_id === $employee_profile_id) {
                    $chief_officer = Division::where('code', 'OMCC')->first()->chief_employee_profile_id;
                    return [
                        "recommending_officer" => $chief_officer,
                        "approving_officer" => $chief_officer
                    ];
                }

                $division_head = Division::find($assigned_area['details']->id)->chief_employee_profile_id;

                return [
                    "recommending_officer" => $division_head,
                    "approving_officer" => $division_head
                ];

            case 'Department':
                // If employee is Department head
                if (Department::find($assigned_area['details']->id)->head_employee_profile_id === $employee_profile_id) {
                    $division = Department::find($assigned_area['details']->id)->division_id;

                    $division_head = Division::find($division)->chief_employee_profile_id;

                    return [
                        "recommending_officer" => $division_head,
                        "approving_officer" => Helpers::getChiefOfficer()
                    ];
                }

                $department_head = Department::find($assigned_area['details']->id)->head_employee_profile_id;

                return [
                    "recommending_officer" => $department_head,
                    "approving_officer" => Division::where('code', 'OMCC')->chief_employee_profile_id
                ];
            case 'Section':
                // If employee is Section head

                $section = Section::find($assigned_area['details']->id);
                if ($section->division !== null) {
                    $division = $section->division;
                    $department = $section->department;
                    //section head
                    if ($section->supervisor_employee_profile_id === $employee_profile_id) {
                        return [
                            "recommending_officer" => $division->chief_employee_profile_id,
                            "approving_officer" => Helpers::getChiefOfficer(),
                        ];
                    }

                    return [
                        "recommending_officer" => $division->chief_employee_profile_id,
                        "approving_officer" => $division->chief_employee_profile_id,
                    ];
                }
                $department = $section->department;
                //regular
                if ($section->supervisor_employee_profile_id === $employee_profile_id) {
                    return [
                        "recommending_officer" => $department->head_employee_profile_id,
                        "approving_officer" => $department->division->chief_employee_profile_id,
                    ];
                }

                //do not remove this
                return [
                    "recommending_officer" => $section->supervisor_employee_profile_id,
                    "approving_officer" => $department->division->chief_employee_profile_id,
                ];

            case 'Unit':
                // If employee is Unit head
                $section = Unit::find($assigned_area['details']->id)->section;
                // if ($section->department_id !== null) {
                //     $department = $section->department;

                //     return [
                //         "recommending_officer" => $department->head_employee_profile_id,
                //         "approving_officer" => $department->division->chief_employee_profile_id
                //     ];
                // }

                return [
                    "recommending_officer" => $section->supervisor_employee_profile_id,
                    "approving_officer" => $section->department->division->chief_employee_profile_id ?? $section->division->chief_employee_profile_id,
                ];
            default:
                return null;
        }
    }

    public static function getApprovingDTR($assigned_area, $employee_profile)
    {
        $position = $employee_profile->position();

        if ($position !== null) {
            if ($position['area']->code === 'OMCC') {
                return [
                    'id' => null,
                    'name' => null
                ];
            }

            switch ($assigned_area['sector']) {
                case "Division":
                    $omcc = Division::where('code', 'OMCC')->first();
                    return [
                        'id' => $omcc->chief->biometric,
                        'name' => $omcc->chief->personalInformation->name()
                    ];
                case "Department":
                    $division = Department::find($assigned_area['details']->id)->division;
                    return [
                        'id' => $division->chief->biometric,
                        'name' => $division->chief->personalInformation->name()
                    ];
                case "Section":
                    $section = Section::find($assigned_area['details']->id);

                    if ($section->division_id !== null) {
                        $division = $section->division;
                        return [
                            'id' => $division->chief->biometric,
                            'name' => $division->chief->personalInformation->name()
                        ];
                    }

                    $department = $section->department;
                    return [
                        'id' => $department->head->biometric,
                        'name' => $department->head->personalInformation->name()
                    ];
                case "Unit":
                    $section = Unit::find($assigned_area['details']->id)->section;
                    return [
                        'id' => $section->supervisor->biometric,
                        'name' => $section->supervisor->personalInformation->name()
                    ];
            }
        }

        switch ($assigned_area['sector']) {
            case "Division":
                $division = $employee_profile->assignedArea->division;
                return [
                    'id' => $division->chief->biometric,
                    'name' => $division->chief->personalInformation->name()
                ];
            case "Department":
                $department = $employee_profile->assignedArea->department;
                return [
                    'id' => $department->head->biometric,
                    'name' => $department->head->personalInformation->name()
                ];
            case "Section":
                $section = $employee_profile->assignedArea->section;
                return [
                    'id' => $section->supervisor->biometric,
                    'name' => $section->supervisor->personalInformation->name()
                ];
            case "Unit":
                $unit = $employee_profile->assignedArea->unit;
                return [
                    'id' => $unit->head->biometric,
                    'name' => $unit->head->personalInformation->name()
                ];
        }

        return null;
    }

    public static function registerSystemLogs($request, $moduleID, $status, $remarks)
    {
        $ip = $request->ip();
        $user = $request->user;
        $permission = $request->permission;
        list($module, $action) = explode(' ', $permission);

        return [
            'employee_profile_id' => $user->id,
            'module_id' => $moduleID,
            'action' => $action,
            'module' => $module,
            'status' => $status,
            'remarks' => $remarks,
            'ip_address' => $ip
        ];
    }

    public static function registerExchangeDutyLogs($data_id, $user_id, $action)
    {
        ExchangeDutyLog::create([
            'exchange_duty_id' => $data_id,
            'action_by' => $user_id,
            'action' => $action
        ]);
    }

    public static function registerPullOutLogs($data_id, $user_id, $action)
    {
        PullOutLog::create([
            'pull_out_id' => $data_id,
            'action_by' => $user_id,
            'action' => $action
        ]);
    }

    public static function registerOfficialBusinessLogs($data_id, $user_id, $action)
    {
        OfficialBusinessLog::create([
            'official_business_id' => $data_id,
            'action_by' => $user_id,
            'action' => $action
        ]);
    }

    public static function registerOfficialTimeLogs($data_id, $user_id, $action)
    {
        OfficialTimeLog::create([
            'official_time_id' => $data_id,
            'action_by' => $user_id,
            'action' => $action
        ]);
    }

    public static function getDatesInMonth($year, $month, $value)
    {
        $start = new DateTime("{$year}-{$month}-01");
        $end = new DateTime("{$year}-{$month}-" . $start->format('t'));

        $interval = new DateInterval('P1D');
        $period = new DatePeriod($start, $interval, $end->modify('+1 day'));

        $dates = [];

        foreach ($period as $date) {
            switch ($value) {
                case 'Day':
                    $dates[] = $date->format('d');
                    break;

                case 'Week':
                    $dates[] = $date->format('D');
                    break;

                case 'Month':
                    $dates[] = $date->format('m');
                    break;

                case 'Year':
                    $dates[] = $date->format('Y');
                    break;

                case 'Days of Week':
                    $dates[] = $date->format('Y-m-d D');
                    break;

                default:
                    $dates[] = $date->format('Y-m-d');
                    break;
            }
        }

        return $dates;
    }

    public static function checkSaveFile($attachment, $FILE_URL)
    {
        $fileName = '';

        try {
            if ($attachment->isValid()) {
                $file = $attachment;
                $filePath = $file->getRealPath();

                $finfo = new \finfo(FILEINFO_MIME);
                $mime = $finfo->file($filePath);
                $mime = explode(';', $mime)[0];

                $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];

                if (!in_array($mime, $allowedMimeTypes)) {
                    return response()->json(['message' => 'Invalid file type'], Response::HTTP_BAD_REQUEST);
                }

                // Check for potential malicious content
                $fileContent = file_get_contents($filePath);

                if (preg_match('/<\s*script|eval|javascript|vbscript|onload|onerror/i', $fileContent)) {
                    return response()->json(['message' => 'File contains potential malicious content'], Response::HTTP_BAD_REQUEST);
                }

                $file = $attachment;
                $fileName = base64_encode(Hash::make(time())) . '.' . $file->getClientOriginalExtension();

                $file->move(public_path($FILE_URL), $fileName);
            }
        } catch (\Throwable $th) {
            return ['failed', $th->getMessage()];
        }

        return $fileName;
    }

    public static function infoLog($controller, $module, $message)
    {
        Log::channel('custom-info')->info($controller . ' Controller [' . $module . ']: message: ' . $message);
    }

    public static function errorLog($controller, $module, $errorMessage)
    {
        Log::channel('custom-error')->error($controller . ' Controller [' . $module . ']: message: ' . $errorMessage);
    }

    public static function generateCodeFromName($name)
    {
        // Convert name to uppercase and take the first letter of each word
        $words = explode(' ', strtoupper($name));
        $code = '';

        foreach ($words as $word) {
            $code .= substr($word, 0, 1);
        }

        return $code;
    }

    public static function ScheduleApprovingOfficer($area, $user)
    {
        if ($area != null) {
            if ($area['sector'] === 'Division') {
                $division = Division::where('id', $user->assignedArea->division->id)->first();

                if ($division->chief_employee_profile_id !== null) {
                    return ["approving_officer" => $division->chief_employee_profile_id];
                }

                if ($division->oic_employee_profile_id !== null) {
                    return ["approving_officer" => $division->oic_employee_profile_id];
                }
            }

            if ($area['sector'] === 'Department') {
                $department = Department::where('id', $user->assignedArea->department->id)->first();

                if ($department->head_employee_profile_id !== null) {
                    return ["approving_officer" => $department->head_employee_profile_id];
                }

                if ($department->oic_employee_profile_id !== null) {
                    return ["approving_officer" => $department->oic_employee_profile_id];
                }
            }

            if ($area['sector'] === 'Section') {
                $section = Section::where('id', $user->assignedArea->section->id)->first();
                if ($section) {
                    if ($section->department_id !== null) {
                        return ["approving_officer" => Department::where('id', $section->department_id)->first()->head_employee_profile_id];
                    }

                    if ($section->supervisor_employee_profile_id !== null) {
                        return ["approving_officer" => $section->supervisor_employee_profile_id];
                    }

                    if ($section->oic_employee_profile_id !== null) {
                        return ["approving_officer" => $section->oic_employee_profile_id];
                    }
                }
            }

            if ($area['sector'] === 'Unit') {
                $unit = Unit::where('id', $user->assignedArea->unit->id)->first();
                if ($unit) {
                    if ($unit->section_id !== null) {
                        return ["approving_officer" => Section::where('id', $unit->section_id)->first()->supervisor_employee_profile_id];
                    }

                    if ($unit->head_employee_profile_id !== null) {
                        return ["approving_officer" => $unit->head_employee_profile_id];
                    }

                    if ($unit->oic_employee_profile_id !== null) {
                        return ["approving_officer" => $unit->oic_employee_profile_id];
                    }
                }
            }
        }
    }

    public static function ExchangeDutyApproval($assigned_area, $employee_profile_id)
    {
        switch ($assigned_area['sector']) {
            case 'Division':
                $division_head = Division::find($assigned_area['details']['id'])->chief_employee_profile_id;
                return ["approve_by" => $division_head];

            case 'Department':
                $department_head = Department::find($assigned_area['details']['id'])->head_employee_profile_id;
                return ["approve_by" => $department_head];

            case 'Section':
                $section = Section::find($assigned_area['details']['id']);
                if ($section->division !== null) {
                    return ["approve_by" => $section->supervisor_employee_profile_id];
                }

                $department = $section->department;
                return ["approve_by" => $department->head_employee_profile_id];

            case 'Unit':
                $unit = Unit::find($assigned_area['details']['id']);
                if ($unit->section_id !== null) {
                    return ["approve_by" => $unit->head_employee_profile_id];
                }

                $section = $unit->section;
                return ["approve_by" => $section->supervisor_employee_profile_id];
            default:
                return null;
        }
    }

    public static function registerEmployeeScheduleLogs($data_id, $user_id, $action)
    {
        EmployeeScheduleLog::create([
            'employee_schedule_id' => $data_id,
            'action_by' => $user_id,
            'action' => $action,
        ]);
    }

    public static function hashKey($encryptedToken)
    {
        return openssl_decrypt($encryptedToken->token, Cache::get('encrypt_decrypt_algorithm'), Cache::get('app_key'), 0, substr(md5(Cache::get('app_key')), 0, 16));
    }

    public static function Cookie_Name()
    {
        return "ZCMCPortal";
    }

    public static function registerTimeAdjustmentLogs($data_id, $user_id, $action)
    {
        TimeAdjustmentLog::create([
            'time_adjusment_id' => $data_id,
            'action_by' => $user_id,
            'action' => $action,
        ]);
    }

    public static function randomHexColor()
    {
        // Generate a random RGB color
        $red = mt_rand(0, 255);
        $green = mt_rand(0, 255);
        $blue = mt_rand(0, 255);

        // Convert RGB to hex
        $hexColor = sprintf("#%02x%02x%02x", $red, $green, $blue);

        $query = TimeShift::where('color', $hexColor)->exists();

        if (!$query) {
            return $hexColor;
        }
    }

    public static function pendingLeaveNotfication($id, $type)
    {
        Notifications::create([
            "description" => "New " . $type . " request.",
            "module_path" => null,
            "employee_profile_id" => $id
        ]);
    }

    public static function notifications($id, $message)
    {
        Notifications::create([
            "description" => $message,
            "module_path" => null,
            "employee_profile_id" => $id
        ]);
    }

    public static function hasOverlappingRecords($start, $end, $employeeId)
    {

        // Check for overlapping dates in LeaveApplication
        $overlappingLeave = LeaveApplication::where(function ($query) use ($start, $end, $employeeId) {
            $query->where('employee_profile_id', $employeeId)
                ->where(function ($query) use ($start, $end) {
                    $query->whereBetween('date_from', [$start, $end])
                        ->orWhereBetween('date_to', [$start, $end])
                        ->orWhere(function ($query) use ($start, $end) {
                            $query->where('date_from', '<=', $start)
                                ->where('date_to', '>=', $end);
                        });
                });
        })->exists();

        // Check for overlapping dates in OfficialBusiness
        $overlappingOb = OfficialBusiness::where(function ($query) use ($start, $end, $employeeId) {
            $query->where('employee_profile_id', $employeeId)
                ->where(function ($query) use ($start, $end) {
                    $query->whereBetween('date_from', [$start, $end])
                        ->orWhereBetween('date_to', [$start, $end])
                        ->orWhere(function ($query) use ($start, $end) {
                            $query->where('date_from', '<=', $start)
                                ->where('date_to', '>=', $end);
                        });
                });
        })->exists();

        $overlappingOT = OfficialTime::where(function ($query) use ($start, $end, $employeeId) {
            $query->where('employee_profile_id', $employeeId)
                ->where(function ($query) use ($start, $end) {
                    $query->whereBetween('date_from', [$start, $end])
                        ->orWhereBetween('date_to', [$start, $end])
                        ->orWhere(function ($query) use ($start, $end) {
                            $query->where('date_from', '<=', $start)
                                ->where('date_to', '>=', $end);
                        });
                });
        })->exists();

        // Return true if any overlap is found, otherwise false
        return $overlappingLeave || $overlappingOb || $overlappingOT;
    }

    public static function hasOverlappingCTO($date, $employeeId)
    {
        $overlappingLeave = LeaveApplication::where(function ($query) use ($date, $employeeId) {
            $query->where('employee_profile_id', $employeeId)
                ->where(function ($query) use ($date) {
                    $query->where('date_from', '<=', $date)
                        ->where('date_to', '>=', $date);
                });
        })->exists();

        $overlappingOb = OfficialBusiness::where(function ($query) use ($date, $employeeId) {
            $query->where('employee_profile_id', $employeeId)
                ->where(function ($query) use ($date) {
                    $query->where('date_from', '<=', $date)
                        ->where('date_to', '>=', $date);
                });
        })->exists();

        $overlappingOT = OfficialTime::where(function ($query) use ($date, $employeeId) {
            $query->where('employee_profile_id', $employeeId)
                ->where(function ($query) use ($date) {
                    $query->where('date_from', '<=', $date)
                        ->where('date_to', '>=', $date);
                });
        })->exists();

        $overlappingCTO = CtoApplication::where('employee_profile_id', $employeeId)
            ->whereDate('date', $date)
            ->exists();

        return $overlappingLeave || $overlappingOb || $overlappingOT || $overlappingCTO;
    }

    public static function generateSchedule($start_duty, $employee_id)
    {
        $duty_start = new DateTime($start_duty);

        // Get the last day of the duty start month
        $duty_end = new DateTime("last day of " . $duty_start->format('Y-m'));

        // Generate schedule from $duty_start to $duty_end
        $scheduleDates = [];

        while ($duty_start <= $duty_end) {
            // Add duty for $duty_start date to the schedule array
            $scheduleDates[] = $duty_start->format('Y-m-d');

            // Move to the next day
            $duty_start->add(new DateInterval('P1D'));
        }

        // Now, $scheduleDates contains all the dates from $start_duty to the end of the month
        foreach ($scheduleDates as $date) {
            $schedule = Schedule::where('time_shift_id', 1)
                ->where('date', $date)
                ->first();

            if (!$schedule) {
                // Create a new schedule if it doesn't exist
                $isWeekend = Carbon::parse($date)->isWeekend();

                if ($isWeekend === false) {
                    $schedule = new Schedule;
                    $schedule->time_shift_id = 1; // Assuming time_shift_id should be set to 1
                    $schedule->date = $date;
                    $schedule->is_weekend = $isWeekend ? 1 : 0;
                    $schedule->save();
                }
            }

            $schedule->employeeProfile()->attach($employee_id);
        }
    }

    public static function hasSchedule($start, $end, $employeeId)
    {
        $checkSchedule = EmployeeSchedule::where('employee_profile_id', $employeeId)
            ->where(function ($query) use ($start, $end) {
                $query->whereHas('schedule', function ($innerQuery) use ($start, $end) {
                    $innerQuery->whereDate('date', '>=', $start)
                        ->whereDate('date', '<=', $end);
                });
            })
            ->exists();

        return $checkSchedule;
    }
}
