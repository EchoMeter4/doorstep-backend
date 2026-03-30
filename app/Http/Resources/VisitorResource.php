<?php

namespace App\Http\Resources;

use App\Models\Visitor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Visitor */
class VisitorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'organizationId' => $this->organization_id,
            'organization'   => $this->whenLoaded('organization', fn() => $this->organization->name),
            'name'           => $this->name,
            'middleName'     => $this->middle_name,
            'firstLastName'  => $this->first_last_name,
            'secondLastName' => $this->second_last_name,
            'email'          => $this->email,
            'phone'          => $this->phone,
            'company'        => $this->company,
            'enabled'        => $this->enabled,
        ];
    }
}
