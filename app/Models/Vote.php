<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vote extends Model
{
    /** @use HasFactory<\Database\Factories\VoteFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'combinatoric_id',
        'survey_id',
        'winner_id',
        'loser_id',
        'tie_score',
        'voted_at',
        'ip_address',
        'user_agent',
        'is_valid',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'user_id' => 'integer',
        'combinatoric_id' => 'integer',
        'survey_id' => 'integer',
        'winner_id' => 'integer',
        'loser_id' => 'integer',
        'tie_score' => 'decimal:2',
        'voted_at' => 'datetime',
        'is_valid' => 'boolean',
    ];

    // --- Relaciones ---
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function combinatoric(): BelongsTo
    {
        return $this->belongsTo(Combinatoric::class);
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'winner_id');
    }

    public function loser(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'loser_id');
    }
}
