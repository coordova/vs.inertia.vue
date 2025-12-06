<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CombinatoricResource extends JsonResource
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
            'survey_id' => $this->survey_id,
            'survey' => new SurveyResource($this->whenLoaded('survey')), // Carga condicional
            'character1_id' => $this->character1_id,
            'character1' => new CharacterResource($this->whenLoaded('character1')), // Carga condicional
            'character2_id' => $this->character2_id,
            'character2' => new CharacterResource($this->whenLoaded('character2')), // Carga condicional
            'total_comparisons' => $this->total_comparisons,
            'last_used_at' => $this->last_used_at,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}