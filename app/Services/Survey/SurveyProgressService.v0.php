<?php

namespace App\Services\Survey;

use App\Models\Survey;
use App\Models\User;
use App\Models\SurveyUser; // Modelo pivote
use Illuminate\Support\Facades\DB; // Para transacciones si es necesario

class SurveyProgressService
{
    /**
     * Verifica el estado de la encuesta para un usuario específico.
     *
     * @param Survey $survey La encuesta.
     * @param User $user El usuario.
     * @return array ['exists' => bool, 'is_completed' => bool, 'progress' => int, 'total_votes' => int, 'pivot' => SurveyUser|null]
     */
    public function getUserSurveyStatus(Survey $survey, User $user): array
    {
        $pivot = $survey->users()->where('user_id', $user->id)->first(); // Obtiene la entrada de survey_user

        if (!$pivot) {
            return [
                'exists' => false,
                'is_completed' => false,
                'progress' => 0,
                'total_votes' => 0,
                'pivot' => null,
            ];
        }

        return [
            'exists' => true,
            'is_completed' => $pivot->pivot->is_completed,
            'progress' => $pivot->pivot->progress_percentage,
            'total_votes' => $pivot->pivot->total_votes,
            'pivot' => $pivot->pivot, // Devolver el objeto pivote para posibles actualizaciones
        ];
    }

    /**
     * Inicia una sesión de votación para un usuario en una encuesta si no existe.
     *
     * @param Survey $survey La encuesta.
     * @param User $user El usuario.
     * @return SurveyUser El objeto pivote de la relación usuario-encuesta.
     */
    public function startSurveySession(Survey $survey, User $user): SurveyUser
    {
        // Usamos firstOrCreate para evitar errores si se llama múltiples veces antes de que se complete la inicialización
        return SurveyUser::firstOrCreate(
            [
                'user_id' => $user->id,
                'survey_id' => $survey->id,
            ],
            [
                'progress_percentage' => 0.00,
                'total_votes' => 0,
                'started_at' => now(),
                'is_completed' => false,
                'last_activity_at' => now(),
                // completion_time no aplica aún
            ]
        );
    }

    /**
     * Actualiza el progreso del usuario en la encuesta después de un voto.
     *
     * @param SurveyUser $surveyUserPivot El objeto pivote ya existente.
     * @param float $newProgress El nuevo porcentaje de progreso (0.00 a 100.00).
     * @param int $newTotalVotes El nuevo número total de votos.
     * @return void
     */
    public function updateProgress(SurveyUser $surveyUserPivot, float $newProgress, int $newTotalVotes): void
    {
        $surveyUserPivot->update([
            'progress_percentage' => $newProgress,
            'total_votes' => $newTotalVotes,
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Marca la sesión de votación del usuario como completada.
     *
     * @param SurveyUser $surveyUserPivot El objeto pivote ya existente.
     * @return void
     */
    public function markAsCompleted(SurveyUser $surveyUserPivot): void
    {
        $surveyUserPivot->update([
            'is_completed' => true,
            'completed_at' => now(),
            // completion_time se podría calcular aquí si no se almacena como campo separado
            // 'completion_time' => now()->diffInSeconds($surveyUserPivot->started_at),
        ]);
    }
}