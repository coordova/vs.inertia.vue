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
        // Asumiendo que $this->resource es una instancia del modelo CharacterSurvey
        // y que el personaje ya está cargado como $this->character (relación belongsTo)

        return [
            // Campos de la tabla pivote character_survey
            'character_id' => $this->character_id, // ID del personaje
            'survey_id' => $this->survey_id,       // ID de la encuesta
            'survey_matches' => $this->survey_matches,
            'survey_wins' => $this->survey_wins,
            'survey_losses' => $this->survey_losses,
            'survey_ties' => $this->survey_ties, // Nueva columna
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'pivot_created_at' => $this->created_at, // created_at de character_survey
            'pivot_updated_at' => $this->updated_at, // updated_at de character_survey

            // Campo calculado: posición en el ranking de la encuesta (añadido por el controlador)
            'survey_position' => $this->when($this->relationLoaded('pivot') && $this->pivot->survey_position !== null, fn() => $this->pivot->survey_position), // <-- Esto es para belongsToMany
            // --- CORRECCIÓN: Campo calculado para belongsTo/Modelo directo ---
            'survey_position' => $this->survey_position, // <-- Esto es para el modelo CharacterSurvey directo
            // --- FIN CORRECCIÓN ---

            // Relación con el modelo 'Character' (personaje relacionado)
            'character' => new CharacterResource($this->whenLoaded('character')), // Asumiendo que 'character' se carga con with()
        ];
    }
}