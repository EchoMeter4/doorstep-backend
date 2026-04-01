<?php

namespace App\Http\Resources;

use App\Models\Pass;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Pass */
class PassResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'visitorId'  => $this->visitor_id,
            'visitor'    => $this->whenLoaded('visitor', fn() => $this->visitor->name),
            'createdBy'  => $this->created_by,
            'validFrom'  => $this->valid_from,
            'validUntil' => $this->valid_until,
            'status'     => $this->status,
            'zones'      => ZoneResource::collection($this->whenLoaded('zones')),
        ];
    }
}
