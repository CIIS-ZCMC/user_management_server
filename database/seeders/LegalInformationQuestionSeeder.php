<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\LegalInformationQuestion;

class LegalInformationQuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // $liq1 = LegalInformationQuestion::create([
        //     'order_by' => 1,
        //     'content_question' => "Are you related by any consanguinity or affinity to the chief of bureau or office who has immediate supervision over you in the Office, Bureau or Division where you will be appointed,",
        //     'has_detail' => TRUE,
        //     'has_yes_no' => FALSE,
        //     'has_date' => FALSE,
        //     'has_sub_question' => TRUE
        // ]);

        // LegalInformationQuestion::create([
        //     'order_by' => 1,
        //     'content_question' => "(a) Within the third degree?",
        //     'has_detail' => FALSE,
        //     'has_yes_no' => TRUE,
        //     'has_date' => FALSE,
        //     'legal_iq_id' => $liq1['id']
        // ]);

        // LegalInformationQuestion::create([
        //     'order_by' => 2,
        //     'content_question' => "(b) Within the fourth degree (for Local Government Unit applied)?",
        //     'has_detail' => FALSE,
        //     'has_yes_no' => TRUE,
        //     'has_date' => FALSE,
        //     'legal_iq_id' => $liq1['id']
        // ]);

        // LegalInformationQuestion::create([
        //     'order_by' => 2,
        //     'content_question' => "Have you ever been found guilty by any administrative offense?",
        //     'has_detail' => TRUE,
        //     'has_yes_no' => TRUE,
        //     'has_date' => FALSE,
        // ]);

        LegalInformationQuestion::create([
            'order_by' => 3,
            'content_question' => "Have you ever been criminally charged before any court?",
            'has_detail' => TRUE,
            'has_yes_no' => TRUE,
            'has_date' => TRUE,
        ]);

        // LegalInformationQuestion::create([
        //     'order_by' => 4,
        //     'content_question' => "Have you ever been convicted of any crime or violation of any law, decree, ordinance or regulation by any court or tribunal?",
        //     'has_detail' => TRUE,
        //     'has_yes_no' => TRUE,
        //     'has_date' => FALSE
        // ]);

        // LegalInformationQuestion::create([
        //     'order_by' => 5,
        //     'content_question' => "Have you ever been separated from the service in any of the following modes: resignation, retirement, dropped from the rolls, dismissal, termination, end of term, finished contract, AWOL or phased out, in the public or private sector?",
        //     'has_detail' => TRUE,
        //     'has_yes_no' => TRUE,
        //     'has_date' => FALSE
        // ]);

        // LegalInformationQuestion::create([
        //     'order_by' => 6,
        //     'content_question' => "Have you ever been a candidate in a national or local election (except Barangay election)?",
        //     'has_detail' => TRUE,
        //     'has_yes_no' => TRUE,
        //     'has_date' => FALSE
        // ]);


        // LegalInformationQuestion::create([
        //     'order_by' => 7,
        //     'content_question' => "(b) Have you resigned from the Government service during (3)-month period before the last election to promote/actively campaign for national or local candidate?",
        //     'has_detail' => TRUE,
        //     'has_yes_no' => TRUE,
        //     'has_date' => FALSE
        // ]);

        // LegalInformationQuestion::create([
        //     'order_by' => 8,
        //     'content_question' => "Have you acquired the status of an immigrant or permanent resident of another country?",
        //     'has_detail' => TRUE,
        //     'has_yes_no' => TRUE,
        //     'has_date' => FALSE
        // ]);

        // $liq9 = LegalInformationQuestion::create([
        //     'order_by' => 9,
        //     'content_question' => "Pursuant to: (a) Indigenous People’s Act (RA 8371); (b) Magna Carta for Disabled Persons (RA 7277);  and (c) Solo Parents Welfare Act of 2000 (RA 8972), please answer the following items:",
        //     'has_detail' => FALSE,
        //     'has_yes_no' => FALSE,
        //     'has_date' => FALSE,
        //     'has_sub_question' => TRUE
        // ]);

        // LegalInformationQuestion::create([
        //     'order_by' => 1,
        //     'content_question' => "(a) Are you a member of any indigenous group?",
        //     'has_detail' => TRUE,
        //     'has_yes_no' => TRUE,
        //     'has_date' => FALSE,
        //     'legal_iq_id' => $liq9['id']
        // ]);

        // LegalInformationQuestion::create([
        //     'order_by' => 2,
        //     'content_question' => "(b) Are you differently abled?",
        //     'has_detail' => TRUE,
        //     'has_yes_no' => TRUE,
        //     'has_date' => FALSE,
        //     'legal_iq_id' => $liq9['id']
        // ]);

        // LegalInformationQuestion::create([
        //     'order_by' => 3,
        //     'content_question' => "(c) Are you a solo parent?",
        //     'has_detail' => TRUE,
        //     'has_yes_no' => TRUE,
        //     'has_date' => FALSE,
        //     'legal_iq_id' => $liq9['id']
        // ]);
    }
}
