<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/**
 * Resource para interfaz de votación
 * 
 * Contiene solo los datos necesarios para la interfaz de votación.
 * Mínimo de datos para mejor performance durante la votación.
 * 
 * @package App\Http\Resources
 */
class SurveyVoteResource extends BaseSurveyResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
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