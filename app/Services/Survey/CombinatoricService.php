<?php

namespace App\Services\Survey;

use App\Models\Combinatoric;
use App\Models\Survey;
use App\Models\Character;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB; // Para transacciones si es necesario
use Illuminate\Database\Eloquent\Builder; // Para construir consultas
use Illuminate\Support\Facades\Auth; // Para obtener el usuario autenticado
use App\Services\Survey\CombinationSelection\CombinationSelector; // Importar el selector
use App\Models\User; // Importar el modelo User

class CombinatoricService
{
    // Inyectar el CombinationSelector en el constructor
    public function __construct(
        protected CombinationSelector $combinationSelector,
    ) {
        // ...
    }

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
                    // Establecer explícitamente last_used_at a una fecha antigua al crear
                    'last_used_at' => '1970-01-01 00:00:00', // <-- Valor inicial explícito
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
            // DB::table('combinatorics')->insert($combinationsToCreate);

            // Usar upsert para evitar duplicados si se llama múltiples veces
            // Asegúrate de que las columnas de la clave única estén en 'uniqueBy'
            DB::table('combinatorics')->upsert(
                $combinationsToCreate,
                ['survey_id', 'character1_id', 'character2_id'], // uniqueBy
                [] // updateColumns - vacío significa no actualizar si ya existe, solo insertar/ignorar
            );
        }
    }

    /**
     * Selecciona la próxima pareja de personajes para mostrar al usuario en una encuesta.
     * Utiliza el servicio CombinationSelector para aplicar la estrategia definida.
     *
     * @param Survey $survey La encuesta activa.
     * @param int $userId El ID del usuario que vota.
     * @return Combinatoric|null El registro de combinación seleccionado, o null si no hay más.
     */
    public function getNextCombination(Survey $survey, int $userId): ?Combinatoric
    {
        // Obtener el modelo User por ID
        /* $user = User::find($userId);
        if (!$user) {
            // Manejar el caso donde el usuario no exista
            \Log::error("User with ID {$userId} not found for combination selection in survey {$survey->id}.");
            return null; // O lanzar una excepción
        } */

        // Obtener el usuario autenticado
        $user = Auth::user();
        if (!$user) {
            // Opcional: Redirigir a login o mostrar mensaje si es necesario
            abort(401, 'Authentication required to view this survey.');
        }

        // Delegar la selección al CombinationSelector
        // Este servicio se encargará de usar la estrategia correcta basada en $survey->selection_strategy
        return $this->combinationSelector->selectCombination($survey, $user);
    }

    /**
     * Selecciona la próxima pareja de personajes para mostrar al usuario en una encuesta.
     * Filtra combinaciones basadas en el estado activo de la combinación, los votos del usuario
     * y el estado activo de ambos personajes involucrados.
     *
     * @param Survey $survey La encuesta activa.
     * @param int $userId El ID del usuario que vota.
     * @return Combinatoric|null El registro de combinación seleccionado, o null si no hay más.
     */
    public function getNextCombination_old3(Survey $survey, int $userId): ?Combinatoric
    {
        // Obtener la estrategia de selección
        $strategy = $survey->selection_strategy; // Ej: 'cooldown', 'random', 'elo_based'

        // --- CORRECCIÓN: Construir la consulta base para combinaciones ACTIVAS DE ESTA ENCUESTA ESPECÍFICA ---
        // Asegurarse de filtrar por survey_id
        $query = $survey->combinatorics() // <-- Esto ya filtra por survey_id
                        ->where('status', true); // <-- Filtrar por estado activo

        // Aplicar la estrategia
        switch ($strategy) {
            case 'random':
                // Seleccionar una combinación aleatoria entre las disponibles para esta encuesta
                $query = $survey->combinatorics() // <-- Asegurar que se parte de la relación de la encuesta
                                ->where('status', true) // <-- Filtrar por estado activo
                                ->whereDoesntHave('votes', function ($subQuery) use ($userId) {
                                    $subQuery->where('user_id', $userId);
                                });
                return $query->with(['character1', 'character2'])->inRandomOrder()->first(); // <-- CARGA RELACIONES

            case 'elo_based':
                // Lógica para seleccionar combinaciones basadas en ELO (más compleja)
                // Por ejemplo, buscar combinaciones donde la diferencia de ELO sea mínima
                // Esto implicaría JOINs con category_character y cálculos.
                // Temporalmente, usar 'cooldown' o 'random' como fallback si no se implementa ahora.
                \Log::warning("ELO-based selection not implemented yet for survey {$survey->id}. Falling back to cooldown.");
                // break; // Continuar con cooldown

            case 'cooldown':
            default: // Por si acaso, usar cooldown como fallback
                // Seleccionar la combinación menos usada recientemente (cooldown) DE ESTA ENCUESTA
                // O la que tenga el last_used_at más antiguo o null
                $query = $survey->combinatorics() // <-- Asegurar que se parte de la relación de la encuesta
                                ->where('status', true) // <-- Filtrar por estado activo
                                ->whereDoesntHave('votes', function ($subQuery) use ($userId) {
                                    $subQuery->where('user_id', $userId);
                                });
                return $query // <-- CARGA RELACIONES
                        ->with(['character1', 'character2']) // Asegura que character1 y character2 se carguen
                        // Verificar estado de personajes (opcional, si se requiere)
                        // ->whereHas('character1', function($q) { $q->where('status', true); })
                        // ->whereHas('character2', function($q) { $q->where('status', true); })
                        ->orderByRaw('COALESCE(last_used_at, "1970-01-01") ASC')
                        ->first();
        }

        // Si no se encuentra ninguna combinación activa o la estrategia es desconocida
        return null;
    }

    /**
     * Selecciona la próxima pareja de personajes para mostrar al usuario en una encuesta.
     * Filtra combinaciones basadas en el estado activo de la combinación, los votos del usuario
     * y el estado activo de ambos personajes involucrados.
     *
     * @param Survey $survey La encuesta activa.
     * @param int $userId El ID del usuario que vota.
     * @return Combinatoric|null El registro de combinación seleccionado, o null si no hay más.
     */
    public function getNextCombination_old2(Survey $survey, int $userId): ?Combinatoric
    {
        // Obtener la estrategia de selección
        $strategy = $survey->selection_strategy; // Ej: 'cooldown', 'random', 'elo_based'

        // --- Construir la consulta base ---
        // Comenzamos con la relación de combinaciones de la encuesta
        $query = $survey->combinatorics();

        // 1. Combinación debe estar activa
        // --- CORRECCIÓN: Calificar explícitamente la tabla para 'status' ---
        $query->where('combinatorics.status', true); // <-- Calificado
        // --- FIN CORRECCIÓN ---

        // 2. El usuario NO debe haber votado por esta combinación
        $query->whereDoesntHave('votes', function ($subQuery) use ($userId) {
            $subQuery->where('user_id', $userId);
        });

        // 3. Ambos personajes (character1 y character2) deben estar activos (status = 1)
        // Usamos joins para verificar el estado de los personajes relacionados.
        $query->join('characters as c1', 'combinatorics.character1_id', '=', 'c1.id')
              ->join('characters as c2', 'combinatorics.character2_id', '=', 'c2.id')
              ->where('c1.status', true) // character1 debe estar activo
              ->where('c2.status', true); // character2 debe estar activo

        // --- Aplicar la estrategia ---
        switch ($strategy) {
            case 'random':
                // Seleccionar una combinación aleatoria entre las disponibles y válidas
                return $query->with(['character1', 'character2']) // Cargar relaciones para el frontend
                             ->inRandomOrder()
                             ->first();

            case 'elo_based':
                // Lógica para seleccionar combinaciones basadas en ELO (más compleja)
                // Por ejemplo, buscar combinaciones donde la diferencia de ELO sea mínima
                // Esto implicaría JOINs con category_character y cálculos.
                // Temporalmente, usar 'cooldown' o 'random' como fallback si no se implementa ahora.
                \Log::warning("ELO-based selection not implemented yet for survey {$survey->id}. Falling back to cooldown.");
                // break; // Continuar con cooldown

            case 'cooldown':
            default: // Por si acaso, usar cooldown como fallback
                // Seleccionar la combinación menos usada recientemente (cooldown)
                // O la que tenga el last_used_at más antiguo o null
                return $query->with(['character1', 'character2']) // Cargar relaciones
                             // --- CORRECCIÓN: También calificar aquí para claridad, aunque no es estrictamente necesario si ya se hizo arriba ---
                             ->orderByRaw('COALESCE(combinatorics.last_used_at, "1970-01-01") ASC') // <-- 'combinatorics.' ya está implícito en la consulta base $query
                             // --- FIN CORRECCIÓN ---
                             ->first();
        }

        // Si no se encuentra ninguna combinación válida o la estrategia es desconocida
        return null;
    }

    /**
     * Selecciona la próxima pareja de personajes para mostrar al usuario en una encuesta.
     *
     * @param Survey $survey La encuesta activa.
     * @param int $userId El ID del usuario que vota.
     * @return Combinatoric|null El registro de combinación seleccionado, o null si no hay más.
     */
    public function getNextCombination_old(Survey $survey, int $userId): ?Combinatoric
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
                        // ->orderByRaw('COALESCE(last_used_at, "1970-01-01") ASC')
                        ->orderBy('combinatorics.last_used_at', 'ASC') // <-- Simplificado
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
        // Actualizar usando incremento y fecha actual
        // Usar update con DB::raw es atómico y evita problemas de concurrencia
        $combinatoric->update([
            // 'total_comparisons' => $combinatoric->total_comparisons + 1,
            'total_comparisons' => DB::raw('total_comparisons + 1'),
            'last_used_at' => now(),
        ]);
    }
}