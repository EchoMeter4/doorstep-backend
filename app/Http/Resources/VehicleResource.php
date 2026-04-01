<?php

namespace App\Http\Resources;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Vehicle */
class VehicleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'plateNumber' => $this->plate_number,
            'make'        => $this->make,
            'model'       => $this->model,
            'year'        => $this->year,
            'color'       => $this->color,
            'type'        => $this->type,
            'users'       => UserResource::collection($this->whenLoaded('users')),
        ];
    }
}
