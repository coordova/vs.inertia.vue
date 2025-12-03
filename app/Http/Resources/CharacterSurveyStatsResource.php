<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\SurveyResource; // Importar el recurso de la encuesta relacionada

/**
 * Resource para representar la estadística de un personaje en una encuesta específica
 * en el contexto de la vista de estadísticas del personaje (CharacterStats).
 * Este recurso maneja un *objeto modelo relacionado* (Survey) *con* su pivote adjunto (CharacterSurvey).
 * $this->resource es un modelo Survey con $this->resource->pivot como CharacterSurvey.
 */
class CharacterSurveyStatsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Asumiendo que $this->resource es un modelo Survey con $this->resource->pivot como CharacterSurvey
        // y que la relación 'survey.category' está cargada si se requiere mostrarla aquí

        $pivot = $this->resource->pivot; // Acceder al modelo pivote

        return [
            // Campos del pivote 'character_survey' (accedidos a través de $this->resource->pivot)
            'character_id' => $pivot->character_id,
            'survey_id' => $pivot->survey_id,
            'survey_matches' => $pivot->survey_matches,
            'survey_wins' => $pivot->survey_wins,
            'survey_losses' => $pivot->survey_losses,
            'survey_ties' => $pivot->survey_ties, // Asegurar que se serialice
            'is_active' => $pivot->is_active,
            'sort_order' => $pivot->sort_order,
            'pivot_created_at' => $pivot->created_at, // created_at de character_survey
            'pivot_updated_at' => $pivot->updated_at, // updated_at de character_survey

            // Campo calculado: posición en el ranking de la encuesta (añadido por el servicio RankingService o calculado aquí si se implementa)
            // 'survey_position' => $pivot->survey_position, // Incluir si se almacena en character_survey o se calcula

            // Relación con el modelo 'Survey' (ya cargado en $this->resource)
            // Asumiendo que SurveyResource ya incluye la categoría si se cargó
            'survey' => SurveyResource::make($this->resource)->resolve(), // <-- Serializar la encuesta y su categoría
        ];
    }
}