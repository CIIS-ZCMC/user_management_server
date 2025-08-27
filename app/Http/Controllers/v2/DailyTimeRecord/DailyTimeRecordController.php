<?php

namespace App\Http\Controllers\v2\DailyTimeRecord;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Contracts\DailyTimeRecord\DailyTimeRecordRepositoryInterface;

class DailyTimeRecordController extends Controller
{
    public function __construct(
        private DailyTimeRecordRepositoryInterface $dailyTimeRecordRepository
    ){}

    public function index(Request $request)
    {
        try {
            return $this->dailyTimeRecordRepository->index($request);

            return response()->json([
                'message' => 'Daily Time Record retrieved successfully',
                'data' => $this->dailyTimeRecordRepository->index($request)
            ]);
        } catch (\Throwable $th) {
            //throw $th;
        }
    }
}
