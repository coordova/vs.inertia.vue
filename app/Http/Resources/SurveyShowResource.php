<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

// use Illuminate\Http\Resources\Json\JsonResource;

class SurveyShowResource extends SurveyBaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);

        return array_merge($this->baseData($request), [
            'description' => $this->description,
            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ] : null,
            // 'date_start_formatted' => $this->date_start->translatedFormat('d-m-Y'),
            // 'date_end_formatted' => $this->date_end->translatedFormat('d-m-Y'),
            'max_votes_per_user' => $this->max_votes_per_user,
            'allow_ties' => (bool) $this->allow_ties,
            'tie_weight' => $this->tie_weight,
            'is_featured' => (bool) $this->is_featured,
            'counter' => $this->counter,
            'duration' => $this->date_start->diffInDays($this->date_end),   // Duración en días a partir de la date_start hasta la date_end
            'characters' => $this->characters ?? null,

            'character_count' => count($this->characters),
            'combinations_count' => $this->combinatorics_count,

            'created_at_formatted' => $this->created_at->translatedFormat('d-m-Y | H:i'),
            'updated_at_formatted' => $this->updated_at->translatedFormat('d-m-Y | H:i'),

            // ----------------------------------------------
            //

            // ✅ Datos optimizados para detalles
            // 'character_count' => $this->characters_count ?? $this->characters->count(),
            // 'combinations_count' => $this->total_combinations,
            // 'user_votes_count' => $userVotesCount,
            // 'progress_percentage' => $this->total_combinations / $userVotesCount * 100,
            // 'recent_votes' => $recentVotes,
            // 'is_completed' => $this->total_combinations / $userVotesCount * 100 >= 100,
            // 'remaining_combinations' => max(0, $this->total_combinations - $userVotesCount),
        ]);
    }
}
