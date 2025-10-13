<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Http\Resources\SurveyBaseResource;

class SurveyIndexResource extends SurveyBaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);
        return array_merge($this->baseData($request), [
            'description' => $this->description,
            'duration' => $this->date_start?->diffInDays($this->date_end),
            'counter' => $this->counter,
            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ] : null,
            'character_count' => $this->characters_count ?? $this->characters->count(),
            'created_at_formatted' => $this->created_at->translatedFormat('d-m-Y'),
        ]);
    }
}
