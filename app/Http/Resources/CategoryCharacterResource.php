<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\CategoryResource; // Recurso para la categoría relacionada

class CategoryCharacterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
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
        ];
    }
}