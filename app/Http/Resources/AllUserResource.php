<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AllUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);

        $deactivated = $this->isDeactivated;
        $account_status = $this->isApproved?'APPROVED':'PENDING';
        $email_verified = $this->isEmailVerified?'YES':'NO';

        return [
            'employee_id' => $this->employee_id,
            'deactivated' => $deactivated,
            'account_status' => $account_status,
            'email_verified' => $email_verified,
            'created_at' => $this->created_at
        ];
    }
}
