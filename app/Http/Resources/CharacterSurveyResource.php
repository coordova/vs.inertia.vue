<?php

// app/Http/Resources/CharacterSurveyResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\SurveyResource; // Recurso para la encuesta relacionada

/**
 * Resource para representar la relación character-survey (estadísticas de un personaje en una encuesta específica).
 * Se usa para mostrar participación en encuestas en CharacterStats o en SurveyResults.
 * Este recurso maneja un *objeto modelo relacionado* (Survey) *con* su pivote adjunto (CharacterSurvey).
 * $this->resource es un modelo Survey con $this->resource->pivot como CharacterSurvey.
 */
class CharacterSurveyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Acceder a los campos del modelo pivote
        $pivot = $this->resource->pivot; // <-- Guardar referencia al pivote

        return [
            // Campos del pivote 'character_survey'
            'character_id' => $pivot->character_id,
            'survey_id' => $pivot->survey_id,
            'survey_matches' => $pivot->survey_matches,
            'survey_wins' => $pivot->survey_wins,
            'survey_losses' => $pivot->survey_losses,
            'survey_ties' => $pivot->survey_ties, // Nueva columna
            'is_active' => $pivot->is_active,
            'sort_order' => $pivot->sort_order,
            'pivot_created_at' => $pivot->created_at,
            'pivot_updated_at' => $pivot->updated_at,

            // Campo calculado: posición en el ranking de la encuesta (añadido por el servicio RankingService o calculado aquí si se implementa)
            // 'survey_position' => $pivot->survey_position, // Incluir si se almacena en character_survey o se calcula

            // Relación con el modelo 'Survey' (ya cargada en $this->resource)
            // Asumiendo que SurveyResource ya incluye la categoría si es necesaria
            'survey' => new SurveyResource($this->resource), // <-- $this->resource es el modelo Survey con sus datos y relaciones (como 'category') ya cargadas
        ];
    }
}