<?php

// app/Http/Resources/CharacterSurveyResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\CharacterResource; // Asumiendo que CharacterResource existe

/**
 * Resource para representar la relación character_survey (estadísticas de un personaje en una encuesta específica).
 * Se usa para mostrar rankings de encuesta.
 */
class CharacterSurveyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Asumiendo que $this->resource es una instancia de la relación character_survey (CharacterSurvey pivot model)
        // y que el personaje ya está cargado como $this->character

        return [
            // Campos de la tabla pivote character_survey
            'character_id' => $this->character_id,
            'survey_id' => $this->survey_id,
            'survey_matches' => $this->survey_matches,
            'survey_wins' => $this->survey_wins,
            'survey_losses' => $this->survey_losses,
            'survey_ties' => $this->survey_ties, // Nueva columna
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'pivot_created_at' => $this->created_at,
            'pivot_updated_at' => $this->updated_at,

            // Campo calculado: posición en el ranking de la encuesta (añadido por el controlador)
            'survey_position' => $this->when($this->resource->relationLoaded('pivot') && $this->resource->pivot->survey_position !== null, fn() => $this->resource->pivot->survey_position),

            // Relación con el modelo 'Character' (personaje relacionado)
            'character' => new CharacterResource($this->whenLoaded('character')), // Asumiendo que 'character' se carga con with()
        ];
    }
}