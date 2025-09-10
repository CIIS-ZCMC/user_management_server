<?php

namespace App\Http\Controllers\v2\DailyTimeRecord;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Resources\v2\DailyTimeRecordResource;
use App\Contracts\DailyTimeRecord\DailyTimeRecordRepositoryInterface;

class DailyTimeRecordController extends Controller
{
    public function __construct(
        private DailyTimeRecordRepositoryInterface $dailyTimeRecordRepository
    ){}

    public function index(Request $request)
    {
        try {
            $dailyTimeRecords = $this->dailyTimeRecordRepository->index($request);

            return DailyTimeRecordResource::collection($dailyTimeRecords)
                ->additional([
                    'message' => 'Daily Time Record retrieved successfully'
                ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Failed to retrieve Daily Time Record',
                'error' => $th->getMessage()
            ], 500);
        }
    }
}
