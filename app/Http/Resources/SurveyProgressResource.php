<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\SurveyBaseResource;

class SurveyProgressResource extends SurveyBaseResource
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
            // ✅ Solo datos esenciales para votación
            'character_count' => $this->characters_count ?? $this->characters->count(),
            'total_combinations' => $this->total_combinations ?? 0,
            'total_votes' => $this->total_votes ?? 0,
            'progress_percentage' => $progressPercentage,
            'is_completed' => $progressPercentage >= 100,
            'remaining_combinations' => max(0, $this->total_combinations - $this->total_votes),
        ]);
    }
}
