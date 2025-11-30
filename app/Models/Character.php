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

    /**
     * Categorías en las que participa el personaje.
     * La tabla pivote es 'category_character'.
     * Incluye datos pivote como elo_rating, matches_played, wins, losses, etc.
     * Incluye la relación con el modelo 'Category'.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_character', 'character_id', 'category_id')
                    ->using(CategoryCharacter::class) // Asumiendo que CategoryCharacter es el modelo pivote
                    ->withPivot([
                        'elo_rating',
                        'matches_played',
                        'wins',
                        'losses',
                        'ties', // Asegurar que 'ties' esté incluido
                        'win_rate',
                        'highest_rating',
                        'lowest_rating',
                        'rating_deviation',
                        'last_match_at',
                        'is_featured',
                        'sort_order',
                        'status',
                        'created_at',
                        'updated_at',
                    ])
                    ->withTimestamps(); // Asumiendo que la tabla pivote tiene created_at, updated_at
    }

    /**
     * Encuestas en las que participa el personaje.
     * La tabla pivote es 'character_survey'.
     * Incluye datos pivote como survey_matches, survey_wins, survey_losses, etc.
     * Incluye la relación con el modelo 'Survey'.
     */
    public function surveys(): BelongsToMany
    {
        return $this->belongsToMany(Survey::class, 'character_survey', 'character_id', 'survey_id')
                    ->using(CharacterSurvey::class) // Asumiendo que CharacterSurvey es el modelo pivote
                    ->withPivot([
                        'survey_matches',
                        'survey_wins',
                        'survey_losses',
                        'survey_ties', // Asegurar que 'survey_ties' esté incluido
                        'is_active',
                        'sort_order',
                        'created_at', // pivot_created_at
                        'updated_at', // pivot_updated_at
                        // 'survey_position' -> Este campo probablemente no se almacene directamente en character_survey,
                        // sino que se calcula dinámicamente o se almacena en otra tabla (como en RankingService).
                        // Si se almacena en character_survey, incluirlo aquí.
                        // 'survey_position',
                    ])
                    ->withTimestamps(); // Asumiendo que la tabla pivote tiene created_at, updated_at
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
