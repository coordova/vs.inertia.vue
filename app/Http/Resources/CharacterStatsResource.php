<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\CategoryCharacterResource; // Recurso para datos pivote categoría-personaje
use App\Http\Resources\CharacterSurveyResource; // Recurso para datos pivote personaje-encuesta

/**
 * Resource para representar las estadísticas detalladas de un personaje.
 * Incluye datos básicos del personaje, sus estadísticas por categoría (ELO, partidas, etc.)
 * y su historial de participación en encuestas con resultados específicos.
 * Extiende CharacterResource para reutilizar sus campos básicos.
 */
class CharacterStatsResource extends CharacterResource // Extender de CharacterResource para reutilizar campos básicos
{
    /**
     * Transform the resource into an array.
     * Incluye datos del personaje, estadísticas por categoría y participación en encuestas.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Obtener los campos básicos del personaje desde CharacterResource
        $basicData = parent::toArray($request);

        // Agregar campos específicos para estadísticas
        $extendedData = [
            // Relación con categorías (y estadísticas dentro de cada categoría)
            'categories_stats' => $this->whenLoaded('categories', fn() => CategoryCharacterResource::collection($this->categories)),

            // Relación con encuestas (y estadísticas dentro de cada encuesta)
            'surveys_participation' => $this->whenLoaded('surveys', fn() => CharacterSurveyResource::collection($this->surveys)),
            // Opcional: Si se quiere un historial más detallado con datos de la encuesta también
            // 'surveys_participation' => $this->whenLoaded('surveys', fn() => $this->surveys->map(function($surveyPivot) {
            //     return [
            //         'survey' => new SurveyResource($surveyPivot), // Asumiendo que la relación 'surveys' carga datos de la encuesta
            //         'pivot_data' => [
            //             'survey_matches' => $surveyPivot->pivot->survey_matches,
            //             'survey_wins' => $surveyPivot->pivot->survey_wins,
            //             // ... otros campos pivote ...
            //         ]
            //     ];
            // })),
        ];

        // Combinar datos básicos y extendidos
        return array_merge($basicData, $extendedData);
    }
}