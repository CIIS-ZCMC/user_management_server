<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\JobPosition;

class JobPositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Accountant I',
            'code' => 'ACC I',
            'salary_grade' => 0
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Accountant II',
            'code' => 'ACC II',
            'salary_grade' => 0
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Accountant III',
            'code' => 'ACC III',
            'salary_grade' => 19
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Administrative Aide I',
            'code' => 'ADA I',
            'salary_grade' => 1
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Administrative Aide III',
            'code' => 'ADA III',
            'salary_grade' => 3
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Administrative Aide IV',
            'code' => 'ADA IV',
            'salary_grade' => 4
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Administrative Aide V',
            'code' => 'ADA V',
            'salary_grade' => 5
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Administrative Aide VI',
            'code' => 'ADA VI',
            'salary_grade' => 6
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Administrative Assistant I',
            'code' => 'ADAS I',
            'salary_grade' => 7
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Administrative Assistant II',
            'code' => 'ADAS II',
            'salary_grade' => 8
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Administrative Assistant III',
            'code' => 'ADAS III',
            'salary_grade' => 9
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Administrative Officer I',
            'code' => 'AO I',
            'salary_grade' => 10
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Administrative Officer II',
            'code' => 'AO II',
            'salary_grade' => 11
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Administrative Officer III',
            'code' => 'AO III',
            'salary_grade' => 14
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Administrative Officer IV',
            'code' => 'AO IV',
            'salary_grade' => 15
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Administrative Officer V',
            'code' => 'AO V',
            'salary_grade' => 18
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Attorney I',
            'code' => 'ATTY I',
            'salary_grade' => 0
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Attorney II',
            'code' => 'ATTY II',
            'salary_grade' => 0
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Attorney III',
            'code' => 'ATTY III',
            'salary_grade' => 0
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Attorney IV',
            'code' => 'ATTY IV',
            'salary_grade' => 23
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Bacteriologist I',
            'code' => 'BACTE I',
            'salary_grade' => 0
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Bacteriologist II',
            'code' => 'BACTE II',
            'salary_grade' => 0
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Chief Administrative Officer',
            'code' => 'CAO',
            'salary_grade' => 24
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Chief of Medical Professional Staff II',
            'code' => 'CMPS II',
            'salary_grade' => 26
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Computer Maintenance Technologist I',
            'code' => 'CMT I',
            'salary_grade' => 0
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Computer Maintenance Technologist II',
            'code' => 'CMT II',
            'salary_grade' => 15
        ]);
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Cook I',
            'code' => 'Cook I',
            'salary_grade' => 0
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Cook II',
            'code' => 'Cook II',
            'salary_grade' => 5
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Dentist I',
            'code' => 'Dentist I',
            'salary_grade' => 0
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Dentist II',
            'code' => 'Dentist II',
            'salary_grade' => 17
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Dentist III',
            'code' => 'Dentist III',
            'salary_grade' => 20
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Engineer I',
            'code' => 'ENGR I',
            'salary_grade' => 0
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Engineer II',
            'code' => 'ENGR II',
            'salary_grade' => 16
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Engineer III',
            'code' => 'ENGR III',
            'salary_grade' => 19
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Financial & Management Officer I',
            'code' => 'FINMO I',
            'salary_grade' => 0
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Financial & Management Officer II',
            'code' => 'FINMO II',
            'salary_grade' => 0
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Food Service Supervisor I',
            'code' => 'FSS I',
            'salary_grade' => 0
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Laboratory Aide II',
            'code' => 'LABA II',
            'salary_grade' => 4
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Laundry Worker I',
            'code' => 'LW I',
            'salary_grade' => 1
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Laundry Worker II',
            'code' => 'LW II',
            'salary_grade' => 3
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Medical Center Chief I',
            'code' => 'MCC I',
            'salary_grade' => 0
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Medical Center Chief II',
            'code' => 'MCC II',
            'salary_grade' => 27
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Medical Equipment Technician I',
            'code' => 'MEQT I',
            'salary_grade' => 6
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Medical Equipment Technician II',
            'code' => 'MEQT II',
            'salary_grade' => 8
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Medical Laboratory Technician III',
            'code' => 'MLT III',
            'salary_grade' => 10
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Medical Officer I',
            'code' => 'MO I',
            'salary_grade' => 0
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Medical Officer II',
            'code' => 'MO II',
            'salary_grade' => 0
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Medical Officer III',
            'code' => 'MO III',
            'salary_grade' => 21
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Medical Officer IV',
            'code' => 'MO IV',
            'salary_grade' => 23
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Medical Specialist I',
            'code' => 'MS I',
            'salary_grade' => 0
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Medical Specialist I (PT)',
            'code' => 'MS I (PT)',
            'salary_grade' => 0
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Medial Specialist II',
            'code' => 'MS II',
            'salary_grade' => 23
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Medical Specialist II (PT)',
            'code' => 'MS II (PT)',
            'salary_grade' => 23
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Medical Specialist III',
            'code' => 'MS III',
            'salary_grade' => 24
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Medical Specialist III (PT)',
            'code' => 'MS III (PT)',
            'salary_grade' => 24
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Medical Specialist IV',
            'code' => 'MS IV',
            'salary_grade' => 25
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Medical Specialist IV (PT)',
            'code' => 'MS IV (PT)',
            'salary_grade' => 0
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Medical Technologist I',
            'code' => 'MDTK I',
            'salary_grade' => 11
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Medical Technologist II',
            'code' => 'MDTK II',
            'salary_grade' => 15
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Medical Technologist III',
            'code' => 'MDTK III',
            'salary_grade' => 18
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Medical Technologist IV',
            'code' => 'MDTK IV',
            'salary_grade' => 20
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Medical Technologist V',
            'code' => 'MDTK V',
            'salary_grade' => 22
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Midwife I',
            'code' => 'MWF I',
            'salary_grade' => 9
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Midwife II',
            'code' => 'MWF II',
            'salary_grade' => 11
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Nurse I',
            'code' => 'N-I',
            'salary_grade' => 15
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Nurse II',
            'code' => 'N-II',
            'salary_grade' => 16
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Nurse III',
            'code' => 'N-III',
            'salary_grade' => 17
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Nurse IV',
            'code' => 'N-IV',
            'salary_grade' => 19
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Nurse V',
            'code' => 'N-V',
            'salary_grade' => 20
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Nurse VI',
            'code' => 'N-VI',
            'salary_grade' => 22
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'OIC-Nurse VII',
            'code' => 'N-VII',
            'salary_grade' => 0
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Nursing Attendant I',
            'code' => 'NA I',
            'salary_grade' => 4
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Nursing Attendant II',
            'code' => 'NA II',
            'salary_grade' => 6
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Nutritionist Dietitian I',
            'code' => 'ND I',
            'salary_grade' => 22
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Nutritionist Dietitian II',
            'code' => 'ND II',
            'salary_grade' => 15
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Nutritionist Dietitian III',
            'code' => 'ND III',
            'salary_grade' => 18
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Nutritionist Dietitian IV',
            'code' => 'ND IV',
            'salary_grade' => 20
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Nutritionist Dietitian V',
            'code' => 'ND V',
            'salary_grade' => 22
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Pharmacist I',
            'code' => 'PH I',
            'salary_grade' => 11
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Pharmacist II',
            'code' => 'PH II',
            'salary_grade' => 15
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Pharmacist III',
            'code' => 'PH III',
            'salary_grade' => 18
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Pharmacist IV',
            'code' => 'PH IV',
            'salary_grade' => 20
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Pharmacist V',
            'code' => 'PH V',
            'salary_grade' => 22
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Radiologic Technologist I',
            'code' => 'RT I',
            'salary_grade' => 11
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Radiologic Technologist II',
            'code' => 'RT II',
            'salary_grade' => 15
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Radiologic Technologist III',
            'code' => 'RT III',
            'salary_grade' => 18
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Seamstress',
            'code' => 'SEAM',
            'salary_grade' => 2
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Social Welfare Assistant I',
            'code' => 'SWAS',
            'salary_grade' => 0
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Social Welfare Officer I',
            'code' => 'SWO I',
            'salary_grade' => 11
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Social Welfare Officer II',
            'code' => 'SWO II',
            'salary_grade' => 15
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Social Welfare Officer III',
            'code' => 'SWO III',
            'salary_grade' => 18
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Social Welfare Officer IV',
            'code' => 'SWO IV',
            'salary_grade' => 22
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Supervising Administrative Officer',
            'code' => 'SAO',
            'salary_grade' => 22
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Warehouseman I',
            'code' => 'WH I',
            'salary_grade' => 0
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Warehouseman II',
            'code' => 'WH II',
            'salary_grade' => 0
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Warehouseman III',
            'code' => 'WH III',
            'salary_grade' => 11
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Physical Teraphy Technician I',
            'code' => 'PTT I',
            'salary_grade' => 0
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Medical Equipment Technician III',
            'code' => 'MEQT III',
            'salary_grade' => 11
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Construction Maintenance Foreman',
            'code' => 'CMF',
            'salary_grade' => 0
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Social Worker Assitant',
            'code' => 'SWA',
            'salary_grade' => 0
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Dental Aide',
            'code' => 'DTA',
            'salary_grade' => 4
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Respiratory Therapist I',
            'code' => 'RSTH1',
            'salary_grade' => 10
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Respiratory Therapist II',
            'code' => 'RSTH2',
            'salary_grade' => 14
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Occupational Therapist II',
            'code' => 'OTII',
            'salary_grade' => 15
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Inf. Tech I',
            'code' => 'IT1',
            'salary_grade' => 0
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'System Analyst I',
            'code' => 'SA I',
            'salary_grade' => 16
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Computer Programmer I',
            'code' => 'CP I',
            'salary_grade' => 11
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Computer Programmer II',
            'code' => 'CP II',
            'salary_grade' => 15
        ]);
        
        JobPosition::create([
            'uuid' => Str::uuid(),
            'name' => 'Computer Programmer III',
            'code' => 'CP III',
            'salary_grade' => 18
        ]);
    }
}
