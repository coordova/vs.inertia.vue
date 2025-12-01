<?php

// app/Http/Resources/CategoryCharacterResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para representar la relación character-category (estadísticas de un personaje en una categoría específica).
 * Se usa para mostrar rankings por categoría o estadísticas en CharacterStats.
 * Este recurso maneja un *objeto modelo relacionado* (Category) *con* su pivote adjunto (CategoryCharacter).
 * $this->resource es un modelo Category con $this->resource->pivot como CategoryCharacter.
 */
class CategoryCharacterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Acceder a los campos del modelo pivote
        $pivot = $this->resource->pivot; // <-- Guardar referencia al pivote

        return [
            // Campos del pivote 'category_character'
            'character_id' => $pivot->character_id,
            'category_id' => $pivot->category_id,
            'elo_rating' => $pivot->elo_rating,
            'matches_played' => $pivot->matches_played,
            'wins' => $pivot->wins,
            'losses' => $pivot->losses,
            'ties' => $pivot->ties,
            'win_rate' => $pivot->win_rate,
            'highest_rating' => $pivot->highest_rating,
            'lowest_rating' => $pivot->lowest_rating,
            'rating_deviation' => $pivot->rating_deviation,
            'last_match_at' => $pivot->last_match_at,
            'is_featured' => $pivot->is_featured,
            'sort_order' => $pivot->sort_order,
            'status' => $pivot->status,
            'pivot_created_at' => $pivot->created_at,
            'pivot_updated_at' => $pivot->updated_at,

            // Campos del modelo 'Category' relacionado (opcional, solo si se necesita en el frontend)
            // Incluir solo campos relevantes para identificar la categoría en el contexto de las estadísticas
            'category_info' => [
                'id' => $this->resource->id,
                'name' => $this->resource->name,
                'slug' => $this->resource->slug,
                'color' => $this->resource->color,
                'icon' => $this->resource->icon,
            ],
            // O, si se prefiere, incluir los campos directamente en el objeto principal de la estadística
            // 'category_name' => $this->resource->name,
            // 'category_slug' => $this->resource->slug,
            // 'category_color' => $this->resource->color,
        ];
    }
}