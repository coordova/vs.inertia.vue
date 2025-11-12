<?php

namespace App\Services\Survey;

use App\Models\Survey;
use App\Models\SurveyUser;
use App\Models\User; // Modelo pivote (aunque no se use directamente para escritura ahora)
use Illuminate\Support\Facades\DB; // Importar Query Builder
use Illuminate\Support\Facades\Log; // Para logging

class SurveyProgressService
{
    /**
     * Verifica el estado de la encuesta para un usuario específico.
     * Carga la entrada pivote SurveyUser REAL desde la base de datos.
     *
     * @param  Survey  $survey  La encuesta.
     * @param  User  $user  El usuario.
     * @return array ['exists' => bool, 'is_completed' => bool, 'progress' => float, 'total_votes' => int, 'total_expected' => int|null, 'pivot_id' => int|null]
     */
    public function getUserSurveyStatus(Survey $survey, User $user): array
    {
        // Usar Query Builder para cargar la entrada pivote específica
        // Esto es más directo y evita problemas con Eloquent en claves compuestas para lectura
        $surveyUserEntry = DB::table('survey_user')
            ->where('survey_id', $survey->id)
            ->where('user_id', $user->id)
            ->first(); // Retorna un stdClass o null

        if (! $surveyUserEntry) {
            return [
                'exists' => false,
                'is_completed' => false,
                'progress' => 0.0,
                'total_votes' => 0,
                'total_expected' => null,
                // 'pivot_id' => null,
            ];
        }

        // Calcular progreso basado en votos actuales vs esperados
        $totalExpected = $surveyUserEntry->total_combinations_expected ?? ($survey->total_combinations ?? 0);
        $progress = 0.0;
        if ($totalExpected && $totalExpected > 0) {
            $progress = min(100.0, ($surveyUserEntry->total_votes / $totalExpected) * 100);
        }

        return [
            'exists' => true,
            'is_completed' => $surveyUserEntry->is_completed,
            'progress' => $progress,
            'total_votes' => $surveyUserEntry->total_votes,
            'total_expected' => $totalExpected,
            // 'pivot_id' => $surveyUserEntry->id, // Asumiendo que hay una columna 'id' adicional o que la clave compuesta es suficiente para identificar la fila
            // Si la clave compuesta (user_id, survey_id) es la única PK, 'pivot_id' podría no existir o no ser útil aquí.
            // La identificación se hace por user_id y survey_id.
            // 'pivot_id' podría eliminarse de este array o reemplazarse por ['user_id' => ..., 'survey_id' => ...]
        ];
    }

    /**
     * Inicia una sesión de votación para un usuario en una encuesta si no existe.
     * Calcula y almacena el número total de combinaciones esperadas.
     * Utiliza Query Builder para crear la entrada si no existe.
     *
     * @param  Survey  $survey  La encuesta.
     * @param  User  $user  El usuario.
     */
    public function startSurveySession(Survey $survey, User $user): void
    {
        // Calcula el total de combinaciones posibles basado en los personajes activos en la encuesta
        // Carga los personajes activos en la relación pivote
        $activeCharacterCount = $survey->characters()->wherePivot('is_active', true)->count();

        // Calcula C(n, 2)
        $totalCombinationsExpected = $activeCharacterCount > 1 ? ($activeCharacterCount * ($activeCharacterCount - 1)) / 2 : 0;

        // Usamos DB::table con updateOrCreate para manejar la clave compuesta eficientemente
        // updateOrCreate intenta actualizar si existe, o crear si no existe
        DB::table('survey_user')->updateOrCreate(
            [
                'user_id' => $user->id,
                'survey_id' => $survey->id,
            ],
            [
                'progress_percentage' => 0.00,
                'total_votes' => 0,
                'total_combinations_expected' => $totalCombinationsExpected, // Almacenamos el total calculado
                'started_at' => now(),
                'is_completed' => false,
                'last_activity_at' => now(),
                // completion_time no aplica aún
                'created_at' => now(), // Asegurar valores de timestamps
                'updated_at' => now(),
            ]
        );

        // No devolvemos nada, la entrada pivote debería existir ahora
    }

    /**
     * Incrementa el número de votos y recalcula el progreso del usuario en la encuesta.
     * Utiliza Query Builder para operaciones atómicas y evitar problemas con claves compuestas.
     *
     * @param  int  $userId  ID del usuario.
     * @param  int  $surveyId  ID de la encuesta.
     * @return array|null ['total_votes' => int, 'progress_percentage' => float] Datos actualizados, o null si la entrada no existe.
     */
    public function incrementAndRecalculateProgress(int $userId, int $surveyId): ?array
    {
        // Cargar la entrada actual para obtener el total esperado y el número de votos actual
        $entry = DB::table('survey_user')
            ->where('user_id', $userId)
            ->where('survey_id', $surveyId)
            ->lockForUpdate() // Bloqueo pesimista para evitar race conditions
            ->first();

        if (! $entry) {
            Log::warning("SurveyProgressService: Entry for user {$userId} and survey {$surveyId} not found for increment.");

            return null;
        }

        // Calcular nuevo total de votos
        $newTotalVotes = $entry->total_votes + 1;

        // Recalcular progreso
        $totalExpected = $entry->total_combinations_expected ?? 0;
        $newProgressPercentage = 0.0;
        if ($totalExpected > 0) {
            $newProgressPercentage = min(100.0, ($newTotalVotes / $totalExpected) * 100);
        }

        // Actualizar la entrada con Query Builder
        $updatedRows = DB::table('survey_user')
            ->where('user_id', $userId)
            ->where('survey_id', $surveyId)
            ->update([
                'total_votes' => $newTotalVotes,
                'progress_percentage' => $newProgressPercentage,
                'last_activity_at' => now(),
                'updated_at' => now(), // Actualizar timestamp
            ]);

        // Verificar que se actualizó exactamente una fila
        if ($updatedRows !== 1) {
            Log::error("SurveyProgressService: Expected to update 1 row for user {$userId} and survey {$surveyId}, but updated {$updatedRows} rows.");

            return null; // Indicar fallo
        }

        // Devolver los nuevos valores calculados
        return [
            'total_votes' => $newTotalVotes,
            'progress_percentage' => $newProgressPercentage,
        ];
    }

    /**
     * Marca la sesión de votación del usuario como completada.
     * Utiliza Query Builder.
     *
     * @param  int  $userId  ID del usuario.
     * @param  int  $surveyId  ID de la encuesta.
     * @return bool True si se actualizó correctamente, false si no se encontró la entrada.
     */
    public function markAsCompleted(int $userId, int $surveyId): bool
    {
        $updatedRows = DB::table('survey_user')
            ->where('user_id', $userId)
            ->where('survey_id', $surveyId)
            ->update([
                'is_completed' => true,
                'completed_at' => now(),
                // completion_time se podría calcular aquí si no se almacena como campo separado
                // 'completion_time' => now()->diffInSeconds($entry->started_at), // Requiere cargar started_at primero
                'updated_at' => now(),
            ]);

        return $updatedRows === 1;
    }
}
