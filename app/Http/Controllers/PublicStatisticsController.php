<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\SurveyResource; // O SurveyIndexResource si se usa para listados
use App\Http\Resources\CategoryResource; // O CategoryIndexResource
use App\Http\Resources\CharacterResource; // O CharacterIndexResource
use App\Models\Survey;
use App\Models\Category;
use App\Models\Character;
use App\Services\Ranking\RankingService; // Asumiendo que ya tienes este servicio o lo crearemos
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador para mostrar estadísticas y rankings públicos del sistema.
 * Gestiona vistas generales y específicas de resultados.
 *
 * @package App\Http\Controllers
 */
class PublicStatisticsController extends Controller
{
    /**
     * Constructor para inyectar dependencias si es necesario.
     *
     * @param RankingService $rankingService Servicio para cálculos de ranking.
     */
    public function __construct(
        protected RankingService $rankingService, // <-- Inyectar si se usa RankingService
    ) {
        // Aplicar middleware de autenticación si es necesario para todas las acciones
        // $this->middleware('auth'); // Si se requiere login para ver estadísticas
        // Opcional: Aplicar middleware para vistas públicas si no se requiere login
        // $this->middleware('auth')->except(['index', 'categoryRankings']);
    }

    /**
     * Muestra la página principal de estadísticas.
     * Incluye rankings generales, categorías activas, encuestas recientes, etc.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        // --- Cargar datos generales para la vista principal de estadísticas ---
        // 1. Cargar encuestas activas recientes (últimas creadas o más votadas)
        $recentSurveys = Survey::where('status', true)
                               ->where('date_start', '<=', now())
                               ->where('date_end', '>=', now())
                               ->with(['category:id,name,slug,color'])
                               ->orderBy('created_at', 'desc')
                               ->limit(5) // Ajustar número según diseño
                               ->get();

        // 2. Cargar categorías con mayor actividad (encuestas activas o cantidad de votos)
        // Ejemplo: Contar encuestas activas por categoría
        $activeCategories = Category::where('status', true)
                                    ->withCount(['surveys' => function($q) {
                                        $q->where('status', true)
                                          ->where('date_start', '<=', now())
                                          ->where('date_end', '>=', now());
                                    }])
                                    ->whereHas('surveys', function($q) {
                                         $q->where('status', true)
                                           ->where('date_start', '<=', now())
                                           ->where('date_end', '>=', now());
                                    })
                                    ->orderByDesc('surveys_count') // Ordenar por conteo de encuestas activas
                                    ->limit(5) // Ajustar número según diseño
                                    ->get();

        // 3. Cargar rankings generales (Top personajes globales por categoría)
        // Esto podría ser costoso, así que se podría cachear o usar una estrategia de carga por demanda
        // Por ahora, asumimos que se cargan rankings para las categorías activas recientes o más populares
        // Opcional: Crear un endpoint API para rankings dinámicos (como hicimos para la próxima combinación)
        // y llamarlo desde el frontend con axios para evitar cargar grandes cantidades de datos en el SSR.

        // --- Opciones para rankings ---
        // Opción A: Cargar rankings aquí (puede ser pesado)
        // $topGlobalCharacters = $this->rankingService->getTopGlobalRankings(); // <-- Si se implementa RankingService

        // Opción B: Cargar rankings de categorías específicas (más manejable)
        // $topCharactersByCategory = [];
        // foreach ($activeCategories as $category) {
        //     // Asumiendo un método en RankingService o en el modelo Category
        //     $topCharactersByCategory[$category->id] = $this->rankingService->getTopRankingsForCategory($category->id, 5); // Top 5
        // }

        // Opción C (Recomendada para index): No cargar rankings aquí, sino mostrar enlaces
        // para ver rankings por categoría o encuesta, y cargar los rankings específicos en sus propias páginas/shows.

        // Para esta vista principal (index), cargamos datos generales y enlaces a vistas específicas
        // No incluimos grandes colecciones de rankings aquí.

        // --- Renderizar la vista Inertia ---
        return Inertia::render('Public/Statistics/Index', [
            'recentSurveys' => SurveyResource::collection($recentSurveys)->resolve(),
            'activeCategories' => CategoryResource::collection($activeCategories)->resolve(),
            // 'topGlobalCharacters' => $topGlobalCharacters, // <-- Opción A (evitar en index si es costoso)
            // 'topCharactersByCategory' => $topCharactersByCategory, // <-- Opción B (evitar en index si es costoso)
            // 'filters' => $request->only(['search', 'category', 'period']), // Ejemplo de filtros para estadísticas
        ]);
    }

    /**
     * Muestra el ranking de personajes para una categoría específica.
     *
     * @param Category $category La categoría para la cual mostrar rankings.
     * @param Request $request
     * @return Response
     */
    public function categoryRankings(Category $category, Request $request): Response
    {
        // Verificar si la categoría está activa
        if (!$category->status) {
            abort(404, 'Category not found or not active.');
        }

        // Cargar la categoría con datos básicos si no están ya cargados
        // $category->loadMissing(['characters']); // Cargar personajes si es necesario para otros fines (aunque no para el ranking directo)

        // --- Cargar Rankings ---
        // El servicio RankingService se encargará de obtener los datos de la base de datos
        // y calcular el ranking basado en el ELO o métrica elegida.
        // Asumiendo que el servicio puede recibir la categoría y aplicar filtros/paginación si es necesario.
        $rankingData = $this->rankingService->getCategoryRanking($category, $request->all());

        // --- Renderizar la vista Inertia ---
        return Inertia::render('Public/Statistics/CategoryRankings', [
            'category' => CategoryResource::make($category)->resolve(), // <-- Resolver el recurso de la categoría
            'ranking' => $rankingData, // <-- Pasar el objeto Paginator directamente
            'filters' => $request->only(['search', 'sort', 'direction', 'per_page', 'page']), // Pasar filtros aplicados
        ]);
    }

    /**
     * Muestra el ranking de personajes para una categoría específica.
     *
     * @param Category $category Categoría específica.
     * @param Request $request
     * @return Response
     */
    public function categoryRankings_wo_ranking_service(Category $category, Request $request): Response
    {
        // Verificar si la categoría está activa
        if (!$category->status) {
            abort(404, 'Category not found or not active.');
        }

        // Opcional: Verificar si hay encuestas activas en esta categoría para mostrar rankings

        // Cargar ranking de personajes en esta categoría
        // Esto probablemente se haría con un servicio专门izado
        // Asumiendo un servicio RankingService que maneja rankings
        // $rankingService = app(RankingService::class); // O inyectar en el constructor
        // $topCharacters = $rankingService->getTopRankingsForCategory($category->id);

        // O directamente desde el modelo Category o Character con joins a category_character
        // Asumiendo que CategoryCharacter tiene un modelo y relaciones
        $topCharacters = $category->characters()
                                  ->wherePivot('status', true) // Solo personajes activos en la categoría
                                  ->orderByPivot('elo_rating', 'desc') // Ordenar por rating ELO descendente
                                  ->orderByPivot('matches_played', 'desc') // Empates por partidas jugadas
                                  ->limit($request->get('limit', 50)) // Paginar o limitar resultados
                                  ->get(['characters.*', 'category_character.elo_rating', 'category_character.matches_played', 'category_character.wins', 'category_character.losses', 'category_character.ties', 'category_character.win_rate']); // Seleccionar campos del personaje y del pivote

        // Usar un recurso específico para el ranking si es necesario
        // Asumiendo que CharacterResource ya incluye los campos necesarios o se crea un CharacterRankingResource
        // $topCharactersResource = CharacterRankingResource::collection($topCharacters);

        return Inertia::render('Public/Statistics/CategoryRankings', [
            'category' => new CategoryResource($category),
            'topCharacters' => $topCharacters, // Pasar directamente la colección o usar un recurso específico
            // 'filters' => $request->only(['limit', 'sort']),
        ]);
    }

    /**
     * Muestra los resultados y ranking final (o actual) de una encuesta específica.
     * Calcula el ranking basado en las estadísticas de la tabla character_survey.
     *
     * @param Survey $survey El modelo de encuesta, inyectado por route model binding.
     * @return Response
     */
    public function surveyResults(Survey $survey): Response
    {
        // Verificar si la encuesta está activa o ha finalizado (opcionalmente, solo mostrar resultados si ha finalizado)
        // if (!$this->isSurveyActive($survey) && $survey->date_end >= now()) {
        //    abort(404, 'Survey not found or not active yet.');
        // }

        // Verificar si la encuesta está activa o ha finalizado
        if (!$survey->status || ($survey->date_start > now() || $survey->date_end < now())) {
            // Permitir ver resultados incluso si la encuesta ya no está activa (ha finalizado)
            // Opcional: Mostrar un mensaje diferente si está activa o inactiva
        }

        // Cargar datos de la encuesta
        $survey->loadMissing(['category']); // Cargar categoría si no está cargada

        // --- OPCIÓN A: Calcular ranking desde character_survey ---
        // Este enfoque asume que character_survey se actualiza correctamente con cada voto
        // y que esos datos representan el estado actual/resumen de la encuesta para cada personaje.
        // Es más directo si la lógica de actualización en character_survey está bien implementada.
        $surveyRanking = CharacterSurvey::where('survey_id', $survey->id)
                                        ->with(['character:id,fullname,nickname,picture']) // Cargar datos básicos del personaje
                                        ->where('is_active', true) // Solo personajes activos en la encuesta
                                        ->orderBy('survey_wins', 'desc') // Ordenar por wins
                                        ->orderBy('survey_ties', 'desc') // Luego por ties
                                        ->orderBy('survey_losses', 'asc') // Luego por losses (menos pérdidas es mejor)
                                        ->orderBy('survey_matches', 'desc') // Empates por matches jugados
                                        ->get(); // <-- Carga la colección

        // Calcular posición manualmente basado en el orden
        $position = 1;
        $previousWins = null;
        $previousTies = null;
        $previousLosses = null;
        $previousMatches = null;

        $rankingWithPositions = $surveyRanking->map(function ($characterSurveyPivot) use (&$position, &$previousWins, &$previousTies, &$previousLosses, &$previousMatches) {
            $currentWins = $characterSurveyPivot->pivot->survey_wins;
            $currentTies = $characterSurveyPivot->pivot->survey_ties;
            $currentLosses = $characterSurveyPivot->pivot->survey_losses;
            $currentMatches = $characterSurveyPivot->pivot->survey_matches;

            // Verificar si hay empate con el personaje anterior (misma cantidad de wins, ties, losses, matches)
            if ($previousWins === $currentWins && $previousTies === $currentTies && $previousLosses === $currentLosses && $previousMatches === $currentMatches) {
                // Mantener la misma posición (empate técnico)
                $characterSurveyPivot->pivot->setAttribute('survey_position', $position - 1); // La posición no cambia
            } else {
                // Actualizar la posición
                $characterSurveyPivot->pivot->setAttribute('survey_position', $position);
                // Actualizar valores anteriores
                $previousWins = $currentWins;
                $previousTies = $currentTies;
                $previousLosses = $currentLosses;
                $previousMatches = $currentMatches;
            }

            $position++;
            return $characterSurveyPivot;
        });

        // --- OPCIÓN B (Alternativa): Usar RankingService para ranking final de encuesta ---
        // Si se implementa un servicio dedicado que calcula el ranking de la encuesta basado en votos
        // o en una tabla intermedia como `rank_positions` (como se ve en codebase4ask2ai.txt),
        // se usaría aquí.
        // $surveyRanking = $this->rankingService->getSurveyFinalRanking($survey);
        // En este caso, RankingService devolvería una colección de objetos con la posición ya calculada y el personaje relacionado.

        // Devolver la vista Inertia con los recursos específicos
        return Inertia::render('Public/Statistics/SurveyResults', [
            'survey' => SurveyResource::make($survey)->resolve(), // <-- Resolver el recurso de la encuesta
            'ranking' => CharacterSurveyResource::collection($rankingWithPositions)->resolve(), // <-- Resolver la colección de ranking
            // Puedes pasar otros datos auxiliares si es necesario (estadísticas generales de la encuesta)
        ]);
    }

    /**
     * Muestra los resultados y ranking de una encuesta específica.
     *
     * @param Survey $survey Encuesta específica.
     * @return Response
     */
    public function surveyResults_wo_(Survey $survey): Response
    {
        // Verificar si la encuesta está activa o ha finalizado
        if (!$survey->status || ($survey->date_end < now() && $survey->date_start > now())) {
            abort(404, 'Survey not found or not active.');
        }

        // Cargar datos de la encuesta
        $survey->loadMissing(['category', 'characters']); // Cargar categoría y personajes participantes

        // Calcular o cargar ranking final de la encuesta
        // Asumiendo un servicio RankingService o lógica específica
        // $surveyRanking = $this->rankingService->getSurveyFinalRanking($survey);

        // O, si los datos se calculan dinámicamente o se almacenan en una tabla de resultados
        // Se podría tener un modelo SurveyResult o una vista/materialized view
        // $surveyRanking = SurveyResult::where('survey_id', $survey->id)->orderBy('final_position')->get();

        // Por ahora, simulamos que no hay un cálculo específico de ranking final de encuesta
        // y mostramos los personajes ordenados por su ELO en la categoría al final de la encuesta
        // o por estadísticas acumuladas en character_survey para esta encuesta específica.
        // La mejor práctica es tener un cálculo de ranking final específico guardado al finalizar la encuesta.
        // Por simplicidad temporal, ordenamos por elo_rating en category_character para esta categoría
        $surveyRanking = $survey->characters()
                                ->orderByPivot('elo_rating', 'desc')
                                ->get(['characters.*', 'character_survey.survey_matches', 'character_survey.survey_wins', 'character_survey.survey_losses', 'character_survey.survey_ties']); // Seleccionar campos relevantes

        return Inertia::render('Public/Statistics/SurveyResults', [
            'survey' => new SurveyResource($survey),
            'ranking' => $surveyRanking, // Pasar colección o recurso
        ]);
    }

    /**
     * Muestra estadísticas detalladas de un personaje específico.
     *
     * @param Character $character Personaje específico.
     * @return Response
     */
    public function characterStats(Character $character): Response
    {
        // Verificar si el personaje está activo
        if (!$character->status) {
            abort(404, 'Character not found or not active.');
        }

        // Cargar datos del personaje
        $character->loadMissing(['categories', 'surveys']); // Cargar categorías y encuestas en las que participa

        // Calcular estadísticas globales del personaje (ELO promedio, win rate global, etc.)
        // Asumiendo que CharacterResource incluye relaciones con stats si se cargan
        // O usando un servicio专门izado
        // $globalStats = $this->rankingService->getCharacterGlobalStats($character);

        // Calcular estadísticas por encuesta
        // $surveyStats = $character->surveyStats; // Relación o método en el modelo

        return Inertia::render('Public/Statistics/CharacterStats', [
            'character' => new CharacterResource($character),
            // 'globalStats' => $globalStats,
            // 'surveyStats' => $surveyStats,
        ]);
    }

    // Otros métodos específicos de estadísticas pueden añadirse aquí
    // public function getGlobalRankings() { ... }
    // public function getSurveyComparisonGraph($surveyId) { ... }
    // public function getCharacterHistory($characterId) { ... }
}