<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Survey;
use App\Models\Combinatoric;
use App\Models\Vote;
use App\Models\User;
use App\Models\CategoryCharacter; // Modelo pivote
use App\Models\CharacterSurvey;  // Modelo pivote
use App\Models\SurveyUser;       // Modelo pivote
use App\Services\Survey\CombinatoricService; // Inyectamos el servicio de combinaciones
use App\Services\Survey\SurveyProgressService; // Inyectamos el servicio de progreso
use App\Services\Rating\EloRatingService; // Inyectamos el servicio de ELO
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse; // Cambiamos a JsonResponse
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\StoreVoteRequest; // Asumiendo que existe y valida correctamente

class SurveyVoteController extends Controller
{
    public function __construct(
        protected CombinatoricService $combinatoricService,
        protected SurveyProgressService $surveyProgressService,
        protected EloRatingService $eloRatingService,
    ) {
        // Aplicar middleware de autenticación si es necesario
        // $this->middleware('auth');
    }

    /**
     * Recibe un voto para una combinación específica dentro de una encuesta.
     * Optimizado para minimizar consultas a la base de datos y manejar transacciones.
     * Devuelve una respuesta JSON para actualización dinámica del frontend.
     *
     * @param StoreVoteRequest $request // Request validado
     * @param int $surveyId ID de la encuesta
     * @return JsonResponse
     */
    public function store(StoreVoteRequest $request, int $surveyId): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        $validatedData = $request->validated();
        $combinatoricId = $validatedData['combinatoric_id'];
        $winnerId = $validatedData['winner_id'] ?? null;
        $loserId = $validatedData['loser_id'] ?? null;
        $tie = $validatedData['tie'] ?? false;

        // --- Validación de Lógica de Negocio DESPUÉS de la validación básica ---
        if ($tie) {
            if ($winnerId !== null || $loserId !== null) {
                return response()->json(['errors' => ['tie' => 'If tie is selected, winner_id and loser_id must be absent.']], 422);
            }
        } else {
            if (!$winnerId || !$loserId) {
                return response()->json(['errors' => ['winner_id' => 'Winner and loser are required if not a tie.']], 422);
            }
            if ($winnerId === $loserId) {
                return response()->json(['errors' => ['winner_id' => 'Winner and loser cannot be the same character.']], 422);
            }
        }

        // --- Carga de datos críticos ANTES de la transacción ---
        // Cargar encuesta, combinación, personajes de la combinación y ratings ELO
        $surveyData = Survey::where('id', $surveyId)
            ->where('status', true)
            ->where('date_start', '<=', now())
            ->where('date_end', '>=', now())
            ->with(['category']) // Cargar categoría para obtener su ID
            ->first();

        if (!$surveyData) {
            return response()->json(['message' => 'Survey not found or not active.'], 404);
        }

        $combinatoric = Combinatoric::where('id', $combinatoricId)
            ->where('survey_id', $surveyId) // Asegurar que pertenece a la encuesta correcta
            ->where('status', true) // Asegurar que la combinación está activa
            ->with(['character1', 'character2']) // Cargar personajes
            ->first();

        if (!$combinatoric) {
            return response()->json(['message' => 'Invalid combination for this survey.'], 400);
        }

        // Verificar si el usuario ya votó esta combinación (fuera de la transacción para lectura)
        $existingVote = Vote::where('user_id', $user->id)->where('combinatoric_id', $combinatoricId)->exists();
        if ($existingVote) {
            return response()->json(['message' => 'User has already voted on this combination.'], 400);
        }

        // Verificar estado del progreso del usuario (fuera de la transacción para lectura)
        $progressStatus = $this->surveyProgressService->getUserSurveyStatus($surveyData, $user);
        if ($progressStatus['is_completed']) {
            return response()->json(['message' => 'Survey already completed for this user.'], 400);
        }

        // Verificar que las IDs coincidan con la combinación (fuera de la transacción)
        if (!$tie) {
            $validIds = [$combinatoric->character1_id, $combinatoric->character2_id];
            if (!in_array($winnerId, $validIds) || !in_array($loserId, $validIds)) {
                return response()->json(['errors' => ['winner_id' => 'Selected characters do not match the combination.']], 422);
            }
        }

        // Cargar ratings ELO de los personajes en la categoría de la encuesta (fuera de la transacción)
        $characterIds = [$combinatoric->character1_id, $combinatoric->character2_id];
        $categoryId = $surveyData->category_id;
        $eloRatings = CategoryCharacter::where('category_id', $categoryId)
                                      ->whereIn('character_id', $characterIds)
                                      ->pluck('elo_rating', 'character_id'); // Colección ['char_id' => rating]

        if ($eloRatings->count() !== 2) {
             Log::error("Ratings not found for one or both characters ({$combinatoric->character1_id}, {$combinatoric->character2_id}) in category {$categoryId} for survey {$surveyId}.");
             return response()->json(['message' => 'Ratings not found for one or both characters in this category.'], 500);
        }

        $character1Rating = $eloRatings[$combinatoric->character1_id];
        $character2Rating = $eloRatings[$combinatoric->character2_id];

        // --- Variables para el cálculo de resultados ---
        $character1Id = $combinatoric->character1_id;
        $character2Id = $combinatoric->character2_id;
        $result = $tie ? 'draw' : ($winnerId === $character1Id ? 'win' : 'loss');


        // --- INICIAR TRANSACCIÓN ÚNICA para operaciones de escritura ---
        $newProgress = 0;
        $newTotalVotes = 0;
        $newRating1 = $character1Rating;
        $newRating2 = $character2Rating;
        $nextCombination = null;

        try {
            DB::transaction(function () use (
                $user, $surveyData, $combinatoric, $result, $tie, $winnerId, $loserId, $character1Rating, $character2Rating, $request,
                &$newProgress, &$newTotalVotes, &$newRating1, &$newRating2, &$nextCombination // Pasar por referencia para obtener resultados
            ) {
                // --- PASO 1: Registrar el voto en `votes` ---
                Vote::create([
                    'user_id' => $user->id,
                    'combinatoric_id' => $combinatoric->id,
                    'survey_id' => $surveyData->id,
                    'winner_id' => $tie ? null : $winnerId,
                    'loser_id' => $tie ? null : $loserId,
                    'tie_score' => $tie ? $surveyData->tie_weight : null,
                    'voted_at' => now(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'is_valid' => true,
                ]);

                // --- PASO 2: Marcar la combinación como usada ---
                // Usar Query Builder para incrementar y actualizar de forma atómica
                DB::table('combinatorics')
                    ->where('id', $combinatoric->id)
                    ->update([
                        'total_comparisons' => DB::raw('total_comparisons + 1'),
                        'last_used_at' => now(),
                    ]);

                // --- PASO 3: Actualizar progreso del usuario en `survey_user` ---
                // Usar Query Builder para manejar la clave compuesta y actualizar progreso de forma atómica
                $surveyUserEntry = DB::table('survey_user')
                    ->where('user_id', $user->id)
                    ->where('survey_id', $surveyData->id)
                    ->lockForUpdate() // Bloqueo pesimista para evitar race conditions
                    ->first();

                if ($surveyUserEntry) {
                    $newTotalVotes = $surveyUserEntry->total_votes + 1;
                    $totalExpected = $surveyUserEntry->total_combinations_expected ?? ($surveyData->total_combinations ?? 0);
                    $newProgress = $totalExpected > 0 ? min(100.0, ($newTotalVotes / $totalExpected) * 100) : 0.0;

                    DB::table('survey_user')
                        ->where('user_id', $user->id)
                        ->where('survey_id', $surveyData->id)
                        ->update([
                            'total_votes' => $newTotalVotes,
                            'progress_percentage' => $newProgress,
                            'last_activity_at' => now(),
                        ]);
                } else {
                    // Si no existía la entrada (aunque debería haberse creado en 'show'), la creamos aquí también
                    // Asumiendo que SurveyProgressService->startSurveySession ya se encargó de esto antes de llegar acá,
                    // pero por coherencia, si no existe, no actualizamos ni calculamos progreso.
                    Log::warning("SurveyUser entry not found for user {$user->id} and survey {$surveyData->id} during vote processing.");
                    $newTotalVotes = 1; // Si se crea aquí, el primer voto es 1
                    // No calculamos progreso si no hay entrada previa, o lo calculamos con el total de la encuesta si se crea aquí.
                    // Por consistencia, asumiremos que startSurveySession *siempre* crea la entrada antes.
                    // Si no se creó, es un error de flujo previo.
                    throw new \Exception("SurveyUser entry missing for user {$user->id} and survey {$surveyData->id}.");
                }


                // --- PASO 4: Calcular y aplicar nuevos ratings ELO ---
                if ($tie) {
                     [$newRating1, $newRating2] = $this->eloRatingService->calculateNewRatings($character1Rating, $character2Rating, 'draw', $surveyData->tie_weight);
                } else {
                     $winnerRating = $result === 'win' ? $character1Rating : $character2Rating;
                     $loserRating = $result === 'win' ? $character2Rating : $character1Rating;

                     [$newWinnerRating, $newLoserRating] = $this->eloRatingService->calculateNewRatings($winnerRating, $loserRating, 'win');

                     if ($result === 'win') {
                        $newRating1 = $newWinnerRating;
                        $newRating2 = $newLoserRating;
                     } else { // result === 'loss'
                        $newRating1 = $newLoserRating;
                        $newRating2 = $newWinnerRating;
                     }
                }

                // Aplicar los nuevos ratings ELO usando Query Builder para clave compuesta
                DB::table('category_character')
                    ->where('category_id', $surveyData->category_id)
                    ->where('character_id', $character1Id)
                    ->update([
                        'elo_rating' => $newRating1,
                        'matches_played' => DB::raw('matches_played + 1'),
                        'wins' => $result === 'win' ? DB::raw('wins + 1') : DB::raw('wins'),
                        'losses' => $result === 'loss' ? DB::raw('losses + 1') : DB::raw('losses'),
                        'ties' => $result === 'draw' ? DB::raw('ties + 1') : DB::raw('ties'), // Nueva columna
                        'win_rate' => DB::raw('(wins + losses + ties) > 0 ? (wins * 100.0 / (wins + losses + ties)) : 0'), // Cálculo en BD
                        'highest_rating' => DB::raw("GREATEST(highest_rating, {$newRating1})"),
                        'lowest_rating' => DB::raw("LEAST(lowest_rating, {$newRating1})"),
                        'last_match_at' => now(),
                    ]);

                DB::table('category_character')
                    ->where('category_id', $surveyData->category_id)
                    ->where('character_id', $character2Id)
                    ->update([
                        'elo_rating' => $newRating2,
                        'matches_played' => DB::raw('matches_played + 1'),
                        'wins' => $result === 'loss' ? DB::raw('wins + 1') : DB::raw('wins'), // Invertido para character2
                        'losses' => $result === 'win' ? DB::raw('losses + 1') : DB::raw('losses'), // Invertido para character2
                        'ties' => $result === 'draw' ? DB::raw('ties + 1') : DB::raw('ties'), // Nueva columna
                        'win_rate' => DB::raw('(wins + losses + ties) > 0 ? (wins * 100.0 / (wins + losses + ties)) : 0'), // Cálculo en BD
                        'highest_rating' => DB::raw("GREATEST(highest_rating, {$newRating2})"),
                        'lowest_rating' => DB::raw("LEAST(lowest_rating, {$newRating2})"),
                        'last_match_at' => now(),
                    ]);


                // --- PASO 5: Actualizar estadísticas en `character_survey` ---
                // Usar Query Builder para clave compuesta
                DB::table('character_survey')
                    ->where('character_id', $character1Id)
                    ->where('survey_id', $surveyData->id)
                    ->update([
                        'survey_matches' => DB::raw('survey_matches + 1'),
                        'survey_wins' => $result === 'win' ? DB::raw('survey_wins + 1') : DB::raw('survey_wins'),
                        'survey_losses' => $result === 'loss' ? DB::raw('survey_losses + 1') : DB::raw('survey_losses'),
                        'survey_ties' => $result === 'draw' ? DB::raw('survey_ties + 1') : DB::raw('survey_ties'), // Nueva columna
                        'updated_at' => now(),
                    ]);

                DB::table('character_survey')
                    ->where('character_id', $character2Id)
                    ->where('survey_id', $surveyData->id)
                    ->update([
                        'survey_matches' => DB::raw('survey_matches + 1'),
                        'survey_wins' => $result === 'loss' ? DB::raw('survey_wins + 1') : DB::raw('survey_wins'), // Invertido
                        'survey_losses' => $result === 'win' ? DB::raw('survey_losses + 1') : DB::raw('survey_losses'), // Invertido
                        'survey_ties' => $result === 'draw' ? DB::raw('survey_ties + 1') : DB::raw('survey_ties'), // Nueva columna
                        'updated_at' => now(),
                    ]);

                // --- PASO 6: Cargar la siguiente combinación para devolverla ---
                // Llamar al servicio para obtener la próxima combinación basada en la estrategia
                // Pasamos $user->id para que el servicio pueda excluir combinaciones ya votadas por este usuario
                $nextCombination = $this->combinatoricService->getNextCombination($surveyData, $user->id);

            }); // --- Fin de la transacción ---

        } catch (\Exception $e) {
            // Si ocurre cualquier error dentro de la transacción, se revierte automáticamente
            Log::error("Transaction failed in SurveyVoteController@store: " . $e->getMessage());
            return response()->json(['message' => 'Failed to process vote due to a server error.'], 500);
        }


        // --- Devolver respuesta JSON con datos actualizados ---
        // El frontend puede usar estos datos para actualizar su estado local
        return response()->json([
            'message' => 'Vote processed successfully.',
            'survey_data' => [
                'id' => $surveyData->id,
                'title' => $surveyData->title,
                'progress_percentage' => $newProgress, // El progreso calculado
                'total_votes' => $newTotalVotes,       // El total de votos actualizado
                'is_completed' => $newProgress >= 100, // Determinar si está completada basado en progreso
                // ... otros campos si se necesitan en el frontend ...
            ],
            'character_ratings' => [ // Opcional: Devolver ratings actualizados si el frontend los necesita para mostrar cambios inmediatos
                $character1Id => $newRating1,
                $character2Id => $newRating2,
            ],
            'next_combination' => $nextCombination ? [
                'id' => $nextCombination->id,
                'character1' => [
                    'id' => $nextCombination->character1->id,
                    'fullname' => $nextCombination->character1->fullname,
                    'picture_url' => $nextCombination->character1->picture_url, // Asumiendo que CharacterResource lo define
                    // ... otros campos necesarios ...
                ],
                'character2' => [
                    'id' => $nextCombination->character2->id,
                    'fullname' => $nextCombination->character2->fullname,
                    'picture_url' => $nextCombination->character2->picture_url, // Asumiendo que CharacterResource lo define
                    // ... otros campos necesarios ...
                ],
            ] : null,
        ], 200);

    } // --- Fin del método store ---
}