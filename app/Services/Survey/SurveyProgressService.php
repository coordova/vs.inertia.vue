<?php

namespace App\Services\Survey;

use App\Models\Survey;
use App\Models\User;
use App\Models\SurveyUser; // Modelo pivote
use Illuminate\Support\Facades\DB; // Para transacciones si es necesario
use Illuminate\Database\Eloquent\Relations\Pivot as EloquentPivot; // Alias para claridad

class SurveyProgressService
{
    /**
     * Verifica el estado de la encuesta para un usuario específico.
     *
     * @param Survey $survey La encuesta.
     * @param User $user El usuario.
     * @return array ['exists' => bool, 'is_completed' => bool, 'progress' => float, 'total_votes' => int, 'total_expected' => int|null, 'pivot' => SurveyUser|null]
     */
    public function getUserSurveyStatus(Survey $survey, User $user): array
    {
        $pivotEntry = SurveyUser::where('survey_id', $survey->id)->where('user_id', $user->id)->first();

        if (!$pivotEntry) {
            return [
                'exists' => false,
                'is_completed' => false,
                'progress' => 0.0,
                'total_votes' => 0,
                'total_expected' => null,
                'pivot' => null, // O incluso omitir 'pivot' si se devuelve $pivotEntry
            ];
        }

        // Calcular progreso basado en $pivotEntry
        $totalExpected = $pivotEntry->total_combinations_expected;
        $progress = 0.0;
        if ($totalExpected && $totalExpected > 0) {
            $progress = min(100.0, ($pivotEntry->total_votes / $totalExpected) * 100);
        }

        return [
            'exists' => true,
            'is_completed' => $pivotEntry->is_completed,
            'progress' => $progress,
            'total_votes' => $pivotEntry->total_votes,
            'total_expected' => $totalExpected,
            'pivot' => $pivotEntry, // Ahora es una instancia de SurveyUser
        ];
    }

    public function getUserSurveyStatus___(Survey $survey, User $user): array
    {
        // Usamos el acceso directo a la relación pivote a través de belongsToMany
        // Esto debería devolver una instancia de SurveyUser si la relación está bien definida
        $pivot = $survey->users()->where('user_id', $user->id)->first(); // Devuelve el modelo User con la relación pivote cargada // Obtiene la entrada de survey_user
        
        if (!$pivot) {
            return [
                'exists' => false,
                'is_completed' => false,
                'progress' => 0.0,
                'total_votes' => 0,
                'total_expected' => null, // No aplica si no existe
                'pivot' => null,
            ];
        }

        // $pivot->pivot es el modelo pivote (SurveyUser en este caso)
        $pivotModel = $pivot->pivot; // <-- Este debería ser de tipo SurveyUser // <-- Este es el objeto Pivot de Laravel, no SurveyUser

        // Calcular progreso basado en votos actuales vs esperados
        $totalExpected = $pivotModel->total_combinations_expected;
        $progress = 0.0;
        if ($totalExpected && $totalExpected > 0) {
            $progress = min(100.0, ($pivotModel->total_votes / $totalExpected) * 100);
        }

        return [
            'exists' => true,
            'is_completed' => $pivotModel->is_completed,
            'progress' => $progress,
            'total_votes' => $pivotModel->total_votes,
            'total_expected' => $totalExpected,
            'pivot' => $pivotModel, // Devolver el objeto pivote para posibles actualizaciones
        ];
    }

    /**
     * Inicia una sesión de votación para un usuario en una encuesta si no existe.
     * Calcula y almacena el número total de combinaciones esperadas.
     *
     * @param Survey $survey La encuesta.
     * @param User $user El usuario.
     * @return SurveyUser El objeto pivote de la relación usuario-encuesta.
     */
    public function startSurveySession(Survey $survey, User $user): SurveyUser
    {
        // Calcula el total de combinaciones posibles basado en los personajes activos en la encuesta
        // Carga los personajes activos en la relación pivote
        $activeCharacterCount = $survey->characters()->wherePivot('is_active', true)->count();

        // Calcula C(n, 2)
        $totalCombinationsExpected = $activeCharacterCount > 1 ? ($activeCharacterCount * ($activeCharacterCount - 1)) / 2 : 0;

        // Usamos firstOrCreate para evitar errores si se llama múltiples veces antes de que se complete la inicialización
        return SurveyUser::firstOrCreate(
            [
                'user_id' => $user->id,
                'survey_id' => $survey->id,
            ],
            [
                'progress_percentage' => 0.00, // Este campo puede volverse redundante si calculamos progreso sobre la marcha
                'total_votes' => 0,
                'total_combinations_expected' => $totalCombinationsExpected, // Almacenamos el total calculado
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
     * @param Pivot|null $surveyUserPivot El objeto pivote de la relación survey_user (adjuntado por Laravel).
     * @param float $newProgress El nuevo porcentaje de progreso (0.00 a 100.00).
     * @param int $newTotalVotes El nuevo número total de votos.
     * @return void
     */
    // public function updateProgress(Pivot|null $surveyUserPivot, float $newProgress, int $newTotalVotes): void // <-- ANTES (Incorrecto si no hay 'use')
    public function updateProgress(SurveyUser|null $surveyUserPivot, /* float $newProgress, */ int $newTotalVotes): void // <-- Usar el alias correcto
    {
        if (!$surveyUserPivot) {
             // O lanzar excepción si es un error crítico
            // throw new \InvalidArgumentException('SurveyUserPivot cannot be null for updateProgress.');
            // O simplemente retornar si es válido que sea null (aunque raro en este contexto)
             \Log::warning('updateProgress called with null pivot.');
            return;
        }

        // --- Calcular el progreso DENTRO del método ---
        $totalExpected = $surveyUserPivot->total_combinations_expected ?? 0;
        $calculatedProgress = 0.0;
        if ($totalExpected > 0) {
            // min(100.0, ...) asegura que no supere el 100% si hay más votos que lo esperado (aunque eso sería raro)
            $calculatedProgress = min(100.0, ($newTotalVotes / $totalExpected) * 100); 
        }

        // Llamar al método update en el objeto pivote.
        // Esto funciona porque el objeto pivote tiene acceso a sus atributos y puede actualizarlos.
        $surveyUserPivot->update([
            'progress_percentage' => $calculatedProgress,
            'total_votes' => $newTotalVotes,
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Actualiza el progreso del usuario en la encuesta después de un voto.
     * El progreso se calcula en base al total almacenado previamente.
     *
     * @param SurveyUser $surveyUserPivot El objeto pivote ya existente.
     * @param int $newTotalVotes El nuevo número total de votos.
     * @return void
     */
    /* public function updateProgress(SurveyUser $surveyUserPivot, int $newTotalVotes): void
    {
        // Recalculamos el progreso basado en el total almacenado
        $totalExpected = $surveyUserPivot->total_combinations_expected;
        $progress = 0.0;
        if ($totalExpected && $totalExpected > 0) {
            $progress = min(100.0, ($newTotalVotes / $totalExpected) * 100);
        }

        $surveyUserPivot->update([
            'total_votes' => $newTotalVotes,
            'progress_percentage' => $progress, // Actualizamos el campo en la DB también, por consistencia o si se usa en otros lugares
            'last_activity_at' => now(),
        ]);
    } */

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