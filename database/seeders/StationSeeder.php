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
            'name' => 'Engineering and Facilities Management Unit',
            'code' => 'EFM',
            'department_id' => $hopps -> id
        ]);
        
        Station::create([
            'name' => 'Housekeeping-Laundry',
            'code' => 'HL',
            'department_id' => $hopps -> id
        ]);
        
        Station::create([
            'name' => 'Human Resource Management Unit',
            'code' => 'HR',
            'department_id' => $hopps -> id
        ]);
        
        Station::create([
            'name' => 'Materials Management Section',
            'code' => 'MMS',
            'department_id' => $hopps -> id
        ]);
        
        Station::create([
            'name' => 'Office of the Administrative Officer',
            'code' => 'AO',
            'department_id' => $hopps -> id
        ]);
        
        Station::create([
            'name' => 'Procurement Unit',
            'code' => 'PU',
            'department_id' => $hopps -> id
        ]);

        /** 
         * HOPPS 
         */

        /**
         * Office of the Medical Center Chief
         */

        $ommc = Department::where('code', 'OMCC')->first();
        
        Station::create([
            'name' => 'Office of the Medical Center Chief',
            'code' => 'OMCC',
            'department_id' => $ommc -> id
        ]);
        
        Station::create([
            'name' => 'Professional Education Training and Research Office',
            'code' => 'PETRO',
            'department_id' => $ommc -> id
        ]);
        
        Station::create([
            'name' => 'Office of Instutitional Strategy and Excellence',
            'code' => 'OISE',
            'department_id' => $ommc -> id
        ]);
        
        Station::create([
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
            'name' => 'Clinical Chemistry Unit',
            'code' => 'CCU',
            'department_id' => $ms -> id
        ]);
        
        Station::create([
            'name' => 'Clinical Laboratory Unit',
            'code' => 'CLU',
            'department_id' => $ms -> id
        ]);
        
        Station::create([
            'name' => 'Clinical Unit',
            'code' => 'CU',
            'department_id' => $ms -> id
        ]);
        
        Station::create([
            'name' => 'Dental Unit',
            'code' => 'DU',
            'department_id' => $ms -> id
        ]);
        
        Station::create([
            'name' => 'Dermatology Unit',
            'code' => 'D',
            'department_id' => $ms -> id
        ]);
        
        Station::create([
            'name' => 'DRRM - Health Unit',
            'code' => 'DRRM-H',
            'department_id' => $ms -> id
        ]);
        
        Station::create([
            'name' => 'Emergency Medicine Unit',
            'code' => 'EMU',
            'department_id' => $ms -> id
        ]);
        
        Station::create([
            'name' => 'Ears Nose Throat Unit',
            'code' => 'ENT',
            'department_id' => $ms -> id
        ]);
        
        Station::create([
            'name' => 'Eye Center Unit',
            'code' => 'EC',
            'department_id' => $ms -> id
        ]);
        
        Station::create([
            'name' => 'Health Information Management Unit',
            'code' => 'HIMU',
            'department_id' => $ms -> id
        ]);
        
        Station::create([
            'name' => 'Internal Medicine Unit',
            'code' => 'IMU',
            'department_id' => $ms -> id
        ]);
        
        Station::create([
            'name' => 'Medical Social Work Unit',
            'code' => 'MSWU',
            'department_id' => $ms -> id
        ]);
        
        Station::create([
            'name' => 'Nutrition and Dietetics Unit',
            'code' => 'NDU',
            'department_id' => $ms -> id
        ]);
        
        Station::create([
            'name' => 'Office of the Medical Professional Staff',
            'code' => 'OMPS',
            'department_id' => $ms -> id
        ]);
        
        Station::create([
            'name' => 'Outpatient Unit',
            'code' => 'OU',
            'department_id' => $ms -> id
        ]);
        
        Station::create([
            'name' => 'Pathology Unit',
            'code' => 'PU',
            'department_id' => $ms -> id
        ]);
        
        Station::create([
            'name' => 'Pathology/Clinical Laboratory Unit',
            'code' => 'PCLU',
            'department_id' => $ms -> id
        ]);
        
        Station::create([
            'name' => 'Pediatrics Unit',
            'code' => 'PU',
            'department_id' => $ms -> id
        ]);
        
        Station::create([
            'name' => 'Pharmacy Unit',
            'code' => 'Pharma-U',
            'department_id' => $ms -> id
        ]);
        
        Station::create([
            'name' => 'Public Health Unit',
            'code' => 'PHU',
            'department_id' => $ms -> id
        ]);
        
        Station::create([
            'name' => 'Radiology Unit',
            'code' => 'RU',
            'department_id' => $ms -> id
        ]);
        
        Station::create([
            'name' => 'Rehabilitation Medicine Department',
            'code' => 'Rehab-MD',
            'department_id' => $ms -> id
        ]);
        
        Station::create([
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
            'name' => 'Accounting Unit',
            'code' => 'AU',
            'department_id' => $fs -> id
        ]);
        
        Station::create([
            'name' => 'Billing and Claims Unit',
            'code' => 'BCU',
            'department_id' => $fs -> id
        ]);
        
        Station::create([
            'name' => 'Budget Unit',
            'code' => 'BU',
            'department_id' => $fs -> id
        ]);
        
        Station::create([
            'name' => 'Cash Operations Unit',
            'code' => 'COU',
            'department_id' => $fs -> id
        ]);
        
        Station::create([
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
            'name' => 'Animal Bite',
            'code' => 'AB',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'name' => 'Central Supply and Sterilization Unit',
            'code' => 'CSSU',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'name' => 'Clinical Nursing Unit (Wards)',
            'code' => 'CNU',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'name' => 'Delivery Room / Labor Room Unit',
            'code' => 'DRLRU',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'name' => 'Emergency Room',
            'code' => 'ER',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'name' => 'Family Medicine (OPD)',
            'code' => 'FM',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'name' => 'Family Planning Unit',
            'code' => 'FPU',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'name' => 'Intencive Care Unit - Maternal',
            'code' => 'ICU-M',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'name' => 'Intencive Care Unit - Medical',
            'code' => 'ICU-Med',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'name' => 'Intencive Care Unit - neonatal',
            'code' => 'ICU-N',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'name' => 'Intencive Care Unit - OB',
            'code' => 'ICU-OB',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'name' => 'Intencive Care Unit - Pedia',
            'code' => 'ICU-Pedia',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'name' => 'Intencive Care Unit - Surgical',
            'code' => 'ICU-Surgical',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'name' => 'Internnal Medicine',
            'code' => 'IM',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'name' => 'Milk Bank (OPD)',
            'code' => 'MB',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'name' => 'Obstetrics',
            'code' => 'OB',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'name' => 'Obstetrics and Gynecology Complex Unit ',
            'code' => 'OBGYN',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'name' => 'Office of the Chief Nurse',
            'code' => 'OCN',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'name' => 'Operating Room - Eye Center',
            'code' => 'OR-Eye',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'name' => 'Operating Room - Main',
            'code' => 'OR-Main',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'name' => 'Operating Room - OB',
            'code' => 'OR-OB',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'name' => 'Out-patient Department',
            'code' => 'Out-PD',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'name' => 'Pathology Unit (laboratory)',
            'code' => 'PUL',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'name' => 'Pediatric',
            'code' => 'Pedia',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'name' => 'Post Anesthesia Care Unit ',
            'code' => 'PACU',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'name' => 'Pulmonary-Respiratory Unit ',
            'code' => 'PRU',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'name' => 'Special Care Area',
            'code' => 'SCA',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'name' => 'Surgery (OPD)',
            'code' => 'S-OPD',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'name' => 'Tuberculosis-Dots (OPD)',
            'code' => 'TB-DOTS',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'name' => 'Trauma and Critical Care Center (Emergency Medicine Unit)',
            'code' => 'TCCC',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'name' => 'Ward - Communicable Diseases (6)',
            'code' => 'W-CC',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'name' => 'Ward - Ears Nose Throat (2)',
            'code' => 'W-ENT',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'name' => 'Ward - Infectious Diseases (7)',
            'code' => 'W-ID',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'name' => 'Ward - Medical (5)',
            'code' => 'W-M',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'name' => 'Ward - OB (1)',
            'code' => 'W-OB',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'name' => 'Ward - Optha',
            'code' => 'W-O',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'name' => 'Ward - Orthopedic (2)',
            'code' => 'W-Ortho',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'name' => 'Ward - Pediatric (8)',
            'code' => 'W-Pedia',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'name' => 'Ward - Psych (9)',
            'code' => 'W-Psych',
            'department_id' => $ns -> id
        ]);
        
        Station::create([
            'name' => 'Ward - Surgical (4)',
            'code' => 'W-Surgical',
            'department_id' => $ns -> id
        ]);
        
        /**
         * End Nursing Service
         */
    }
}
