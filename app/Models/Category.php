<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    /** @use HasFactory<\Database\Factories\CategoryFactory> */
    use HasFactory, SoftDeletes; // Añadido SoftDeletes

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'image',
        'color',
        'icon',
        'sort_order',
        'status',
        'is_featured',
        'meta_title',
        'meta_description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'sort_order' => 'integer',
        'status' => 'boolean',
        'is_featured' => 'boolean',
    ];

    // --- Relaciones ---
    public function characters(): BelongsToMany
    {
        return $this->belongsToMany(Character::class, 'category_character')
                    ->withPivot(['elo_rating', 'matches_played', 'wins', 'losses', 'win_rate', 'highest_rating', 'lowest_rating', 'rating_deviation', 'last_match_at', 'is_featured', 'sort_order', 'status'])
                    ->withTimestamps();
    }

    public function surveys(): HasMany
    {
        return $this->hasMany(Survey::class);
    }
}
