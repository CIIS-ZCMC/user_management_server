<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnnouncementsResource extends JsonResource
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
            'title' => $this->title,
            'content' => $this->content,
            'scheduled_at'=>$this->scheduled_at,
            'attachments' => $this->attachments,
            'created_at' => $this->created_at,
            'forsupervisors'=>$this->forsupervisors,
            'posted'=>$this->posted
        ];
    }
}
