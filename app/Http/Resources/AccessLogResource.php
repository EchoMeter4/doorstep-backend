<?php

namespace App\Http\Resources;

use App\Models\AccessLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AccessLog */
class AccessLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->log_code ?? $this->id,
            'credentialType'  => $this->credential_type,
            'credentialValue' => $this->credential_value,
            'users'           => $this->whenLoaded('users', fn() =>
                $this->users->map(fn($user) => [
                    'id'         => $user->id,
                    'name'       => $user->name,
                    'enabled'    => $user->enabled,
                    'credential' => [
                        'type'   => $this->credential_type,
                        'number' => $this->credential_value,
                    ],
                ])
            ),
            'zone'            => $this->whenLoaded('zone', fn() => [
                'name' => $this->zone->name,
                'type' => $this->zone->type,
                'enabled' => $this->zone->enabled,
            ]),
            'timestamp'       => $this->created_at,
            'authorized'      => $this->is_authorized,
        ];
    }
}
