<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterSurvey extends Model
{
    // use HasFactory;

    protected $table = 'character_survey';

    public $timestamps = true; // Asumiendo que tiene timestamps

    protected $fillable = [
        'character_id',
        'survey_id',
        'survey_matches',
        'survey_wins',
        'survey_losses',
        'survey_ties',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'character_id' => 'integer',
        'survey_id' => 'integer',
        'survey_matches' => 'integer',
        'survey_wins' => 'integer',
        'survey_losses' => 'integer',
        'survey_ties' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // --- Relaciones ---
    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }
}
