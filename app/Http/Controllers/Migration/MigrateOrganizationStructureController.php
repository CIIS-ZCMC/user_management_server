<?php

namespace App\Http\Controllers\migration;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Section;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MigrateOrganizationStructureController extends Controller
{

    private $organization  = [
        // 3	Hospital Operations and Patient Support System
        3 => [
            'dep' => [],
            'sec' => [
                [
                    'name' => 'Human Resource Management Office',
                    'code' => 'HRM',
                ],
            ]
        ],
        // 5	Finance Service
        5 => [
            'dep' => [],
            'sec' => [
                [
                    'name' => 'Finance Service Staff',
                    'code' => 'FSS',
                ],
                [
                    'name' => 'Budget Section',
                    'code' => 'B',
                ],
                [
                    'name' => 'Accounting Section',
                    'code' => 'Acc',
                ],
                [
                    'name' => 'Cash Operations',
                    'code' => 'CO',
                ],
            ]
        ],
        // 2	Medical Service
        2 => [
            'dep' => [
                [
                    'name' => 'Family Medicine',
                    'code' => 'FAMED'
                ],
                [
                    'name' => 'Pediatrics',
                    'code' => 'Pedia'
                ],
                [
                    'name' => 'OB-Gyne',
                    'code' => 'OB-Dep-M'
                ],
                [
                    'name' => 'Orthopedics',
                    'code' => 'ORTHO'
                ],
            ],
            'sec' => []
        ],
        // 4	Nursing Service
        4 => [
            'dep' => [
                [
                    'name' => 'OB-Gyne Complex',
                    'code' => 'OB-D-NS',
                    'sec' => [
                        [
                            'name' => 'OB Complex',
                            'code' => 'OB-S-NS',
                            'units' => [
                                [
                                    'name' => 'Delivery Room',
                                    'code' => 'DR-NS'
                                ],
                                [
                                    'name' => 'Ob-Gyne Ward A',
                                    'code' => 'OBWA-U-NS'
                                ],
                                [
                                    'name' => 'Ob-Gyne Ward B',
                                    'code' => 'OBWB-U-NS'
                                ],
                                [
                                    'name' => 'Family Planning',
                                    'code' => 'FP-U-NS'
                                ],
                                [
                                    'name' => 'GYNE ONCOLOGY AND NEW BORNSCREENING',
                                    'code' => 'OB-D-NS'
                                ],
                                [
                                    'name' => 'IMU & LABOR ROOM',
                                    'code' => 'OB-D-NS'
                                ],
                            ]
                        ],
                    ]
                ],
                [
                    'name' => 'Pediatric Complex',
                    'code' => 'Pedia',
                    'sec' => [
                        [
                            'name' => 'Pediatric Complex',
                            'code' => 'PEDIA-S-NS',
                            'units' => [
                                [
                                    'name' => 'Pediatric Ward',
                                    'code' => 'PEDIAW-U-NS'
                                ],
                                [
                                    'name' => 'Pediatric ICU',
                                    'code' => 'PEDIAICU-U-NS'
                                ],
                                [
                                    'name' => 'NEONATHAL ICU',
                                    'code' => 'NICU-U-NS'
                                ]
                            ]
                        ],
                    ]
                ],
                [
                    'name' => 'OR Complex',
                    'code' => 'OR-D-NS',
                    'sec' => [
                        [
                            'name' => 'Operating Room Complex',
                            'code' => 'OR-S-NS',
                            'units' => [
                                [
                                    'name' => 'PACU',
                                    'code' => 'PACU'
                                ],
                                [
                                    'name' => 'Operating Room Nurses',
                                    'code' => 'ORN'
                                ],
                                [
                                    'name' => 'OB Operating Room A',
                                    'code' => 'OBORA'
                                ],
                                [
                                    'name' => 'OB Operating Room B',
                                    'code' => 'OBORB'
                                ]
                            ]
                        ],
                    ]
                ],
                [
                    'name' => 'Surgery Complex',
                    'code' => 'S',
                    'sec' => [
                        [
                            'name' => 'Surgery Complex',
                            'code' => 'S',
                            'units' => [
                                [
                                    'name' => 'Orthopedic Ward',
                                    'code' => 'PEDIAW-U-NS'
                                ]
                            ]
                        ],
                    ]
                ],
            ],
            'sec' => []
        ]
    ];
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }



    /**
     * Show the form for creating a new resource.
     */


    public function create()
    {
        try {

            // For migrating the personal information
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            // Truncate the table
            DB::table('departments')->truncate();
            DB::table('sections')->truncate();
            DB::table('units')->truncate();
            // Re-enable foreign key checks


            DB::beginTransaction();


            DB::commit();

            // list from seeder
            // 1	Office of Medical Center Chief
            // 2	Medical Service
            // 3	Hospital Operations and Patient Support System
            // 4	Nursing Service
            // 5	Finance Service
            foreach ($this->organization as $division => $org) {
                $dep = $org['dep'];
                $sec = $org['sec'];

                if (!count($dep) < 1) {
                    // if it is department
                    foreach ($dep as $dep1) {
                        $dep1det = Department::create([
                            'name' => $dep1['name'],
                            'code' => $dep1['code'],
                            'division_id' => $division,
                        ]);
                        if (array_key_exists('sec', $dep1)) {
                            // if the department has section
                            foreach ($dep1['sec'] as $sec1) {
                                $Sec1Det = Section::create([
                                    'name' => $sec1['name'],
                                    'code' => $sec1['code'],
                                    'division_id' => $division,
                                    'department_id' => $dep1det->id
                                ]);
                                if (array_key_exists('units', $sec1)) {
                                    foreach ($sec1['units']  as $units) {
                                        Unit::create([
                                            'name' => $units['name'],
                                            'code' => $units['code'],
                                            'section_id' => $Sec1Det->id,
                                        ]);
                                    }
                                } else {
                                }
                            }
                        }
                    }
                } else {
                    foreach ($sec as $sec1) {
                        // dd(['data' =>  $sec1, 'isunit' => array_key_exists('units',  $sec1) && count($sec1) < 0]);
                        Section::create([
                            'name' => $sec1['name'],
                            'code' => $sec1['code'],
                            'division_id' => $division,
                        ]);
                    }
                }
            }
            // Department::create(['']);
            // Section::create(['']);
            // Unit::create(['']);
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            return response()->json('Organization Seed success');
        } catch (\Throwable $th) {
            DB::rollBack();
            dd($th);
            return response()->json([
                'message' => $th->getMessage()
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
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
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
