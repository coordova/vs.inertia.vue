<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyUser extends Model
{
    // use HasFactory;

    protected $table = 'survey_user';

    public $timestamps = true; // Asumiendo que tiene timestamps

    protected $primaryKey = ['user_id', 'survey_id'];

    protected $fillable = [
        'user_id',
        'survey_id',
        'progress_percentage',
        'total_votes',
        'completed_at',
        'started_at',
        'last_activity_at',
        'is_completed',
        'completion_time',
        'total_combinations_expected',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'survey_id' => 'integer',
        'progress_percentage' => 'decimal:2',
        'total_votes' => 'integer',
        'completed_at' => 'datetime',
        'started_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'is_completed' => 'boolean',
        'completion_time' => 'integer', // segundos
        'total_combinations_expected' => 'integer',
    ];

    // --- Relaciones ---
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }
}
