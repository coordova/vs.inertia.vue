<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Combinatoric extends Model
{
    /** @use HasFactory<\Database\Factories\CombinatoricFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'survey_id',
        'character1_id',
        'character2_id',
        'total_comparisons',
        'last_used_at',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'survey_id' => 'integer',
        'character1_id' => 'integer',
        'character2_id' => 'integer',
        'total_comparisons' => 'integer',
        'last_used_at' => 'datetime',
        'status' => 'boolean',
    ];

    // --- Relaciones ---
    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function character1(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'character1_id');
    }

    public function character2(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'character2_id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }
}
