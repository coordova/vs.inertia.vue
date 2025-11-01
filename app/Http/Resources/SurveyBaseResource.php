<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\Math\CombinatoricsService;

class SurveyBaseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }

    /**
     * Datos base esenciales para todas las vistas de encuestas
     * 
     * @param Request $request
     * @return array<string, mixed>
     */
    protected function baseData(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'status' => (bool) $this->status,
            'type' => (int) $this->type,
            'category_id' => $this->category_id,
            'date_start' => $this->date_start?->format('Y-m-d'),
            'date_end' => $this->date_end?->format('Y-m-d'),
            'selection_strategy' => $this->selection_strategy,
            'slug' => $this->slug,
            'is_active' => $this->date_start <= now() && $this->date_end >= now(),
            
            // Datos de fechas formateadas
            'date_start_formatted' => $this->date_start?->utc()->format('d-m-Y'),
            'date_end_formatted' => $this->date_end?->utc()->format('d-m-Y'),
        ];
    }

    /**
     * Calcular número de combinaciones usando datos precargados
     */
    /* protected function getCombinationsCount(): int
    {
        $characterCount = $this->characters_count ?? $this->characters->count();
        return $characterCount >= 2 
            ? CombinatoricsService::combinations($characterCount, 2)
            : 0;
    } */

    /**
     * Calcular porcentaje de progreso usando datos precargados
     */
    /* protected function getProgressPercentage(): float
    {
        $combinationsCount = $this->getCombinationsCount();
        $userVotesCount = $this->user_votes_count ?? 0;
        
        return $combinationsCount > 0 
            ? round(($userVotesCount / $combinationsCount) * 100, 1)
            : 0;
    } */
}
