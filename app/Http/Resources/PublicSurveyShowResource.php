<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

// use Illuminate\Http\Resources\Json\JsonResource;

class PublicSurveyShowResource extends SurveyBaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);

        // $combinationsCount = $this->getCombinationsCount();
        $userVotesCount = $this->user_votes_count ?? 0;
        // $progressPercentage = $this->getProgressPercentage();

        // ✅ Solo cargar votos recientes si se necesitan
        $recentVotes = collect();
        if ($this->relationLoaded('userVotes')) {
            $recentVotes = $this->userVotes->take(5)->map(fn ($vote) => [
                'id' => $vote->id,
                'winner' => $vote->winner->fullname ?? 'Unknown',
                'loser' => $vote->loser->fullname ?? 'Unknown',
                'created_at' => $vote->created_at->diffForHumans(),
                'created_at_raw' => $vote->created_at->toISOString(),
            ]);
        }

        return array_merge($this->baseData($request), [
            'description' => $this->description,
            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ] : null,
            'characters' => $this->characters ?? null,
            'reverse' => (bool) $this->reverse,
            'max_votes_per_user' => $this->max_votes_per_user, // 0=ilimitado
            'allow_ties' => $this->allow_ties,
            'counter' => $this->counter,
            'duration' => $this->date_start?->diffInDays($this->date_end),   // Duración en días a partir de la date_start hasta la date_end
            'created_at_formatted' => $this->created_at->translatedFormat('d-m-Y'),
            'updated_at_formatted' => $this->updated_at->translatedFormat('d-m-Y'),
            'date_start_formatted' => $this->date_start?->utc()->format('d-m-Y'),
            'date_end_formatted' => $this->date_end?->utc()->format('d-m-Y'),
            // 'created_at' => $this->created_at->toIso8601String(),
            // 'created_at_utc' => $this->created_at->utc()->toIso8601String(),
            // 'date_start_utc' => $this->date_start?->utc()->toIso8601String(),
            // 'date_end_utc' => $this->date_end?->utc()->toIso8601String(),

        ]);
    }
}
