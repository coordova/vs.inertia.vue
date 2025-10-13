<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SurveyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'category' => new CategoryResource($this->whenLoaded('category')), // Carga condicional
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'image' => $this->image,
            'type' => $this->type,
            'status' => $this->status,
            'date_start' => $this->date_start,
            'date_end' => $this->date_end,
            'selection_strategy' => $this->selection_strategy,
            'max_votes_per_user' => $this->max_votes_per_user,
            'allow_ties' => $this->allow_ties,
            'tie_weight' => $this->tie_weight,
            'is_featured' => $this->is_featured,
            'sort_order' => $this->sort_order,
            'reverse' => $this->reverse,
            'counter' => $this->counter,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            // 'deleted_at' => $this->when($this->trashed(), $this->deleted_at),
        ];
    }
}