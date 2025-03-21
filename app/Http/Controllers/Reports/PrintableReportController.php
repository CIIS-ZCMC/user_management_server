<?php

namespace App\Http\Controllers\Reports;

use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PrintableReportController extends Controller
{


    private $CONTROLLER_NAME = 'Printable Report';

    
    public function generatePrintableReport(Request $request) {
        try{

            $type = $request->type;
            $columns = $request->columns;  // field and headerName
            $rows = $request->rows;  
            // return response()->json(['data' => $columns], 200);
            // return view('report.blood_type_report',  [
            // 'columns' => $columns
            // ]);
            // $options = new Options();
            // $options->set('isPhpEnabled', true);
            // $options->set('isHtml5ParserEnabled', true);
            // $options->set('isRemoteEnabled', true);
            // $dompdf = new Dompdf($options);
            // $dompdf->getOptions()->setChroot([base_path() . '/public/storage']);
            // $html = view('pds.pdsForm', [])->render();
            // $dompdf->loadHtml($html);

            $options = new Options();
            $options->set('isPhpEnabled', true);
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            $dompdf = new Dompdf($options);
            $dompdf->getOptions()->setChroot([base_path() . '/public/storage']);
            $html = view('report.employee_record_report',  [
            'columns' => $columns, 'rows' => $rows
            ])->render();
            $dompdf->loadHtml($html);

            $dompdf->setPaper('Legal', 'portrait');
            $dompdf->render();
            $filename = 'PDS.pdf';
            
            // /* Downloads as PDF */
            $dompdf->stream($filename); 
        }catch(\Throwable $th){
            Helpers::errorLog($this->CONTROLLER_NAME,'generatePrintableReport', $th->getMessage());
            return response()->json(['message' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

}