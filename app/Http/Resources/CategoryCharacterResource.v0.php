<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\CategoryResource; // Recurso para la categoría relacionada

class CategoryCharacterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Asumiendo que $this->resource es una instancia del modelo pivote CategoryCharacter
        // y que la relación 'category' está cargada (gracias a withPivot y possibly ->with() en el controlador)

        return [
            // Campos de la tabla pivote 'category_character'
            'category_id' => $this->resource->category_id,
            'character_id' => $this->resource->character_id,
            'elo_rating' => $this->resource->elo_rating,
            'matches_played' => $this->resource->matches_played,
            'wins' => $this->resource->wins,
            'losses' => $this->resource->losses,
            'ties' => $this->resource->ties, // Asegurar que se serialice
            'win_rate' => $this->resource->win_rate,
            'highest_rating' => $this->resource->highest_rating,
            'lowest_rating' => $this->resource->lowest_rating,
            'rating_deviation' => $this->resource->rating_deviation,
            'last_match_at' => $this->resource->last_match_at,
            'is_featured' => $this->resource->is_featured,
            'sort_order' => $this->resource->sort_order,
            'status' => $this->resource->status,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,

            // Relación con la categoría (cargada por la relación belongsToMany en Character)
            'category' => $this->whenLoaded('category', fn() => new CategoryResource($this->resource->category)),
        ];
        
        /* return [
            // Campos de la tabla pivote category_character
            'category_id' => $this->category_id,
            'character_id' => $this->character_id,
            'elo_rating' => $this->elo_rating,
            'matches_played' => $this->matches_played,
            'wins' => $this->wins,
            'losses' => $this->losses,
            'ties' => $this->ties, // Nueva columna
            'win_rate' => $this->win_rate,
            'highest_rating' => $this->highest_rating,
            'lowest_rating' => $this->lowest_rating,
            'rating_deviation' => $this->rating_deviation,
            'last_match_at' => $this->last_match_at,
            'is_featured' => $this->is_featured,
            'sort_order' => $this->sort_order,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relación con la categoría (si se necesita mostrar el nombre de la categoría en la vista de stats)
            'category' => $this->whenLoaded('category', fn() => new CategoryResource($this->category)),
        ]; */
    }
}