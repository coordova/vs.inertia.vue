<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Survey;
use App\Models\Combinatoric;
use App\Models\Vote;
use App\Models\User;
// Modelos pivote (no se usarán directamente para escritura, solo para lectura/creación inicial)
use App\Models\CategoryCharacter; // Modelo pivote (solo lectura aquí)
use App\Models\CharacterSurvey; // Modelo pivote (solo lectura/creación aquí)
use App\Models\SurveyUser; // Modelo pivote (solo lectura/creación aquí)
// Servicios
use App\Services\Survey\CombinatoricService;
use App\Services\Survey\SurveyProgressService;
use App\Services\Rating\EloRatingService;
// Request y Response
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
// Facades
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // Para registro de errores
// Form Request
use App\Http\Requests\StoreVoteRequest; // Asumiendo que existe y valida correctamente

class SurveyVoteController extends Controller
{
    // Inyección de dependencias en el constructor
    public function __construct(
        protected CombinatoricService $combinatoricService,
        protected SurveyProgressService $surveyProgressService,
        protected EloRatingService $eloRatingService,
    ) {
        // Aplicar middleware de autenticación si es necesario para todas las acciones de este controlador
        // $this->middleware('auth');
    }

    /**
     * Recibe un voto para una combinación específica dentro de una encuesta.
     * Optimizado para minimizar consultas a la base de datos y manejar transacciones.
     * Utiliza Query Builder para tablas pivote con clave primaria compuesta.
     *
     * @param StoreVoteRequest $request // Request validado
     * @param int $surveyId ID de la encuesta
     * @return RedirectResponse
     */
    public function store(StoreVoteRequest $request, int $surveyId): RedirectResponse
    {
        // 1. Verificar autenticación
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Authentication required.');
        }

        // 2. Obtener datos validados del request
        $validatedData = $request->validated();
        $combinatoricId = $validatedData['combinatoric_id'];
        $winnerId = $validatedData['winner_id'] ?? null; // Puede ser null
        $loserId = $validatedData['loser_id'] ?? null;   // Puede ser null
        $tie = $validatedData['tie'] ?? false;

        // --- Validación de Lógica de Negocio DESPUÉS de la validación básica ---
        if ($tie) {
            // Si es empate, winner_id y loser_id DEBEN estar ausentes o null en la validación
            // y por lo tanto también serán null aquí. No necesitamos verificarlos.
            // La validación de StoreVoteRequest ya lo garantiza.
        } else {
            // Si NO es empate, winner_id y loser_id SON obligatorios y deben existir.
            // La validación de StoreVoteRequest ya lo garantiza.
            if ($winnerId === $loserId) {
                return back()->withErrors(['winner_id' => 'Winner and loser cannot be the same character.'])->withInput();
            }
        }

        // 3. Cargar datos críticos en una sola transacción con JOINs para evitar N+1
        // Cargamos la encuesta, la combinación, los personajes de la combinación y sus ratings ELO en la categoría de la encuesta
        // También cargamos survey.total_combinations (nueva columna)
        $surveyData = Survey::with([
                'category:id,name,slug', // Cargar solo campos necesarios de la categoría
                'combinatorics' => function ($query) use ($combinatoricId) {
                    $query->where('id', $combinatoricId) // Filtrar por la combinación específica
                          ->with(['character1:id,fullname,picture', 'character2:id,fullname,picture']); // Cargar personajes solo con campos necesarios
                }
            ])
            ->where('id', $surveyId)
            ->where('status', true) // Verificar estado activo
            ->where('date_start', '<=', now()) // Verificar fecha de inicio
            ->where('date_end', '>=', now()) // Verificar fecha de fin
            ->first();

        if (!$surveyData) {
            // Encuesta no encontrada, inactiva o fuera de rango de fechas
            return redirect()->route('surveys.public.index')->with('error', 'Survey not found or not active.');
        }

        $combinatoric = $surveyData->combinatorics->first();
        if (!$combinatoric) {
            // Combinación no encontrada o no pertenece a la encuesta (verificado por el where en with)
            return redirect()->route('surveys.public.index')->with('error', 'Invalid combination for this survey.');
        }

        // 4. Verificar si el usuario ya votó por esta combinación específica (fuera de la transacción principal para lectura)
        $existingVote = Vote::where('user_id', $user->id)
                            ->where('combinatoric_id', $combinatoric->id)
                            ->exists(); // Usar exists() es más eficiente que first() si solo se quiere saber si existe
        if ($existingVote) {
            return redirect()->route('surveys.public.index')->with('error', 'User has already voted on this combination.');
        }

        // 5. Verificar que las IDs de ganador/perdedor coincidan con la combinación actual (si no es empate)
        if (!$tie) {
            $validCharacterIds = [$combinatoric->character1_id, $combinatoric->character2_id];
            if (!in_array($winnerId, $validCharacterIds) || !in_array($loserId, $validCharacterIds)) {
                return back()->withErrors(['winner_id' => 'Selected characters do not match the combination.'])->withInput();
            }
            // La verificación $winnerId !== $loserId ya se hizo arriba.
        }

        // 6. Verificar estado del progreso del usuario (fuera de la transacción principal para lectura)
        $status = $this->surveyProgressService->getUserSurveyStatus($surveyData, $user);
        if ($status['is_completed']) {
            return redirect()->route('surveys.public.index')->with('error', 'Survey already completed for this user.');
        }

        // 7. Determinar el resultado y los personajes involucrados
        $character1Id = $combinatoric->character1_id;
        $character2Id = $combinatoric->character2_id;

        $result = '';
        if ($tie) {
            $result = 'draw';
        } else {
            if ($winnerId === $character1Id && $loserId === $character2Id) {
                $result = 'win'; // character1 gana contra character2
            } elseif ($winnerId === $character2Id && $loserId === $character1Id) {
                $result = 'loss'; // character1 pierde contra character2 (o character2 gana contra character1)
            } else {
                // Esta validación ya se hizo, pero por seguridad extra...
                return back()->withErrors(['winner_id' => 'Invalid winner/loser combination.'])->withInput();
            }
        }

        // 8. Cargar ratings ELO de los personajes en la categoría de la encuesta (fuera de la transacción principal también)
        // Usamos una sola consulta para obtener ambos ratings
        $characterIds = [$character1Id, $character2Id];
        $categoryId = $surveyData->category_id; // Obtenido de $surveyData->category->id
        $eloRatings = CategoryCharacter::where('category_id', $categoryId)
                                      ->whereIn('character_id', $characterIds)
                                      ->pluck('elo_rating', 'character_id'); // Colección ['char_id' => rating]

        if ($eloRatings->count() !== 2) {
             // Uno o ambos personajes no tienen entrada en category_character para esta categoría
            Log::error("Ratings not found for one or both characters ({$character1Id}, {$character2Id}) in category {$categoryId} for survey {$surveyId}.");
            return redirect()->route('surveys.public.index')->with('error', 'Ratings not found for one or both characters in this category.'); // Error interno o inconsistencia de datos
        }

        $character1Rating = $eloRatings[$character1Id];
        $character2Rating = $eloRatings[$character2Id];


        // --- Iniciar transacción solo para operaciones de escritura y cálculos críticos ---
        DB::transaction(function () use (
            $user,
            $surveyData, // Encuesta con categoría y combinación cargada
            $combinatoric, // Combinación específica
            $result, // Resultado del voto ('win', 'loss', 'draw')
            $tie, // Booleano
            $winnerId, $loserId, // IDs (pueden ser null)
            $character1Rating, $character2Rating, // Ratings ELO cargados
            $character1Id, $character2Id, // IDs de personajes de la combinación
            $categoryId, // ID de la categoría de la encuesta
            $request // Para obtener IP/UserAgent
        ) {
            // --- PASO 1: Registrar el voto en la tabla `votes` ---
            Vote::create([
                'user_id' => $user->id,
                'combinatoric_id' => $combinatoric->id,
                'survey_id' => $surveyData->id,
                'winner_id' => $tie ? null : $winnerId, // null si es empate
                'loser_id' => $tie ? null : $loserId,  // null si es empate
                'tie_score' => $tie ? $surveyData->tie_weight : null, // null si no es empate
                'voted_at' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'is_valid' => true, // Asumimos válido por ahora
                // 'notes' se deja null por defecto
            ]);

            // --- PASO 2: Marcar la combinación como usada ---
            // Incrementar total_comparisons de forma atómica y actualizar last_used_at
            $combinatoric->increment('total_comparisons'); // Atómico
            $combinatoric->update(['last_used_at' => now()]); // Actualizar last_used_at

            // --- PASO 3: Actualizar el progreso del usuario en `survey_user` ---
            // Obtener o crear la entrada pivote (aunque debería existir por getUserSurveyStatus/startSurveySession)
            /** @var SurveyUser $surveyUserPivot */
            $surveyUserPivot = SurveyUser::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'survey_id' => $surveyData->id,
                ],
                [
                    // Valores por defecto si se crea (aunque startSurveySession debería haberlo creado)
                    'progress_percentage' => 0.00,
                    'total_votes' => 0,
                    'total_combinations_expected' => $surveyData->total_combinations ?? 0, // Usar la nueva columna
                    'started_at' => now(),
                    'is_completed' => false,
                    'last_activity_at' => now(),
                    // completion_time no aplica aún
                ]
            );

            // Calcular nuevo total de votos y progreso
            $newTotalVotes = $surveyUserPivot->total_votes + 1;
            // Usar la columna `total_combinations_expected` de `surveys` para el cálculo
            $totalExpected = $surveyUserPivot->total_combinations_expected ?? ($surveyData->total_combinations ?? 0); // Fallback doble
            $newProgressPercentage = 0.0;
            if ($totalExpected > 0) {
                $newProgressPercentage = min(100.0, ($newTotalVotes / $totalExpected) * 100);
            }

            // --- CORRECCIÓN FINAL: Actualizar progreso usando Query Builder ---
            // Usar DB::table()->where()->update() para evitar problemas con claves compuestas
            $updatedRows = DB::table('survey_user')
                ->where('user_id', $user->id)
                ->where('survey_id', $surveyData->id)
                ->update([
                    'total_votes' => DB::raw('total_votes + 1'), // Incrementar total_votes en 1 de forma atómica
                    'progress_percentage' => $newProgressPercentage,
                    'last_activity_at' => now(),
                    'updated_at' => now(), // Actualizar updated_at
                    // 'is_completed' y 'completed_at' se pueden actualizar aquí si se completa, o en otro proceso.
                ]);

            // Verificar que se actualizó exactamente una fila
            if ($updatedRows !== 1) {
                 Log::warning("SurveyVoteController@store: Expected to update 1 row in survey_user for user {$user->id} and survey {$surveyData->id}, but updated {$updatedRows} rows.");
                // O lanzar una excepción si se considera un error crítico
                // throw new \Exception("Failed to update survey_user progress for user {$user->id} and survey {$surveyData->id}.");
            }
            // --- FIN CORRECCIÓN FINAL ---


            // --- PASO 4: Calcular y aplicar nuevos ratings ELO ---
            // Pasamos los ratings ya cargados
            if ($tie) {
                 [$newRating1, $newRating2] = $this->eloRatingService->calculateNewRatings($character1Rating, $character2Rating, 'draw', $surveyData->tie_weight);
            } else {
                 // Determinar quién es el ganador/perdedor para el cálculo ELO
                 $winnerRating = $winnerId === $character1Id ? $character1Rating : $character2Rating;
                 $loserRating = $winnerId === $character1Id ? $character2Rating : $character1Rating;

                 [$newWinnerRating, $newLoserRating] = $this->eloRatingService->calculateNewRatings($winnerRating, $loserRating, 'win');

                 // Asignar los nuevos ratings a las variables correctas
                 if ($winnerId === $character1Id) {
                    $newRating1 = $newWinnerRating;
                    $newRating2 = $newLoserRating;
                 } else {
                    $newRating1 = $newLoserRating;
                    $newRating2 = $newWinnerRating;
                 }
            }

            // Pasar los nuevos ratings y el resultado al servicio ELO para aplicarlos
            // El servicio debe encargarse de cargar los modelos CategoryCharacter y actualizarlos
            // --- CORRECCIÓN FINAL: Pasar $character1Id, $character2Id en lugar de $winnerId, $loserId ---
            $this->eloRatingService->applyRatings($categoryId, $character1Id, $character2Id, $newRating1, $newRating2, $result);
            // --- FIN CORRECCIÓN FINAL ---


            // --- PASO 5: Actualizar estadísticas específicas de encuesta en `character_survey` ---
            // Para character1
            /** @var CharacterSurvey $characterSurvey1Pivot */
            $characterSurvey1Pivot = CharacterSurvey::firstOrCreate(
                [
                    'character_id' => $character1Id,
                    'survey_id' => $surveyData->id,
                ],
                [
                    // Valores por defecto si se crea (debería existir si está en la encuesta)
                    'survey_matches' => 0,
                    'survey_wins' => 0,
                    'survey_losses' => 0,
                    'survey_ties' => 0, // Nueva columna
                    'is_active' => true,
                    'sort_order' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            // --- CORRECCIÓN FINAL: Actualizar estadísticas de character1 usando Query Builder ---
            // Usar DB::table()->where()->update() para evitar problemas con claves compuestas
            // y DB::raw para incrementos atómicos
            $updateDataChar1 = [
                'survey_matches' => DB::raw('survey_matches + 1'),
                'updated_at' => now(),
            ];
            if ($result === 'win') {
                $updateDataChar1['survey_wins'] = DB::raw('survey_wins + 1');
            } elseif ($result === 'loss') {
                $updateDataChar1['survey_losses'] = DB::raw('survey_losses + 1');
            } elseif ($result === 'draw') {
                $updateDataChar1['survey_ties'] = DB::raw('survey_ties + 1');
            }
            $updatedRowsChar1 = DB::table('character_survey')
                ->where('character_id', $character1Id)
                ->where('survey_id', $surveyData->id)
                ->update($updateDataChar1);

            // Verificar que se actualizó exactamente una fila
            if ($updatedRowsChar1 !== 1) {
                 Log::warning("SurveyVoteController@store: Expected to update 1 row in character_survey for character {$character1Id} and survey {$surveyData->id}, but updated {$updatedRowsChar1} rows.");
                // O lanzar una excepción si se considera un error crítico
                // throw new \Exception("Failed to update character_survey stats for character {$character1Id} and survey {$surveyData->id}.");
            }
            // --- FIN CORRECCIÓN FINAL ---

            // Para character2
            /** @var CharacterSurvey $characterSurvey2Pivot */
            $characterSurvey2Pivot = CharacterSurvey::firstOrCreate(
                [
                    'character_id' => $character2Id,
                    'survey_id' => $surveyData->id,
                ],
                [
                    // Valores por defecto si se crea
                    'survey_matches' => 0,
                    'survey_wins' => 0,
                    'survey_losses' => 0,
                    'survey_ties' => 0, // Nueva columna
                    'is_active' => true,
                    'sort_order' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            // --- CORRECCIÓN FINAL: Actualizar estadísticas de character2 usando Query Builder ---
            // Usar DB::table()->where()->update() para evitar problemas con claves compuestas
            // y DB::raw para incrementos atómicos
            $updateDataChar2 = [
                'survey_matches' => DB::raw('survey_matches + 1'),
                'updated_at' => now(),
            ];
            // Invertir lógica para character2
            if ($result === 'loss') { // Si char1 ganó, char2 perdió
                $updateDataChar2['survey_wins'] = DB::raw('survey_wins + 1');
            } elseif ($result === 'win') { // Si char1 perdió, char2 ganó
                $updateDataChar2['survey_losses'] = DB::raw('survey_losses + 1');
            } elseif ($result === 'draw') {
                $updateDataChar2['survey_ties'] = DB::raw('survey_ties + 1');
            }
            $updatedRowsChar2 = DB::table('character_survey')
                ->where('character_id', $character2Id)
                ->where('survey_id', $surveyData->id)
                ->update($updateDataChar2);

            // Verificar que se actualizó exactamente una fila
            if ($updatedRowsChar2 !== 1) {
                 Log::warning("SurveyVoteController@store: Expected to update 1 row in character_survey for character {$character2Id} and survey {$surveyData->id}, but updated {$updatedRowsChar2} rows.");
                // O lanzar una excepción si se considera un error crítico
                // throw new \Exception("Failed to update character_survey stats for character {$character2Id} and survey {$surveyData->id}.");
            }
            // --- FIN CORRECCIÓN FINAL ---

        }); // --- Fin de la transacción DB::transaction ---

        // --- Si llegamos aquí, la transacción fue exitosa ---
        return back()->with('success', 'Vote processed successfully.');

    } // --- Fin del método store ---

    // ... (resto del controlador: show, edit, update, destroy, etc.) ...
}