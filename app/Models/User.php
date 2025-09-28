<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'timezone',
        'locale',
        'type',
        'status',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'type' => 'integer', // Asumiendo tinyInteger
            'status' => 'boolean', // Asumiendo tinyInteger
            'last_login_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    // --- Relaciones ---
    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function surveys(): BelongsToMany
    {
        return $this->belongsToMany(Survey::class, 'survey_user')
                    ->withPivot(['progress_percentage', 'total_votes', 'completed_at', 'started_at', 'last_activity_at', 'is_completed', 'completion_time'])
                    ->withTimestamps();
    }

    public function surveyUserPivots(): HasMany
    {
        return $this->hasMany(SurveyUser::class, 'user_id');
    }
}
