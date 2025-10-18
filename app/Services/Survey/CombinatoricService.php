<?php

namespace App\Services\Survey;

use App\Models\Combinatoric;
use App\Models\Survey;
use App\Models\Character;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB; // Para transacciones si es necesario
use Illuminate\Database\Eloquent\Builder; // Para construir consultas

class CombinatoricService
{
    /**
     * Genera todas las combinaciones posibles de personajes para una encuesta.
     * Este método se podría llamar desde un Observer de Survey o un servicio dedicado.
     *
     * @param Survey $survey La encuesta para la cual generar combinaciones.
     * @param Collection|null $characters Colección opcional de personajes. Si no se provee, se cargan de la encuesta.
     * @return void
     */
    public function generateInitialCombinations(Survey $survey, ?Collection $characters = null): void
    {
        $characters = $characters ?? $survey->characters()->wherePivot('is_active', true)->get(); // Filtrar personajes activos en la encuesta

        $characterIds = $characters->pluck('id')->sort()->values()->toArray();
        $n = count($characterIds);

        // Lógica para generar combinaciones C(n, 2)
        // Ejemplo simple con bucles anidados
        // Para n grande, considerar algoritmos más eficientes o bibliotecas externas.
        $combinationsToCreate = [];
        for ($i = 0; $i < $n - 1; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                // Asegurar que character1_id < character2_id para evitar duplicados tipo (A,B) y (B,A)
                $char1Id = min($characterIds[$i], $characterIds[$j]);
                $char2Id = max($characterIds[$i], $characterIds[$j]);

                $combinationsToCreate[] = [
                    'survey_id' => $survey->id,
                    'character1_id' => $char1Id,
                    'character2_id' => $char2Id,
                    'status' => true, // Activa por defecto
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Insertar en bloque para mejor rendimiento
        if (!empty($combinationsToCreate)) {
            // Opcional: Eliminar combinaciones antiguas si se está regenerando
            // Combinatoric::where('survey_id', $survey->id)->delete();
            // Opcional: Solo insertar si no existen
            // DB::table('combinatorics')->insertOrIgnore($combinationsToCreate);

            // Para evitar duplicados, una estrategia común es truncar y recrear o usar upsert.
            // Upsert: Requiere que las columnas de la clave única estén en 'uniqueBy'
            // DB::table('combinatorics')->upsert($combinationsToCreate, ['survey_id', 'character1_id', 'character2_id'], []);

            // Por ahora, asumiremos que se llama en un contexto limpio o que se manejan duplicados externamente si es necesario.
            // La clave única (survey_id, character1_id, character2_id) en la migración prevendrá duplicados reales.
            DB::table('combinatorics')->insert($combinationsToCreate);
        }
    }

    /**
     * Selecciona la próxima pareja de personajes para mostrar al usuario en una encuesta.
     *
     * @param Survey $survey La encuesta activa.
     * @param int $userId El ID del usuario que vota.
     * @return Combinatoric|null El registro de combinación seleccionado, o null si no hay más.
     */
    public function getNextCombination(Survey $survey, int $userId): ?Combinatoric
    {
        // Obtener la estrategia de selección
        $strategy = $survey->selection_strategy; // Ej: 'cooldown', 'random', 'elo_based'

        // Construir la consulta base para combinaciones activas de esta encuesta
        $query = $survey->combinatorics()->where('status', true);

        // Aplicar la estrategia
        switch ($strategy) {
            case 'random':
                // Seleccionar una combinación aleatoria entre las disponibles
                $query = $survey->combinatorics()
                                ->where('status', true)
                                ->whereDoesntHave('votes', function ($subQuery) use ($userId) {
                                    $subQuery->where('user_id', $userId);
                                });
                return $query->with(['character1', 'character2'])->inRandomOrder()->first(); // <-- CARGA RELACIONES
            case 'elo_based':
                // Lógica para seleccionar combinaciones basadas en ELO (más compleja)
                // Por ejemplo, buscar combinaciones donde la diferencia de ELO sea mínima
                // Esto implicaría JOINs con category_character y cálculos.
                // Temporalmente, usar 'cooldown' o 'random' como fallback si no se implementa ahora.
                // TODO: Implementar lógica de selección basada en ELO
                \Log::warning("ELO-based selection not implemented yet for survey {$survey->id}. Falling back to cooldown.");
                // break; // Continuar con cooldown
            case 'cooldown':
            default: // Por si acaso, usar cooldown como fallback
                // Seleccionar la combinación menos usada recientemente (cooldown)
                // O la que tenga el last_used_at más antiguo o null
                // return $query->orderByRaw('COALESCE(last_used_at, "1970-01-01") ASC')->first();
                $query = $survey->combinatorics()
                                ->where('status', true)
                                ->whereDoesntHave('votes', function ($subQuery) use ($userId) {
                                    $subQuery->where('user_id', $userId);
                                });
                return $query // <-- CARGA RELACIONES
                        ->with(['character1', 'character2']) // Asegura que character1 y character2 se carguen
                        ->orderByRaw('COALESCE(last_used_at, "1970-01-01") ASC')
                        ->first();
        }

        // Si no se encuentra ninguna combinación activa o la estrategia es desconocida
        return null;
    }

    /**
     * Marca una combinación específica como usada y actualiza métricas.
     *
     * @param Combinatoric $combinatoric La combinación a actualizar.
     * @return void
     */
    public function markCombinationUsed(Combinatoric $combinatoric): void
    {
        $combinatoric->update([
            'total_comparisons' => $combinatoric->total_comparisons + 1,
            'last_used_at' => now(),
        ]);
    }
}