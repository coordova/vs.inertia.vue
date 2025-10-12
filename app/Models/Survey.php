<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Survey extends Model
{
    /** @use HasFactory<\Database\Factories\SurveyFactory> */
    use HasFactory, SoftDeletes; // Añadido SoftDeletes

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'category_id',
        'title',
        'slug',
        'description',
        'image',
        'type',
        'status',
        'reverse',
        'date_start',
        'date_end',
        'selection_strategy',
        'max_votes_per_user',
        'allow_ties',
        'tie_weight',
        'is_featured',
        'sort_order',
        'counter',
        'meta_title',
        'meta_description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'category_id' => 'integer',
        'type' => 'integer',
        'status' => 'boolean',
        'reverse' => 'boolean',
        'date_start' => 'date',
        'date_end' => 'date',
        'max_votes_per_user' => 'integer',
        'allow_ties' => 'boolean',
        'tie_weight' => 'decimal:2',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
        'counter' => 'integer',
    ];

    // --- Relaciones ---
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function characters(): BelongsToMany
    {
        return $this->belongsToMany(Character::class, 'character_survey')
                    ->withPivot(['survey_matches', 'survey_wins', 'survey_losses', 'survey_ties', 'is_active', 'sort_order'])
                    ->withTimestamps();
    }

    public function combinatorics(): HasMany
    {
        return $this->hasMany(Combinatoric::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'survey_user')
                    ->withPivot(['progress_percentage', 'total_votes', 'completed_at', 'started_at', 'last_activity_at', 'is_completed', 'completion_time'])
                    ->withTimestamps();
    }

    public function surveyUserPivots(): HasMany
    {
        return $this->hasMany(SurveyUser::class, 'survey_id');
    }
}
