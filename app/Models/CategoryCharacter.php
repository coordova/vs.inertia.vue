<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryCharacter extends Model
{
    // use HasFactory;

    // No usamos $fillable ni $guarded aquí, ya que es una tabla pivote con campos propios
    // Se maneja comúnmente a través de belongsToMany con ->withPivot()

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'category_character';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true; // Asumiendo que tiene timestamps

    protected $fillable = [
        'elo_rating',
        'matches_played',
        'wins',
        'losses',
        'ties',
        'win_rate',
        'highest_rating',
        'lowest_rating',
        'rating_deviation',
        'last_match_at',
        'is_featured',
        'sort_order',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'elo_rating' => 'integer',
        'matches_played' => 'integer',
        'wins' => 'integer',
        'losses' => 'integer',
        'ties' => 'integer',
        'win_rate' => 'decimal:2',
        'highest_rating' => 'integer',
        'lowest_rating' => 'integer',
        'rating_deviation' => 'decimal:2',
        'last_match_at' => 'datetime',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
        'status' => 'boolean',
    ];

    // --- Relaciones ---
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }
}
