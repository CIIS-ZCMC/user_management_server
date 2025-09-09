<?php

namespace App\Repositories\DailyTimeRecord;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection;

use App\Contracts\DailyTimeRecord\DailyTimeRecordRepositoryInterface;
use App\Models\DailyTimeRecords;

class DailyTimeRecordRepository implements DailyTimeRecordRepositoryInterface
{
    public function index(Request $request): Collection
    { 
        $user = $request->user;
        $biometric_id = $user->biometric_id;
        
        return DailyTimeRecords::where('biometric_id', $biometric_id)->get();
    }
}
