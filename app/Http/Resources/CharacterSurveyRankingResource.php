<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\CharacterResource; // Importar el recurso del personaje
use Illuminate\Support\Facades\Storage;

/**
 * Resource para representar la estadística de un personaje en una encuesta específica
 * en el contexto del ranking de resultados de una encuesta (SurveyResults).
 * Este recurso maneja un *objeto stdClass* devuelto por una consulta JOIN compleja
 * que contiene campos de character_survey, category_character y character.
 * $this->resource es un stdClass con campos planos como character_id, survey_id, elo_rating, fullname, etc.
 */
class CharacterSurveyRankingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Asumiendo que $this->resource es un stdClass con campos planos
        // Devolver la estructura esperada para la tabla de ranking

        return [
            // Campos del pivote 'character_survey' (accedidos directamente desde stdClass)
            'character_id' => $this->resource->character_id,
            'survey_id' => $this->resource->survey_id,
            'survey_matches' => $this->resource->survey_matches,
            'survey_wins' => $this->resource->survey_wins,
            'survey_losses' => $this->resource->survey_losses,
            'survey_ties' => $this->resource->survey_ties, // Asegurar que se serialice
            'is_active' => $this->resource->is_active,
            'sort_order' => $this->resource->sort_order,
            'pivot_created_at' => $this->resource->pivot_created_at, // created_at de character_survey (renombrado en la consulta JOIN)
            'pivot_updated_at' => $this->resource->pivot_updated_at, // updated_at de character_survey (renombrado en la consulta JOIN)

            // Campo calculado: posición en el ranking de la encuesta (ya calculado por RankingService o incluido en el stdClass)
            'survey_position' => $this->resource->survey_position, // <-- Tomado del stdClass modificado por RankingService o cálculo previo

            // Campos del rating ELO en la categoría de la encuesta (desde category_character, ya incluidos en stdClass)
            'elo_rating_in_category' => $this->resource->elo_rating, // <-- Tomado del stdClass
            'matches_played_in_category' => $this->resource->matches_played, // Opcional, desde stdClass
            'wins_in_category' => $this->resource->wins,             // Opcional, desde stdClass
            'losses_in_category' => $this->resource->losses,         // Opcional, desde stdClass
            'ties_in_category' => $this->resource->ties,             // Opcional, desde stdClass
            'win_rate_in_category' => $this->resource->win_rate,     // Opcional, desde stdClass

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