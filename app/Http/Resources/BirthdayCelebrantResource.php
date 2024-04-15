<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Cache;

class BirthdayCelebrantResource extends JsonResource
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
            'profile_url' => Cache::get("server_domain").$this->employeeProfile->profile_url,
            'employee_id' => $this->employeeProfile->employee_id,
            'name' => $this->name(),
            'age' => Carbon::now()->diffInYears($this->date_of_birth),
        ];
    }
}
