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
        // Obtener la entrada pivote SurveyUser REAL desde la base de datos
        // Esto es más explícito y seguro para actualizaciones posteriores.
        $surveyUserPivot = SurveyUser::where('survey_id', $survey->id)
                                      ->where('user_id', $user->id)
                                      ->first();

        if (!$surveyUserPivot) {
            return [
                'exists' => false,
                'is_completed' => false,
                'progress' => 0.0,
                'total_votes' => 0,
                'total_expected' => null,
                'pivot_id' => null, // Devolvemos el ID, no el objeto
            ];
        }
// dd($surveyUserPivot);
        // Calcular progreso basado en votos actuales vs esperados
        $totalExpected = $surveyUserPivot->total_combinations_expected;
        $progress = 0.0;
        if ($totalExpected && $totalExpected > 0) {
            $progress = min(100.0, ($surveyUserPivot->total_votes / $totalExpected) * 100);
        }

        return [
            'exists' => true,
            'is_completed' => $surveyUserPivot->is_completed,
            'progress' => $progress,
            'total_votes' => $surveyUserPivot->total_votes,
            'total_expected' => $totalExpected,
            'pivot_id' => $surveyUserPivot->id, // Devolvemos el ID del modelo
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

        // Usamos updateOrCreate para asegurar que siempre devolvemos una instancia válida
        // y actualizamos last_activity_at si ya existía.
        $surveyUser = SurveyUser::updateOrCreate(
            [
                'user_id' => $user->id,
                'survey_id' => $survey->id,
            ],
            [
                'progress_percentage' => 0.00,
                'total_votes' => 0,
                'total_combinations_expected' => $totalCombinationsExpected,
                'started_at' => now(),
                'is_completed' => false,
                'last_activity_at' => now(),
                // completion_time no aplica aún
            ]
        );

        return $surveyUser; // Devolvemos la instancia guardada
    }

    /**
     * Actualiza el progreso del usuario en la encuesta después de un voto.
     * Optimizado para tablas pivote con clave primaria compuesta usando DB::table.
     *
     * @param int|null $surveyUserId ID del modelo SurveyUser (no se usa directamente para actualizar).
     * @param int $newTotalVotes El nuevo número total de votos.
     * @return void
     */
    public function updateProgress(?int $surveyUserId, int $newTotalVotes): void
    {
        if (!$surveyUserId) {
            Log::warning('SurveyProgressService@updateProgress called with null survey_user ID.');
            return;
        }

        // Cargar el modelo SurveyUser real por su ID para obtener user_id y survey_id
        $surveyUserPivot = SurveyUser::find($surveyUserId);

        if (!$surveyUserPivot) {
            Log::error("SurveyProgressService@updateProgress: SurveyUser model with ID {$surveyUserId} not found.");
            return;
        }

        $userId = $surveyUserPivot->user_id;
        $surveyId = $surveyUserPivot->survey_id;
        $totalExpected = $surveyUserPivot->total_combinations_expected ?? 0;
        $calculatedProgress = 0.0;
        if ($totalExpected > 0) {
            $calculatedProgress = min(100.0, ($newTotalVotes / $totalExpected) * 100);
        }

        // --- Actualizar el progreso en `survey_user` usando DB::table para evitar problemas con claves compuestas ---
        // Usar DB::table()->where()->update() para actualizar directamente la entrada pivote
        $updatedRows = DB::table('survey_user')
            ->where('user_id', $userId)
            ->where('survey_id', $surveyId)
            ->update([
                'progress_percentage' => $calculatedProgress,
                'total_votes' => $newTotalVotes,
                'last_activity_at' => now(),
                'updated_at' => now(), // Actualizar updated_at
            ]);

        // Verificar que se actualizó exactamente una fila
        if ($updatedRows !== 1) {
            Log::error("SurveyProgressService@updateProgress: Expected to update 1 row for user {$userId} in survey {$surveyId}, but updated {$updatedRows} rows.");
            // O lanzar una excepción si se considera un error crítico
            // throw new \Exception("Failed to update progress for user {$userId} in survey {$surveyId}.");
        }
    }

    /**
     * Actualiza el progreso del usuario en la encuesta después de un voto.
     * Carga el modelo SurveyUser por ID y lo actualiza.
     *
     * @param int|null $surveyUserId ID del modelo SurveyUser.
     * @param int $newTotalVotes El nuevo número total de votos.
     * @return void
     */
    /* public function updateProgress(?int $surveyUserId, int $newTotalVotes): void
    {
        if (!$surveyUserId) {
            \Log::warning('updateProgress called with null survey_user ID.');
            return;
        }

        // Cargar el modelo SurveyUser real por su ID
        $surveyUserPivot = SurveyUser::find($surveyUserId);

        if (!$surveyUserPivot) {
            \Log::error("SurveyUser model with ID {$surveyUserId} not found for updateProgress.");
            return;
        }

        $totalExpected = $surveyUserPivot->total_combinations_expected ?? 0;
        $calculatedProgress = 0.0;
        if ($totalExpected > 0) {
            $calculatedProgress = min(100.0, ($newTotalVotes / $totalExpected) * 100);
        }

        // Actualizar el modelo Eloquent real
        $surveyUserPivot->update([
            'progress_percentage' => $calculatedProgress,
            'total_votes' => $newTotalVotes,
            'last_activity_at' => now(),
        ]);
    } */

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