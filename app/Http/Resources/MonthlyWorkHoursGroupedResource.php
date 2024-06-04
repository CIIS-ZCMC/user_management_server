<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class MonthlyWorkHoursGroupedResource extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray($request)
    {
        // Map through the collection to format the data
        $formattedData = $this->resource->map(function ($items, $monthYear) {
            return [
                'id' => 1,
                'month_year' => $monthYear,
                'employment_type' => $items->map(function ($item) {
                    return [
                        'id' => $item->employmentType->id,
                        'name' => $item->employmentType->name,
                        'work_hours' => $item->employmentType->monthlyWorkingHours->work_hours,
                    ];
                })->values()
            ];
        })->values();

        return $formattedData;
    }
}
