<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Department;
use App\Models\Station;

class StationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        /**
         * Hospital Operations and Patient Support Service
         */

        $hopps = Department::where('code', 'HOPPS')->first();

        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Engineering and Facilities Management Unit',
            'code' => 'EFM',
            'department_id' => $hopps -> uuid
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Housekeeping-Laundry',
            'code' => 'HL',
            'department_id' => $hopps -> uuid
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Human Resource Management Unit',
            'code' => 'HR',
            'department_id' => $hopps -> uuid
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Materials Management Section',
            'code' => 'MMS',
            'department_id' => $hopps -> uuid
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Office of the Administrative Officer',
            'code' => 'AO',
            'department_id' => $hopps -> uuid
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Procurement Unit',
            'code' => 'PU',
            'department_id' => $hopps -> uuid
        ]);

        /** 
         * HOPPS 
         */

        /**
         * Office of the Medical Center Chief
         */

        $ommc = Department::where('code', 'OMCC')->first();
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Office of the Medical Center Chief',
            'code' => 'OMCC',
            'department_id' => $ommc -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Professional Education Training and Research Office',
            'code' => 'PETRO',
            'department_id' => $ommc -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Office of Instutitional Strategy and Excellence',
            'code' => 'OISE',
            'department_id' => $ommc -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Innovation and Information Systems Unit',
            'code' => 'IISU',
            'department_id' => $ommc -> id
        ]);

        /** 
         * OMMC END 
         */

        /**
         * Medical Services/Arcillary
         */

        $ms = Department::where('code', 'MS')->first();
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Clinical Chemistry Unit',
            'code' => 'CCU',
            'department_id' => $ms -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Clinical Laboratory Unit',
            'code' => 'CLU',
            'department_id' => $ms -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Clinical Unit',
            'code' => 'CU',
            'department_id' => $ms -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Dental Unit',
            'code' => 'DU',
            'department_id' => $ms -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Dermatology Unit',
            'code' => 'D',
            'department_id' => $ms -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'DRRM - Health Unit',
            'code' => 'DRRM-H',
            'department_id' => $ms -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Emergency Medicine Unit',
            'code' => 'EMU',
            'department_id' => $ms -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Ears Nose Throat Unit',
            'code' => 'ENT',
            'department_id' => $ms -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Eye Center Unit',
            'code' => 'EC',
            'department_id' => $ms -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Health Information Management Unit',
            'code' => 'HIMU',
            'department_id' => $ms -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Internal Medicine Unit',
            'code' => 'IMU',
            'department_id' => $ms -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Medical Social Work Unit',
            'code' => 'MSWU',
            'department_id' => $ms -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Nutrition and Dietetics Unit',
            'code' => 'NDU',
            'department_id' => $ms -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Office of the Medical Professional Staff',
            'code' => 'OMPS',
            'department_id' => $ms -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Outpatient Unit',
            'code' => 'OU',
            'department_id' => $ms -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Pathology Unit',
            'code' => 'PU',
            'department_id' => $ms -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Pathology/Clinical Laboratory Unit',
            'code' => 'PCLU',
            'department_id' => $ms -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Pediatrics Unit',
            'code' => 'PU',
            'department_id' => $ms -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Pharmacy Unit',
            'code' => 'Pharma-U',
            'department_id' => $ms -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Public Health Unit',
            'code' => 'PHU',
            'department_id' => $ms -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Radiology Unit',
            'code' => 'RU',
            'department_id' => $ms -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Rehabilitation Medicine Department',
            'code' => 'Rehab-MD',
            'department_id' => $ms -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Surgery Unit',
            'code' => 'SU',
            'department_id' => $ms -> id
        ]);

        /**
         * END Medical Service
         */

        /**
         *  Finance Service
         */

        $fs = Department::where('code', 'FS')->first();
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Accounting Unit',
            'code' => 'AU',
            'department_id' => $fs -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Billing and Claims Unit',
            'code' => 'BCU',
            'department_id' => $fs -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Budget Unit',
            'code' => 'BU',
            'department_id' => $fs -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Cash Operations Unit',
            'code' => 'COU',
            'department_id' => $fs -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Office of the Finance Officer',
            'code' => 'OFO',
            'department_id' => $fs -> id
        ]);
        
        /**
         * End Finance Service
         */

        /**
         * Nursing Service
         */

        $ns = Department::where('code', 'NS')->first();
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Animal Bite',
            'code' => 'AB',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Central Supply and Sterilization Unit',
            'code' => 'CSSU',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Clinical Nursing Unit (Wards)',
            'code' => 'CNU',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Delivery Room / Labor Room Unit',
            'code' => 'DRLRU',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Emergency Room',
            'code' => 'ER',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Family Medicine (OPD)',
            'code' => 'FM',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Family Planning Unit',
            'code' => 'FPU',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Intencive Care Unit - Maternal',
            'code' => 'ICU-M',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Intencive Care Unit - Medical',
            'code' => 'ICU-Med',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Intencive Care Unit - neonatal',
            'code' => 'ICU-N',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Intencive Care Unit - OB',
            'code' => 'ICU-OB',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Intencive Care Unit - Pedia',
            'code' => 'ICU-Pedia',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Intencive Care Unit - Surgical',
            'code' => 'ICU-Surgical',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Internnal Medicine',
            'code' => 'IM',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Milk Bank (OPD)',
            'code' => 'MB',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Obstetrics',
            'code' => 'OB',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Obstetrics and Gynecology Complex Unit ',
            'code' => 'OBGYN',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Office of the Chief Nurse',
            'code' => 'OCN',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Operating Room - Eye Center',
            'code' => 'OR-Eye',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Operating Room - Main',
            'code' => 'OR-Main',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Operating Room - OB',
            'code' => 'OR-OB',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Out-patient Department',
            'code' => 'Out-PD',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Pathology Unit (laboratory)',
            'code' => 'PUL',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Pediatric',
            'code' => 'Pedia',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Post Anesthesia Care Unit ',
            'code' => 'PACU',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Pulmonary-Respiratory Unit ',
            'code' => 'PRU',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Special Care Area',
            'code' => 'SCA',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Surgery (OPD)',
            'code' => 'S-OPD',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Tuberculosis-Dots (OPD)',
            'code' => 'TB-DOTS',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Trauma and Critical Care Center (Emergency Medicine Unit)',
            'code' => 'TCCC',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Ward - Communicable Diseases (6)',
            'code' => 'W-CC',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Ward - Ears Nose Throat (2)',
            'code' => 'W-ENT',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Ward - Infectious Diseases (7)',
            'code' => 'W-ID',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Ward - Medical (5)',
            'code' => 'W-M',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Ward - OB (1)',
            'code' => 'W-OB',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Ward - Optha',
            'code' => 'W-O',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Ward - Orthopedic (2)',
            'code' => 'W-Ortho',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Ward - Pediatric (8)',
            'code' => 'W-Pedia',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Ward - Psych (9)',
            'code' => 'W-Psych',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Ward - Surgical (4)',
            'code' => 'W-Surgical',
            'department_id' => $ns -> id
        ]);
        
        /**
         * End Nursing Service
         */
    }
}
