<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CharacterResource extends JsonResource
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
            'fullname' => $this->fullname,
            'nickname' => $this->nickname,
            'slug' => $this->slug,
            'bio' => $this->bio,
            'dob' => $this->dob ? $this->dob->format('Y-m-d') : null,
            'dob_for_humans' => $this->dob ? $this->dob->diffForHumans(['parts' => 3]) : null,
            'gender' => $this->gender,
            'nationality' => $this->nationality,
            'occupation' => $this->occupation,
            'picture' => $this->picture, // $this->picture ? Storage::url($this->picture) : null,
            'picture_url' => $this->picture ? Storage::url($this->picture) : null,
            // 'picture_thumb' => $this->picture ? $this->picture : null,
            'status' => $this->status,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'created_at_formatted' => $this->created_at->translatedFormat('Y-m-d H:i:s'),
            'updated_at_formatted' => $this->updated_at->translatedFormat('Y-m-d H:i:s'),
            // 'deleted_at' => $this->when($this->trashed(), $this->deleted_at),
        ];
    }
}