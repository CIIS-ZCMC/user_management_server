<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Department;
use App\Models\Designation;
use App\Models\SalaryGrade;

class DesignationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        
        Designation::create([
            'name' => 'Accountant III',
            'code' => 'ACC III', 
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 19)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Accountant IV',
            'code' => 'ACC IV',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 22)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Administrative Aide I',
            'code' => 'ADA I',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 1)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Administrative Aide III',
            'code' => 'ADA III',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 3)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Administrative Aide IV',
            'code' => 'ADA IV',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 4)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Administrative Aide V',
            'code' => 'ADA V',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 5)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Administrative Aide VI',
            'code' => 'ADA VI',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 6)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Administrative Assistant I',
            'code' => 'ADAS I',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 7)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Administrative Assistant II',
            'code' => 'ADAS II',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 8)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Administrative Assistant III',
            'code' => 'ADAS III',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 9)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Administrative Officer I',
            'code' => 'AO I',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 10)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Administrative Officer II',
            'code' => 'AO II',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 11)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Administrative Officer III',
            'code' => 'AO III',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 14)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Administrative Officer IV',
            'code' => 'AO IV',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 15)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Administrative Officer V',
            'code' => 'AO V',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 18)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Attorney I',
            'code' => 'ATTY I',
            'salary_grade_id' => 1
        ]);
        
        Designation::create([
            'name' => 'Attorney II',
            'code' => 'ATTY II',
            'salary_grade_id' => 1
        ]);
        
        Designation::create([
            'name' => 'Attorney III',
            'code' => 'ATTY III',
            'salary_grade_id' => 1
        ]);
        
        Designation::create([
            'name' => 'Attorney IV',
            'code' => 'ATTY IV',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 23)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Bacteriologist I',
            'code' => 'BACTE I',
            'salary_grade_id' => 1
        ]);
        
        Designation::create([
            'name' => 'Bacteriologist II',
            'code' => 'BACTE II',
            'salary_grade_id' => 1
        ]);
        
        Designation::create([
            'name' => 'Chief Administrative Officer',
            'code' => 'CAO',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 24)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Chief of Medical Professional Staff II',
            'code' => 'CMPS II',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 26)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Computer Maintenance Technologist I',
            'code' => 'CMT I',
            'salary_grade_id' => 1
        ]);
        
        Designation::create([
            'name' => 'Computer Maintenance Technologist III',
            'code' => 'CMT I',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 17)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Computer Maintenance Technologist II',
            'code' => 'CMT II',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 15)->first()->id
        ]);
        Designation::create([
            'name' => 'Cook I',
            'code' => 'Cook I',
            'salary_grade_id' => 1
        ]);
        
        Designation::create([
            'name' => 'Cook II',
            'code' => 'Cook II',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 5)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Chemist II',
            'code' => 'Chemist II',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 15)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Chemist III',
            'code' => 'Chemist III',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 18)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Dentist I',
            'code' => 'Dentist I',
            'salary_grade_id' => 1
        ]);
        
        Designation::create([
            'name' => 'Dentist II',
            'code' => 'Dentist II',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 16)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Dentist III',
            'code' => 'Dentist III',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 20)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Dentist V',
            'code' => 'Dentist V',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 24)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Engineer I',
            'code' => 'ENGR I',
            'salary_grade_id' => 1
        ]);
        
        Designation::create([
            'name' => 'Engineer II',
            'code' => 'ENGR II',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 16)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Engineer III',
            'code' => 'ENGR III',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 19)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Engineer IV',
            'code' => 'ENGR IV',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 22)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Financial Management Officer I',
            'code' => 'FINMO I',
            'salary_grade_id' => 1
        ]);
        
        Designation::create([
            'name' => 'Financial Management Officer II',
            'code' => 'FINMO II',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 24)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Health Education and Promotion Officer III',
            'code' => 'HEPO III',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 18)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Health Physicist III',
            'code' => 'HP III',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 22)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Hospital Housekeeper',
            'code' => 'HHK',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 8)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Food Service Supervisor I',
            'code' => 'FSS I',
            'salary_grade_id' => 1
        ]);
        
        Designation::create([
            'name' => 'Laboratory Aide II',
            'code' => 'LABA II',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 4)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Laundry Worker I',
            'code' => 'LW I',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 1)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Laundry Worker II',
            'code' => 'LW II',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 3)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Medical Center Chief I',
            'code' => 'MCC I',
            'salary_grade_id' => 1
        ]);
        
        Designation::create([
            'name' => 'Medical Center Chief II',
            'code' => 'MCC II',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 27)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Medical Equipment Technician I',
            'code' => 'MEQT I',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 6)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Medical Equipment Technician II',
            'code' => 'MEQT II',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 8)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Medical Laboratory Technician III',
            'code' => 'MLT III',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 10)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Medical Officer I',
            'code' => 'MO I',
            'salary_grade_id' => 1
        ]);
        
        Designation::create([
            'name' => 'Medical Officer II',
            'code' => 'MO II',
            'salary_grade_id' => 1
        ]);
        
        Designation::create([
            'name' => 'Medical Officer III',
            'code' => 'MO III',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 21)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Medical Officer IV',
            'code' => 'MO IV',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 23)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Medical Specialist I',
            'code' => 'MS I',
            'salary_grade_id' => 1
        ]);
        
        Designation::create([
            'name' => 'Medical Specialist I (PT)',
            'code' => 'MS I (PT)',
            'salary_grade_id' => 1
        ]);
        
        Designation::create([
            'name' => 'Medial Specialist II',
            'code' => 'MS II',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 23)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Medical Specialist II (PT)',
            'code' => 'MS II (PT)',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 23)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Medical Specialist III',
            'code' => 'MS III',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 24)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Medical Specialist III (PT)',
            'code' => 'MS III (PT)',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 24)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Medical Specialist IV',
            'code' => 'MS IV',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 25)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Medical Specialist IV (PT)',
            'code' => 'MS IV (PT)',
            'salary_grade_id' => 1
        ]);
        
        Designation::create([
            'name' => 'Medical Technologist I',
            'code' => 'MDTK I',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 11)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Medical Technologist II',
            'code' => 'MDTK II',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 15)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Medical Technologist III',
            'code' => 'MDTK III',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 18)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Medical Technologist IV',
            'code' => 'MDTK IV',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 20)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Medical Technologist V',
            'code' => 'MDTK V',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 22)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Midwife I',
            'code' => 'MWF I',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 9)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Midwife II',
            'code' => 'MWF II',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 11)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Nurse I',
            'code' => 'N-I',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 15)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Nurse II',
            'code' => 'N-II',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 15)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Nurse III',
            'code' => 'N-III',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 17)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Nurse IV',
            'code' => 'N-IV',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 19)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Nurse V',
            'code' => 'N-V',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 20)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Nurse VI',
            'code' => 'N-VI',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 22)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Nurse VII',
            'code' => 'N-VII',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 24)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Nursing Attendant I',
            'code' => 'NA I',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 4)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Nursing Attendant II',
            'code' => 'NA II',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 6)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Nutritionist Dietitian II',
            'code' => 'ND II',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 15)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Nutritionist Dietitian III',
            'code' => 'ND III',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 18)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Nutritionist Dietitian IV',
            'code' => 'ND IV',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 20)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Nutritionist Dietitian V',
            'code' => 'ND V',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 22)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Pharmacist I',
            'code' => 'PH I',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 11)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Pharmacist II',
            'code' => 'PH II',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 15)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Pharmacist III',
            'code' => 'PH III',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 18)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Pharmacist IV',
            'code' => 'PH IV',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 20)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Pharmacist V',
            'code' => 'PH V',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 22)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Physical Theraphy Technician I',
            'code' => 'PTT I',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 6)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Physical Therapist I',
            'code' => 'PT I',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 11)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Physical Therapist II',
            'code' => 'PT I',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 15)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Physical Therapist III',
            'code' => 'PT I',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 18)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Psychologist II',
            'code' => 'PT I',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 15)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Radiologic Technologist I',
            'code' => 'RT I',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 11)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Radiologic Technologist II',
            'code' => 'RT II',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 13)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Radiologic Technologist III',
            'code' => 'RT III',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 15)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Radiologic Technologist IV',
            'code' => 'RT IV',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 18)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Seamstress',
            'code' => 'SEAM',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 2)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Social Welfare Assistant I',
            'code' => 'SWAS',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 8)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Social Welfare Officer I',
            'code' => 'SWO I',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 11)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Social Welfare Officer II',
            'code' => 'SWO II',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 15)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Social Welfare Officer IV',
            'code' => 'SWO IV',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 22)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Statistician II',
            'code' => 'SC II',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 15)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Statistician III',
            'code' => 'SC III',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 18)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Supervising Administrative Officer',
            'code' => 'SAO',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 22)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Training Assistant',
            'code' => 'TA',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 8)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Training Specialist IV',
            'code' => 'TS IV',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 22)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Warehouseman I',
            'code' => 'WH I',
            'salary_grade_id' => 1
        ]);
        
        Designation::create([
            'name' => 'Warehouseman II',
            'code' => 'WH II',
            'salary_grade_id' => 1
        ]);
        
        Designation::create([
            'name' => 'Warehouseman III',
            'code' => 'WH III',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 11)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Physical Teraphy Technician I',
            'code' => 'PTT I',
            'salary_grade_id' => 1
        ]);
        
        Designation::create([
            'name' => 'Medical Equipment Technician III',
            'code' => 'MEQT III',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 11)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Medical Equipment Technician IV',
            'code' => 'MEQT IV',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 13)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Construction Maintenance Foreman',
            'code' => 'CMF',
            'salary_grade_id' => 1
        ]);
        
        Designation::create([
            'name' => 'Social Worker Assitant',
            'code' => 'SWA',
            'salary_grade_id' => 1
        ]);
        
        Designation::create([
            'name' => 'Dental Aide',
            'code' => 'DTA',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 4)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Respiratory Therapist I',
            'code' => 'RSTH1',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 11)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Respiratory Therapist III',
            'code' => 'RSTH3',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 14)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Occupational Therapist II',
            'code' => 'OTII',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 15)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Inf. Tech I',
            'code' => 'IT1',
            'salary_grade_id' => 1
        ]);
        
        Designation::create([
            'name' => 'System Analyst I',
            'code' => 'SA I',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 16)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Computer Programmer I',
            'code' => 'CP I',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 11)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Computer Programmer II',
            'code' => 'CP II',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 15)->first()->id
        ]);
        
        Designation::create([
            'name' => 'Computer Programmer III',
            'code' => 'CP III',
            'salary_grade_id' => SalaryGrade::where('salary_grade_number', 18)->first()->id
        ]);
    }
}
