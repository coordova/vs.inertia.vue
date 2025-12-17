<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class SurveyIndexResource extends SurveyBaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $now = now();
        // dump($now);
        // dd($this->date_end);
        // return parent::toArray($request);
        return array_merge($this->baseData($request), [
            'description' => $this->description,
            'duration' => $this->date_start?->diffInDays($this->date_end),
            'duration_left' => (int) $now->diffInDays($this->date_end, false),  // duration left in days from now
            'counter' => $this->counter,
            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ] : null,
            'total_combinations' => $this->total_combinations,
            'character_count' => $this->characters_count ?? $this->characters->count(),
            'created_at_formatted' => $this->created_at->translatedFormat('d-m-Y'),
        ]);
    }
}
