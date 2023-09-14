<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Station;

class StationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Human Resource Management',
            'code' => 'HRM'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Procurement',
            'code' => 'BAC'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Material Management',
            'code' => 'Supply'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Security',
            'code' => 'Sec'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Engineering & Facilities Management',
            'code' => 'EFM'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Housekeeping',
            'code' => 'Linen and Laundry'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'PACCU',
            'code' => 'PACCU'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Integrated Hospital Operation & Management Program',
            'code' => 'IHOMP'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Training Office/Library',
            'code' => 'PET-RO'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Internal Control',
            'code' => 'IC'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Health Emergency Management Staff',
            'code' => 'HEMS'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Budget',
            'code' => 'B'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Accounting',
            'code' => 'Acc'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Billing and Claims',
            'code' => 'BC'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Cash Operations',
            'code' => 'CO'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Outpatient',
            'code' => 'OPD'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Emergency Room',
            'code' => 'ER'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Laboratory',
            'code' => 'Lab'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Blood bank',
            'code' => 'BB'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Nutrition & Dietetics',
            'code' => 'Dietary'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Pharmacy',
            'code' => 'Pharma'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Medical Social Services',
            'code' => 'MSS'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Ears Nose Throat',
            'code' => 'ENT'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Tzuchi Rehab',
            'code' => 'TR'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Claims Medical Unit',
            'code' => 'CMU'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Medial & Arcillas Staffs',
            'code' => 'MAS'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Planning',
            'code' => 'PL'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Medical Center Chief Staffs',
            'code' => 'MCCS'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Public Health Unit',
            'code' => 'PHU'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Nursing Service Office',
            'code' => 'NSO'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Communicate Ward',
            'code' => 'CW'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Infectious Ward',
            'code' => 'IW'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Women And Children Protection Unit',
            'code' => 'WCPU'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Finance Service Staff',
            'code' => 'FSS'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Document Controller',
            'code' => 'DC'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Ob-Gyne Ward',
            'code' => 'OB-W'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Emergency Room - Nurses',
            'code' => 'ER-N'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Orthopedic Ward',
            'code' => 'OW'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Surgical Ward',
            'code' => 'SW'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Surgical ICU',
            'code' => 'SICU'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Medical Ward',
            'code' => 'MW'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Medical ICU',
            'code' => 'MICU'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Pediatric Ward',
            'code' => 'PW'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Pediatric ICU',
            'code' => 'PICU'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Psychiatric Ward',
            'code' => 'PsW'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Operating Room-Nurses',
            'code' => 'OR-N'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'OB-Operating Room',
            'code' => 'OB-OR'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'PACU-Nurses',
            'code' => 'PACU'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Delivery Room',
            'code' => 'DR'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Neonatal ICU',
            'code' => 'NICU'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Hemodialysis Unit',
            'code' => 'HEMO'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Family Planning',
            'code' => 'FP'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Radiology',
            'code' => 'Rad'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Dental',
            'code' => 'D'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Health Information Management',
            'code' => 'HIM'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Admitting/Information',
            'code' => 'AI'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Anesthesia',
            'code' => 'An'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Eye Center',
            'code' => 'EC'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Family Medicine',
            'code' => 'FAMED'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Internal Medicine',
            'code' => 'IM'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'OB-Gyne',
            'code' => 'OB'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Orthopedics',
            'code' => 'Ortho'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Pediatrics',
            'code' => 'Pedia'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Surgery',
            'code' => 'S'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Psychiatry',
            'code' => 'Psych'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Pulmonary',
            'code' => 'Pulmo'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'HOPSS Staffs',
            'code' => 'HS'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Oncology',
            'code' => 'Onco'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Rehabilitation Medicine',
            'code' => 'Rehab'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Pulmo Special Dept',
            'code' => 'PSD'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Birthing Clinic',
            'code' => 'BC'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Central Supply and Sterilization Unit',
            'code' => 'CSS'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Eye Center Ward',
            'code' => 'ECW'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Oncology Ward',
            'code' => 'OW'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Human Milk Bank',
            'code' => 'HMB'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'ZCMC Treatment Hub',
            'code' => 'ZTH'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Infection Control',
            'code' => 'ICC'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Burn Unit Nurses',
            'code' => 'BURN'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'PMDT',
            'code' => 'TBDOT'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'ER Encoder',
            'code' => 'ERCODER'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Nursing Service Office 2',
            'code' => 'NSO2'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'HOPSS Division Head',
            'code' => 'HDH'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'HOPSS SECRETARIES',
            'code' => 'HS'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'OMCC-Division Heads',
            'code' => 'MCC-DH'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'OB COMPLEX',
            'code' => 'OB-C'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Medical & Arcilliary Supervisors',
            'code' => 'MAS'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Medical Center Chief-Secretary',
            'code' => 'MCC-SEC'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Medical Office IV-Service',
            'code' => 'MO-IVs'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Rehabilitation Medicine Staffs',
            'code' => 'RH Staff'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Outpatient Supervisor',
            'code' => 'OS'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Hemodialysis Unit 2',
            'code' => 'HU2'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Human Milk Bank 2',
            'code' => 'HMB2'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Emergency Room - Nurses 2',
            'code' => 'ER-N2'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Nuclear Medicine',
            'code' => 'NUC'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Emergency Room-Supervisor',
            'code' => 'ER-S'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Office of the Strategy Management',
            'code' => 'OSM'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => 'Occupational Safety and Health Unit',
            'code' => 'OSHU'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => "MMS-Support (JO's)",
            'code' => 'MMS-S'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => "Cancer Center-Nurses",
            'code' => 'CN'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => "Data Protection Unit",
            'code' => 'DPU'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => "Finance Heads",
            'code' => 'FH'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => "Occupational Safety and Health",
            'code' => 'OSH'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => "ISO-Modular/Covid Facility",
            'code' => 'ISO-Nurses'
        ]);
        
        Station::create([
            'uuid' => Str::uuid(),
            'name' => "Outpatient Doctors",
            'code' => 'OPD-D'
        ]);
    }
}
