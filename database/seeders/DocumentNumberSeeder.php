<?php

namespace Database\Seeders;

use App\Models\DocumentNumber;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DocumentNumberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DocumentNumber::create([
            'division_id'=>1,
            'document_no' =>'ZCMC-F-HRMO-02',
            'revision_no' =>'2',
            'document_title' =>'Application for Leave (OMCC)',
            'is_abroad' => 0,
            'effective_date'=>'2021-06-01'
        ]);

        DocumentNumber::create([
            'division_id'=>2,
            'document_no' =>'ZCMC-F-HRMO-02(A)',
            'revision_no' =>'2',
            'document_title' =>'Application for Leave (OCMPS)',
            'is_abroad' => 0,
            'effective_date'=>'2021-06-01'
        ]);

        DocumentNumber::create([
            'division_id'=>3,
            'document_no' =>'ZCMC-F-HRMO-02(B)',
            'revision_no' =>'2',
            'document_title' =>'Application for Leave (CAO)',
            'is_abroad' => 0,
            'effective_date'=>'2021-06-01'
        ]);

        DocumentNumber::create([
            'division_id'=>5,
            'document_no' =>'ZCMC-F-HRMO-02(C)',
            'revision_no' =>'2',
            'document_title' =>'Application for Leave (FINANCE)',
            'is_abroad' => 0,
            'effective_date'=>'2021-06-01'
        ]);

        DocumentNumber::create([
            'division_id'=>4,
            'document_no' =>'ZCMC-F-HRMO-02(D)',
            'revision_no' =>'2',
            'document_title' =>'Application for Leave (NSO)',
            'is_abroad' => 0,
            'effective_date'=>'2021-06-01'
        ]);

        DocumentNumber::create([
            'division_id'=>null,
            'document_no' =>'ZCMC-F-HRMO-02(E)',
            'revision_no' =>'2',
            'document_title' =>'Application for Leave (Regional Health Office)',
            'is_abroad' => 0,
            'effective_date'=>'2021-06-01'
        ]);

        DocumentNumber::create([
            'division_id'=>1,
            'document_no' =>'ZCMC-F-HRMO-02(F)',
            'revision_no' =>'2',
            'document_title' =>'Application for Leave (Abroad) - OMCC',
            'is_abroad' => 1,
            'effective_date'=>'2021-06-01'
        ]);
    }
}
