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
    private string $CONTROLLER_NAME = "Attendance Reports";

    private function baseQueryByPeriod($month_of, $year_of, $first_half, $second_half): Builder
    {
        return DB::table('assigned_areas as a')
            ->leftJoin('employee_profiles as ep', 'a.employee_profile_id', '=', 'ep.id')
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

    public function reportByPeriod(Request $request): JsonResponse
    {
        try {
            $area_id = $request->area_id;
            $sector = $request->sector;
            $area_under = strtolower($request->area_under);
            $month_of = (int)$request->month_of;
            $year_of = (int)$request->year_of;
            $employment_type = $request->employment_type_id;
            $designation_id = $request->designation_id;
            $absent_leave_without_pay = $request->absent_leave_without_pay;
            $absent_without_official_leave = $request->absent_without_official_leave;
            $first_half = (bool)$request->first_half;
            $second_half = (bool)$request->second_half;
            $limit = $request->limit;
            $sort_order = $request->sort_order;
            $report_type = $request->report_type;

            $cache_key = "employees_report_{$report_type}_{$month_of}_{$year_of}_{$first_half}_{$second_half}_{$designation_id}_{$employment_type}_{$sort_order}_{$limit}";
            $employees = collect();

            if ($sector && !$area_id) {
                return response()->json(['message' => 'Area ID is required when Sector is provided'], 400);
            }

            if (!$sector && !$area_id) {
                // Try to get the results from the cache
                $employees = Cache::remember($cache_key, now()->addHours(6), function () use ($report_type, $month_of, $year_of, $first_half, $second_half, $designation_id, $employment_type, $sort_order, $limit) {
                    // Get based query by period
                    $base_query = $this->baseQueryByPeriod($month_of, $year_of, $first_half, $second_half);
                    return match ($report_type) {
                        'absences' => $base_query
                            ->addSelect(
                                // Days Present
                                DB::raw('COUNT(DISTINCT CASE
                                                        WHEN MONTH(dtr.dtr_date) = ' . $month_of . '
                                                        AND YEAR(dtr.dtr_date) = ' . $year_of . '
                                                        ' . (!$first_half && !$second_half ? '' : ($first_half ? 'AND DAY(dtr.dtr_date) <= 15' : 'AND DAY(dtr.dtr_date) > 15')) . '
                                                        THEN dtr.dtr_date END) as days_present'),
                                // Days Absent
                                DB::raw("GREATEST(
                                                COUNT(DISTINCT CASE
                                                    WHEN MONTH(sch.date) = $month_of
                                                    AND YEAR(sch.date) = $year_of
                                                    AND sch.date <= CURDATE() -- Ensure counting only up to the current date
                                                    " . (!$first_half && !$second_half ? '' : ($first_half ? 'AND DAY(sch.date) <= 15' : 'AND DAY(sch.date) > 15')) . "
                                                    AND la.id IS NULL
                                                    AND cto.id IS NULL
                                                    AND oba.id IS NULL
                                                    AND ota.id IS NULL
                                                    THEN sch.date END)
                                                - COUNT(DISTINCT CASE
                                                    WHEN MONTH(dtr.dtr_date) = $month_of
                                                    AND YEAR(dtr.dtr_date) = $year_of
                                                    AND dtr.dtr_date <= CURDATE() -- Ensure counting only up to the current date
                                                    " . (!$first_half && !$second_half ? '' : ($first_half ? 'AND DAY(dtr.dtr_date) <= 15' : 'AND DAY(dtr.dtr_date) > 15')) . "
                                                    THEN dtr.dtr_date END), 0) as days_absent"),
                            )
                            ->groupBy(
                                'ep.id',
                                'ep.employee_id',
                                'ep.biometric_id',
                                'employment_type_name',
                                'employee_name',
                                'employee_designation_name',
                                'employee_designation_code',
                                'employee_area_name',
                                'employee_area_code')
                            ->havingRaw('days_absent > 0')
                            ->when($sort_order, function ($query, $sort_order) {
                                return $query->orderBy('days_absent', $sort_order);
                            })
                            ->when($limit, function ($query, $limit) {
                                return $query->limit($limit);
                            })
                            ->get(),
                        'tardiness' => $base_query
                            ->addSelect(/* additional columns specific to tardiness */)
                            ->groupBy(/* necessary groupings */)
                            ->havingRaw('days_with_tardiness > 0')
                            ->when($sort_order, function ($query, $sort_order) {
                                return $query->orderBy('days_with_tardiness', $sort_order);
                            })
                            ->when($limit, function ($query, $limit) {
                                return $query->limit($limit);
                            })
                            ->get(),
                        'undertime' => $base_query
                            ->addSelect(/* additional columns specific to undertime */)
                            ->groupBy(/* necessary groupings */)
                            ->havingRaw('total_days_with_early_out > 0')
                            ->havingRaw('SUM(total_early_out_minutes) > 0')
                            ->when($sort_order, function ($query, $sort_order) {
                                return $query->orderBy('total_days_with_early_out', $sort_order);
                            })
                            ->when($limit, function ($query, $limit) {
                                return $query->limit($limit);
                            })
                            ->get(),
                        'perfect' => $base_query
                            ->addSelect(/* additional columns specific to perfect attendance */)
                            ->groupBy(/* necessary groupings */)
                            ->havingRaw('
                            SUM(CASE WHEN dtr.first_in > ts.first_in OR (dtr.second_in IS NOT NULL AND dtr.second_in > ts.second_in) THEN 1 ELSE 0 END) = 0 AND
                            SUM(CASE WHEN dtr.undertime_minutes > 0 THEN 1 ELSE 0 END) = 0 AND
                            COUNT(DISTINCT sch.date) = COUNT(DISTINCT dtr.dtr_date)
                        ')
                            ->when($sort_order, function ($query, $sort_order) {
                                return $query->orderBy('employee_area_name', $sort_order);
                            })
                            ->when($limit, function ($query, $limit) {
                                return $query->limit($limit);
                            })
                            ->get(),
                        default => collect(),
                    };
                });
            }


            return response()->json(
                [
                    'count' => count($employees),
                    'data' => $employees,
                    'message' => 'Data successfully retrieved',
                ], 200);
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
}
