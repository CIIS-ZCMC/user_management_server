<?php

namespace App\Http\Controllers\Reports;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Helpers\Helpers;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use Symfony\Component\HttpFoundation\Response as ResponseAlias;

/**
 * Class AttendanceReportController
 * @package App\Http\Controllers\Reports
 *
 * Controller for handling attendance reports.
 */
class AttendanceReportController extends Controller
{
    private string $CONTROLLER_NAME = "Employee Attendance Reports";

    private function baseQuery($employment_type, $designation_id): Builder
    {
        return DB::table('assigned_areas as a')
            ->when($designation_id, function ($query, $designation_id) {
                return $query->where('a.designation_id', $designation_id);
            })
            ->leftJoin('employee_profiles as ep', 'a.employee_profile_id', '=', 'ep.id')
            ->when($employment_type, function ($query, $employment_type) {
                return $query->where('ep.employment_type_id', $employment_type);
            })
            ->leftJoin('personal_informations as pi', 'ep.personal_information_id', '=', 'pi.id')
            ->leftJoin('designations as des', 'a.designation_id', '=', 'des.id')
            ->leftJoin('employment_types as et', 'ep.employment_type_id', '=', 'et.id')
            ->leftJoin('divisions as d', 'a.division_id', '=', 'd.id')
            ->leftJoin('departments as dept', 'a.department_id', '=', 'dept.id')
            ->leftJoin('sections as s', 'a.section_id', '=', 's.id')
            ->leftJoin('units as u', 'a.unit_id', '=', 'u.id')
            ->leftJoin('biometrics as b', 'ep.biometric_id', '=', 'b.biometric_id')
            ->leftJoin('daily_time_records as dtr', function ($join) {
                $join->on('b.biometric_id', '=', 'dtr.biometric_id');
            })
            ->leftJoin('employee_profile_schedule as eps', 'ep.id', '=', 'eps.employee_profile_id')
            ->leftJoin('schedules as sch', 'eps.schedule_id', '=', 'sch.id')
            ->leftJoin('time_shifts as ts', 'sch.time_shift_id', '=', 'ts.id')
            ->leftJoin('cto_applications as cto', function ($join) {
                $join->on('ep.id', '=', 'cto.employee_profile_id')
                    ->where('cto.status', '=', 'approved')
                    ->whereRaw('DATE(cto.date) = DATE(sch.date)');
            })
            ->leftJoin('official_business_applications as oba', function ($join) {
                $join->on('ep.id', '=', 'oba.employee_profile_id')
                    ->where('oba.status', '=', 'approved')
                    ->whereBetween('sch.date', [DB::raw('oba.date_from'), DB::raw('oba.date_to')]);
            })
            ->leftJoin('leave_applications as la', function ($join) {
                $join->on('ep.id', '=', 'la.employee_profile_id')
                    ->where('la.status', '=', 'approved')
                    ->whereBetween(DB::raw('sch.date'), [DB::raw('DATE(la.date_from)'), DB::raw('DATE(la.date_to)')]);
            })
            ->leftJoin('official_time_applications as ota', function ($join) {
                $join->on('ep.id', '=', 'ota.employee_profile_id')
                    ->where('ota.status', '=', 'approved')
                    ->whereBetween(DB::raw('sch.date'), [DB::raw('ota.date_from'), DB::raw('ota.date_to')]);
            })
            ->whereNotNull('ep.biometric_id') // Ensure the employee has biometric data
            ->whereNull('ep.deactivated_at')
            ->where('ep.personal_information_id', '<>', 1)
            ->select(
                'ep.id',
                'ep.employee_id',
                'ep.biometric_id',
                'des.name as employee_designation_name',
                'des.code as employee_designation_code',
                'et.name as employment_type_name',
                DB::raw("CONCAT(
                        pi.first_name, ' ',
                        IF(pi.middle_name IS NOT NULL AND pi.middle_name != '', CONCAT(SUBSTRING(pi.middle_name, 1, 1), '. '), ''),
                        pi.last_name,
                        IF(pi.name_extension IS NOT NULL AND pi.name_extension != '', CONCAT(' ', pi.name_extension), ' '),
                        IF(pi.name_title IS NOT NULL AND pi.name_title != '', CONCAT(', ', pi.name_title), ' ')
                    ) as employee_name"),
                DB::raw('COALESCE(u.name, s.name, dept.name, d.name) as employee_area_name'),
                DB::raw('COALESCE(u.code, s.code, dept.code, d.code) as employee_area_code')
            );
    }

    private function baseQueryByPeriod($month_of, $year_of, $first_half, $second_half, $employment_type, $designation_id): Builder
    {
        return DB::table('assigned_areas as a')
            ->when($designation_id, function ($query, $designation_id) {
                return $query->where('a.designation_id', $designation_id);
            })
            ->leftJoin('employee_profiles as ep', 'a.employee_profile_id', '=', 'ep.id')
            ->when($employment_type, function ($query, $employment_type) {
                return $query->where('ep.employment_type_id', $employment_type);
            })
            ->leftJoin('personal_informations as pi', 'ep.personal_information_id', '=', 'pi.id')
            ->leftJoin('designations as des', 'a.designation_id', '=', 'des.id')
            ->leftJoin('employment_types as et', 'ep.employment_type_id', '=', 'et.id')
            ->leftJoin('divisions as d', 'a.division_id', '=', 'd.id')
            ->leftJoin('departments as dept', 'a.department_id', '=', 'dept.id')
            ->leftJoin('sections as s', 'a.section_id', '=', 's.id')
            ->leftJoin('units as u', 'a.unit_id', '=', 'u.id')
            ->leftJoin('biometrics as b', 'ep.biometric_id', '=', 'b.biometric_id')
            ->leftJoin('daily_time_records as dtr', function ($join) use ($month_of, $year_of, $first_half, $second_half) {
                $join->on('b.biometric_id', '=', 'dtr.biometric_id')
                    ->whereMonth('dtr.dtr_date', '=', $month_of)
                    ->whereYear('dtr.dtr_date', '=', $year_of);
                if ($first_half) {
                    $join->whereDay('dtr.dtr_date', '<=', 15);
                }
                if ($second_half) {
                    $join->whereDay('dtr.dtr_date', '>', 15);
                }
            })
            ->leftJoin('employee_profile_schedule as eps', 'ep.id', '=', 'eps.employee_profile_id')
            ->leftJoin('schedules as sch', 'eps.schedule_id', '=', 'sch.id')
            ->leftJoin('time_shifts as ts', 'sch.time_shift_id', '=', 'ts.id')
            ->leftJoin('cto_applications as cto', function ($join) {
                $join->on('ep.id', '=', 'cto.employee_profile_id')
                    ->where('cto.status', '=', 'approved')
                    ->whereRaw('DATE(cto.date) = DATE(sch.date)');
            })
            ->leftJoin('official_business_applications as oba', function ($join) {
                $join->on('ep.id', '=', 'oba.employee_profile_id')
                    ->where('oba.status', '=', 'approved')
                    ->whereBetween('sch.date', [DB::raw('oba.date_from'), DB::raw('oba.date_to')]);
            })
            ->leftJoin('leave_applications as la', function ($join) {
                $join->on('ep.id', '=', 'la.employee_profile_id')
                    ->where('la.status', '=', 'approved')
                    ->whereBetween(DB::raw('sch.date'), [DB::raw('DATE(la.date_from)'), DB::raw('DATE(la.date_to)')]);
            })
            ->leftJoin('official_time_applications as ota', function ($join) {
                $join->on('ep.id', '=', 'ota.employee_profile_id')
                    ->where('ota.status', '=', 'approved')
                    ->whereBetween(DB::raw('sch.date'), [DB::raw('ota.date_from'), DB::raw('ota.date_to')]);
            })
            ->whereNotNull('ep.biometric_id') // Ensure the employee has biometric data
            ->whereNull('ep.deactivated_at')
            ->where('ep.personal_information_id', '<>', 1)
            ->select(
                'ep.id',
                'ep.employee_id',
                'ep.biometric_id',
                'des.name as employee_designation_name',
                'des.code as employee_designation_code',
                'et.name as employment_type_name',
                DB::raw("CONCAT(
                        pi.first_name, ' ',
                        IF(pi.middle_name IS NOT NULL AND pi.middle_name != '', CONCAT(SUBSTRING(pi.middle_name, 1, 1), '. '), ''),
                        pi.last_name,
                        IF(pi.name_extension IS NOT NULL AND pi.name_extension != '', CONCAT(' ', pi.name_extension), ' '),
                        IF(pi.name_title IS NOT NULL AND pi.name_title != '', CONCAT(', ', pi.name_title), ' ')
                    ) as employee_name"),
                DB::raw('COALESCE(u.name, s.name, dept.name, d.name) as employee_area_name'),
                DB::raw('COALESCE(u.code, s.code, dept.code, d.code) as employee_area_code')
            );
    }

    private function baseQueryByDateRange($start_date, $end_date, $employment_type, $designation_id): Builder
    {
        return DB::table('assigned_areas as a')
            ->when($designation_id, function ($query, $designation_id) {
                return $query->where('a.designation_id', $designation_id);
            })
            ->leftJoin('employee_profiles as ep', 'a.employee_profile_id', '=', 'ep.id')
            ->when($employment_type, function ($query, $employment_type) {
                return $query->where('ep.employment_type_id', $employment_type);
            })
            ->leftJoin('personal_informations as pi', 'ep.personal_information_id', '=', 'pi.id')
            ->leftJoin('designations as des', 'a.designation_id', '=', 'des.id')
            ->leftJoin('employment_types as et', 'ep.employment_type_id', '=', 'et.id')
            ->leftJoin('divisions as d', 'a.division_id', '=', 'd.id')
            ->leftJoin('departments as dept', 'a.department_id', '=', 'dept.id')
            ->leftJoin('sections as s', 'a.section_id', '=', 's.id')
            ->leftJoin('units as u', 'a.unit_id', '=', 'u.id')
            ->leftJoin('biometrics as b', 'ep.biometric_id', '=', 'b.biometric_id')
            ->leftJoin('daily_time_records as dtr', function ($join) use ($start_date, $end_date) {
                $join->on('b.biometric_id', '=', 'dtr.biometric_id')
                    ->whereBetween('dtr.dtr_date', [$start_date, $end_date]);
            })
            ->leftJoin('employee_profile_schedule as eps', 'ep.id', '=', 'eps.employee_profile_id')
            ->leftJoin('schedules as sch', 'eps.schedule_id', '=', 'sch.id')
            ->leftJoin('time_shifts as ts', 'sch.time_shift_id', '=', 'ts.id')
            ->leftJoin('cto_applications as cto', function ($join) {
                $join->on('ep.id', '=', 'cto.employee_profile_id')
                    ->where('cto.status', '=', 'approved')
                    ->whereRaw('DATE(cto.date) = DATE(sch.date)');
            })
            ->leftJoin('official_business_applications as oba', function ($join) {
                $join->on('ep.id', '=', 'oba.employee_profile_id')
                    ->where('oba.status', '=', 'approved')
                    ->whereBetween('sch.date', [DB::raw('oba.date_from'), DB::raw('oba.date_to')]);
            })
            ->leftJoin('leave_applications as la', function ($join) {
                $join->on('ep.id', '=', 'la.employee_profile_id')
                    ->where('la.status', '=', 'approved')
                    ->whereBetween(DB::raw('sch.date'), [DB::raw('DATE(la.date_from)'), DB::raw('DATE(la.date_to)')]);
            })
            ->leftJoin('official_time_applications as ota', function ($join) {
                $join->on('ep.id', '=', 'ota.employee_profile_id')
                    ->where('ota.status', '=', 'approved')
                    ->whereBetween(DB::raw('sch.date'), [DB::raw('ota.date_from'), DB::raw('ota.date_to')]);
            })
            ->whereNotNull('ep.biometric_id') // Ensure the employee has biometric data
            ->whereNull('ep.deactivated_at')
            ->where('ep.personal_information_id', '<>', 1)
            ->select(
                'ep.id',
                'ep.employee_id',
                'ep.biometric_id',
                'des.name as employee_designation_name',
                'des.code as employee_designation_code',
                'et.name as employment_type_name',
                DB::raw("CONCAT(
                        pi.first_name, ' ',
                        IF(pi.middle_name IS NOT NULL AND pi.middle_name != '', CONCAT(SUBSTRING(pi.middle_name, 1, 1), '. '), ''),
                        pi.last_name,
                        IF(pi.name_extension IS NOT NULL AND pi.name_extension != '', CONCAT(' ', pi.name_extension), ' '),
                        IF(pi.name_title IS NOT NULL AND pi.name_title != '', CONCAT(', ', pi.name_title), ' ')
                    ) as employee_name"),
                DB::raw('COALESCE(u.name, s.name, dept.name, d.name) as employee_area_name'),
                DB::raw('COALESCE(u.code, s.code, dept.code, d.code) as employee_area_code')
            );
    }

    private function baseQueryDivision($area_id, $area_under, $employment_type, $designation_id): Builder
    {
        return DB::table('assigned_areas as a')
            ->when($designation_id, function ($query, $designation_id) {
                return $query->where('a.designation_id', $designation_id);
            })
            ->leftJoin('employee_profiles as ep', 'a.employee_profile_id', '=', 'ep.id')
            ->when($employment_type, function ($query, $employment_type) {
                return $query->where('ep.employment_type_id', $employment_type);
            })
            ->leftJoin('personal_informations as pi', 'ep.personal_information_id', '=', 'pi.id')
            ->leftJoin('designations as des', 'a.designation_id', '=', 'des.id')
            ->leftJoin('employment_types as et', 'ep.employment_type_id', '=', 'et.id')
            ->leftJoin('divisions as d', 'a.division_id', '=', 'd.id')
            ->leftJoin('departments as dept', 'a.department_id', '=', 'dept.id')
            ->leftJoin('sections as s', 'a.section_id', '=', 's.id')
            ->leftJoin('units as u', 'a.unit_id', '=', 'u.id')
            ->leftJoin('biometrics as b', 'ep.biometric_id', '=', 'b.biometric_id')
            ->leftJoin('daily_time_records as dtr', function ($join) {
                $join->on('b.biometric_id', '=', 'dtr.biometric_id');
            })
            ->leftJoin('employee_profile_schedule as eps', 'ep.id', '=', 'eps.employee_profile_id')
            ->leftJoin('schedules as sch', 'eps.schedule_id', '=', 'sch.id')
            ->leftJoin('time_shifts as ts', 'sch.time_shift_id', '=', 'ts.id')
            ->leftJoin('cto_applications as cto', function ($join) {
                $join->on('ep.id', '=', 'cto.employee_profile_id')
                    ->where('cto.status', '=', 'approved')
                    ->whereRaw('DATE(cto.date) = DATE(sch.date)');
            })
            ->leftJoin('official_business_applications as oba', function ($join) {
                $join->on('ep.id', '=', 'oba.employee_profile_id')
                    ->where('oba.status', '=', 'approved')
                    ->whereBetween('sch.date', [DB::raw('oba.date_from'), DB::raw('oba.date_to')]);
            })
            ->leftJoin('leave_applications as la', function ($join) {
                $join->on('ep.id', '=', 'la.employee_profile_id')
                    ->where('la.status', '=', 'approved')
                    ->whereBetween(DB::raw('sch.date'), [DB::raw('DATE(la.date_from)'), DB::raw('DATE(la.date_to)')]);
            })
            ->leftJoin('official_time_applications as ota', function ($join) {
                $join->on('ep.id', '=', 'ota.employee_profile_id')
                    ->where('ota.status', '=', 'approved')
                    ->whereBetween(DB::raw('sch.date'), [DB::raw('ota.date_from'), DB::raw('ota.date_to')]);
            })
            ->where(function ($query) use ($area_id, $area_under) {
                switch ($area_under) {
                    case 'all':
                        $query->where('a.division_id', $area_id)
                            ->orWhereIn('a.department_id', function ($query) use ($area_id) {
                                $query->select('id')
                                    ->from('departments')
                                    ->where('division_id', $area_id);
                            })
                            ->orWhereIn('a.section_id', function ($query) use ($area_id) {
                                $query->select('id')
                                    ->from('sections')
                                    ->where('division_id', $area_id)
                                    ->orWhereIn('department_id', function ($query) use ($area_id) {
                                        $query->select('id')
                                            ->from('departments')
                                            ->where('division_id', $area_id);
                                    });
                            })
                            ->orWhereIn('a.unit_id', function ($query) use ($area_id) {
                                $query->select('id')
                                    ->from('units')
                                    ->whereIn('section_id', function ($query) use ($area_id) {
                                        $query->select('id')
                                            ->from('sections')
                                            ->where('division_id', $area_id)
                                            ->orWhereIn('department_id', function ($query) use ($area_id) {
                                                $query->select('id')
                                                    ->from('departments')
                                                    ->where('division_id', $area_id);
                                            });
                                    });
                            });
                        break;
                    case 'under':
                        $query->where('a.division_id', $area_id);
                        break;
                    default:
                        $query->whereRaw('1 = 0'); // Ensures no results are returned for unknown `area_under` values
                }
            })
            ->whereNotNull('ep.biometric_id') // Ensure the employee has biometric data
            ->whereNull('ep.deactivated_at')
            ->where('ep.personal_information_id', '<>', 1)
            ->select(
                'ep.id',
                'ep.employee_id',
                'ep.biometric_id',
                'des.name as employee_designation_name',
                'des.code as employee_designation_code',
                'et.name as employment_type_name',
                DB::raw("CONCAT(
                        pi.first_name, ' ',
                        IF(pi.middle_name IS NOT NULL AND pi.middle_name != '', CONCAT(SUBSTRING(pi.middle_name, 1, 1), '. '), ''),
                        pi.last_name,
                        IF(pi.name_extension IS NOT NULL AND pi.name_extension != '', CONCAT(' ', pi.name_extension), ' '),
                        IF(pi.name_title IS NOT NULL AND pi.name_title != '', CONCAT(', ', pi.name_title), ' ')
                    ) as employee_name"),
                DB::raw('COALESCE(u.name, s.name, dept.name, d.name) as employee_area_name'),
                DB::raw('COALESCE(u.code, s.code, dept.code, d.code) as employee_area_code')
            );
    }

    private function baseQueryDivisionByPeriod($month_of, $year_of, $first_half, $second_half, $area_id, $area_under, $employment_type, $designation_id): Builder
    {
        return DB::table('assigned_areas as a')
            ->when($designation_id, function ($query, $designation_id) {
                return $query->where('a.designation_id', $designation_id);
            })
            ->leftJoin('employee_profiles as ep', 'a.employee_profile_id', '=', 'ep.id')
            ->when($employment_type, function ($query, $employment_type) {
                return $query->where('ep.employment_type_id', $employment_type);
            })
            ->leftJoin('personal_informations as pi', 'ep.personal_information_id', '=', 'pi.id')
            ->leftJoin('designations as des', 'a.designation_id', '=', 'des.id')
            ->leftJoin('employment_types as et', 'ep.employment_type_id', '=', 'et.id')
            ->leftJoin('divisions as d', 'a.division_id', '=', 'd.id')
            ->leftJoin('departments as dept', 'a.department_id', '=', 'dept.id')
            ->leftJoin('sections as s', 'a.section_id', '=', 's.id')
            ->leftJoin('units as u', 'a.unit_id', '=', 'u.id')
            ->leftJoin('biometrics as b', 'ep.biometric_id', '=', 'b.biometric_id')
            ->leftJoin('daily_time_records as dtr', function ($join) use ($month_of, $year_of, $first_half, $second_half) {
                $join->on('b.biometric_id', '=', 'dtr.biometric_id')
                    ->whereMonth('dtr.dtr_date', '=', $month_of)
                    ->whereYear('dtr.dtr_date', '=', $year_of);
                if ($first_half) {
                    $join->whereDay('dtr.dtr_date', '<=', 15);
                }
                if ($second_half) {
                    $join->whereDay('dtr.dtr_date', '>', 15);
                }
            })
            ->leftJoin('employee_profile_schedule as eps', 'ep.id', '=', 'eps.employee_profile_id')
            ->leftJoin('schedules as sch', 'eps.schedule_id', '=', 'sch.id')
            ->leftJoin('time_shifts as ts', 'sch.time_shift_id', '=', 'ts.id')
            ->leftJoin('cto_applications as cto', function ($join) {
                $join->on('ep.id', '=', 'cto.employee_profile_id')
                    ->where('cto.status', '=', 'approved')
                    ->whereRaw('DATE(cto.date) = DATE(sch.date)');
            })
            ->leftJoin('official_business_applications as oba', function ($join) {
                $join->on('ep.id', '=', 'oba.employee_profile_id')
                    ->where('oba.status', '=', 'approved')
                    ->whereBetween('sch.date', [DB::raw('oba.date_from'), DB::raw('oba.date_to')]);
            })
            ->leftJoin('leave_applications as la', function ($join) {
                $join->on('ep.id', '=', 'la.employee_profile_id')
                    ->where('la.status', '=', 'approved')
                    ->whereBetween(DB::raw('sch.date'), [DB::raw('DATE(la.date_from)'), DB::raw('DATE(la.date_to)')]);
            })
            ->leftJoin('official_time_applications as ota', function ($join) {
                $join->on('ep.id', '=', 'ota.employee_profile_id')
                    ->where('ota.status', '=', 'approved')
                    ->whereBetween(DB::raw('sch.date'), [DB::raw('ota.date_from'), DB::raw('ota.date_to')]);
            })
            ->where(function ($query) use ($area_id, $area_under) {
                switch ($area_under) {
                    case 'all':
                        $query->where('a.division_id', $area_id)
                            ->orWhereIn('a.department_id', function ($query) use ($area_id) {
                                $query->select('id')
                                    ->from('departments')
                                    ->where('division_id', $area_id);
                            })
                            ->orWhereIn('a.section_id', function ($query) use ($area_id) {
                                $query->select('id')
                                    ->from('sections')
                                    ->where('division_id', $area_id)
                                    ->orWhereIn('department_id', function ($query) use ($area_id) {
                                        $query->select('id')
                                            ->from('departments')
                                            ->where('division_id', $area_id);
                                    });
                            })
                            ->orWhereIn('a.unit_id', function ($query) use ($area_id) {
                                $query->select('id')
                                    ->from('units')
                                    ->whereIn('section_id', function ($query) use ($area_id) {
                                        $query->select('id')
                                            ->from('sections')
                                            ->where('division_id', $area_id)
                                            ->orWhereIn('department_id', function ($query) use ($area_id) {
                                                $query->select('id')
                                                    ->from('departments')
                                                    ->where('division_id', $area_id);
                                            });
                                    });
                            });
                        break;
                    case 'under':
                        $query->where('a.division_id', $area_id);
                        break;
                    default:
                        $query->whereRaw('1 = 0'); // Ensures no results are returned for unknown `area_under` values
                }
            })
            ->whereNotNull('ep.biometric_id') // Ensure the employee has biometric data
            ->whereNull('ep.deactivated_at')
            ->where('ep.personal_information_id', '<>', 1)
            ->select(
                'ep.id',
                'ep.employee_id',
                'ep.biometric_id',
                'des.name as employee_designation_name',
                'des.code as employee_designation_code',
                'et.name as employment_type_name',
                DB::raw("CONCAT(
                        pi.first_name, ' ',
                        IF(pi.middle_name IS NOT NULL AND pi.middle_name != '', CONCAT(SUBSTRING(pi.middle_name, 1, 1), '. '), ''),
                        pi.last_name,
                        IF(pi.name_extension IS NOT NULL AND pi.name_extension != '', CONCAT(' ', pi.name_extension), ' '),
                        IF(pi.name_title IS NOT NULL AND pi.name_title != '', CONCAT(', ', pi.name_title), ' ')
                    ) as employee_name"),
                DB::raw('COALESCE(u.name, s.name, dept.name, d.name) as employee_area_name'),
                DB::raw('COALESCE(u.code, s.code, dept.code, d.code) as employee_area_code')
            );
    }

    private function baseQueryDivisionByDateRange($start_date, $end_date, $area_id, $area_under, $employment_type, $designation_id)
    {
        return DB::table('assigned_areas as a')
            ->when($designation_id, function ($query, $designation_id) {
                return $query->where('a.designation_id', $designation_id);
            })
            ->leftJoin('employee_profiles as ep', 'a.employee_profile_id', '=', 'ep.id')
            ->when($employment_type, function ($query, $employment_type) {
                return $query->where('ep.employment_type_id', $employment_type);
            })
            ->leftJoin('personal_informations as pi', 'ep.personal_information_id', '=', 'pi.id')
            ->leftJoin('designations as des', 'a.designation_id', '=', 'des.id')
            ->leftJoin('employment_types as et', 'ep.employment_type_id', '=', 'et.id')
            ->leftJoin('divisions as d', 'a.division_id', '=', 'd.id')
            ->leftJoin('departments as dept', 'a.department_id', '=', 'dept.id')
            ->leftJoin('sections as s', 'a.section_id', '=', 's.id')
            ->leftJoin('units as u', 'a.unit_id', '=', 'u.id')
            ->leftJoin('biometrics as b', 'ep.biometric_id', '=', 'b.biometric_id')
            ->leftJoin('daily_time_records as dtr', function ($join) use ($start_date, $end_date) {
                $join->on('b.biometric_id', '=', 'dtr.biometric_id')
                    ->whereBetween('dtr.dtr_date', [$start_date, $end_date]);
            })
            ->leftJoin('employee_profile_schedule as eps', 'ep.id', '=', 'eps.employee_profile_id')
            ->leftJoin('schedules as sch', 'eps.schedule_id', '=', 'sch.id')
            ->leftJoin('time_shifts as ts', 'sch.time_shift_id', '=', 'ts.id')
            ->leftJoin('cto_applications as cto', function ($join) {
                $join->on('ep.id', '=', 'cto.employee_profile_id')
                    ->where('cto.status', '=', 'approved')
                    ->whereRaw('DATE(cto.date) = DATE(sch.date)');
            })
            ->leftJoin('official_business_applications as oba', function ($join) {
                $join->on('ep.id', '=', 'oba.employee_profile_id')
                    ->where('oba.status', '=', 'approved')
                    ->whereBetween('sch.date', [DB::raw('oba.date_from'), DB::raw('oba.date_to')]);
            })
            ->leftJoin('leave_applications as la', function ($join) {
                $join->on('ep.id', '=', 'la.employee_profile_id')
                    ->where('la.status', '=', 'approved')
                    ->whereBetween(DB::raw('sch.date'), [DB::raw('DATE(la.date_from)'), DB::raw('DATE(la.date_to)')]);
            })
            ->leftJoin('official_time_applications as ota', function ($join) {
                $join->on('ep.id', '=', 'ota.employee_profile_id')
                    ->where('ota.status', '=', 'approved')
                    ->whereBetween(DB::raw('sch.date'), [DB::raw('ota.date_from'), DB::raw('ota.date_to')]);
            })
            ->where(function ($query) use ($area_id, $area_under) {
                switch ($area_under) {
                    case 'all':
                        $query->where('a.division_id', $area_id)
                            ->orWhereIn('a.department_id', function ($query) use ($area_id) {
                                $query->select('id')
                                    ->from('departments')
                                    ->where('division_id', $area_id);
                            })
                            ->orWhereIn('a.section_id', function ($query) use ($area_id) {
                                $query->select('id')
                                    ->from('sections')
                                    ->where('division_id', $area_id)
                                    ->orWhereIn('department_id', function ($query) use ($area_id) {
                                        $query->select('id')
                                            ->from('departments')
                                            ->where('division_id', $area_id);
                                    });
                            })
                            ->orWhereIn('a.unit_id', function ($query) use ($area_id) {
                                $query->select('id')
                                    ->from('units')
                                    ->whereIn('section_id', function ($query) use ($area_id) {
                                        $query->select('id')
                                            ->from('sections')
                                            ->where('division_id', $area_id)
                                            ->orWhereIn('department_id', function ($query) use ($area_id) {
                                                $query->select('id')
                                                    ->from('departments')
                                                    ->where('division_id', $area_id);
                                            });
                                    });
                            });
                        break;
                    case 'under':
                        $query->where('a.division_id', $area_id);
                        break;
                    default:
                        $query->whereRaw('1 = 0'); // Ensures no results are returned for unknown `area_under` values
                }
            })
            ->whereNotNull('ep.biometric_id') // Ensure the employee has biometric data
            ->whereNull('ep.deactivated_at')
            ->where('ep.personal_information_id', '<>', 1)
            ->select(
                'ep.id',
                'ep.employee_id',
                'ep.biometric_id',
                'des.name as employee_designation_name',
                'des.code as employee_designation_code',
                'et.name as employment_type_name',
                DB::raw("CONCAT(
                        pi.first_name, ' ',
                        IF(pi.middle_name IS NOT NULL AND pi.middle_name != '', CONCAT(SUBSTRING(pi.middle_name, 1, 1), '. '), ''),
                        pi.last_name,
                        IF(pi.name_extension IS NOT NULL AND pi.name_extension != '', CONCAT(' ', pi.name_extension), ' '),
                        IF(pi.name_title IS NOT NULL AND pi.name_title != '', CONCAT(', ', pi.name_title), ' ')
                    ) as employee_name"),
                DB::raw('COALESCE(u.name, s.name, dept.name, d.name) as employee_area_name'),
                DB::raw('COALESCE(u.code, s.code, dept.code, d.code) as employee_area_code')
            );
    }

    private function baseQueryDepartment($area_id, $area_under, $employment_type, $designation_id): Builder
    {
        return DB::table('assigned_areas as a')
            ->when($designation_id, function ($query, $designation_id) {
                return $query->where('a.designation_id', $designation_id);
            })
            ->leftJoin('employee_profiles as ep', 'a.employee_profile_id', '=', 'ep.id')
            ->when($employment_type, function ($query, $employment_type) {
                return $query->where('ep.employment_type_id', $employment_type);
            })
            ->leftJoin('personal_informations as pi', 'ep.personal_information_id', '=', 'pi.id')
            ->leftJoin('designations as des', 'a.designation_id', '=', 'des.id')
            ->leftJoin('employment_types as et', 'ep.employment_type_id', '=', 'et.id')
            ->leftJoin('divisions as d', 'a.division_id', '=', 'd.id')
            ->leftJoin('departments as dept', 'a.department_id', '=', 'dept.id')
            ->leftJoin('sections as s', 'a.section_id', '=', 's.id')
            ->leftJoin('units as u', 'a.unit_id', '=', 'u.id')
            ->leftJoin('biometrics as b', 'ep.biometric_id', '=', 'b.biometric_id')
            ->leftJoin('daily_time_records as dtr', function ($join) {
                $join->on('b.biometric_id', '=', 'dtr.biometric_id');
            })
            ->leftJoin('employee_profile_schedule as eps', 'ep.id', '=', 'eps.employee_profile_id')
            ->leftJoin('schedules as sch', 'eps.schedule_id', '=', 'sch.id')
            ->leftJoin('time_shifts as ts', 'sch.time_shift_id', '=', 'ts.id')
            ->leftJoin('cto_applications as cto', function ($join) {
                $join->on('ep.id', '=', 'cto.employee_profile_id')
                    ->where('cto.status', '=', 'approved')
                    ->whereRaw('DATE(cto.date) = DATE(sch.date)');
            })
            ->leftJoin('official_business_applications as oba', function ($join) {
                $join->on('ep.id', '=', 'oba.employee_profile_id')
                    ->where('oba.status', '=', 'approved')
                    ->whereBetween('sch.date', [DB::raw('oba.date_from'), DB::raw('oba.date_to')]);
            })
            ->leftJoin('leave_applications as la', function ($join) {
                $join->on('ep.id', '=', 'la.employee_profile_id')
                    ->where('la.status', '=', 'approved')
                    ->whereBetween(DB::raw('sch.date'), [DB::raw('DATE(la.date_from)'), DB::raw('DATE(la.date_to)')]);
            })
            ->leftJoin('official_time_applications as ota', function ($join) {
                $join->on('ep.id', '=', 'ota.employee_profile_id')
                    ->where('ota.status', '=', 'approved')
                    ->whereBetween(DB::raw('sch.date'), [DB::raw('ota.date_from'), DB::raw('ota.date_to')]);
            })
            ->where(function ($query) use ($area_id, $area_under) {
                switch ($area_under) {
                    case 'all':
                        $query->where('a.department_id', $area_id)
                            ->orWhereIn('a.section_id', function ($query) use ($area_id) {
                                $query->select('id')->from('sections')->where('department_id', $area_id);
                            })->orWhereIn('a.unit_id', function ($query) use ($area_id) {
                                $query->select('id')
                                    ->from('units')
                                    ->whereIn('section_id', function ($query) use ($area_id) {
                                        $query->select('id')
                                            ->from('sections')
                                            ->where('department_id', $area_id)
                                            ->orWhereIn('department_id', function ($query) use ($area_id) {
                                                $query->select('id')
                                                    ->from('departments')
                                                    ->where('department_id', $area_id);
                                            });
                                    });
                            });
                        break;
                    case 'under':
                        $query->where(function ($query) use ($area_id) {
                            $query->where('a.division_id', $area_id);
                        });
                        break;
                    default:
                        $query->whereRaw('1 = 0'); // Ensures no results are returned for unknown `area_under` values
                }
            })
            ->whereNotNull('ep.biometric_id') // Ensure the employee has biometric data
            ->whereNull('ep.deactivated_at')
            ->where('ep.personal_information_id', '<>', 1)
            ->select(
                'ep.id',
                'ep.employee_id',
                'ep.biometric_id',
                'des.name as employee_designation_name',
                'des.code as employee_designation_code',
                'et.name as employment_type_name',
                DB::raw("CONCAT(
                        pi.first_name, ' ',
                        IF(pi.middle_name IS NOT NULL AND pi.middle_name != '', CONCAT(SUBSTRING(pi.middle_name, 1, 1), '. '), ''),
                        pi.last_name,
                        IF(pi.name_extension IS NOT NULL AND pi.name_extension != '', CONCAT(' ', pi.name_extension), ' '),
                        IF(pi.name_title IS NOT NULL AND pi.name_title != '', CONCAT(', ', pi.name_title), ' ')
                    ) as employee_name"),
                DB::raw('COALESCE(u.name, s.name, dept.name, d.name) as employee_area_name'),
                DB::raw('COALESCE(u.code, s.code, dept.code, d.code) as employee_area_code')
            );
    }

    private function baseQueryDepartmentByPeriod($month_of, $year_of, $first_half, $second_half, $area_id, $area_under, $employment_type, $designation_id): Builder
    {
        return DB::table('assigned_areas as a')
            ->when($designation_id, function ($query, $designation_id) {
                return $query->where('a.designation_id', $designation_id);
            })
            ->leftJoin('employee_profiles as ep', 'a.employee_profile_id', '=', 'ep.id')
            ->when($employment_type, function ($query, $employment_type) {
                return $query->where('ep.employment_type_id', $employment_type);
            })
            ->leftJoin('personal_informations as pi', 'ep.personal_information_id', '=', 'pi.id')
            ->leftJoin('designations as des', 'a.designation_id', '=', 'des.id')
            ->leftJoin('employment_types as et', 'ep.employment_type_id', '=', 'et.id')
            ->leftJoin('divisions as d', 'a.division_id', '=', 'd.id')
            ->leftJoin('departments as dept', 'a.department_id', '=', 'dept.id')
            ->leftJoin('sections as s', 'a.section_id', '=', 's.id')
            ->leftJoin('units as u', 'a.unit_id', '=', 'u.id')
            ->leftJoin('biometrics as b', 'ep.biometric_id', '=', 'b.biometric_id')
            ->leftJoin('daily_time_records as dtr', function ($join) use ($month_of, $year_of, $first_half, $second_half) {
                $join->on('b.biometric_id', '=', 'dtr.biometric_id')
                    ->whereMonth('dtr.dtr_date', '=', $month_of)
                    ->whereYear('dtr.dtr_date', '=', $year_of);
                if ($first_half) {
                    $join->whereDay('dtr.dtr_date', '<=', 15);
                }
                if ($second_half) {
                    $join->whereDay('dtr.dtr_date', '>', 15);
                }
            })
            ->leftJoin('employee_profile_schedule as eps', 'ep.id', '=', 'eps.employee_profile_id')
            ->leftJoin('schedules as sch', 'eps.schedule_id', '=', 'sch.id')
            ->leftJoin('time_shifts as ts', 'sch.time_shift_id', '=', 'ts.id')
            ->leftJoin('cto_applications as cto', function ($join) {
                $join->on('ep.id', '=', 'cto.employee_profile_id')
                    ->where('cto.status', '=', 'approved')
                    ->whereRaw('DATE(cto.date) = DATE(sch.date)');
            })
            ->leftJoin('official_business_applications as oba', function ($join) {
                $join->on('ep.id', '=', 'oba.employee_profile_id')
                    ->where('oba.status', '=', 'approved')
                    ->whereBetween('sch.date', [DB::raw('oba.date_from'), DB::raw('oba.date_to')]);
            })
            ->leftJoin('leave_applications as la', function ($join) {
                $join->on('ep.id', '=', 'la.employee_profile_id')
                    ->where('la.status', '=', 'approved')
                    ->whereBetween(DB::raw('sch.date'), [DB::raw('DATE(la.date_from)'), DB::raw('DATE(la.date_to)')]);
            })
            ->leftJoin('official_time_applications as ota', function ($join) {
                $join->on('ep.id', '=', 'ota.employee_profile_id')
                    ->where('ota.status', '=', 'approved')
                    ->whereBetween(DB::raw('sch.date'), [DB::raw('ota.date_from'), DB::raw('ota.date_to')]);
            })
            ->where(function ($query) use ($area_id, $area_under) {
                switch ($area_under) {
                    case 'all':
                        $query->where('a.department_id', $area_id)
                            ->orWhereIn('a.section_id', function ($query) use ($area_id) {
                                $query->select('id')->from('sections')->where('department_id', $area_id);
                            })->orWhereIn('a.unit_id', function ($query) use ($area_id) {
                                $query->select('id')
                                    ->from('units')
                                    ->whereIn('section_id', function ($query) use ($area_id) {
                                        $query->select('id')
                                            ->from('sections')
                                            ->where('department_id', $area_id)
                                            ->orWhereIn('department_id', function ($query) use ($area_id) {
                                                $query->select('id')
                                                    ->from('departments')
                                                    ->where('department_id', $area_id);
                                            });
                                    });
                            });
                        break;
                    case 'under':
                        $query->where(function ($query) use ($area_id) {
                            $query->where('a.division_id', $area_id);
                        });
                        break;
                    default:
                        $query->whereRaw('1 = 0'); // Ensures no results are returned for unknown `area_under` values
                }
            })
            ->whereNotNull('ep.biometric_id') // Ensure the employee has biometric data
            ->whereNull('ep.deactivated_at')
            ->where('ep.personal_information_id', '<>', 1)
            ->select(
                'ep.id',
                'ep.employee_id',
                'ep.biometric_id',
                'des.name as employee_designation_name',
                'des.code as employee_designation_code',
                'et.name as employment_type_name',
                DB::raw("CONCAT(
                        pi.first_name, ' ',
                        IF(pi.middle_name IS NOT NULL AND pi.middle_name != '', CONCAT(SUBSTRING(pi.middle_name, 1, 1), '. '), ''),
                        pi.last_name,
                        IF(pi.name_extension IS NOT NULL AND pi.name_extension != '', CONCAT(' ', pi.name_extension), ' '),
                        IF(pi.name_title IS NOT NULL AND pi.name_title != '', CONCAT(', ', pi.name_title), ' ')
                    ) as employee_name"),
                DB::raw('COALESCE(u.name, s.name, dept.name, d.name) as employee_area_name'),
                DB::raw('COALESCE(u.code, s.code, dept.code, d.code) as employee_area_code')
            );
    }

    private function baseQueryDepartmentByDateRange($start_date, $end_date, $area_id, $area_under, $employment_type, $designation_id)
    {
        return DB::table('assigned_areas as a')
            ->when($designation_id, function ($query, $designation_id) {
                return $query->where('a.designation_id', $designation_id);
            })
            ->leftJoin('employee_profiles as ep', 'a.employee_profile_id', '=', 'ep.id')
            ->when($employment_type, function ($query, $employment_type) {
                return $query->where('ep.employment_type_id', $employment_type);
            })
            ->leftJoin('personal_informations as pi', 'ep.personal_information_id', '=', 'pi.id')
            ->leftJoin('designations as des', 'a.designation_id', '=', 'des.id')
            ->leftJoin('employment_types as et', 'ep.employment_type_id', '=', 'et.id')
            ->leftJoin('divisions as d', 'a.division_id', '=', 'd.id')
            ->leftJoin('departments as dept', 'a.department_id', '=', 'dept.id')
            ->leftJoin('sections as s', 'a.section_id', '=', 's.id')
            ->leftJoin('units as u', 'a.unit_id', '=', 'u.id')
            ->leftJoin('biometrics as b', 'ep.biometric_id', '=', 'b.biometric_id')
            ->leftJoin('daily_time_records as dtr', function ($join) use ($start_date, $end_date) {
                $join->on('b.biometric_id', '=', 'dtr.biometric_id')
                    ->whereBetween('dtr.dtr_date', [$start_date, $end_date]);
            })
            ->leftJoin('employee_profile_schedule as eps', 'ep.id', '=', 'eps.employee_profile_id')
            ->leftJoin('schedules as sch', 'eps.schedule_id', '=', 'sch.id')
            ->leftJoin('time_shifts as ts', 'sch.time_shift_id', '=', 'ts.id')
            ->leftJoin('cto_applications as cto', function ($join) {
                $join->on('ep.id', '=', 'cto.employee_profile_id')
                    ->where('cto.status', '=', 'approved')
                    ->whereRaw('DATE(cto.date) = DATE(sch.date)');
            })
            ->leftJoin('official_business_applications as oba', function ($join) {
                $join->on('ep.id', '=', 'oba.employee_profile_id')
                    ->where('oba.status', '=', 'approved')
                    ->whereBetween('sch.date', [DB::raw('oba.date_from'), DB::raw('oba.date_to')]);
            })
            ->leftJoin('leave_applications as la', function ($join) {
                $join->on('ep.id', '=', 'la.employee_profile_id')
                    ->where('la.status', '=', 'approved')
                    ->whereBetween(DB::raw('sch.date'), [DB::raw('DATE(la.date_from)'), DB::raw('DATE(la.date_to)')]);
            })
            ->leftJoin('official_time_applications as ota', function ($join) {
                $join->on('ep.id', '=', 'ota.employee_profile_id')
                    ->where('ota.status', '=', 'approved')
                    ->whereBetween(DB::raw('sch.date'), [DB::raw('ota.date_from'), DB::raw('ota.date_to')]);
            })
            ->where(function ($query) use ($area_id, $area_under) {
                switch ($area_under) {
                    case 'all':
                        $query->where('a.department_id', $area_id)
                            ->orWhereIn('a.section_id', function ($query) use ($area_id) {
                                $query->select('id')->from('sections')->where('department_id', $area_id);
                            })->orWhereIn('a.unit_id', function ($query) use ($area_id) {
                                $query->select('id')
                                    ->from('units')
                                    ->whereIn('section_id', function ($query) use ($area_id) {
                                        $query->select('id')
                                            ->from('sections')
                                            ->where('department_id', $area_id)
                                            ->orWhereIn('department_id', function ($query) use ($area_id) {
                                                $query->select('id')
                                                    ->from('departments')
                                                    ->where('department_id', $area_id);
                                            });
                                    });
                            });
                        break;
                    case 'under':
                        $query->where(function ($query) use ($area_id) {
                            $query->where('a.division_id', $area_id);
                        });
                        break;
                    default:
                        $query->whereRaw('1 = 0'); // Ensures no results are returned for unknown `area_under` values
                }
            })
            ->whereNotNull('ep.biometric_id') // Ensure the employee has biometric data
            ->whereNull('ep.deactivated_at')
            ->where('ep.personal_information_id', '<>', 1)
            ->select(
                'ep.id',
                'ep.employee_id',
                'ep.biometric_id',
                'des.name as employee_designation_name',
                'des.code as employee_designation_code',
                'et.name as employment_type_name',
                DB::raw("CONCAT(
                        pi.first_name, ' ',
                        IF(pi.middle_name IS NOT NULL AND pi.middle_name != '', CONCAT(SUBSTRING(pi.middle_name, 1, 1), '. '), ''),
                        pi.last_name,
                        IF(pi.name_extension IS NOT NULL AND pi.name_extension != '', CONCAT(' ', pi.name_extension), ' '),
                        IF(pi.name_title IS NOT NULL AND pi.name_title != '', CONCAT(', ', pi.name_title), ' ')
                    ) as employee_name"),
                DB::raw('COALESCE(u.name, s.name, dept.name, d.name) as employee_area_name'),
                DB::raw('COALESCE(u.code, s.code, dept.code, d.code) as employee_area_code')
            );
    }

    private function baseQuerySection($area_id, $area_under, $employment_type, $designation_id): Builder
    {
        return DB::table('assigned_areas as a')
            ->when($designation_id, function ($query, $designation_id) {
                return $query->where('a.designation_id', $designation_id);
            })
            ->leftJoin('employee_profiles as ep', 'a.employee_profile_id', '=', 'ep.id')
            ->when($employment_type, function ($query, $employment_type) {
                return $query->where('ep.employment_type_id', $employment_type);
            })
            ->leftJoin('personal_informations as pi', 'ep.personal_information_id', '=', 'pi.id')
            ->leftJoin('designations as des', 'a.designation_id', '=', 'des.id')
            ->leftJoin('employment_types as et', 'ep.employment_type_id', '=', 'et.id')
            ->leftJoin('divisions as d', 'a.division_id', '=', 'd.id')
            ->leftJoin('departments as dept', 'a.department_id', '=', 'dept.id')
            ->leftJoin('sections as s', 'a.section_id', '=', 's.id')
            ->leftJoin('units as u', 'a.unit_id', '=', 'u.id')
            ->leftJoin('biometrics as b', 'ep.biometric_id', '=', 'b.biometric_id')
            ->leftJoin('daily_time_records as dtr', function ($join) {
                $join->on('b.biometric_id', '=', 'dtr.biometric_id');
            })
            ->leftJoin('employee_profile_schedule as eps', 'ep.id', '=', 'eps.employee_profile_id')
            ->leftJoin('schedules as sch', 'eps.schedule_id', '=', 'sch.id')
            ->leftJoin('time_shifts as ts', 'sch.time_shift_id', '=', 'ts.id')
            ->leftJoin('cto_applications as cto', function ($join) {
                $join->on('ep.id', '=', 'cto.employee_profile_id')
                    ->where('cto.status', '=', 'approved')
                    ->whereRaw('DATE(cto.date) = DATE(sch.date)');
            })
            ->leftJoin('official_business_applications as oba', function ($join) {
                $join->on('ep.id', '=', 'oba.employee_profile_id')
                    ->where('oba.status', '=', 'approved')
                    ->whereBetween('sch.date', [DB::raw('oba.date_from'), DB::raw('oba.date_to')]);
            })
            ->leftJoin('leave_applications as la', function ($join) {
                $join->on('ep.id', '=', 'la.employee_profile_id')
                    ->where('la.status', '=', 'approved')
                    ->whereBetween(DB::raw('sch.date'), [DB::raw('DATE(la.date_from)'), DB::raw('DATE(la.date_to)')]);
            })
            ->leftJoin('official_time_applications as ota', function ($join) {
                $join->on('ep.id', '=', 'ota.employee_profile_id')
                    ->where('ota.status', '=', 'approved')
                    ->whereBetween(DB::raw('sch.date'), [DB::raw('ota.date_from'), DB::raw('ota.date_to')]);
            })
            ->where(function ($query) use ($area_id, $area_under) {
                switch ($area_under) {
                    case 'all':
                        $query->where('a.section_id', $area_id)
                            ->orWhereIn('unit_id', function ($query) use ($area_id) {
                                $query->select('id')
                                    ->from('units')
                                    ->whereIn('section_id', function ($query) use ($area_id) {
                                        $query->select('id')
                                            ->from('sections')
                                            ->where('section_id', $area_id)
                                            ->orWhereIn('section_id', function ($query) use ($area_id) {
                                                $query->select('id')
                                                    ->from('sections')
                                                    ->where('section_id', $area_id);
                                            });
                                    });
                            });
                        break;
                    case 'under':
                        $query->where(function ($query) use ($area_id) {
                            $query->where('a.section_id', $area_id);
                        });
                        break;
                    default:
                        $query->whereRaw('1 = 0'); // Ensures no results are returned for unknown `area_under` values
                }
            })
            ->whereNotNull('ep.biometric_id') // Ensure the employee has biometric data
            ->whereNull('ep.deactivated_at')
            ->where('ep.personal_information_id', '<>', 1);
    }

    private function baseQuerySectionByPeriod($month_of, $year_of, $first_half, $second_half, $area_id, $area_under, $employment_type, $designation_id): Builder
    {
        return DB::table('assigned_areas as a')
            ->when($designation_id, function ($query, $designation_id) {
                return $query->where('a.designation_id', $designation_id);
            })
            ->leftJoin('employee_profiles as ep', 'a.employee_profile_id', '=', 'ep.id')
            ->when($employment_type, function ($query, $employment_type) {
                return $query->where('ep.employment_type_id', $employment_type);
            })
            ->leftJoin('personal_informations as pi', 'ep.personal_information_id', '=', 'pi.id')
            ->leftJoin('designations as des', 'a.designation_id', '=', 'des.id')
            ->leftJoin('employment_types as et', 'ep.employment_type_id', '=', 'et.id')
            ->leftJoin('divisions as d', 'a.division_id', '=', 'd.id')
            ->leftJoin('departments as dept', 'a.department_id', '=', 'dept.id')
            ->leftJoin('sections as s', 'a.section_id', '=', 's.id')
            ->leftJoin('units as u', 'a.unit_id', '=', 'u.id')
            ->leftJoin('biometrics as b', 'ep.biometric_id', '=', 'b.biometric_id')
            ->leftJoin('daily_time_records as dtr', function ($join) use ($month_of, $year_of, $first_half, $second_half) {
                $join->on('b.biometric_id', '=', 'dtr.biometric_id')
                    ->whereMonth('dtr.dtr_date', '=', $month_of)
                    ->whereYear('dtr.dtr_date', '=', $year_of);
                if ($first_half) {
                    $join->whereDay('dtr.dtr_date', '<=', 15);
                }
                if ($second_half) {
                    $join->whereDay('dtr.dtr_date', '>', 15);
                }
            })
            ->leftJoin('employee_profile_schedule as eps', 'ep.id', '=', 'eps.employee_profile_id')
            ->leftJoin('schedules as sch', 'eps.schedule_id', '=', 'sch.id')
            ->leftJoin('time_shifts as ts', 'sch.time_shift_id', '=', 'ts.id')
            ->leftJoin('cto_applications as cto', function ($join) {
                $join->on('ep.id', '=', 'cto.employee_profile_id')
                    ->where('cto.status', '=', 'approved')
                    ->whereRaw('DATE(cto.date) = DATE(sch.date)');
            })
            ->leftJoin('official_business_applications as oba', function ($join) {
                $join->on('ep.id', '=', 'oba.employee_profile_id')
                    ->where('oba.status', '=', 'approved')
                    ->whereBetween('sch.date', [DB::raw('oba.date_from'), DB::raw('oba.date_to')]);
            })
            ->leftJoin('leave_applications as la', function ($join) {
                $join->on('ep.id', '=', 'la.employee_profile_id')
                    ->where('la.status', '=', 'approved')
                    ->whereBetween(DB::raw('sch.date'), [DB::raw('DATE(la.date_from)'), DB::raw('DATE(la.date_to)')]);
            })
            ->leftJoin('official_time_applications as ota', function ($join) {
                $join->on('ep.id', '=', 'ota.employee_profile_id')
                    ->where('ota.status', '=', 'approved')
                    ->whereBetween(DB::raw('sch.date'), [DB::raw('ota.date_from'), DB::raw('ota.date_to')]);
            })
            ->where(function ($query) use ($area_id, $area_under) {
                switch ($area_under) {
                    case 'all':
                        $query->where('a.section_id', $area_id)
                            ->orWhereIn('unit_id', function ($query) use ($area_id) {
                                $query->select('id')
                                    ->from('units')
                                    ->whereIn('section_id', function ($query) use ($area_id) {
                                        $query->select('id')
                                            ->from('sections')
                                            ->where('section_id', $area_id)
                                            ->orWhereIn('section_id', function ($query) use ($area_id) {
                                                $query->select('id')
                                                    ->from('sections')
                                                    ->where('section_id', $area_id);
                                            });
                                    });
                            });
                        break;
                    case 'under':
                        $query->where(function ($query) use ($area_id) {
                            $query->where('a.section_id', $area_id);
                        });
                        break;
                    default:
                        $query->whereRaw('1 = 0'); // Ensures no results are returned for unknown `area_under` values
                }
            })
            ->whereNotNull('ep.biometric_id') // Ensure the employee has biometric data
            ->whereNull('ep.deactivated_at')
            ->where('ep.personal_information_id', '<>', 1)
            ->select(
                'ep.id',
                'ep.employee_id',
                'ep.biometric_id',
                'des.name as employee_designation_name',
                'des.code as employee_designation_code',
                'et.name as employment_type_name',
                DB::raw("CONCAT(
                        pi.first_name, ' ',
                        IF(pi.middle_name IS NOT NULL AND pi.middle_name != '', CONCAT(SUBSTRING(pi.middle_name, 1, 1), '. '), ''),
                        pi.last_name,
                        IF(pi.name_extension IS NOT NULL AND pi.name_extension != '', CONCAT(' ', pi.name_extension), ' '),
                        IF(pi.name_title IS NOT NULL AND pi.name_title != '', CONCAT(', ', pi.name_title), ' ')
                    ) as employee_name"),
                DB::raw('COALESCE(u.name, s.name, dept.name, d.name) as employee_area_name'),
                DB::raw('COALESCE(u.code, s.code, dept.code, d.code) as employee_area_code')
            );
    }

    private function baseQuerySectionByDateRange($start_date, $end_date, $area_id, $area_under, $employment_type, $designation_id)
    {
        return DB::table('assigned_areas as a')
            ->when($designation_id, function ($query, $designation_id) {
                return $query->where('a.designation_id', $designation_id);
            })
            ->leftJoin('employee_profiles as ep', 'a.employee_profile_id', '=', 'ep.id')
            ->when($employment_type, function ($query, $employment_type) {
                return $query->where('ep.employment_type_id', $employment_type);
            })
            ->leftJoin('personal_informations as pi', 'ep.personal_information_id', '=', 'pi.id')
            ->leftJoin('designations as des', 'a.designation_id', '=', 'des.id')
            ->leftJoin('employment_types as et', 'ep.employment_type_id', '=', 'et.id')
            ->leftJoin('divisions as d', 'a.division_id', '=', 'd.id')
            ->leftJoin('departments as dept', 'a.department_id', '=', 'dept.id')
            ->leftJoin('sections as s', 'a.section_id', '=', 's.id')
            ->leftJoin('units as u', 'a.unit_id', '=', 'u.id')
            ->leftJoin('biometrics as b', 'ep.biometric_id', '=', 'b.biometric_id')
            ->leftJoin('daily_time_records as dtr', function ($join) use ($start_date, $end_date) {
                $join->on('b.biometric_id', '=', 'dtr.biometric_id')
                    ->whereBetween('dtr.dtr_date', [$start_date, $end_date]);
            })
            ->leftJoin('employee_profile_schedule as eps', 'ep.id', '=', 'eps.employee_profile_id')
            ->leftJoin('schedules as sch', 'eps.schedule_id', '=', 'sch.id')
            ->leftJoin('time_shifts as ts', 'sch.time_shift_id', '=', 'ts.id')
            ->leftJoin('cto_applications as cto', function ($join) {
                $join->on('ep.id', '=', 'cto.employee_profile_id')
                    ->where('cto.status', '=', 'approved')
                    ->whereRaw('DATE(cto.date) = DATE(sch.date)');
            })
            ->leftJoin('official_business_applications as oba', function ($join) {
                $join->on('ep.id', '=', 'oba.employee_profile_id')
                    ->where('oba.status', '=', 'approved')
                    ->whereBetween('sch.date', [DB::raw('oba.date_from'), DB::raw('oba.date_to')]);
            })
            ->leftJoin('leave_applications as la', function ($join) {
                $join->on('ep.id', '=', 'la.employee_profile_id')
                    ->where('la.status', '=', 'approved')
                    ->whereBetween(DB::raw('sch.date'), [DB::raw('DATE(la.date_from)'), DB::raw('DATE(la.date_to)')]);
            })
            ->leftJoin('official_time_applications as ota', function ($join) {
                $join->on('ep.id', '=', 'ota.employee_profile_id')
                    ->where('ota.status', '=', 'approved')
                    ->whereBetween(DB::raw('sch.date'), [DB::raw('ota.date_from'), DB::raw('ota.date_to')]);
            })
            ->where(function ($query) use ($area_id, $area_under) {
                switch ($area_under) {
                    case 'all':
                        $query->where('a.section_id', $area_id)
                            ->orWhereIn('unit_id', function ($query) use ($area_id) {
                                $query->select('id')
                                    ->from('units')
                                    ->whereIn('section_id', function ($query) use ($area_id) {
                                        $query->select('id')
                                            ->from('sections')
                                            ->where('section_id', $area_id)
                                            ->orWhereIn('section_id', function ($query) use ($area_id) {
                                                $query->select('id')
                                                    ->from('sections')
                                                    ->where('section_id', $area_id);
                                            });
                                    });
                            });
                        break;
                    case 'under':
                        $query->where(function ($query) use ($area_id) {
                            $query->where('a.section_id', $area_id);
                        });
                        break;
                    default:
                        $query->whereRaw('1 = 0'); // Ensures no results are returned for unknown `area_under` values
                }
            })
            ->whereNotNull('ep.biometric_id') // Ensure the employee has biometric data
            ->whereNull('ep.deactivated_at')
            ->where('ep.personal_information_id', '<>', 1)
            ->select(
                'ep.id',
                'ep.employee_id',
                'ep.biometric_id',
                'des.name as employee_designation_name',
                'des.code as employee_designation_code',
                'et.name as employment_type_name',
                DB::raw("CONCAT(
                        pi.first_name, ' ',
                        IF(pi.middle_name IS NOT NULL AND pi.middle_name != '', CONCAT(SUBSTRING(pi.middle_name, 1, 1), '. '), ''),
                        pi.last_name,
                        IF(pi.name_extension IS NOT NULL AND pi.name_extension != '', CONCAT(' ', pi.name_extension), ' '),
                        IF(pi.name_title IS NOT NULL AND pi.name_title != '', CONCAT(', ', pi.name_title), ' ')
                    ) as employee_name"),
                DB::raw('COALESCE(u.name, s.name, dept.name, d.name) as employee_area_name'),
                DB::raw('COALESCE(u.code, s.code, dept.code, d.code) as employee_area_code')
            );
    }

    private function baseQueryUnit($area_id, $employment_type, $designation_id): Builder
    {
        return DB::table('assigned_areas as a')
            ->when($designation_id, function ($query, $designation_id) {
                return $query->where('a.designation_id', $designation_id);
            })
            ->leftJoin('employee_profiles as ep', 'a.employee_profile_id', '=', 'ep.id')
            ->when($employment_type, function ($query, $employment_type) {
                return $query->where('ep.employment_type_id', $employment_type);
            })
            ->leftJoin('personal_informations as pi', 'ep.personal_information_id', '=', 'pi.id')
            ->leftJoin('designations as des', 'a.designation_id', '=', 'des.id')
            ->leftJoin('employment_types as et', 'ep.employment_type_id', '=', 'et.id')
            ->leftJoin('divisions as d', 'a.division_id', '=', 'd.id')
            ->leftJoin('departments as dept', 'a.department_id', '=', 'dept.id')
            ->leftJoin('sections as s', 'a.section_id', '=', 's.id')
            ->leftJoin('units as u', 'a.unit_id', '=', 'u.id')
            ->leftJoin('biometrics as b', 'ep.biometric_id', '=', 'b.biometric_id')
            ->leftJoin('daily_time_records as dtr', function ($join) {
                $join->on('b.biometric_id', '=', 'dtr.biometric_id');
            })
            ->leftJoin('employee_profile_schedule as eps', 'ep.id', '=', 'eps.employee_profile_id')
            ->leftJoin('schedules as sch', 'eps.schedule_id', '=', 'sch.id')
            ->leftJoin('time_shifts as ts', 'sch.time_shift_id', '=', 'ts.id')
            ->leftJoin('cto_applications as cto', function ($join) {
                $join->on('ep.id', '=', 'cto.employee_profile_id')
                    ->where('cto.status', '=', 'approved')
                    ->whereRaw('DATE(cto.date) = DATE(sch.date)');
            })
            ->leftJoin('official_business_applications as oba', function ($join) {
                $join->on('ep.id', '=', 'oba.employee_profile_id')
                    ->where('oba.status', '=', 'approved')
                    ->whereBetween('sch.date', [DB::raw('oba.date_from'), DB::raw('oba.date_to')]);
            })
            ->leftJoin('leave_applications as la', function ($join) {
                $join->on('ep.id', '=', 'la.employee_profile_id')
                    ->where('la.status', '=', 'approved')
                    ->whereBetween(DB::raw('sch.date'), [DB::raw('DATE(la.date_from)'), DB::raw('DATE(la.date_to)')]);
            })
            ->leftJoin('official_time_applications as ota', function ($join) {
                $join->on('ep.id', '=', 'ota.employee_profile_id')
                    ->where('ota.status', '=', 'approved')
                    ->whereBetween(DB::raw('sch.date'), [DB::raw('ota.date_from'), DB::raw('ota.date_to')]);
            })
            ->where(function ($query) use ($area_id) {
                $query->where('a.unit_id', $area_id);
            })
            ->whereNotNull('ep.biometric_id') // Ensure the employee has biometric data
            ->whereNull('ep.deactivated_at')
            ->where('ep.personal_information_id', '<>', 1);
    }

    private function baseQueryUnitByPeriod($month_of, $year_of, $first_half, $second_half, $area_id, $area_under, $employment_type, $designation_id): Builder
    {
        return DB::table('assigned_areas as a')
            ->when($designation_id, function ($query, $designation_id) {
                return $query->where('a.designation_id', $designation_id);
            })
            ->leftJoin('employee_profiles as ep', 'a.employee_profile_id', '=', 'ep.id')
            ->when($employment_type, function ($query, $employment_type) {
                return $query->where('ep.employment_type_id', $employment_type);
            })
            ->leftJoin('personal_informations as pi', 'ep.personal_information_id', '=', 'pi.id')
            ->leftJoin('designations as des', 'a.designation_id', '=', 'des.id')
            ->leftJoin('employment_types as et', 'ep.employment_type_id', '=', 'et.id')
            ->leftJoin('divisions as d', 'a.division_id', '=', 'd.id')
            ->leftJoin('departments as dept', 'a.department_id', '=', 'dept.id')
            ->leftJoin('sections as s', 'a.section_id', '=', 's.id')
            ->leftJoin('units as u', 'a.unit_id', '=', 'u.id')
            ->leftJoin('biometrics as b', 'ep.biometric_id', '=', 'b.biometric_id')
            ->leftJoin('daily_time_records as dtr', function ($join) use ($month_of, $year_of, $first_half, $second_half) {
                $join->on('b.biometric_id', '=', 'dtr.biometric_id')
                    ->whereMonth('dtr.dtr_date', '=', $month_of)
                    ->whereYear('dtr.dtr_date', '=', $year_of);
                if ($first_half) {
                    $join->whereDay('dtr.dtr_date', '<=', 15);
                }
                if ($second_half) {
                    $join->whereDay('dtr.dtr_date', '>', 15);
                }
            })
            ->leftJoin('employee_profile_schedule as eps', 'ep.id', '=', 'eps.employee_profile_id')
            ->leftJoin('schedules as sch', 'eps.schedule_id', '=', 'sch.id')
            ->leftJoin('time_shifts as ts', 'sch.time_shift_id', '=', 'ts.id')
            ->leftJoin('cto_applications as cto', function ($join) {
                $join->on('ep.id', '=', 'cto.employee_profile_id')
                    ->where('cto.status', '=', 'approved')
                    ->whereRaw('DATE(cto.date) = DATE(sch.date)');
            })
            ->leftJoin('official_business_applications as oba', function ($join) {
                $join->on('ep.id', '=', 'oba.employee_profile_id')
                    ->where('oba.status', '=', 'approved')
                    ->whereBetween('sch.date', [DB::raw('oba.date_from'), DB::raw('oba.date_to')]);
            })
            ->leftJoin('leave_applications as la', function ($join) {
                $join->on('ep.id', '=', 'la.employee_profile_id')
                    ->where('la.status', '=', 'approved')
                    ->whereBetween(DB::raw('sch.date'), [DB::raw('DATE(la.date_from)'), DB::raw('DATE(la.date_to)')]);
            })
            ->leftJoin('official_time_applications as ota', function ($join) {
                $join->on('ep.id', '=', 'ota.employee_profile_id')
                    ->where('ota.status', '=', 'approved')
                    ->whereBetween(DB::raw('sch.date'), [DB::raw('ota.date_from'), DB::raw('ota.date_to')]);
            })
            ->where(function ($query) use ($area_id) {
                $query->where('a.unit_id', $area_id);
            })
            ->whereNotNull('ep.biometric_id') // Ensure the employee has biometric data
            ->whereNull('ep.deactivated_at')
            ->where('ep.personal_information_id', '<>', 1)
            ->select(
                'ep.id',
                'ep.employee_id',
                'ep.biometric_id',
                'des.name as employee_designation_name',
                'des.code as employee_designation_code',
                'et.name as employment_type_name',
                DB::raw("CONCAT(
                        pi.first_name, ' ',
                        IF(pi.middle_name IS NOT NULL AND pi.middle_name != '', CONCAT(SUBSTRING(pi.middle_name, 1, 1), '. '), ''),
                        pi.last_name,
                        IF(pi.name_extension IS NOT NULL AND pi.name_extension != '', CONCAT(' ', pi.name_extension), ' '),
                        IF(pi.name_title IS NOT NULL AND pi.name_title != '', CONCAT(', ', pi.name_title), ' ')
                    ) as employee_name"),
                DB::raw('COALESCE(u.name, s.name, dept.name, d.name) as employee_area_name'),
                DB::raw('COALESCE(u.code, s.code, dept.code, d.code) as employee_area_code')
            );
    }

    private function baseQueryUnitByDateRange($start_date, $end_date, $area_id, $area_under, $employment_type, $designation_id)
    {
        return DB::table('assigned_areas as a')
            ->when($designation_id, function ($query, $designation_id) {
                return $query->where('a.designation_id', $designation_id);
            })
            ->leftJoin('employee_profiles as ep', 'a.employee_profile_id', '=', 'ep.id')
            ->when($employment_type, function ($query, $employment_type) {
                return $query->where('ep.employment_type_id', $employment_type);
            })
            ->leftJoin('personal_informations as pi', 'ep.personal_information_id', '=', 'pi.id')
            ->leftJoin('designations as des', 'a.designation_id', '=', 'des.id')
            ->leftJoin('employment_types as et', 'ep.employment_type_id', '=', 'et.id')
            ->leftJoin('divisions as d', 'a.division_id', '=', 'd.id')
            ->leftJoin('departments as dept', 'a.department_id', '=', 'dept.id')
            ->leftJoin('sections as s', 'a.section_id', '=', 's.id')
            ->leftJoin('units as u', 'a.unit_id', '=', 'u.id')
            ->leftJoin('biometrics as b', 'ep.biometric_id', '=', 'b.biometric_id')
            ->leftJoin('daily_time_records as dtr', function ($join) use ($start_date, $end_date) {
                $join->on('b.biometric_id', '=', 'dtr.biometric_id')
                    ->whereBetween('dtr.dtr_date', [$start_date, $end_date]);
            })
            ->leftJoin('employee_profile_schedule as eps', 'ep.id', '=', 'eps.employee_profile_id')
            ->leftJoin('schedules as sch', 'eps.schedule_id', '=', 'sch.id')
            ->leftJoin('time_shifts as ts', 'sch.time_shift_id', '=', 'ts.id')
            ->leftJoin('cto_applications as cto', function ($join) {
                $join->on('ep.id', '=', 'cto.employee_profile_id')
                    ->where('cto.status', '=', 'approved')
                    ->whereRaw('DATE(cto.date) = DATE(sch.date)');
            })
            ->leftJoin('official_business_applications as oba', function ($join) {
                $join->on('ep.id', '=', 'oba.employee_profile_id')
                    ->where('oba.status', '=', 'approved')
                    ->whereBetween('sch.date', [DB::raw('oba.date_from'), DB::raw('oba.date_to')]);
            })
            ->leftJoin('leave_applications as la', function ($join) {
                $join->on('ep.id', '=', 'la.employee_profile_id')
                    ->where('la.status', '=', 'approved')
                    ->whereBetween(DB::raw('sch.date'), [DB::raw('DATE(la.date_from)'), DB::raw('DATE(la.date_to)')]);
            })
            ->leftJoin('official_time_applications as ota', function ($join) {
                $join->on('ep.id', '=', 'ota.employee_profile_id')
                    ->where('ota.status', '=', 'approved')
                    ->whereBetween(DB::raw('sch.date'), [DB::raw('ota.date_from'), DB::raw('ota.date_to')]);
            })
            ->where(function ($query) use ($area_id) {
                $query->where('a.unit_id', $area_id);
            })
            ->whereNotNull('ep.biometric_id') // Ensure the employee has biometric data
            ->whereNull('ep.deactivated_at')
            ->where('ep.personal_information_id', '<>', 1)
            ->select(
                'ep.id',
                'ep.employee_id',
                'ep.biometric_id',
                'des.name as employee_designation_name',
                'des.code as employee_designation_code',
                'et.name as employment_type_name',
                DB::raw("CONCAT(
                        pi.first_name, ' ',
                        IF(pi.middle_name IS NOT NULL AND pi.middle_name != '', CONCAT(SUBSTRING(pi.middle_name, 1, 1), '. '), ''),
                        pi.last_name,
                        IF(pi.name_extension IS NOT NULL AND pi.name_extension != '', CONCAT(' ', pi.name_extension), ' '),
                        IF(pi.name_title IS NOT NULL AND pi.name_title != '', CONCAT(', ', pi.name_title), ' ')
                    ) as employee_name"),
                DB::raw('COALESCE(u.name, s.name, dept.name, d.name) as employee_area_name'),
                DB::raw('COALESCE(u.code, s.code, dept.code, d.code) as employee_area_code')
            );
    }

    private function generateAbsencesByPeriodQuery($base_query, $month_of, $year_of, $first_half, $second_half, $sort_order, $limit, $absent_leave_without_pay, $absent_without_official_leave): mixed
    {
        return $base_query
            ->addSelect(
                DB::raw('COUNT(DISTINCT CASE
                                    WHEN MONTH(dtr.dtr_date) = ' . $month_of . '
                                    AND YEAR(dtr.dtr_date) = ' . $year_of . '
                                    ' . (!$first_half && !$second_half ? '' : ($first_half ? 'AND DAY(dtr.dtr_date) <= 15' : 'AND DAY(dtr.dtr_date) > 15')) . '
                                    THEN dtr.dtr_date END) as days_present'),
                DB::raw("GREATEST(
                            COUNT(DISTINCT CASE
                                WHEN MONTH(sch.date) = $month_of
                                AND YEAR(sch.date) = $year_of
                                AND sch.date <= CURDATE()
                                " . (!$first_half && !$second_half ? '' : ($first_half ? 'AND DAY(sch.date) <= 15' : 'AND DAY(sch.date) > 15')) . "
                                AND la.id IS NULL
                                AND cto.id IS NULL
                                AND oba.id IS NULL
                                AND ota.id IS NULL
                                THEN sch.date END)
                            - COUNT(DISTINCT CASE
                                WHEN MONTH(dtr.dtr_date) = $month_of
                                AND YEAR(dtr.dtr_date) = $year_of
                                AND dtr.dtr_date <= CURDATE()
                                " . (!$first_half && !$second_half ? '' : ($first_half ? 'AND DAY(dtr.dtr_date) <= 15' : 'AND DAY(dtr.dtr_date) > 15')) . "
                                THEN dtr.dtr_date END), 0) as days_absent"),
                DB::raw("(SELECT COUNT(*) FROM leave_applications la WHERE la.employee_profile_id = ep.id AND la.status = 'approved') as total_leave_applications"),
                DB::raw("(SELECT COUNT(*) FROM official_time_applications ota WHERE ota.employee_profile_id = ep.id AND ota.status = 'approved') as total_official_time_applications")
            )
            // Apply conditions based on variables
            ->when($absent_leave_without_pay, function ($query) use ($month_of, $year_of, $first_half, $second_half) {
                return $query->addSelect(DB::raw("COUNT(DISTINCT CASE
                                                                            WHEN MONTH(sch.date) = $month_of
                                                                            AND YEAR(sch.date) = $year_of
                                                                            " . (!$first_half && !$second_half ? '' : ($first_half ? 'AND DAY(sch.date) <= 15' : 'AND DAY(sch.date) > 15')) . "
                                                                            AND la.id IS NOT NULL  -- No approved leave application
                                                                            THEN sch.date END) as absent_leave_without_pay"));
            })
            ->when($absent_without_official_leave, function ($query) use ($month_of, $year_of, $first_half, $second_half) {
                return $query->addSelect(DB::raw("COUNT(DISTINCT CASE
                                                                            WHEN MONTH(sch.date) = $month_of
                                                                            AND YEAR(sch.date) = $year_of
                                                                            " . (!$first_half && !$second_half ? '' : ($first_half ? 'AND DAY(sch.date) <= 15' : 'AND DAY(sch.date) > 15')) . "
                                                                            AND la.id IS NOT NULL  -- No approved leave application
                                                                            AND cto.id IS NOT NULL -- No CTO application
                                                                            AND oba.id IS NOT NULL -- No Official Business application
                                                                            AND ota.id IS NOT NULL -- No Official Time application
                                                                            THEN sch.date END) as absent_without_official_leave"));
            })
            ->groupBy(
                'ep.id',
                'ep.employee_id',
                'ep.biometric_id',
                'employment_type_name',
                'employee_name',
                'employee_designation_name',
                'employee_designation_code',
                'employee_area_name',
                'employee_area_code'
            )
            ->havingRaw('days_absent > 0')
            ->when($sort_order, function ($query, $sort_order) {
                return $query->orderBy('days_absent', $sort_order);
            })
            ->when($limit, function ($query, $limit) {
                return $query->limit($limit);
            })
            ->get();
    }

    private function generateAbsencesByDateRangeQuery($base_query, $start_date, $end_date, $sort_order, $limit, $absent_leave_without_pay, $absent_without_official_leave): mixed
    {
        return $base_query
            ->addSelect(
            // Scheduled Days
                DB::raw('COUNT(DISTINCT CASE
                                    WHEN sch.date BETWEEN "' . $start_date . '" AND "' . $end_date . '"
                                    THEN sch.date END) as scheduled_days'),

                // Days Absent
                DB::raw("GREATEST(
                                    COUNT(DISTINCT CASE
                                        WHEN sch.date BETWEEN '$start_date' AND '$end_date'
                                        AND sch.date <= CURDATE() -- Ensure counting only up to the current date
                                        AND la.id IS NULL
                                        AND cto.id IS NULL
                                        AND oba.id IS NULL
                                        AND ota.id IS NULL
                                        THEN sch.date END)
                                    - COUNT(DISTINCT CASE
                                        WHEN dtr.dtr_date BETWEEN '$start_date' AND '$end_date'
                                        AND dtr.dtr_date <= CURDATE() -- Ensure counting only up to the current date
                                        THEN dtr.dtr_date END), 0) as days_absent"),

                DB::raw("(SELECT COUNT(*) FROM leave_applications la WHERE la.employee_profile_id = ep.id AND la.status = 'approved') as total_leave_applications"),
                DB::raw("(SELECT COUNT(*) FROM official_time_applications ota WHERE ota.employee_profile_id = ep.id AND ota.status = 'approved') as total_official_time_applications")
            )
            // Apply conditions based on variables
            ->when($absent_leave_without_pay, function ($query) use ($start_date, $end_date) {
                return $query->addSelect(DB::raw("COUNT(DISTINCT CASE
                                    WHEN sch.date BETWEEN '$start_date' AND '$end_date'
                                    AND la.id IS NULL  -- No approved leave application
                                    THEN sch.date END) as absent_leave_without_pay"));
            })
            ->when($absent_without_official_leave, function ($query) use ($start_date, $end_date) {
                return $query->addSelect(DB::raw("COUNT(DISTINCT CASE
                                    WHEN sch.date BETWEEN '$start_date' AND '$end_date'
                                    AND la.id IS NULL  -- No approved leave application
                                    AND cto.id IS NULL -- No CTO application
                                    AND oba.id IS NULL -- No Official Business application
                                    AND ota.id IS NULL -- No Official Time application
                                    THEN sch.date END) as absent_without_official_leave"));
            })
            ->groupBy('ep.id', 'ep.employee_id', 'ep.biometric_id', 'employment_type_name', 'employee_name', 'employee_designation_name', 'employee_designation_code', 'employee_area_name', 'employee_area_code')
            ->havingRaw('days_absent > 0')
            ->when($sort_order, function ($query, $sort_order) {
                if ($sort_order === 'asc') {
                    return $query->orderByRaw('days_absent ASC');
                } elseif ($sort_order === 'desc') {
                    return $query->orderByRaw('days_absent DESC');
                } else {
                    return response()->json(['message' => 'Invalid sort order'], 400);
                }
            })
            ->orderBy('employee_area_name')->orderBy('ep.id')
            ->when($limit, function ($query, $limit) {
                return $query->limit($limit);
            })
            ->get();
    }

    private function generateTardinessByPeriodQuery($base_query, $month_of, $year_of, $first_half, $second_half, $sort_order, $limit, $absent_leave_without_pay, $absent_without_official_leave): mixed
    {
        return $base_query
            ->addSelect(
            // Scheduled Days
                DB::raw('COUNT(DISTINCT CASE
                                    WHEN MONTH(sch.date) = ' . $month_of . '
                                    AND YEAR(sch.date) = ' . $year_of . '
                                    ' . (!$first_half && !$second_half ? '' : ($first_half ? 'AND DAY(sch.date) <= 15' : 'AND DAY(sch.date) > 15')) . '
                                    THEN sch.date END) as scheduled_days'),

                // Days with Tardiness
                DB::raw("COUNT(DISTINCT CASE
                                                    WHEN (MONTH(dtr.dtr_date) = $month_of
                                                        AND YEAR(dtr.dtr_date) = $year_of
                                                        " . (!$first_half && !$second_half ? '' : ($first_half ? 'AND DAY(dtr.dtr_date) <= 15' : 'AND DAY(dtr.dtr_date) > 15')) . ")
                                                    AND (dtr.first_in > ADDTIME(ts.first_in, '0:01:00')
                                                        OR (dtr.second_in IS NOT NULL AND dtr.second_in > ADDTIME(ts.second_in, '0:01:00')))
                                                    THEN dtr.dtr_date
                                                END) as days_with_tardiness"),
                DB::raw("(SELECT COUNT(*) FROM leave_applications la WHERE la.employee_profile_id = ep.id AND la.status = 'approved') as total_leave_applications"),
                DB::raw("(SELECT COUNT(*) FROM official_time_applications ota WHERE ota.employee_profile_id = ep.id AND ota.status = 'approved') as total_official_time_applications")
            )
            // Apply conditions based on variables
            ->when($absent_leave_without_pay, function ($query) use ($month_of, $year_of, $first_half, $second_half) {
                return $query->addSelect(DB::raw("COUNT(DISTINCT CASE
                                                                            WHEN MONTH(sch.date) = $month_of
                                                                            AND YEAR(sch.date) = $year_of
                                                                            " . (!$first_half && !$second_half ? '' : ($first_half ? 'AND DAY(sch.date) <= 15' : 'AND DAY(sch.date) > 15')) . "
                                                                            AND la.id IS NOT NULL  -- No approved leave application
                                                                            THEN sch.date END) as absent_leave_without_pay"));
            })
            ->when($absent_without_official_leave, function ($query) use ($month_of, $year_of, $first_half, $second_half) {
                return $query->addSelect(DB::raw("COUNT(DISTINCT CASE
                                                                            WHEN MONTH(sch.date) = $month_of
                                                                            AND YEAR(sch.date) = $year_of
                                                                            " . (!$first_half && !$second_half ? '' : ($first_half ? 'AND DAY(sch.date) <= 15' : 'AND DAY(sch.date) > 15')) . "
                                                                            AND la.id IS NOT NULL  -- No approved leave application
                                                                            AND cto.id IS NOT NULL -- No CTO application
                                                                            AND oba.id IS NOT NULL -- No Official Business application
                                                                            AND ota.id IS NOT NULL -- No Official Time application
                                                                            THEN sch.date END) as absent_without_official_leave"));
            })
            ->groupBy(
                'ep.id',
                'ep.employee_id',
                'ep.biometric_id',
                'employment_type_name',
                'employee_name',
                'employee_designation_name',
                'employee_designation_code',
                'employee_area_name',
                'employee_area_code'
            )
            ->havingRaw('days_with_tardiness > 0')
            ->when($sort_order, function ($query, $sort_order) {
                if ($sort_order === 'asc') {
                    return $query->orderByRaw('days_with_tardiness ASC');
                } elseif ($sort_order === 'desc') {
                    return $query->orderByRaw('days_with_tardiness DESC');
                } else {
                    return response()->json(['message' => 'Invalid sort order'], 400);
                }
            })
            ->orderBy('employee_area_name')->orderBy('ep.id')
            ->when($limit, function ($query, $limit) {
                return $query->limit($limit);
            })
            ->get();
    }

    private function generateTardinessByDateRangeQuery($base_query, $start_date, $end_date, $sort_order, $limit, $absent_leave_without_pay, $absent_without_official_leave): mixed
    {
        return $base_query
            ->addSelect(
            // Scheduled Days
                DB::raw('COUNT(DISTINCT CASE
                                    WHEN sch.date BETWEEN "' . $start_date . '" AND "' . $end_date . '"
                                    THEN sch.date END) as scheduled_days'),

                // Days with Tardiness
                DB::raw("COUNT(DISTINCT CASE
                                        WHEN (dtr.dtr_date BETWEEN '$start_date' AND '$end_date')
                                        AND (dtr.first_in > ADDTIME(ts.first_in, '0:01:00')
                                            OR (dtr.second_in IS NOT NULL AND dtr.second_in > ADDTIME(ts.second_in, '0:01:00')))
                                        THEN dtr.dtr_date
                                    END) as days_with_tardiness"),

                DB::raw("(SELECT COUNT(*) FROM leave_applications la WHERE la.employee_profile_id = ep.id AND la.status = 'approved') as total_leave_applications"),
                DB::raw("(SELECT COUNT(*) FROM official_time_applications ota WHERE ota.employee_profile_id = ep.id AND ota.status = 'approved') as total_official_time_applications")
            )
            // Apply conditions based on variables
            ->when($absent_leave_without_pay, function ($query) use ($start_date, $end_date) {
                return $query->addSelect(DB::raw("COUNT(DISTINCT CASE
                                    WHEN sch.date BETWEEN '$start_date' AND '$end_date'
                                    AND la.id IS NULL  -- No approved leave application
                                    THEN sch.date END) as absent_leave_without_pay"));
            })
            ->when($absent_without_official_leave, function ($query) use ($start_date, $end_date) {
                return $query->addSelect(DB::raw("COUNT(DISTINCT CASE
                                    WHEN sch.date BETWEEN '$start_date' AND '$end_date'
                                    AND la.id IS NULL  -- No approved leave application
                                    AND cto.id IS NULL -- No CTO application
                                    AND oba.id IS NULL -- No Official Business application
                                    AND ota.id IS NULL -- No Official Time application
                                    THEN sch.date END) as absent_without_official_leave"));
            })
            ->groupBy('ep.id', 'ep.employee_id', 'ep.biometric_id', 'employment_type_name', 'employee_name', 'employee_designation_name', 'employee_designation_code', 'employee_area_name', 'employee_area_code')
            ->havingRaw('days_with_tardiness > 0')
            ->when($sort_order, function ($query, $sort_order) {
                if ($sort_order === 'asc') {
                    return $query->orderByRaw('days_with_tardiness ASC');
                } elseif ($sort_order === 'desc') {
                    return $query->orderByRaw('days_with_tardiness DESC');
                } else {
                    return response()->json(['message' => 'Invalid sort order'], 400);
                }
            })
            ->orderBy('employee_area_name')->orderBy('ep.id')
            ->when($limit, function ($query, $limit) {
                return $query->limit($limit);
            })
            ->get();
    }

    private function generateUndertimeByPeriodQuery($base_query, $month_of, $year_of, $first_half, $second_half, $sort_order, $limit, $absent_leave_without_pay, $absent_without_official_leave): mixed
    {
        return $base_query
            ->addSelect(
            // Scheduled Days
                DB::raw('COUNT(DISTINCT CASE
                                    WHEN MONTH(sch.date) = ' . $month_of . '
                                    AND YEAR(sch.date) = ' . $year_of . '
                                    ' . (!$first_half && !$second_half ? '' : ($first_half ? 'AND DAY(sch.date) <= 15' : 'AND DAY(sch.date) > 15')) . '
                                    THEN sch.date END) as scheduled_days'),

                // Total Days with Early Out
                DB::raw('COUNT(DISTINCT CASE
                                            WHEN (
                                                MONTH(dtr.dtr_date) = ' . $month_of . '
                                                AND YEAR(dtr.dtr_date) = ' . $year_of . '
                                                AND dtr.dtr_date <= CURDATE()
                                                ' . (!$first_half && !$second_half ? '' : ($first_half ? 'AND DAY(dtr.dtr_date) <= 15' : 'AND DAY(dtr.dtr_date) > 15')) . '
                                                AND (
                                                    (dtr.first_out IS NOT NULL AND dtr.first_out < ts.first_out) OR
                                                    (dtr.second_out IS NOT NULL AND dtr.second_out < ts.second_out)
                                                )
                                            ) THEN dtr.dtr_date
                                            END) as total_days_with_early_out'),

                // Total Early Out Minutes
                DB::raw("
                                            ROUND(
                                                    IF(
                                                        MONTH(dtr.dtr_date) = $month_of
                                                        AND YEAR(dtr.dtr_date) = $year_of
                                                        AND dtr.dtr_date < CURDATE()
                                                        " . (!$first_half && !$second_half ? '' : ($first_half ? 'AND DAY(dtr.dtr_date) <= 15' : 'AND DAY(dtr.dtr_date) > 15')) . "
                                                        AND STR_TO_DATE(DATE_FORMAT(dtr.first_out, '%H:%i:%s'), '%H:%i:%s') < ts.first_out,
                                                        HOUR(TIMEDIFF(ts.first_out, STR_TO_DATE(DATE_FORMAT(dtr.first_out, '%H:%i:%s'), '%H:%i:%s'))) * 60 +
                                                        MINUTE(TIMEDIFF(ts.first_out, STR_TO_DATE(DATE_FORMAT(dtr.first_out, '%H:%i:%s'), '%H:%i:%s'))) +
                                                        SECOND(TIMEDIFF(ts.first_out, STR_TO_DATE(DATE_FORMAT(dtr.first_out, '%H:%i:%s'), '%H:%i:%s'))) / 60.0,
                                                        0
                                                    )
                                                    +
                                                    IF(
                                                        MONTH(dtr.dtr_date) = $month_of
                                                        AND YEAR(dtr.dtr_date) = $year_of
                                                        AND dtr.dtr_date < CURDATE()
                                                        " . (!$first_half && !$second_half ? '' : ($first_half ? 'AND DAY(dtr.dtr_date) <= 15' : 'AND DAY(dtr.dtr_date) > 15')) . "
                                                        AND STR_TO_DATE(DATE_FORMAT(dtr.second_out, '%H:%i:%s'), '%H:%i:%s') < ts.second_out,
                                                        HOUR(TIMEDIFF(ts.second_out, STR_TO_DATE(DATE_FORMAT(dtr.second_out, '%H:%i:%s'), '%H:%i:%s'))) * 60 +
                                                        MINUTE(TIMEDIFF(ts.second_out, STR_TO_DATE(DATE_FORMAT(dtr.second_out, '%H:%i:%s'), '%H:%i:%s'))) +
                                                        SECOND(TIMEDIFF(ts.second_out, STR_TO_DATE(DATE_FORMAT(dtr.second_out, '%H:%i:%s'), '%H:%i:%s'))) / 60.0,
                                                        0
                                                    ),
                                                    2
                                                ) AS total_early_out_minutes
                                            "),
                DB::raw("(SELECT COUNT(*) FROM leave_applications la WHERE la.employee_profile_id = ep.id AND la.status = 'approved') as total_leave_applications"),
                DB::raw("(SELECT COUNT(*) FROM official_time_applications ota WHERE ota.employee_profile_id = ep.id AND ota.status = 'approved') as total_official_time_applications")
            )
            // Apply conditions based on variables
            ->when($absent_leave_without_pay, function ($query) use ($month_of, $year_of, $first_half, $second_half) {
                return $query->addSelect(DB::raw("COUNT(DISTINCT CASE
                                                                            WHEN MONTH(sch.date) = $month_of
                                                                            AND YEAR(sch.date) = $year_of
                                                                            " . (!$first_half && !$second_half ? '' : ($first_half ? 'AND DAY(sch.date) <= 15' : 'AND DAY(sch.date) > 15')) . "
                                                                            AND la.id IS NOT NULL  -- No approved leave application
                                                                            THEN sch.date END) as absent_leave_without_pay"));
            })
            ->when($absent_without_official_leave, function ($query) use ($month_of, $year_of, $first_half, $second_half) {
                return $query->addSelect(DB::raw("COUNT(DISTINCT CASE
                                                                            WHEN MONTH(sch.date) = $month_of
                                                                            AND YEAR(sch.date) = $year_of
                                                                            " . (!$first_half && !$second_half ? '' : ($first_half ? 'AND DAY(sch.date) <= 15' : 'AND DAY(sch.date) > 15')) . "
                                                                            AND la.id IS NOT NULL  -- No approved leave application
                                                                            AND cto.id IS NOT NULL -- No CTO application
                                                                            AND oba.id IS NOT NULL -- No Official Business application
                                                                            AND ota.id IS NOT NULL -- No Official Time application
                                                                            THEN sch.date END) as absent_without_official_leave"));
            })
            ->groupBy('ep.id', 'ep.employee_id', 'employment_type_name', 'employee_name', 'ep.biometric_id', 'employee_designation_name', 'employee_designation_code', 'employee_area_name', 'employee_area_code', 'total_early_out_minutes')
            ->havingRaw('total_early_out_minutes > 0')
            ->havingRaw('SUM(total_early_out_minutes) > 0')
            ->when($sort_order, function ($query, $sort_order) {
                if ($sort_order === 'asc') {
                    return $query->orderByRaw('total_days_with_early_out ASC');
                } elseif ($sort_order === 'desc') {
                    return $query->orderByRaw('total_days_with_early_out DESC');
                } else {
                    return response()->json(['message' => 'Invalid sort order'], 400);
                }
            })
            ->orderBy('employee_area_name')->orderBy('ep.id')
            ->when($limit, function ($query, $limit) {
                return $query->limit($limit);
            })
            ->get();
    }

    private function generateUndertimeByDateRangeQuery($base_query, $start_date, $end_date, $sort_order, $limit, $absent_leave_without_pay, $absent_without_official_leave): mixed
    {
        return $base_query
            ->addSelect(
            // Scheduled Days
                DB::raw('COUNT(DISTINCT CASE
                                WHEN sch.date BETWEEN "' . $start_date . '" AND "' . $end_date . '"
                                THEN sch.date END) as scheduled_days'),

                // Total Days with Early Out
                DB::raw('COUNT(DISTINCT CASE
                                WHEN (
                                    dtr.dtr_date BETWEEN ' . $start_date . ' AND ' . $end_date . '
                                    AND dtr.dtr_date <= CURDATE()
                                    AND (
                                        (dtr.first_out IS NOT NULL AND STR_TO_DATE(DATE_FORMAT(dtr.first_out, "%H:%i:%s"), "%H:%i:%s") < ts.first_out) OR
                                        (dtr.second_out IS NOT NULL AND STR_TO_DATE(DATE_FORMAT(dtr.second_out, "%H:%i:%s"), "%H:%i:%s") < ts.second_out)
                                    )
                                ) THEN dtr.dtr_date
                                END) as total_days_with_early_out'),

                // Total Early Out Minutes
                DB::raw("
                                ROUND(
                                    IF(
                                        dtr.dtr_date BETWEEN $start_date AND $end_date
                                        AND dtr.dtr_date <= CURDATE()
                                        AND STR_TO_DATE(DATE_FORMAT(dtr.first_out, '%H:%i:%s'), '%H:%i:%s') < ts.first_out,
                                        HOUR(TIMEDIFF(ts.first_out, STR_TO_DATE(DATE_FORMAT(dtr.first_out, '%H:%i:%s'), '%H:%i:%s'))) * 60 +
                                        MINUTE(TIMEDIFF(ts.first_out, STR_TO_DATE(DATE_FORMAT(dtr.first_out, '%H:%i:%s'), '%H:%i:%s'))) +
                                        SECOND(TIMEDIFF(ts.first_out, STR_TO_DATE(DATE_FORMAT(dtr.first_out, '%H:%i:%s'), '%H:%i:%s'))) / 60.0,
                                        0
                                    )
                                    +
                                    IF(
                                        dtr.dtr_date BETWEEN $start_date AND $end_date
                                        AND dtr.dtr_date <= CURDATE()
                                        AND STR_TO_DATE(DATE_FORMAT(dtr.second_out, '%H:%i:%s'), '%H:%i:%s') < ts.second_out,
                                        HOUR(TIMEDIFF(ts.second_out, STR_TO_DATE(DATE_FORMAT(dtr.second_out, '%H:%i:%s'), '%H:%i:%s'))) * 60 +
                                        MINUTE(TIMEDIFF(ts.second_out, STR_TO_DATE(DATE_FORMAT(dtr.second_out, '%H:%i:%s'), '%H:%i:%s'))) +
                                        SECOND(TIMEDIFF(ts.second_out, STR_TO_DATE(DATE_FORMAT(dtr.second_out, '%H:%i:%s'), '%H:%i:%s'))) / 60.0,
                                        0
                                    ),
                                    2
                                ) AS total_early_out_minutes
                                "),
                DB::raw("(SELECT COUNT(*) FROM leave_applications la WHERE la.employee_profile_id = ep.id AND la.status = 'approved') as total_leave_applications"),
                DB::raw("(SELECT COUNT(*) FROM official_time_applications ota WHERE ota.employee_profile_id = ep.id AND ota.status = 'approved') as total_official_time_applications")
            )
            // Apply conditions based on variables
            ->when($absent_leave_without_pay, function ($query) use ($start_date, $end_date) {
                return $query->addSelect(DB::raw("COUNT(DISTINCT CASE
                                WHEN sch.date BETWEEN '$start_date' AND '$end_date'
                                AND la.id IS NOT NULL  -- No approved leave application
                                THEN sch.date END) as absent_leave_without_pay"));
            })
            ->when($absent_without_official_leave, function ($query) use ($start_date, $end_date) {
                return $query->addSelect(DB::raw("COUNT(DISTINCT CASE
                                WHEN sch.date BETWEEN '$start_date' AND '$end_date'
                                AND la.id IS NOT NULL  -- No approved leave application
                                AND cto.id IS NOT NULL -- No CTO application
                                AND oba.id IS NOT NULL -- No Official Business application
                                AND ota.id IS NOT NULL -- No Official Time application
                                THEN sch.date END) as absent_without_official_leave"));
            })
            ->groupBy('ep.id', 'ep.employee_id', 'employment_type_name', 'employee_name', 'ep.biometric_id', 'employee_designation_name', 'employee_designation_code', 'employee_area_name', 'employee_area_code', 'total_early_out_minutes')
            ->havingRaw('total_days_with_early_out > 0')
            ->havingRaw('SUM(total_early_out_minutes) > 0')
            ->when($sort_order, function ($query, $sort_order) {
                if ($sort_order === 'asc') {
                    return $query->orderByRaw('total_days_with_early_out ASC');
                } elseif ($sort_order === 'desc') {
                    return $query->orderByRaw('total_days_with_early_out DESC');
                } else {
                    return response()->json(['message' => 'Invalid sort order'], 400);
                }
            })
            ->orderBy('employee_area_name')->orderBy('ep.id')
            ->when($limit, function ($query, $limit) {
                return $query->limit($limit);
            })
            ->get();
    }

    private function generatePerfectByPeriodQuery($base_query, $month_of, $year_of, $first_half, $second_half, $sort_order, $limit, $absent_leave_without_pay, $absent_without_official_leave): mixed
    {
        return $base_query
            ->addSelect(
            // Scheduled Days
                DB::raw('COUNT(DISTINCT CASE
                                    WHEN MONTH(sch.date) = ' . $month_of . '
                                    AND YEAR(sch.date) = ' . $year_of . '
                                    ' . (!$first_half && !$second_half ? '' : ($first_half ? 'AND DAY(sch.date) <= 15' : 'AND DAY(sch.date) > 15')) . '
                                    THEN sch.date END) as scheduled_days'),

                // Days Present
                DB::raw('COUNT(DISTINCT CASE
                                    WHEN MONTH(dtr.dtr_date) = ' . $month_of . '
                                    AND YEAR(dtr.dtr_date) = ' . $year_of . '
                                    ' . (!$first_half && !$second_half ? '' : ($first_half ? 'AND DAY(dtr.dtr_date) <= 15' : 'AND DAY(dtr.dtr_date) > 15')) . '
                                    THEN dtr.dtr_date END) as days_present'),
                DB::raw("(SELECT COUNT(*) FROM leave_applications la WHERE la.employee_profile_id = ep.id AND la.status = 'approved') as total_leave_applications"),
                DB::raw("(SELECT COUNT(*) FROM official_time_applications ota WHERE ota.employee_profile_id = ep.id AND ota.status = 'approved') as total_official_time_applications")
            )
            // Apply conditions based on variables
            ->when($absent_leave_without_pay, function ($query) use ($month_of, $year_of, $first_half, $second_half) {
                return $query->addSelect(DB::raw("COUNT(DISTINCT CASE
                                                                            WHEN MONTH(sch.date) = $month_of
                                                                            AND YEAR(sch.date) = $year_of
                                                                            " . (!$first_half && !$second_half ? '' : ($first_half ? 'AND DAY(sch.date) <= 15' : 'AND DAY(sch.date) > 15')) . "
                                                                            AND la.id IS NOT NULL  -- No approved leave application
                                                                            THEN sch.date END) as absent_leave_without_pay"));
            })
            ->when($absent_without_official_leave, function ($query) use ($month_of, $year_of, $first_half, $second_half) {
                return $query->addSelect(DB::raw("COUNT(DISTINCT CASE
                                                                            WHEN MONTH(sch.date) = $month_of
                                                                            AND YEAR(sch.date) = $year_of
                                                                            " . (!$first_half && !$second_half ? '' : ($first_half ? 'AND DAY(sch.date) <= 15' : 'AND DAY(sch.date) > 15')) . "
                                                                            AND la.id IS NOT NULL  -- No approved leave application
                                                                            AND cto.id IS NOT NULL -- No CTO application
                                                                            AND oba.id IS NOT NULL -- No Official Business application
                                                   s                         AND ota.id IS NOT NULL -- No Official Time application
                                                                            THEN sch.date END) as absent_without_official_leave"));
            })
            ->groupBy('ep.id', 'ep.employee_id', 'ep.biometric_id', 'employment_type_name', 'employee_name', 'employee_designation_name', 'employee_designation_code', 'employee_area_name', 'employee_area_code')
            ->havingRaw('
                                                SUM(CASE WHEN dtr.first_in > ts.first_in OR (dtr.second_in IS NOT NULL AND dtr.second_in > ts.second_in) THEN 1 ELSE 0 END) = 0 AND
                                                SUM(CASE WHEN dtr.undertime_minutes > 0 THEN 1 ELSE 0 END) = 0 AND
                                                COUNT(DISTINCT sch.date) = COUNT(DISTINCT dtr.dtr_date)
                                            ')
            ->when($sort_order, function ($query, $sort_order) {
                if ($sort_order === 'asc') {
                    return $query->orderByRaw('days_present ASC');
                } elseif ($sort_order === 'desc') {
                    return $query->orderByRaw('days_present DESC');
                } else {
                    return response()->json(['message' => 'Invalid sort order'], 400);
                }
            })
            ->orderBy('employee_area_name')->orderBy('ep.id')
            ->when($limit, function ($query, $limit) {
                return $query->limit($limit);
            })
            ->get();
    }

    private function generatePerfectByDateRangeQuery($base_query, $start_date, $end_date, $sort_order, $limit, $absent_leave_without_pay, $absent_without_official_leave): mixed
    {
        return $base_query
            ->addSelect(
            // Scheduled Days
                DB::raw('COUNT(DISTINCT CASE
                                    WHEN sch.date BETWEEN "' . $start_date . '" AND "' . $end_date . '"
                                    THEN sch.date END) as scheduled_days'),

                DB::raw("(SELECT COUNT(*) FROM leave_applications la WHERE la.employee_profile_id = ep.id AND la.status = 'approved') as total_leave_applications"),
                DB::raw("(SELECT COUNT(*) FROM official_time_applications ota WHERE ota.employee_profile_id = ep.id AND ota.status = 'approved') as total_official_time_applications")
            )
            // Apply conditions based on variables
            ->when($absent_leave_without_pay, function ($query) use ($start_date, $end_date) {
                return $query->addSelect(DB::raw("COUNT(DISTINCT CASE
                                WHEN sch.date BETWEEN '$start_date' AND '$end_date'
                                AND la.id IS NOT NULL  -- No approved leave application
                                THEN sch.date END) as absent_leave_without_pay"));
            })
            ->when($absent_without_official_leave, function ($query) use ($start_date, $end_date) {
                return $query->addSelect(DB::raw("COUNT(DISTINCT CASE
                                WHEN sch.date BETWEEN '$start_date' AND '$end_date'
                                AND la.id IS NOT NULL  -- No approved leave application
                                AND cto.id IS NOT NULL -- No CTO application
                                AND oba.id IS NOT NULL -- No Official Business application
                                AND ota.id IS NOT NULL -- No Official Time application
                                THEN sch.date END) as absent_without_official_leave"));
            })
            ->groupBy('ep.id', 'ep.employee_id', 'employment_type_name', 'employee_name', 'ep.biometric_id', 'employee_designation_name', 'employee_designation_code', 'employee_area_name', 'employee_area_code', 'total_early_out_minutes')
            ->havingRaw('total_days_with_early_out > 0')
            ->havingRaw('SUM(total_early_out_minutes) > 0')
            ->when($sort_order, function ($query, $sort_order) {
                if ($sort_order === 'asc') {
                    return $query->orderByRaw('total_days_with_early_out ASC');
                } elseif ($sort_order === 'desc') {
                    return $query->orderByRaw('total_days_with_early_out DESC');
                } else {
                    return response()->json(['message' => 'Invalid sort order'], 400);
                }
            })
            ->orderBy('employee_area_name')->orderBy('ep.id')
            ->when($limit, function ($query, $limit) {
                return $query->limit($limit);
            })
            ->get();
    }

    private function getEmployeesByPeriod(mixed $report_type, Builder $base_query, int $month_of, int $year_of, bool $first_half, bool $second_half, mixed $sort_order, mixed $limit, mixed $absent_leave_without_pay, mixed $absent_without_official_leave)
    {
        return match ($report_type) {
            'absences' => $this->generateAbsencesByPeriodQuery($base_query, $month_of, $year_of, $first_half, $second_half, $sort_order, $limit, $absent_leave_without_pay, $absent_without_official_leave),
            'tardiness' => $this->generateTardinessByPeriodQuery($base_query, $month_of, $year_of, $first_half, $second_half, $sort_order, $limit, $absent_leave_without_pay, $absent_without_official_leave),
            'undertime' => $this->generateUndertimeByPeriodQuery($base_query, $month_of, $year_of, $first_half, $second_half, $sort_order, $limit, $absent_leave_without_pay, $absent_without_official_leave),
            'perfect' => $this->generatePerfectByPeriodQuery($base_query, $month_of, $year_of, $first_half, $second_half, $sort_order, $limit, $absent_leave_without_pay, $absent_without_official_leave),
            default => response()->json(['data' => collect(), 'message' => 'Invalid report type']),
        };
    }

    private function getEmployeesByDateRange(mixed $report_type, Builder $base_query, mixed $start_date, mixed $end_date, mixed $sort_order, mixed $limit, mixed $absent_leave_without_pay, mixed $absent_without_official_leave)
    {
        return match ($report_type) {
            'absences' => $this->generateAbsencesByDateRangeQuery($base_query, $start_date, $end_date, $sort_order, $limit, $absent_leave_without_pay, $absent_without_official_leave),
            'tardiness' => $this->generateTardinessByDateRangeQuery($base_query, $start_date, $end_date, $sort_order, $limit, $absent_leave_without_pay, $absent_without_official_leave),
            'undertime' => $this->generateUndertimeByDateRangeQuery($base_query, $start_date, $end_date, $sort_order, $limit, $absent_leave_without_pay, $absent_without_official_leave),
            'perfect' => $this->generatePerfectByDateRangeQuery($base_query, $start_date, $end_date, $sort_order, $limit, $absent_leave_without_pay, $absent_without_official_leave),
            default => response()->json(['data' => collect(), 'message' => 'Invalid report type']),
        };
    }

    public function reportByPeriod(Request $request): JsonResponse
    {
        try {
            $area_id = $request->query('area_id');
            $sector = $request->query('sector');
            $area_under = strtolower($request->query('area_under'));
            $month_of = (int)$request->query('month_of');
            $year_of = (int)$request->query('year_of');
            $employment_type = $request->query('employment_type');
            $designation_id = $request->query('designation_id');
            $absent_leave_without_pay = $request->query('absent_leave_without_pay');
            $absent_without_official_leave = $request->query('absent_without_official_leave');
            $first_half = (bool)$request->query('first_half');
            $second_half = (bool)$request->query('second_half');
            $limit = $request->query('limit');
            $sort_order = $request->query('sort_order');
            $report_type = $request->query('report_type');

            if ($sector && !$area_id) {
                return response()->json(['message' => 'Area ID is required when Sector is provided'], 400);
            }

            if (!$sector && !$area_id) {
                // Get base query by period
                $base_query = $this->baseQueryByPeriod($month_of, $year_of, $first_half, $second_half, $employment_type, $designation_id);
                $employees = $this->getEmployeesByPeriod($report_type, $base_query, $month_of, $year_of, $first_half, $second_half, $sort_order, $limit, $absent_leave_without_pay, $absent_without_official_leave);
            } else {
                // Get base query division by period
                switch ($sector) {
                    case 'division':
                        if (!$area_under) {
                            return response()->json(['message' => 'Area under is required when Division is provided'], 400);
                        }
                        $base_query = $this->baseQueryDivisionByPeriod($month_of, $year_of, $first_half, $second_half, $area_id, $area_under, $employment_type, $designation_id);
                        $employees = $this->getEmployeesByPeriod($report_type, $base_query, $month_of, $year_of, $first_half, $second_half, $sort_order, $limit, $absent_leave_without_pay, $absent_without_official_leave);
                        break;
                    case 'department':
                        if (!$area_under) {
                            return response()->json(['message' => 'Area under is required when Department is provided'], 400);
                        }
                        $base_query = $this->baseQueryDepartmentByPeriod($month_of, $year_of, $first_half, $second_half, $area_id, $area_under, $employment_type, $designation_id);
                        $employees = $this->getEmployeesByPeriod($report_type, $base_query, $month_of, $year_of, $first_half, $second_half, $sort_order, $limit, $absent_leave_without_pay, $absent_without_official_leave);
                        break;
                    case 'section':
                        if (!$area_under) {
                            return response()->json(['message' => 'Area under is required when Section is provided'], 400);
                        }
                        $base_query = $this->baseQuerySectionByPeriod($month_of, $year_of, $first_half, $second_half, $area_id, $area_under, $employment_type, $designation_id);
                        $employees = $this->getEmployeesByPeriod($report_type, $base_query, $month_of, $year_of, $first_half, $second_half, $sort_order, $limit, $absent_leave_without_pay, $absent_without_official_leave);
                        break;
                    case 'unit':
                        if (!$area_under) {
                            return response()->json(['message' => 'Area under is required when Unit is provided'], 400);
                        }
                        $base_query = $this->baseQueryUnitByPeriod($month_of, $year_of, $first_half, $second_half, $area_id, $area_under, $employment_type, $designation_id);
                        $employees = $this->getEmployeesByPeriod($report_type, $base_query, $month_of, $year_of, $first_half, $second_half, $sort_order, $limit, $absent_leave_without_pay, $absent_without_official_leave);
                        break;
                    default:
                        return response()->json(['message' => 'Invalid sector provided'], 400);
                }
            }

            return response()->json(
                [
                    'count' => count($employees),
                    'data' => $employees,
                    'message' => 'Data successfully retrieved',
                ],
                ResponseAlias::HTTP_OK
            );
        } catch (\Throwable $th) {
            // Log the error and return an internal server error response
            Helpers::errorLog($this->CONTROLLER_NAME, 'filterAttendanceReport', $th->getMessage());
            return response()->json(
                [
                    'message' => $th->getMessage()
                ],
                ResponseAlias::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function reportByDateRange(Request $request): JsonResponse
    {
        try {
            $area_id = $request->query('area_id');
            $sector = $request->query('sector');
            $area_under = strtolower($request->query('area_under'));
            $start_date = $request->query('start_date');
            $end_date = $request->query('end_date');
            $employment_type = $request->query('employment_type');
            $designation_id = $request->query('designation_id');
            $absent_leave_without_pay = $request->query('absent_leave_without_pay');
            $absent_without_official_leave = $request->query('absent_without_official_leave');
            $limit = $request->query('limit');
            $sort_order = $request->query('sort_order');
            $sort_order = $request->query('sort_order');
            $report_type = $request->query('report_type');

            if ($sector && !$area_id) {
                return response()->json(['message' => 'Area ID is required when Sector is provided'], 400);
            }

            if (!$sector && !$area_id) {
                $base_query = $this->baseQueryByDateRange($start_date, $end_date, $employment_type, $designation_id);
                $employees = $this->getEmployeesByDateRange($report_type, $base_query, $start_date, $end_date, $sort_order, $limit, $absent_leave_without_pay, $absent_without_official_leave);
            } else {
                switch ($sector) {
                    case 'division':
                        if (!$area_under) {
                            return response()->json(['message' => 'Area under is required when Division is provided'], 400);
                        }
                        $base_query = $this->baseQueryDivisionByDateRange($start_date, $end_date, $area_id, $area_under, $employment_type, $designation_id);
                        $employees = $this->getEmployeesByDateRange($report_type, $base_query, $start_date, $end_date, $sort_order, $limit, $absent_leave_without_pay, $absent_without_official_leave);
                        break;
                    case 'department':
                        if (!$area_under) {
                            return response()->json(['message' => 'Area under is required when Department is provided'], 400);
                        }
                        $base_query = $this->baseQueryDepartmentByDateRange($start_date, $end_date, $area_id, $area_under, $employment_type, $designation_id);
                        $employees = $this->getEmployeesByDateRange($report_type, $base_query, $start_date, $end_date, $sort_order, $limit, $absent_leave_without_pay, $absent_without_official_leave);
                        break;
                    case 'section':
                        if (!$area_under) {
                            return response()->json(['message' => 'Area under is required when Section is provided'], 400);
                        }
                        $base_query = $this->baseQuerySectionByDateRange($start_date, $end_date, $area_id, $area_under, $employment_type, $designation_id);
                        $employees = $this->getEmployeesByDateRange($report_type, $base_query, $start_date, $end_date, $sort_order, $limit, $absent_leave_without_pay, $absent_without_official_leave);
                        break;
                    case 'unit':
                        if (!$area_under) {
                            return response()->json(['message' => 'Area under is required when Unit is provided'], 400);
                        }
                        $base_query = $this->baseQueryUnitByDateRange($start_date, $end_date, $area_id, $area_under, $employment_type, $designation_id);
                        $employees = $this->getEmployeesByDateRange($report_type, $base_query, $start_date, $end_date, $sort_order, $limit, $absent_leave_without_pay, $absent_without_official_leave);
                        break;
                    default:
                        return response()->json(['message' => 'Invalid sector provided'], 400);
                }
            }
            return response()->json(
                [
                    'count' => count($employees),
                    'data' => $employees,
                    'message' => 'Data successfully retrieved',
                ],
                ResponseAlias::HTTP_OK
            );
        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'reportByDateRange', $th->getMessage());
            return response()->json(
                [
                    'message' => $th->getMessage()
                ],
                ResponseAlias::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function reportSummary(Request $request): JsonResponse
    {
        try {
            $employment_type = $request->query('employment_type');
            $designation_id = $request->query('designation_id');
            $sector = $request->query('sector');
            $area_id = $request->query('area_id');
            $area_under = strtolower($request->query('area_under'));

            if ($sector && !$area_id) {
                return response()->json(['message' => 'Area ID is required when Sector is provided'], 400);
            }

            if (!$sector) {
                return response()->json(['message' => 'Sector cannot be empty. Please provide a valid input'], 400);
            }

            switch ($sector) {
                case 'division':
                    if (!$area_under) {
                        return response()->json(['message' => 'Area under is required when Division is provided'], 400);
                    }
                    $base_query = $this->baseQueryDivision($area_id, $area_under, $employment_type, $designation_id);

                    $results = $base_query->select(
                        DB::raw('COUNT(DISTINCT ep.id) as total_employees'),
                        DB::raw('SUM(CASE WHEN dtr.id IS NULL THEN 1 ELSE 0 END) as total_absences'),
                        DB::raw('SUM(CASE WHEN (dtr.first_out < ts.first_out OR dtr.second_out < ts.second_out) THEN 1 ELSE 0 END) as total_undertime'),
                        DB::raw("(SELECT COUNT(*) FROM leave_applications la WHERE la.employee_profile_id = ep.id AND la.status = 'approved') as total_leave_applications"),
                        DB::raw("(SELECT COUNT(*) FROM official_time_applications ota WHERE ota.employee_profile_id = ep.id AND ota.status = 'approved') as total_official_time_applications")
                    )->first();
                    break;
                case 'department':
                    if (!$area_under) {
                        return response()->json(['message' => 'Area under is required when Division is provided'], 400);
                    }
                    $base_query = $this->baseQueryDepartment($area_id, $area_under, $employment_type, $designation_id);

                    $results = $base_query->select(
                        DB::raw('COUNT(DISTINCT ep.id) as total_employees'),
                        DB::raw('SUM(CASE WHEN dtr.id IS NULL THEN 1 ELSE 0 END) as total_absences'),
                        DB::raw('SUM(CASE WHEN dtr.first_in > ADDTIME(ts.first_in, "0:01:00") THEN 1 ELSE 0 END) as total_tardiness'),
                        DB::raw('SUM(CASE WHEN (dtr.first_out < ts.first_out OR dtr.second_out < ts.second_out) THEN 1 ELSE 0 END) as total_undertime'),
                        DB::raw('SUM(CASE WHEN la.id IS NOT NULL THEN 1 ELSE 0 END) as total_leave_applications'),
                        DB::raw('SUM(CASE WHEN cto.id IS NOT NULL THEN 1 ELSE 0 END) as total_cto_applications'),
                        DB::raw('SUM(CASE WHEN ota.id IS NOT NULL THEN 1 ELSE 0 END) as total_official_time_applications'),
                        DB::raw('SUM(CASE WHEN oba.id IS NOT NULL THEN 1 ELSE 0 END) as total_official_business_applications')
                    )->first();
                    break;
                case 'section':
                    if (!$area_under) {
                        return response()->json(['message' => 'Area under is required when Division is provided'], 400);
                    }
                    $base_query = $this->baseQuerySection($area_id, $area_under, $employment_type, $designation_id);

                    $results = $base_query->select(
                        DB::raw('COUNT(DISTINCT ep.id) as total_employees'),
                        DB::raw('SUM(CASE WHEN dtr.id IS NULL THEN 1 ELSE 0 END) as total_absences'),
                        DB::raw('SUM(CASE WHEN dtr.first_in > ADDTIME(ts.first_in, "0:01:00") THEN 1 ELSE 0 END) as total_tardiness'),
                        DB::raw('SUM(dtr.undertime_minutes) as total_undertime'),
                        DB::raw('SUM(CASE WHEN la.id IS NOT NULL THEN 1 ELSE 0 END) as total_leave_applications'),
                        DB::raw('SUM(CASE WHEN cto.id IS NOT NULL THEN 1 ELSE 0 END) as total_cto_applications'),
                        DB::raw('SUM(CASE WHEN ota.id IS NOT NULL THEN 1 ELSE 0 END) as total_official_time_applications'),
                        DB::raw('SUM(CASE WHEN oba.id IS NOT NULL THEN 1 ELSE 0 END) as total_official_business_applications')
                    )->first();
                    break;
                case 'unit':
                    if (!$area_under) {
                        return response()->json(['message' => 'Area under is required when Division is provided'], 400);
                    }
                    $base_query = $this->baseQueryUnit($area_id, $area_under, $employment_type, $designation_id);

                    $results = $base_query->select(
                        DB::raw('COUNT(DISTINCT ep.id) as total_employees'),
                        DB::raw('SUM(CASE WHEN dtr.id IS NULL THEN 1 ELSE 0 END) as total_absences'),
                        DB::raw('SUM(CASE WHEN dtr.first_in > ADDTIME(ts.first_in, "0:01:00") THEN 1 ELSE 0 END) as total_tardiness'),
                        DB::raw('SUM(dtr.undertime_minutes) as total_undertime'),
                        DB::raw('SUM(CASE WHEN la.id IS NOT NULL THEN 1 ELSE 0 END) as total_leave_applications'),
                        DB::raw('SUM(CASE WHEN cto.id IS NOT NULL THEN 1 ELSE 0 END) as total_cto_applications'),
                        DB::raw('SUM(CASE WHEN ota.id IS NOT NULL THEN 1 ELSE 0 END) as total_official_time_applications'),
                        DB::raw('SUM(CASE WHEN oba.id IS NOT NULL THEN 1 ELSE 0 END) as total_official_business_applications')
                    )->first();
                    break;
                default:
                    return response()->json(['message' => 'Invalid sector provided'], 400);
            }

            return response()->json([
                'data' => $results,
                'message' => 'Summary successfully generated'
            ], ResponseAlias::HTTP_OK);

        } catch (\Throwable $th) {
            Helpers::errorLog($this->CONTROLLER_NAME, 'summaryDisplay', $th->getMessage());
            return response()->json([
                'message' => $th->getMessage()
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

}
