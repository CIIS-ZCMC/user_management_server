<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

use App\Models\Department;
use App\Models\Division;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        

        /**
         * Hospital Operations and Patient Support Service
         */

         $hopps = Division::where('code', 'HOPPS')->first();

         Department::create([
             'name' => 'Engineering and Facilities Management Unit',
             'code' => 'EFM',
             'division_id' => $hopps -> id
         ]);
         
         Department::create([
             'name' => 'Housekeeping-Laundry',
             'code' => 'HL',
             'division_id' => $hopps -> id
         ]);
         
         Department::create([
             'name' => 'Human Resource Management Unit',
             'code' => 'HR',
             'division_id' => $hopps -> id
         ]);
         
         Department::create([
             'name' => 'Materials Management Section',
             'code' => 'MMS',
             'division_id' => $hopps -> id
         ]);
         
         Department::create([
             'name' => 'Office of the Administrative Officer',
             'code' => 'AO',
             'division_id' => $hopps -> id
         ]);
         
         Department::create([
             'name' => 'Procurement Unit',
             'code' => 'PU',
             'division_id' => $hopps -> id
         ]);
         
        /** 
         * HOPPS 
         */

        /**
         * Office of the Medical Center Chief
         */

        $ommc = Division::where('code', 'OMCC')->first();
        
        Department::create([
            'name' => 'Office of the Medical Center Chief',
            'code' => 'OMCC',
            'division_id' => $ommc -> id
        ]);
        
        Department::create([
            'name' => 'Professional Education Training and Research Office',
            'code' => 'PETRO',
            'division_id' => $ommc -> id
        ]);
        
        Department::create([
            'name' => 'Office of Instutitional Strategy and Excellence',
            'code' => 'OISE',
            'division_id' => $ommc -> id
        ]);
        
        Department::create([
            'name' => 'Innovation and Information Systems Unit',
            'code' => 'IISU',
            'division_id' => $ommc -> id
        ]);

        /** 
         * OMMC END 
         */

        /**
         * Medical Services/Arcillary
         */

        $ms = Division::where('code', 'MS')->first();
        
        Department::create([
            'name' => 'Clinical Chemistry Unit',
            'code' => 'CCU',
            'division_id' => $ms -> id
        ]);
        
        Department::create([
            'name' => 'Clinical Laboratory Unit',
            'code' => 'CLU',
            'division_id' => $ms -> id
        ]);
        
        Department::create([
            'name' => 'Clinical Unit',
            'code' => 'CU',
            'division_id' => $ms -> id
        ]);
        
        Department::create([
            'name' => 'Dental Unit',
            'code' => 'DU',
            'division_id' => $ms -> id
        ]);
        
        Department::create([
            'name' => 'Dermatology Unit',
            'code' => 'D',
            'division_id' => $ms -> id
        ]);
        
        Department::create([
            'name' => 'DRRM - Health Unit',
            'code' => 'DRRM-H',
            'division_id' => $ms -> id
        ]);
        
        Department::create([
            'name' => 'Emergency Medicine Unit',
            'code' => 'EMU',
            'division_id' => $ms -> id
        ]);
        
        Department::create([
            'name' => 'Ears Nose Throat Unit',
            'code' => 'ENT',
            'division_id' => $ms -> id
        ]);
        
        Department::create([
            'name' => 'Eye Center Unit',
            'code' => 'EC',
            'division_id' => $ms -> id
        ]);
        
        Department::create([
            'name' => 'Health Information Management Unit',
            'code' => 'HIMU',
            'division_id' => $ms -> id
        ]);
        
        Department::create([
            'name' => 'Internal Medicine Unit',
            'code' => 'IMU',
            'division_id' => $ms -> id
        ]);
        
        Department::create([
            'name' => 'Medical Social Work Unit',
            'code' => 'MSWU',
            'division_id' => $ms -> id
        ]);
        
        Department::create([
            'name' => 'Nutrition and Dietetics Unit',
            'code' => 'NDU',
            'division_id' => $ms -> id
        ]);
        
        Department::create([
            'name' => 'Office of the Medical Professional Staff',
            'code' => 'OMPS',
            'division_id' => $ms -> id
        ]);
        
        Department::create([
            'name' => 'Outpatient Unit',
            'code' => 'OU',
            'division_id' => $ms -> id
        ]);
        
        Department::create([
            'name' => 'Pathology Unit',
            'code' => 'PU',
            'division_id' => $ms -> id
        ]);
        
        Department::create([
            'name' => 'Pathology/Clinical Laboratory Unit',
            'code' => 'PCLU',
            'division_id' => $ms -> id
        ]);
        
        Department::create([
            'name' => 'Pediatrics Unit',
            'code' => 'PU',
            'division_id' => $ms -> id
        ]);
        
        Department::create([
            'name' => 'Pharmacy Unit',
            'code' => 'Pharma-U',
            'division_id' => $ms -> id
        ]);
        
        Department::create([
            'name' => 'Public Health Unit',
            'code' => 'PHU',
            'division_id' => $ms -> id
        ]);
        
        Department::create([
            'name' => 'Radiology Unit',
            'code' => 'RU',
            'division_id' => $ms -> id
        ]);
        
        Department::create([
            'name' => 'Rehabilitation Medicine Division',
            'code' => 'Rehab-MD',
            'division_id' => $ms -> id
        ]);
        
        Department::create([
            'name' => 'Surgery Unit',
            'code' => 'SU',
            'division_id' => $ms -> id
        ]);

        /**
         * END Medical Service
         */

        /**
         *  Finance Service
         */

        $fs = Division::where('code', 'FS')->first();
        
        Department::create([
            'name' => 'Accounting Unit',
            'code' => 'AU',
            'division_id' => $fs -> id
        ]);
        
        Department::create([
            'name' => 'Billing and Claims Unit',
            'code' => 'BCU',
            'division_id' => $fs -> id
        ]);
        
        Department::create([
            'name' => 'Budget Unit',
            'code' => 'BU',
            'division_id' => $fs -> id
        ]);
        
        Department::create([
            'name' => 'Cash Operations Unit',
            'code' => 'COU',
            'division_id' => $fs -> id
        ]);
        
        Department::create([
            'name' => 'Office of the Finance Officer',
            'code' => 'OFO',
            'division_id' => $fs -> id
        ]);
        
        /**
         * End Finance Service
         */

        /**
         * Nursing Service
         */

        $ns = Division::where('code', 'NS')->first();
        
        Department::create([
            'name' => 'Animal Bite',
            'code' => 'AB',
            'division_id' => $ns -> id
        ]);
        
        Department::create([
            'name' => 'Central Supply and Sterilization Unit',
            'code' => 'CSSU',
            'division_id' => $ns -> id
        ]);
        
        Department::create([
            'name' => 'Clinical Nursing Unit (Wards)',
            'code' => 'CNU',
            'division_id' => $ns -> id
        ]);
        
        Department::create([
            'name' => 'Delivery Room / Labor Room Unit',
            'code' => 'DRLRU',
            'division_id' => $ns -> id
        ]);
        
        Department::create([
            'name' => 'Emergency Room',
            'code' => 'ER',
            'division_id' => $ns -> id
        ]);
        
        Department::create([
            'name' => 'Family Medicine (OPD)',
            'code' => 'FM',
            'division_id' => $ns -> id
        ]);
        
        Department::create([
            'name' => 'Family Planning Unit',
            'code' => 'FPU',
            'division_id' => $ns -> id
        ]);
        
        Department::create([
            'name' => 'Intencive Care Unit - Maternal',
            'code' => 'ICU-M',
            'division_id' => $ns -> id
        ]);
        
        Department::create([
            'name' => 'Intencive Care Unit - Medical',
            'code' => 'ICU-Med',
            'division_id' => $ns -> id
        ]);
        
        Department::create([
            'name' => 'Intencive Care Unit - neonatal',
            'code' => 'ICU-N',
            'division_id' => $ns -> id
        ]);
        
        Department::create([
            'name' => 'Intencive Care Unit - OB',
            'code' => 'ICU-OB',
            'division_id' => $ns -> id
        ]);
        
        Department::create([
            'name' => 'Intencive Care Unit - Pedia',
            'code' => 'ICU-Pedia',
            'division_id' => $ns -> id
        ]);
        
        Department::create([
            'name' => 'Intencive Care Unit - Surgical',
            'code' => 'ICU-Surgical',
            'division_id' => $ns -> id
        ]);
        
        Department::create([
            'name' => 'Internnal Medicine',
            'code' => 'IM',
            'division_id' => $ns -> id
        ]);
        
        Department::create([
            'name' => 'Milk Bank (OPD)',
            'code' => 'MB',
            'division_id' => $ns -> id
        ]);
        
        Department::create([
            'name' => 'Obstetrics',
            'code' => 'OB',
            'division_id' => $ns -> id
        ]);
        
        Department::create([
            'name' => 'Obstetrics and Gynecology Complex Unit ',
            'code' => 'OBGYN',
            'division_id' => $ns -> id
        ]);
        
        Department::create([
            'name' => 'Office of the Chief Nurse',
            'code' => 'OCN',
            'division_id' => $ns -> id
        ]);
        
        Department::create([
            'name' => 'Operating Room - Eye Center',
            'code' => 'OR-Eye',
            'division_id' => $ns -> id
        ]);
        
        Department::create([
            'name' => 'Operating Room - Main',
            'code' => 'OR-Main',
            'division_id' => $ns -> id
        ]);
        
        Department::create([
            'name' => 'Operating Room - OB',
            'code' => 'OR-OB',
            'division_id' => $ns -> id
        ]);
        
        Department::create([
            'name' => 'Out-patient Division',
            'code' => 'Out-PD',
            'division_id' => $ns -> id
        ]);
        
        Department::create([
            'name' => 'Pathology Unit (laboratory)',
            'code' => 'PUL',
            'division_id' => $ns -> id
        ]);
        
        Department::create([
            'name' => 'Pediatric',
            'code' => 'Pedia',
            'division_id' => $ns -> id
        ]);
        
        Department::create([
            'name' => 'Post Anesthesia Care Unit ',
            'code' => 'PACU',
            'division_id' => $ns -> id
        ]);
        
        Department::create([
            'name' => 'Pulmonary-Respiratory Unit ',
            'code' => 'PRU',
            'division_id' => $ns -> id
        ]);
        
        Department::create([
            'name' => 'Special Care Area',
            'code' => 'SCA',
            'division_id' => $ns -> id
        ]);
        
        Department::create([
            'name' => 'Surgery (OPD)',
            'code' => 'S-OPD',
            'division_id' => $ns -> id
        ]);
        
        Department::create([
            'name' => 'Tuberculosis-Dots (OPD)',
            'code' => 'TB-DOTS',
            'division_id' => $ns -> id
        ]);
        
        Department::create([
            'name' => 'Trauma and Critical Care Center (Emergency Medicine Unit)',
            'code' => 'TCCC',
            'division_id' => $ns -> id
        ]);
        
        Department::create([
            'name' => 'Ward - Communicable Diseases (6)',
            'code' => 'W-CC',
            'division_id' => $ns -> id
        ]);
        
        Department::create([
            'name' => 'Ward - Ears Nose Throat (2)',
            'code' => 'W-ENT',
            'division_id' => $ns -> id
        ]);
        
        Department::create([
            'name' => 'Ward - Infectious Diseases (7)',
            'code' => 'W-ID',
            'division_id' => $ns -> id
        ]);
        
        Department::create([
            'name' => 'Ward - Medical (5)',
            'code' => 'W-M',
            'division_id' => $ns -> id
        ]);
        
        Department::create([
            'name' => 'Ward - OB (1)',
            'code' => 'W-OB',
            'division_id' => $ns -> id
        ]);
        
        Department::create([
            'name' => 'Ward - Optha',
            'code' => 'W-O',
            'division_id' => $ns -> id
        ]);
        
        Department::create([
            'name' => 'Ward - Orthopedic (2)',
            'code' => 'W-Ortho',
            'division_id' => $ns -> id
        ]);
        
        Department::create([
            'name' => 'Ward - Pediatric (8)',
            'code' => 'W-Pedia',
            'division_id' => $ns -> id
        ]);
        
        Department::create([
            'name' => 'Ward - Psych (9)',
            'code' => 'W-Psych',
            'division_id' => $ns -> id
        ]);
        
        Department::create([
            'name' => 'Ward - Surgical (4)',
            'code' => 'W-Surgical',
            'division_id' => $ns -> id
        ]);
        
        /**
         * End Nursing Service
         */
    }
}
