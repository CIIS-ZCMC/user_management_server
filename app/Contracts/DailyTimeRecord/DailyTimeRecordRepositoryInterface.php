<?php

namespace App\Contracts\DailyTimeRecord;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection;

interface DailyTimeRecordRepositoryInterface
{
    public function index(Request $request): Collection;
}
