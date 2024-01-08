<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HolidayResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'description'   => $this->description,
            'month_day'     => $this->month_day,
            'isspecial'     => $this->isspecial,
            'effectiveDate' => $this->effectiveDate,
            'created_at'    => (string) $this->created_at,
            'updated_at'    => (string) $this->updated_at,
        ];
    }
}
