<?php

namespace App\Http\Resources;

use App\Services\Math\CombinatoricsService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
     * @return array<string, mixed>
     */
    protected function baseData(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'type' => (int) $this->type,
            'status' => (bool) $this->status,
            'reverse' => (bool) $this->reverse,
            'date_start_formatted' => $this->date_start->translatedFormat('d-m-Y'), // o 'Y-m-d' o 'M j, Y'
            'date_end_formatted' => $this->date_end->translatedFormat('d-m-Y'),
            
            'is_active' => ($this->status && $this->date_start <= now() && $this->date_end >= now()),

            // 'date_start' => $this->date_start, // $this->date_start?->format('Y-m-d'),
            // 'date_end' => $this->date_end, // $this->date_end?->format('Y-m-d'),
            // 'selection_strategy' => $this->selection_strategy,

            // Datos de fechas formateadas
            // 'date_start_formatted' => $this->date_start?->utc()->format('d-m-Y'),
            // 'date_end_formatted' => $this->date_end?->utc()->format('d-m-Y'),
            // 'created_at_formatted' => $this->created_at->translatedFormat('d-m-Y H:i:s'),
            // 'updated_at_formatted' => $this->updated_at->translatedFormat('d-m-Y H:i:s'),
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
