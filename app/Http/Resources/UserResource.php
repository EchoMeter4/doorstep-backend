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
            'id'               => $this->id,
            'user_type_id'     => $this->user_type_id,
            'user_type'        => $this->whenLoaded('userType', fn() => $this->userType->name),
            'name'             => $this->name,
            'middle_name'      => $this->middle_name,
            'first_last_name'  => $this->first_last_name,
            'second_last_name' => $this->second_last_name,
            'email'            => $this->email,
            'enabled'          => $this->enabled,
        ];
    }
}
