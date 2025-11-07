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
use App\Http\Resources\SurveyVoteResource; // <-- Importar el recurso específico para la vista de votación
use App\Http\Resources\CombinatoricResource; // <-- Importar el recurso de combinación
use App\Http\Resources\CharacterResource;
use App\Http\Resources\SurveyIndexResource; // Asegúrate de importar SurveyIndexResource
use App\Http\Resources\SurveyShowResource; // <-- Importar el recurso específico para la vista de detalle (si existe)

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
                         ->with(['category:id,name,slug']) // Cargar solo campos básicos de la categoría
                         ->withCount(['characters' => function ($q) { $q->wherePivot('is_active', true); }]) // Contar personajes activos
                         ->orderBy('created_at', 'desc') // O el orden que prefieras
                         ->paginate($request->get('per_page', 15))
                         ->withQueryString();

        // Pasar datos a la vista Inertia
        return Inertia::render('Public/Surveys/Index', [ // <-- CORREGIDO: Ruta correcta
            'surveys' => SurveyIndexResource::collection($surveys), // Usar Resource para coherencia
            'filters' => $request->only(['search', 'category_id', 'per_page']), // Ejemplo de filtros
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
        $survey->loadMissing(['category:id,name,slug,color,icon']); // Cargar categoría con campos específicos

        // Cargar personajes activos en esta encuesta
        $activeCharacters = $survey->characters()
                                   ->wherePivot('is_active', true)
                                   /* ->with(['category_stats' => function($q) use ($survey) { // Cargar stats del personaje en la categoría de la encuesta
                                       $q->where('category_id', $survey->category_id);
                                   }]) */
                                   ->get();

        // Verificar el estado del progreso del usuario en esta encuesta (opcional, para mostrar en la vista de show)
        $progressStatus = $this->surveyProgressService->getUserSurveyStatus($survey, $user);

        // Pasar datos a la vista Inertia de resumen
        return Inertia::render('Public/Surveys/Show', [ // <-- CORREGIDO: Ruta correcta
            'survey' => new SurveyShowResource($survey), // <-- CORREGIDO: Usar SurveyShowResource
            'characters' => CharacterResource::collection($activeCharacters),
            'userProgress' => $progressStatus, // <-- Puede ser opcional si SurveyShowResource lo incluye
            // Puedes pasar otros datos necesarios aquí (estadísticas generales, etc.)
        ]);
    }

    /**
     * Prepara la encuesta para que el usuario pueda votar.
     * Carga los datos necesarios para la vista de votación.
     *
     * @param Survey $survey El modelo de encuesta, inyectado por route model binding.
     * @return Response
     */
    public function vote(Survey $survey): Response
    {
        // 1. Verificar autenticación
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Authentication required.');
        }

        // 2. Verificar si la encuesta está activa
        if (!$this->isSurveyActive($survey)) {
            abort(404, 'Survey not found or not active.');
        }

        // 3. Cargar solo los datos necesarios para la vista de votación
        // Cargamos la categoría con campos específicos
        $survey->loadMissing([
            'category:id,name,slug,color,icon', // Cargar solo campos básicos de la categoría
            // 'characters' -> No es necesario cargar todos los caracteres aquí para mostrar la encuesta o la próxima combinación
        ]);

        // 4. Cargar datos de progreso del usuario actual
        $progressStatus = $this->surveyProgressService->getUserSurveyStatus($survey, $user);

        // 5. Obtener la próxima combinación para mostrar al usuario
        $nextCombination = $this->combinatoricService->getNextCombination($survey, $user->id);

        // 6. Renderizar la vista Inertia con los recursos específicos
        return Inertia::render('Public/Surveys/Vote', [ // <-- CORREGIDO: Ruta correcta
            // Usar SurveyVoteResource para serializar solo los datos necesarios de la encuesta
            'survey' => SurveyVoteResource::make($survey)->resolve(), // <-- Usar .resolve() para pasar el array directamente
            // Pasar la próxima combinación como un recurso separado
            'nextCombination' => $nextCombination ? CombinatoricResource::make($nextCombination)->resolve() : null, // <-- CORREGIDO: Nombrar la prop como 'nextCombination'
            // 'userProgress' => $progressStatus, // <-- NO ES NECESARIO SI LOS DATOS YA ESTÁN EN 'survey' RESUELTO
            // Puedes pasar otros datos auxiliares si es necesario (por ejemplo, la lista de estrategias si se puede cambiar dinámicamente)
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