<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVoteRequest;
use App\Models\Combinatoric;
use App\Models\Survey;
use App\Models\Vote;
use App\Services\Rating\EloRatingService;
use App\Services\Survey\CombinatoricService;
use App\Services\Survey\SurveyProgressService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SurveyVoteController extends Controller
{
    public function __construct(
        protected CombinatoricService $combinatoricService,
        protected EloRatingService $eloRatingService,
        protected SurveyProgressService $surveyProgressService,
        // Nota: SurveyProgressService ya no se inyecta aquí si su lógica se mueve al QB en el controlador
    ) {
        // Aplicar middleware de autenticación si es necesario
        // $this->middleware('auth');
    }

    /**
     * Recibe un voto para una combinación específica dentro de una encuesta.
     * Optimizado para usar Query Builder en todas las operaciones de escritura en tablas pivote.
     * Devuelve una respuesta JSON.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreVoteRequest $request, int $surveyId)
    {
        // dd($request->all(), $surveyId);
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        $validatedData = $request->validated();
        $combinatoricId = $validatedData['combinatoric_id'];
        $winnerId = $validatedData['winner_id'] ?? null;
        $loserId = $validatedData['loser_id'] ?? null;
        $tie = $validatedData['tie'] ?? false;

        // dd($combinatoricId, $winnerId, $loserId, $tie);

        // --- Validación de Lógica de Negocio ---
        if ($tie) {
            if ($winnerId !== null || $loserId !== null) {
                return response()->json(['errors' => ['tie' => 'If tie is selected, winner_id and loser_id must be absent.']], 422);
            }
        } else {
            if (! $winnerId || ! $loserId) {
                return response()->json(['errors' => ['winner_id' => 'Winner and loser are required if not a tie.']], 422);
            }
            if ($winnerId === $loserId) {
                return response()->json(['errors' => ['winner_id' => 'Winner and loser cannot be the same character.']], 422);
            }
        }

        // --- Carga de Datos Iniciales Fuera de la Transacción ---
        $surveyData = Survey::with(['category:id,name,slug'])
            ->where('id', $surveyId)
            ->where('status', true)
            ->where('date_start', '<=', now())
            ->where('date_end', '>=', now())
            ->first();

        if (! $surveyData) {
            return response()->json(['message' => 'Survey not found or not active.'], 404);
        }

        $combinatoric = Combinatoric::with(['character1:id,fullname,picture', 'character2:id,fullname,picture'])
            ->where('id', $combinatoricId)
            ->where('survey_id', $surveyId)
            ->where('status', true)
            ->first();

        if (! $combinatoric) {
            return response()->json(['message' => 'Invalid combination for this survey.'], 400);
        }

        $existingVote = Vote::where('user_id', $user->id)
            ->where('combinatoric_id', $combinatoric->id)
            ->exists();
        if ($existingVote) {
            return response()->json(['message' => 'User has already voted on this combination.'], 400);
        }

        // dd($surveyData, $combinatoric, $existingVote);

        $characterIds = [$combinatoric->character1_id, $combinatoric->character2_id];
        $categoryId = $surveyData->category_id;
        $eloRatings = DB::table('category_character')
            ->where('category_id', $categoryId)
            ->whereIn('character_id', $characterIds)
            ->pluck('elo_rating', 'character_id');

        if ($eloRatings->count() !== 2) {
            Log::error("Ratings not found for one or both characters ({$combinatoric->character1_id}, {$combinatoric->character2_id}) in category {$categoryId} for survey {$surveyId}.");

            return response()->json(['message' => 'Ratings not found for one or both characters in this category.'], 500);
        }

        $character1Rating = $eloRatings[$combinatoric->character1_id];
        $character2Rating = $eloRatings[$combinatoric->character2_id];

        $result = $tie ? 'draw' : ($winnerId === $combinatoric->character1_id ? 'win' : 'loss');

        // --- Iniciar Transacción ---
        $newProgress = 0;
        $newTotalVotes = 0;
        $newRating1 = $character1Rating;
        $newRating2 = $character2Rating;
        $nextCombination = null;

        try {
            DB::transaction(function () use (
                $user, $surveyData, $combinatoric, $categoryId, $characterIds, $result, $tie, $winnerId, $loserId, $character1Rating, $character2Rating, $request,
                &$newProgress, &$newTotalVotes, &$newRating1, &$newRating2, &$nextCombination
            ) {
                // --- PASO 1: Registrar el voto ---
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
                DB::table('combinatorics')
                    ->where('id', $combinatoric->id)
                    ->update([
                        'total_comparisons' => DB::raw('total_comparisons + 1'),
                        'last_used_at' => now(),
                    ]);

                // --- PASO 3.1: Actualizar el progreso del usuario en `survey_user` usando SurveyProgressService ---
                // En lugar de cargar el modelo y usar Eloquent para update, usamos el servicio dedicado
                // que ya maneja Query Builder correctamente.
                // El servicio se encarga de cargar, incrementar, recalcular y actualizar la entrada survey_user.
                $updatedProgressData = $this->surveyProgressService->incrementAndRecalculateProgress($user->id, $surveyData->id);

                if (!$updatedProgressData) {
                    // Si el servicio falla (por ejemplo, si no encuentra la entrada), es un error inesperado
                    Log::error("SurveyVoteController: Failed to increment and recalculate progress for user {$user->id} and survey {$surveyData->id}.");
                    // Opcional: Lanzar una excepción para que la transacción falle
                    throw new \Exception("Failed to update user progress.");
                }

                // Opcional: Actualizar el objeto $surveyData local con los nuevos valores si se necesitan más adelante en la transacción
                // $surveyData->progress_percentage = $updatedProgressData['progress_percentage'];
                // $surveyData->total_votes = $updatedProgressData['total_votes'];

                // --- PASO 3.2: Actualizar progreso del usuario (survey_user) ---
                // Cargar el pivote para obtener total_combinations_expected y el progreso actual
                /* $surveyUserEntry = DB::table('survey_user')
                    ->where('user_id', $user->id)
                    ->where('survey_id', $surveyData->id)
                    ->lockForUpdate() // Prevenir race conditions
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
                            'updated_at' => now(),
                        ]);
                } else {
                    // Manejar el caso donde no existía la entrada (aunque debería haberse creado en 'show' o 'vote')
                    Log::warning("SurveyUser entry missing for user {$user->id}, survey {$surveyData->id} during vote processing.");
                    // Opcional: Lanzar una excepción para abortar la transacción si es inesperado
                    // throw new \Exception("SurveyUser entry not found.");
                    // O, si se permite crear aquí (menos recomendado, debería ser en 'startSurveySession'):
                    // $this->surveyProgressService->startSurveySession($surveyData, $user); // Llama al servicio
                    // return; // O continuar con valores predeterminados
                } */

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
                    } else {
                        $newRating1 = $newLoserRating;
                        $newRating2 = $newWinnerRating;
                    }
                }

                // --- PASO 4.1: Actualizar ratings en category_character ---
                // Cargar datos actuales para calcular nuevas estadísticas
                $currentCharacterStats = DB::table('category_character')
                    ->where('category_id', $categoryId)
                    ->whereIn('character_id', $characterIds)
                    ->get(['character_id', 'elo_rating', 'matches_played', 'wins', 'losses', 'ties', 'highest_rating', 'lowest_rating']); // Seleccionar solo campos necesarios

                $statMap = $currentCharacterStats->keyBy('character_id')->toArray();

                $stat1 = $statMap[$combinatoric->character1_id];
                $stat2 = $statMap[$combinatoric->character2_id];

                // Calcular nuevas estadísticas para character1
                $newMatchesPlayed1 = $stat1->matches_played + 1;
                $newWins1 = $stat1->wins;
                $newLosses1 = $stat1->losses;
                $newTies1 = $stat1->ties ?? 0;
                if ($result === 'win') {
                    $newWins1++;
                } elseif ($result === 'loss') {
                    $newLosses1++;
                } elseif ($result === 'draw') {
                    $newTies1++;
                }

                $newWinRate1 = $newMatchesPlayed1 > 0 ? ($newWins1 / $newMatchesPlayed1) * 100 : 0.00;
                $newHighestRating1 = max($stat1->highest_rating, $newRating1);
                $newLowestRating1 = min($stat1->lowest_rating, $newRating1);

                // Calcular nuevas estadísticas para character2
                $newMatchesPlayed2 = $stat2->matches_played + 1;
                $newWins2 = $stat2->wins;
                $newLosses2 = $stat2->losses;
                $newTies2 = $stat2->ties ?? 0;
                if ($result === 'loss') {
                    $newWins2++;
                } // Si 1 ganó, 2 perdió
                elseif ($result === 'win') {
                    $newLosses2++;
                } // Si 1 perdió, 2 ganó
                elseif ($result === 'draw') {
                    $newTies2++;
                } // Si empate

                $newWinRate2 = $newMatchesPlayed2 > 0 ? ($newWins2 / $newMatchesPlayed2) * 100 : 0.00;
                $newHighestRating2 = max($stat2->highest_rating, $newRating2);
                $newLowestRating2 = min($stat2->lowest_rating, $newRating2);

                // Aplicar actualizaciones a category_character usando Query Builder
                DB::table('category_character')
                    ->where('category_id', $categoryId)
                    ->where('character_id', $combinatoric->character1_id)
                    ->update([
                        'elo_rating' => $newRating1,
                        'matches_played' => $newMatchesPlayed1,
                        'wins' => $newWins1,
                        'losses' => $newLosses1,
                        'ties' => $newTies1, // <-- Columna añadida
                        'win_rate' => $newWinRate1,
                        'highest_rating' => $newHighestRating1,
                        'lowest_rating' => $newLowestRating1,
                        'last_match_at' => now(),
                        'updated_at' => now(),
                    ]);

                DB::table('category_character')
                    ->where('category_id', $categoryId)
                    ->where('character_id', $combinatoric->character2_id)
                    ->update([
                        'elo_rating' => $newRating2,
                        'matches_played' => $newMatchesPlayed2,
                        'wins' => $newWins2,
                        'losses' => $newLosses2,
                        'ties' => $newTies2, // <-- Columna añadida
                        'win_rate' => $newWinRate2,
                        'highest_rating' => $newHighestRating2,
                        'lowest_rating' => $newLowestRating2,
                        'last_match_at' => now(),
                        'updated_at' => now(),
                    ]);

                // --- PASO 5: Actualizar estadísticas en character_survey ---
                // Calcular nuevas estadísticas para character1 en la encuesta
                $newSurveyMatches1 = DB::raw('survey_matches + 1');
                $newSurveyWins1 = $result === 'win' ? DB::raw('survey_wins + 1') : DB::raw('survey_wins');
                $newSurveyLosses1 = $result === 'loss' ? DB::raw('survey_losses + 1') : DB::raw('survey_losses');
                $newSurveyTies1 = $result === 'draw' ? DB::raw('survey_ties + 1') : DB::raw('survey_ties');

                // Calcular nuevas estadísticas para character2 en la encuesta
                $newSurveyMatches2 = DB::raw('survey_matches + 1');
                $newSurveyWins2 = $result === 'loss' ? DB::raw('survey_wins + 1') : DB::raw('survey_wins'); // Invertido
                $newSurveyLosses2 = $result === 'win' ? DB::raw('survey_losses + 1') : DB::raw('survey_losses'); // Invertido
                $newSurveyTies2 = $result === 'draw' ? DB::raw('survey_ties + 1') : DB::raw('survey_ties'); // Igual

                // Aplicar actualizaciones a character_survey usando Query Builder
                DB::table('character_survey')
                    ->where('character_id', $combinatoric->character1_id)
                    ->where('survey_id', $surveyData->id)
                    ->update([
                        'survey_matches' => $newSurveyMatches1,
                        'survey_wins' => $newSurveyWins1,
                        'survey_losses' => $newSurveyLosses1,
                        'survey_ties' => $newSurveyTies1, // <-- Nueva columna
                        'updated_at' => now(),
                    ]);

                DB::table('character_survey')
                    ->where('character_id', $combinatoric->character2_id)
                    ->where('survey_id', $surveyData->id)
                    ->update([
                        'survey_matches' => $newSurveyMatches2,
                        'survey_wins' => $newSurveyWins2,
                        'survey_losses' => $newSurveyLosses2,
                        'survey_ties' => $newSurveyTies2, // <-- Nueva columna
                        'updated_at' => now(),
                    ]);

                // --- PASO 6: Cargar la siguiente combinación para devolverla --- NO ES NECESARIO porque la logica para nextCombination se la realiza en Voto.vue
                // Llamar al servicio para obtener la próxima combinación basada en la estrategia
                // $nextCombination = $this->combinatoricService->getNextCombination($surveyData, $user->id);

            }); // --- Fin de la transacción ---

        } catch (\Exception $e) {
            Log::error('Transaction failed in SurveyVoteController@store: '.$e->getMessage());

            return response()->json(['message' => 'Failed to process vote due to a server error.'], 500);
        }

        // --- Devolver respuesta JSON ---
        return response()->json([
            'message' => 'Vote processed successfully.',
            'survey_data' => [
                'id' => $surveyData->id,
                'title' => $surveyData->title,
                'progress_percentage' => $newProgress,
                'total_votes' => $newTotalVotes,
                'is_completed' => $newProgress >= 100,
            ],
            'character_ratings' => [
                $combinatoric->character1_id => $newRating1,
                $combinatoric->character2_id => $newRating2,
            ],
            'next_combination' => $nextCombination ? [
                'id' => $nextCombination->id,
                'character1' => [
                    'id' => $nextCombination->character1->id,
                    'fullname' => $nextCombination->character1->fullname,
                    'picture_url' => $nextCombination->character1->picture_url,
                ],
                'character2' => [
                    'id' => $nextCombination->character2->id,
                    'fullname' => $nextCombination->character2->fullname,
                    'picture_url' => $nextCombination->character2->picture_url,
                ],
            ] : null,
        ], 200);
        // ], 200)->header('X-Inertia', 'true');
    }
}
