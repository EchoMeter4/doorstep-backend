<?php

namespace App\Http\Resources;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Role
 */
class RoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'organizationId' => $this->organization_id,
            'name'            => $this->name,
            'description'     => $this->description,
            'enabled'         => $this->enabled,
            'zones'           => ZoneResource::collection($this->whenLoaded('zones')),
            'userIds'         => $this->whenLoaded('users', fn() => $this->users->pluck('id')),
        ];
    }
}
