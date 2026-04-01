<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
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
            'userTypeId' => $this->user_type_id,
            'userType' => $this->whenLoaded('userType', fn() => $this->userType->name),
            'name' => $this->name,
            'middleName' => $this->middle_name,
            'firstLastName' => $this->first_last_name,
            'secondLastName' => $this->second_last_name,
            'email' => $this->email,
            'roles' => RoleResource::collection($this->whenLoaded('roles')),
            'vehicles' => VehicleResource::collection($this->whenLoaded('vehicles')),
            'enabled' => $this->enabled,
        ];
    }
}
