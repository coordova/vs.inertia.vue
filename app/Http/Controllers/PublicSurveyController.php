<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Survey;
use App\Services\Survey\SurveyProgressService; // Inyectamos el servicio
use App\Services\Survey\CombinatoricService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth; // Para obtener el usuario autenticado
use App\Http\Resources\CharacterResource;
use App\Http\Resources\SurveyIndexResource; // Asegúrate de importar SurveyResource
use App\Http\Resources\SurveyVoteResource;
use App\Http\Resources\CombinatoricResource;

use App\Http\Resources\SurveyBaseResource;

class PublicSurveyController extends Controller
{
    public function __construct(
        protected SurveyProgressService $surveyProgressService,
        protected CombinatoricService $combinatoricService,
    ) {
        // Aplicar middleware de autenticación si es necesario para todas las acciones de este controlador
        // $this->middleware('auth');
    }

    public function index(Request $request): Response
    {
        // TODO: Implementar paginación, búsqueda, filtros si es necesario
        // Obtener encuestas públicas activas
        $surveys = Survey::where('status', true)
                         ->where('date_start', '<=', now())
                         ->where('date_end', '>=', now())
                         ->with(['category']) // Cargar categoría para mostrarla
                         ->orderBy('created_at', 'desc') // O el orden que prefieras
                         ->paginate($request->get('per_page', 15))->withQueryString();

        // Pasar datos a la vista Inertia
        return Inertia::render('Surveys/PublicIndex', [
            'surveys' => SurveyIndexResource::collection($surveys), // Usar Resource para coherencia
        ]);
    }

    /**
     * Verifica y muestra la información de resumen de una encuesta específica.
     * NO inicia automáticamente la sesión de votación.
     *
     * @param Survey $survey El modelo de encuesta, inyectado por Laravel gracias al route model binding.
     * @return Response
     */
    public function show(Survey $survey): Response
    {
        // Verificar si la encuesta está activa
        if (!$this->isSurveyActive($survey)) {
            abort(404, 'Survey not found or not active.');
        }

        // Obtener el usuario autenticado
        $user = Auth::user();
        if (!$user) {
            // Opcional: Redirigir a login o mostrar mensaje si es necesario
            abort(401, 'Authentication required to view this survey.');
        }

        // Cargar datos necesarios para el resumen
        $survey->loadMissing(['category']); // Cargar categoría si no está cargada

        // Cargar personajes activos en esta encuesta
        $activeCharacters = $survey->characters()->wherePivot('is_active', true)->get();

        // Verificar el estado del progreso del usuario en esta encuesta
        $progressStatus = $this->surveyProgressService->getUserSurveyStatus($survey, $user);

        // Pasar datos a la vista Inertia de resumen
        return Inertia::render('Surveys/PublicShow', [ // O 'Surveys/Summary'
            'survey' => /* new SurveyShowResource */($survey), // Usar Resource
            'characters' => CharacterResource::collection($activeCharacters),
            // 'userProgress' => $progressStatus,
            // Puedes pasar otros datos necesarios aquí (estadísticas generales, etc.)
        ]);
    }

    /**
     * Prepara la encuesta para que el usuario pueda votar.
     * Verifica la encuesta, inicia la sesión del usuario si no existe,
     * y obtiene la próxima combinación para mostrar.
     *
     * @param Survey $survey El modelo de encuesta, inyectado por Laravel.
     * @return Response
     */
    public function vote(Survey $survey): Response
    {
         // Verificar si la encuesta está activa
        if (!$this->isSurveyActive($survey)) {
            abort(404, 'Survey not found or not active.');
        }

        // Obtener el usuario autenticado
        $user = Auth::user();
        if (!$user) {
            abort(401, 'Authentication required to participate in this survey.');
        }

        // Cargar datos necesarios
        $survey->loadMissing(['category']); // Cargar categoría

        // --- Verificar/Iniciar sesión de votación del usuario ---
        // Obtener o iniciar el progreso del usuario en esta encuesta
        $progressStatus = $this->surveyProgressService->getUserSurveyStatus($survey, $user);
        
        if (!$progressStatus['exists']) {
            // Si no existe una entrada en survey_user, la creamos/iniciamos
            // Esto también calcula y almacena total_combinations_expected
            $this->surveyProgressService->startSurveySession($survey, $user);
            // Refrescar el estado después de iniciar la sesión
            $progressStatus = $this->surveyProgressService->getUserSurveyStatus($survey, $user);
        }

        if ($progressStatus['is_completed']) {
            // Si el usuario ya completó la encuesta, redirigir o mostrar mensaje
            return redirect()->route('surveys.public.show', $survey)->with('info', 'You have already completed this survey.');
        }

        // Cargar personajes activos en esta encuesta (para mostrar en la UI si es necesario)
        $activeCharacters = $survey->characters()->wherePivot('is_active', true)->get();

        // Unir datos de encuesta y progreso como objeto
        // $surveyData = (object) array_merge($survey->toArray(), $progressStatus);
        // dd($surveyData);

        // --- Obtener la próxima combinación ---
        // Aquí es donde se conectaría la lógica para obtener la combinación
        // Por ejemplo, usando CombinatoricService (que aún no se muestra, pero se asume existe)
        $nextCombination = $this->combinatoricService->getNextCombination($survey, $user->id);
        // Si no hay más combinaciones, $nextCombination será null
        
        // Pasar datos a la vista Inertia de votación
        return Inertia::render('Surveys/PublicVote', [ // O 'Surveys/VoteInterface' (nombre del componente Vue)
            // 'survey' => new SurveyVoteResource($survey), // Usar Resource
            'survey' => SurveyVoteResource::make($survey)->resolve(), // Usar Resource
            'characters' => CharacterResource::collection($activeCharacters),
            // 'userProgress' => /* new SurveyProgressResource */($progressStatus),
            // 'nextCombination' => $nextCombination ? new CombinatoricResource($nextCombination) : null, // Si se usa CombinatoricService
            'nextCombination' => $nextCombination ?  CombinatoricResource::make($nextCombination)->resolve() : null, // Si se usa CombinatoricService
            // Puedes pasar otros datos necesarios aquí
        ]);
    }


    /**
     * Verifica si una encuesta está activa.
     *
     * @param Survey $survey
     * @return bool
     */
    private function isSurveyActive(Survey $survey): bool
    {
        return $survey->status && $survey->date_start <= now() && $survey->date_end >= now();
    }

    // Opcional: Otros métodos como store (para recibir votos) irían aquí o en un SurveyVoteController dedicado
    // public function store(...) { ... }
}
