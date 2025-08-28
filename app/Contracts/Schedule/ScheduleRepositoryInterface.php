<?php

namespace App\Contracts\Schedule;

use Illuminate\Database\Eloquent\Collection;

interface ScheduleRepositoryInterface
{
    public function index($user): Collection;
}
