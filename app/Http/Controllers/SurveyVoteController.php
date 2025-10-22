<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Survey;
use App\Models\Combinatoric;
use App\Models\Vote;
use App\Models\User;
use App\Models\CategoryCharacter; // Nuevo: para cargar ratings eficientemente
use App\Services\Survey\CombinatoricService;
use App\Services\Survey\SurveyProgressService;
use App\Services\Rating\EloRatingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\StoreVoteRequest;

class SurveyVoteController extends Controller
{
    // Inyección de dependencias en el constructor
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
     * Optimizado para minimizar consultas a la base de datos.
     *
     * @param StoreVoteRequest $request
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

        // 2. Validar los datos del voto (sin reglas que disparen consultas innecesarias aquí)
        /* $validatedData = $request->validate([
            'combinatoric_id' => 'required|integer|exists:combinatorics,id', // Validar existencia básica, relación se verifica con JOIN
            'winner_id' => 'required_without:tie|integer|exists:characters,id',
            'loser_id' => 'required_without:tie|integer|exists:characters,id',
            'tie' => 'required_without:winner_id,loser_id|boolean',
        ]); */

        // Usar los datos validados del Request
        $validatedData = $request->validated(); // <-- Usar datos validados
        
        /*--------------------------------------------------*/
        // --- Validación de Lógica de Negocio DESPUÉS de la validación básica ---
        $tie = $validatedData['tie'] ?? false;
        $winnerId = $validatedData['winner_id'] ?? null;
        $loserId = $validatedData['loser_id'] ?? null;

        if ($tie) {
            // Si es empate, winner_id y loser_id DEBEN estar ausentes o ser null
            if ($winnerId !== null || $loserId !== null) {
                return back()->withErrors(['tie' => 'If tie is selected, winner_id and loser_id must be absent.'])->withInput();
            }
        } else {
            // Si NO es empate, winner_id y loser_id SON obligatorios
            if (!$winnerId || !$loserId) {
                return back()->withErrors(['winner_id' => 'Winner and loser are required if not a tie.'])->withInput();
            }
            if ($winnerId === $loserId) {
                return back()->withErrors(['winner_id' => 'Winner and loser cannot be the same.'])->withInput();
            }
            // Validar que winner_id y loser_id sean parte de la combinación actual
            // (Este código ya lo tienes más abajo, solo lo menciono para completar la lógica)
        }
        /*--------------------------------------------------*/

        // 3. Cargar datos críticos en una sola transacción con JOINs para evitar N+1 y consultas redundantes
        // Cargamos la encuesta, la combinación, los personajes de la combinación y sus ratings ELO en la categoría de la encuesta
        $surveyData = Survey::with(['combinatorics' => function ($query) use ($validatedData) {
                $query->where('id', $validatedData['combinatoric_id']) // Filtrar por la combinación específica
                      ->with(['character1', 'character2']); // Cargar personajes
            }])
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

        // 4. Verificar si el usuario ya votó por esta combinación específica (fuera de la transacción principal)
        $existingVote = Vote::where('user_id', $user->id)
                            ->where('combinatoric_id', $combinatoric->id)
                            ->exists(); // Usar exists() es más eficiente que first() si solo se quiere saber si existe
        if ($existingVote) {
            return redirect()->route('surveys.public.index')->with('error', 'User has already voted on this combination.');
        }

        // 5. Verificar que las IDs de ganador/perdedor coincidan con la combinación actual
        $validCharacterIds = [$combinatoric->character1_id, $combinatoric->character2_id];
        if (isset($validatedData['winner_id']) && !in_array($validatedData['winner_id'], $validCharacterIds)) {
            return redirect()->route('surveys.public.index')->with('error', 'Winner ID does not match characters in the combination.');
        }
        if (isset($validatedData['loser_id']) && !in_array($validatedData['loser_id'], $validCharacterIds)) {
            return redirect()->route('surveys.public.index')->with('error', 'Loser ID does not match characters in the combination.');
        }
        if (isset($validatedData['winner_id']) && isset($validatedData['loser_id']) && $validatedData['winner_id'] === $validatedData['loser_id']) {
            return redirect()->route('surveys.public.index')->with('error', 'Winner and loser cannot be the same character.');
        }

        // 6. Verificar estado del progreso del usuario (fuera de la transacción principal para lectura)
        $status = $this->surveyProgressService->getUserSurveyStatus($surveyData, $user);
        if ($status['is_completed']) {
            return redirect()->route('surveys.public.index')->with('error', 'Survey already completed for this user.');
        }

        // 7. Determinar el resultado
        $tie = $validatedData['tie'] ?? false;
        $winnerId = $validatedData['winner_id'] ?? null;
        $loserId = $validatedData['loser_id'] ?? null;

        $result = '';
        if ($tie) {
            $result = 'draw';
        } else {
            if ($winnerId === $combinatoric->character1_id && $loserId === $combinatoric->character2_id) {
                $result = 'win';
            } elseif ($winnerId === $combinatoric->character2_id && $loserId === $combinatoric->character1_id) {
                $result = 'loss';
            } else {
                return redirect()->route('surveys.public.index')->with('error', 'Invalid winner/loser combination.');
            }
        }

        // 8. Cargar ratings ELO de los personajes en la categoría de la encuesta (fuera de la transacción principal también)
        // Usamos una sola consulta para obtener ambos ratings
        $characterIds = [$combinatoric->character1_id, $combinatoric->character2_id];
        $eloRatings = CategoryCharacter::where('category_id', $surveyData->category_id)
                                      ->whereIn('character_id', $characterIds)
                                      ->pluck('elo_rating', 'character_id'); // Colección ['char_id' => rating]

        if ($eloRatings->count() !== 2) {
             // Uno o ambos personajes no tienen entrada en category_character para esta categoría
            return redirect()->route('surveys.public.index')->with('error', 'Ratings not found for one or both characters in this category.'); // Error interno o inconsistencia de datos
        }

        $character1Rating = $eloRatings[$combinatoric->character1_id];
        $character2Rating = $eloRatings[$combinatoric->character2_id];


        // 9. Iniciar transacción solo para operaciones de escritura y cálculos críticos
        DB::transaction(function () use ($user, $surveyData, $combinatoric, $result, $tie, $winnerId, $loserId, $character1Rating, $character2Rating, $request) {
            // --- Capturar información adicional del voto ---
            $ipAddress = $request->ip(); // Obtener la IP del cliente
            $userAgent = $request->userAgent(); // Obtener el User-Agent
            
            // 10. Registrar el voto
            Vote::create([
                'user_id' => $user->id,
                'combinatoric_id' => $combinatoric->id,
                'survey_id' => $surveyData->id,
                'winner_id' => $tie ? null : $winnerId,
                'loser_id' => $tie ? null : $loserId,
                'tie_score' => $tie ? $surveyData->tie_weight : null,
                // --- Campos adicionales ---
                'ip_address' => $ipAddress, // <-- Almacenar IP
                'user_agent' => $userAgent, // <-- Almacenar User-Agent
                'voted_at' => now(), // Fecha/hora específica del voto
                'is_valid' => true, // Asumimos válido por ahora
                // notes se deja null por defecto
            ]);

            // 11. Marcar la combinación como usada (OK, delegado al servicio)
            $this->combinatoricService->markCombinationUsed($combinatoric);

            // 12. Actualizar el progreso del usuario (OK, delegado al servicio)
            $currentProgress = $this->surveyProgressService->getUserSurveyStatus($surveyData, $user);
            $newTotalVotes = $currentProgress['total_votes'] + 1;
            // $progressPercentage = $currentProgress['progress']; // Ya no se usa

            // Pasar el ID del pivote al servicio
            $this->surveyProgressService->updateProgress($currentProgress['pivot_id'], $newTotalVotes);
            
            /* 
            // Obtenemos el progreso *dentro* de la transacción para asegurar consistencia si se comparte entre hilos
            $currentProgress = $this->surveyProgressService->getUserSurveyStatus($surveyData, $user);
            $newTotalVotes = $currentProgress['total_votes'] + 1;
            // Cálculo de progreso se mantiene como placeholder o se implementa lógica real aquí si es necesario
            // $progressPercentage = $currentProgress['progress'];
            // $surveyUserPivot = $currentProgress['pivot'] ?? $this->surveyProgressService->startSurveySession($surveyData, $user);
            // $this->surveyProgressService->updateProgress($surveyUserPivot, $progressPercentage, $newTotalVotes);
            // Pasar el objeto pivote al servicio
            
            // Asegúrate de que $currentProgress['pivot'] NO es null antes de pasar.
            if ($currentProgress['pivot']) {
                $this->surveyProgressService->updateProgress($currentProgress['pivot'], $newTotalVotes);
            } else {
                // Manejar el caso donde no hay pivote (aunque getUserSurveyStatus debería haberlo creado o cargado)
                \Log::warning("Attempted to update progress but pivot was null for user {$user->id} on survey {$surveyData->id}.");
            } 
            */

            // 13. Calcular y aplicar los nuevos ratings ELO (OK, delegado al servicio)
            // Pasamos los ratings ya cargados
            if ($tie) {
                 [$newRating1, $newRating2] = $this->eloRatingService->calculateNewRatings($character1Rating, $character2Rating, 'draw', $surveyData->tie_weight);
            } else {
                 // Determinar quién es el ganador/perdedor para el cálculo ELO
                 $winnerRating = $winnerId === $combinatoric->character1_id ? $character1Rating : $character2Rating;
                 $loserRating = $winnerId === $combinatoric->character1_id ? $character2Rating : $character1Rating;

                 [$newWinnerRating, $newLoserRating] = $this->eloRatingService->calculateNewRatings($winnerRating, $loserRating, 'win');

                 // Asignar los nuevos ratings a las variables correctas
                 if ($winnerId === $combinatoric->character1_id) {
                    $newRating1 = $newWinnerRating;
                    $newRating2 = $newLoserRating;
                 } else {
                    $newRating1 = $newLoserRating;
                    $newRating2 = $newWinnerRating;
                 }
            }

            $this->eloRatingService->applyRatings($surveyData->category_id, $combinatoric->character1_id, $combinatoric->character2_id, $newRating1, $newRating2, $result);
        });

        // 14. Devolver respuesta de éxito
        // return response()->json(['message' => 'Vote processed successfully.'], 200);
        return back()->with('success', 'Vote processed successfully.'); // <-- CORRECTO
        // O redirigir a la misma encuesta
        // return to_route('surveys.public.show', $survey)->with('success', 'Vote processed successfully.');
    }
}