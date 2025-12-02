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
        // Acceder a los campos del modelo pivote - $this->resource es un modelo Category con el pivote adjunto $this->resource->pivot.
        $pivot = $this->resource->pivot; // <-- Guardar referencia al pivote

        return [
            // Campos del pivote 'category_character' (accedidos a través de $this->resource->pivot)
            'character_id' => $pivot->character_id,
            'category_id' => $pivot->category_id,
            'elo_rating' => $pivot->elo_rating,
            'matches_played' => $pivot->matches_played,
            'wins' => $pivot->wins,
            'losses' => $pivot->losses,
            'ties' => $pivot->ties, // Asegurar que se serialice
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

            // Información resumida de la categoría (ya disponible en $this->resource)
            // Opcional: Devolver la categoría completa si se necesita más info o si no se incluye en CharacterStatsResource
            // 'category' => new CategoryResource($this->resource), // <-- $this->resource es Category
            // O devolver solo un subconjunto de datos de la categoría directamente en este objeto
            'category_info' => [
                'id' => $this->resource->id, // <-- $this->resource es Category
                'name' => $this->resource->name,
                'slug' => $this->resource->slug,
                'color' => $this->resource->color,
                'icon' => $this->resource->icon,
            ],

            // Campos del modelo 'Category' relacionado (opcional, solo si se necesita en el frontend)
            // Incluir solo campos relevantes para identificar la categoría en el contexto de las estadísticas
            // 'category_info' => [
            //     'id' => $this->resource->id,
            //     'name' => $this->resource->name,
            //     'slug' => $this->resource->slug,
            //     'color' => $this->resource->color,
            //     'icon' => $this->resource->icon,
            // ],
            // O, si se prefiere, incluir los campos directamente en el objeto principal de la estadística
            // 'category_name' => $this->resource->name,
            // 'category_slug' => $this->resource->slug,
            // 'category_color' => $this->resource->color,
        ];
    }
}