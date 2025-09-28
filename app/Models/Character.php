<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Character extends Model
{
    /** @use HasFactory<\Database\Factories\CharacterFactory> */
    use HasFactory, SoftDeletes; // Añadido SoftDeletes

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'fullname',
        'nickname',
        'slug',
        'bio',
        'dob',
        'gender',
        'nationality',
        'occupation',
        'picture',
        'status',
        'meta_title',
        'meta_description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'dob' => 'date',
        'gender' => 'integer',
        'status' => 'boolean',
    ];

    // --- Relaciones ---
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_character')
                    ->withPivot(['elo_rating', 'matches_played', 'wins', 'losses', 'win_rate', 'highest_rating', 'lowest_rating', 'rating_deviation', 'last_match_at', 'is_featured', 'sort_order', 'status'])
                    ->withTimestamps();
    }

    public function surveys(): BelongsToMany
    {
        return $this->belongsToMany(Survey::class, 'character_survey')
                    ->withPivot(['survey_matches', 'survey_wins', 'survey_losses', 'survey_ties', 'is_active', 'sort_order'])
                    ->withTimestamps();
    }

    public function votesAsWinner(): HasMany
    {
        return $this->hasMany(Vote::class, 'winner_id');
    }

    public function votesAsLoser(): HasMany
    {
        return $this->hasMany(Vote::class, 'loser_id');
    }

    public function combinatoricsAsCharacter1(): HasMany
    {
        return $this->hasMany(Combinatoric::class, 'character1_id');
    }

    public function combinatoricsAsCharacter2(): HasMany
    {
        return $this->hasMany(Combinatoric::class, 'character2_id');
    }
}
