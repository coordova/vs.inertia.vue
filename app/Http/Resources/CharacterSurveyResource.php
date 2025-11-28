<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\CharacterResource; // Asumiendo que CharacterResource existe
use Illuminate\Support\Facades\Storage;

/**
 * Resource para representar la estadística de un personaje en una encuesta específica (fila del ranking).
 * Se usa para mostrar rankings de encuesta.
 * Ahora recibe un stdClass con campos de character_survey, category_character y character (JOINs).
 */
class CharacterSurveyResource extends JsonResource
{
    /**
     * Transform the resource (stdClass) into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Asumiendo que $this->resource es un stdClass resultado de la consulta JOIN
        // y contiene: survey_matches, survey_wins, survey_losses, survey_ties, elo_rating, fullname, nickname, picture, etc.

        return [
            // Campos de la tabla pivote character_survey
            'character_id' => $this->resource->character_id,
            'survey_id' => $this->resource->survey_id,
            'survey_matches' => $this->resource->survey_matches,
            'survey_wins' => $this->resource->survey_wins,
            'survey_losses' => $this->resource->survey_losses,
            'survey_ties' => $this->resource->survey_ties, // Nueva columna
            'is_active' => $this->resource->is_active,
            'sort_order' => $this->resource->sort_order,
            'pivot_created_at' => $this->resource->created_at, // created_at de character_survey
            'pivot_updated_at' => $this->resource->updated_at, // updated_at de character_survey

            // Campo calculado: posición en el ranking de la encuesta (añadido por el servicio RankingService)
            'survey_position' => $this->resource->survey_position, // <-- Tomado del stdClass modificado por RankingService

            // Campos del rating ELO en la categoría de la encuesta (desde category_character)
            'elo_rating_in_category' => $this->resource->elo_rating, // <-- Tomado del stdClass
            'matches_played_in_category' => $this->resource->matches_played, // Opcional
            'wins_in_category' => $this->resource->wins,             // Opcional
            'losses_in_category' => $this->resource->losses,         // Opcional
            'ties_in_category' => $this->resource->ties,             // Opcional
            'win_rate_in_category' => $this->resource->win_rate,     // Opcional

            // Relación con el modelo 'Character' (datos del personaje, tomados del stdClass)
            'character' => [
                'id' => $this->resource->character_id, // Tomado del stdClass
                'fullname' => $this->resource->fullname, // Tomado del stdClass
                'nickname' => $this->resource->nickname, // Tomado del stdClass
                'picture' => $this->resource->picture,   // Tomado del stdClass (ruta relativa)
                'picture_url' => $this->resource->picture ? Storage::url($this->resource->picture) : null, // Generar URL si existe
                'slug' => $this->resource->slug,       // Tomado del stdClass
                // Añadir otros campos necesarios del personaje si se usan en la UI
            ],
        ];
    }
}