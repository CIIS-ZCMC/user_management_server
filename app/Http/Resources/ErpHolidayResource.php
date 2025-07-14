<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ErpHolidayResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id'             => $this->id,
            'description'    => $this->description,
            'month_day'      => $this->month_day, // e.g., "10-31"
            'isspecial'      => (bool) $this->isspecial, // cast to boolean
            'effective_date' => optional($this->effectiveDate)->format('Y-m-d'),
        ];
    }
}
