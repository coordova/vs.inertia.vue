<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VoteResource extends JsonResource
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
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')), // Carga condicional
            'combinatoric_id' => $this->combinatoric_id,
            'combinatoric' => new CombinatoricResource($this->whenLoaded('combinatoric')), // Carga condicional
            'survey_id' => $this->survey_id,
            'survey' => new SurveyResource($this->whenLoaded('survey')), // Carga condicional
            'winner_id' => $this->winner_id,
            'winner' => new CharacterResource($this->whenLoaded('winner')), // Carga condicional
            'loser_id' => $this->loser_id,
            'loser' => new CharacterResource($this->whenLoaded('loser')), // Carga condicional
            'tie_score' => $this->tie_score,
            'voted_at' => $this->voted_at,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent, // Puede ser muy largo, considerar si exponerlo
            'is_valid' => $this->is_valid,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}