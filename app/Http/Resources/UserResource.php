<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Importante: No exponer información sensible como contraseñas o tokens de 2FA
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'timezone' => $this->timezone,
            'locale' => $this->locale,
            'type' => $this->type, // Considerar si exponer este campo al frontend público
            'status' => $this->status,
            'last_login_at' => $this->last_login_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            // 'deleted_at' => $this->when($this->trashed(), $this->deleted_at),
            'created_at_formatted' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}