<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MyApprovedLeaveApplicationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'from' => Carbon::parse($this->date_from)->format('Y-m-d'),
            'to' => Carbon::parse($this->date_to)->addDay()->format('Y-m-d'),
            'leave_type' => $this->leaveType->name
        ];
    }
}
