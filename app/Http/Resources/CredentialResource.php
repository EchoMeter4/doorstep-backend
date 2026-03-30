<?php

namespace App\Http\Resources;

use App\Models\Credential;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Credential */
class CredentialResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'userId'         => $this->user_id,
            'user'           => $this->whenLoaded('user', fn() => $this->user->name),
            'credentialType' => $this->credential_type,
            'credentialCode' => $this->credential_code,
            'isActive'       => $this->is_active,
            'issuedAt'       => $this->issued_at,
        ];
    }
}
