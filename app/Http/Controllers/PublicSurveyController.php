<?php

namespace App\Http\Controllers;

use App\Http\Resources\CharacterResource;
use App\Http\Resources\CombinatoricResource; // Inyectamos el servicio
use App\Http\Resources\PublicSurveyShowResource;
use App\Http\Resources\SurveyIndexResource;
use App\Http\Resources\SurveyVoteResource;
use App\Models\Survey;
use App\Services\Survey\CombinatoricService; // Para obtener el usuario autenticado
use App\Services\Survey\SurveyProgressService; // <-- Importar el recurso específico para la vista de votación
use Illuminate\Http\JsonResponse; // <-- Importar el recurso de combinación
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Asegúrate de importar SurveyIndexResource
use Inertia\Inertia; // <-- Importar el recurso específico para la vista de detalle (si existe)
use Inertia\Response; // Importar JsonResponse

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
            // ->where('date_start', '<=', now())
            // ->where('date_end', '>=', now())
            ->with(['category:id,name,slug']) // Cargar solo campos básicos de la categoría
            ->withCount(['characters']) // Contar personajes (activos o no)
            /* ->withCount(['characters' => function ($q) {
                $q->wherePivot('is_active', true);
            }]) // Contar personajes activos */
            ->orderBy('created_at', 'desc') // O el orden que prefieras
            ->paginate($request->get('per_page', 15))
            ->withQueryString();

        // dd($surveys);

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
     * @param  Survey  $survey  El modelo de encuesta, inyectado por Laravel gracias al route model binding.
     */
    public function show(Survey $survey): Response
    {
        // Verificar si la encuesta está activa
        if (! $this->isSurveyActive($survey)) {
            abort(404, 'Survey not found or not active.');
        }

        // Obtener el usuario autenticado
        $user = Auth::user();
        if (! $user) {
            // Opcional: Redirigir a login o mostrar mensaje si es necesario
            abort(401, 'Authentication required to view this survey.');
        }

        // Cargar datos necesarios para el resumen
        $survey->loadMissing(['category:id,name']); // Cargar categoría con campos específicos: id,name,slug,color,icon

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
            'survey' => PublicSurveyShowResource::make($survey)->resolve(), // <-- CORREGIDO: Usar SurveyShowResource
            'characters' => CharacterResource::collection($activeCharacters)->resolve(),
            'userProgress' => $progressStatus, // <-- Puede ser opcional si SurveyShowResource lo incluye
            // Puedes pasar otros datos necesarios aquí (estadísticas generales, etc.)
        ]);
    }

    /**
     * Prepara la encuesta para que el usuario pueda votar.
     * Carga los datos necesarios para la vista de votación.
     *
     * @param  Survey  $survey  El modelo de encuesta, inyectado por route model binding.
     */
    public function vote(Survey $survey): Response
    {
        // 1. Verificar autenticación
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('login')->with('error', 'Authentication required.');
        }

        // 2. Verificar si la encuesta está activa
        if (! $this->isSurveyActive($survey)) {
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
            // 'survey' => SurveyVoteResource::make($survey, ['userProgress' => $progressStatus])->resolve(), // <-- Usar .resolve() para pasar el array directamente
            'survey' => SurveyVoteResource::make($survey)->resolve(), // <-- Usar .resolve() para pasar el array directamente
            // Pasar la próxima combinación como un recurso separado
            'nextCombination' => $nextCombination ? CombinatoricResource::make($nextCombination)->resolve() : null, // <-- CORREGIDO: Nombrar la prop como 'nextCombination'
            'userProgress' => $progressStatus, // <-- NO ES NECESARIO SI LOS DATOS YA ESTÁN EN 'survey' RESUELTO
            // Puedes pasar otros datos auxiliares si es necesario (por ejemplo, la lista de estrategias si se puede cambiar dinámicamente)
        ]);
    }

    public function voto(Survey $survey): Response
    {
        // 1. Verificar autenticación
        $user = Auth::user();

        // 2. Verificar si la encuesta está activa - ya lo hace el SurveyVoteResource

        // 3. Cargar solo los datos necesarios para la vista de votación
        $survey->loadCount(['combinatorics']);
        // 4. Cargar datos de progreso del usuario actual
        $progressStatus = $this->surveyProgressService->getUserSurveyStatus($survey, $user);
        // dump($survey);
        // dump(SurveyVoteResource::make($survey)->resolve());
        // dump($progressStatus);
        // dd($user);

        // 5. Obtener la próxima combinación para mostrar al usuario

        // 6. Renderizar la vista Inertia con los recursos específicos
        return Inertia::render('Public/Surveys/Voto', [
            // Usar SurveyVoteResource para serializar solo los datos necesarios de la encuesta
            'survey' => SurveyVoteResource::make($survey)->resolve(), // <-- Usar .resolve() para pasar el array directamente
            'userProgress' => $progressStatus,
            // Puedes pasar otros datos auxiliares si es necesario (por ejemplo, la lista de estrategias si se puede cambiar dinámicamente)
        ]);
    }

    /**
     * Obtiene la próxima combinación de personajes para votar en una encuesta específica.
     * Endpoint para ser llamado vía AJAX desde el frontend (Voto.vue).
     * Devuelve un JSON estándar, no una respuesta de Inertia.
     *
     * @param  Survey  $survey  El modelo de encuesta, inyectado por route model binding.
     */
    public function getCombination4Voto(Survey $survey): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            // Si no hay usuario autenticado, devolver error 401
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        // Verificar si la encuesta está activa
        if (! $this->isSurveyActive($survey)) {
            return response()->json(['message' => 'Survey not found or not active.'], 404);
        }

        // Verificar el estado del progreso del usuario
        $progressStatus = $this->surveyProgressService->getUserSurveyStatus($survey, $user);

        if ($progressStatus['is_completed']) {
            // Si el usuario ya completó la encuesta, no hay más combinaciones
            return response()->json(['combination' => null, 'message' => 'Survey already completed for this user.'], 200);
        }

        // --- Obtener la próxima combinación ---
        // Aquí es donde se conectaría la lógica para obtener la combinación
        // Usando CombinatoricService (que ya implementamos con Query Builder)
        $nextCombination = $this->combinatoricService->getNextCombination($survey, $user->id);

        if (! $nextCombination) {
            // Si getNextCombination devuelve null, significa que no hay más combinaciones posibles
            // para este usuario en esta encuesta en este momento.
            return response()->json(['combination' => null, 'message' => 'No more combinations available.'], 200);
        }
        // dd(CombinatoricResource::make($nextCombination)->resolve());

        // Devolver la combinación encontrada como un JSON estándar
        // Usar CombinatoricResource y .resolve() para serializarla
        return response()->json([
            'combination' => CombinatoricResource::make($nextCombination)->resolve(), // <-- Resolver el recurso a un array
            'progress' => $progressStatus,
            'message' => 'Combination retrieved successfully.', // Mensaje opcional
        ], 200);

        // NOTA: NO usar ->header('X-Inertia', 'true') ni ->response() aquí para una llamada axios.
        // Ese patrón es para respuestas que Inertia.js maneja internamente tras una navegación.
    }

    public function getCombination4Voto2(Survey $survey)
    {
        $user = Auth::user();
        // 5. Obtener la próxima combinación para mostrar al usuario
        $nextCombination = $this->combinatoricService->getNextCombination($survey, $user->id);
        // $nextCombination->loadMissing(['character1', 'character2']);
        $combination = CombinatoricResource::make($nextCombination)->resolve();

        $combination2 = (new CombinatoricResource($nextCombination))->response()->header('X-Inertia', 'true');
        // dump($nextCombination);
        // dd($combination, $combination2);

        // return (new CombinatoricResource($nextCombination))->response()->header('X-Inertia', 'true');

        return $combination2;
    }

    /**
     * Verifica si una encuesta está activa.
     */
    private function isSurveyActive(Survey $survey): bool
    {
        return $survey->status && $survey->date_start <= now() && $survey->date_end >= now();
    }

    // Opcional: Otros métodos como store (para recibir votos) irían aquí o en un SurveyVoteController dedicado
    // public function store(...) { ... }
}
