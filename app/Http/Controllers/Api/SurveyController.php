<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Survey;
use App\Services\Survey\CombinatoricService;
use App\Services\Survey\SurveyProgressService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\CharacterResource;

class SurveyController extends Controller
{
    public function __construct(
        protected CombinatoricService $combinatoricService,
        protected SurveyProgressService $surveyProgressService,
    ) {
        // Aplicar middleware de autenticación
        // $this->middleware('auth');
    }

    /**
     * Obtiene la próxima combinación de personajes para votar en una encuesta específica.
     *
     * @param Survey $survey El modelo de encuesta, inyectado por route model binding.
     * @return JsonResponse
     */
    public function getNextCombination(Survey $survey): JsonResponse
    {
        // La autenticación se verifica mediante el middleware 'auth' en routes/api.php
        // $user = Auth::user(); // <-- REMOVIDO: Redundante
        // No necesitamos la variable $user aquí explícitamente, ya que $surveyProgressService y combinatoricService
        // la obtienen internamente de Auth::user() o la reciben como parámetro si se refactoriza.

        // Verificar si la encuesta está activa
        if (!$survey->status || $survey->date_start > now() || $survey->date_end < now()) {
            return response()->json(['message' => 'Survey not found or not active.'], 404);
        }

        // Obtener el usuario autenticado (esto se haría internamente en los servicios si no se pasa como parámetro)
        // Si los servicios no obtienen Auth::user() internamente, debes pasarlo explícitamente.
        $user = \Illuminate\Support\Facades\Auth::user(); // Ojo: Si el middleware 'auth' falla, esta línea no se ejecuta.
        // En lugar de repetir Auth::user(), es mejor que los servicios dependan de que el usuario esté autenticado
        // y lo obtengan ellos mismos, o que el controlador se lo pase como parámetro.
        // La forma más limpia es que el servicio lo obtenga, pero para mantener la firma actual de los servicios,
        // pasamos $user aquí. Asumiremos que los servicios esperan el $user como parámetro.

        // Verificar el estado del progreso del usuario
        $status = $this->surveyProgressService->getUserSurveyStatus($survey, $user);

        if ($status['is_completed']) {
            // Si el usuario ya completó la encuesta, no hay más combinaciones
            return response()->json(['combination' => null, 'message' => 'Survey already completed for this user.'], 200);
        }

        // Obtener la próxima combinación usando el servicio
        $nextCombination = $this->combinatoricService->getNextCombination($survey, $user->id);

        if (!$nextCombination) {
            // Si getNextCombination devuelve null, significa que no hay más combinaciones posibles
            // Opcional: Marcar la encuesta como completada para el usuario aquí si se cumple la condición
            // Por ejemplo, si se han votado todas las combinaciones posibles (esto es complejo de determinar sin un contador global)
            // Por ahora, asumimos que si getNextCombination devuelve null, es porque no hay más pendientes.
            return response()->json(['combination' => null, 'message' => 'No more combinations available.'], 200);
        }

        // Devolver la combinación encontrada
        // Asumimos que CombinatoricResource existe y está bien definido para serializar
        // la combinación y sus personajes relacionados.
        // return response()->json(['combination' => new CombinatoricResource($nextCombination)], 200);

        // Si no usas un recurso específico para Combinatoric, devolvemos una estructura simple
        // que incluya la información necesaria para el frontend.
        // Asegúrate de que las relaciones 'character1' y 'character2' estén cargadas si no usas un Resource.
        // En la consulta de CombinatoricService->getNextCombination, puedes usar ->with(['character1', 'character2'])
        // o hacerlo aquí si no lo hace el servicio.
        // $nextCombination->loadMissing(['character1', 'character2']); // Carga si no está cargada

        // Asegurarse de que las relaciones 'character1' y 'character2' estén cargadas
        // Esto es crucial para la serialización. Si CombinatoricService no las carga,
        // debemos hacerlo aquí.
        // Suponiendo que CombinatoricService->getNextCombination puede devolver el modelo
        // sin las relaciones cargadas, las cargamos aquí.
        // OJO: Idealmente, la consulta en CombinatoricService debería incluir 'with' si se sabe que se necesitan.
        // Por ahora, lo hacemos aquí para garantizar los datos.// No necesitamos loadMissing aquí si el servicio ya lo hizo con 'with'
        // $nextCombination->loadMissing(['character1', 'character2']); // Carga si no está cargada las relaciones de character1 y character2 

        // Verificar que las relaciones se hayan cargado
        if (!$nextCombination->relationLoaded('character1') || !$nextCombination->relationLoaded('character2')) {
            \Log::warning("Relations character1 or character2 not loaded for combinatoric ID: {$nextCombination->id}");
            return response()->json(['message' => 'Internal error: character data not loaded.'], 500);
        }

        // Devolver la combinación encontrada
        // Usar CharacterResource para serializar correctamente cada personaje
        return response()->json([
            'combination' => [
                'combinatoric_id' => $nextCombination->id, // Usamos 'id' del modelo Combinatoric
                'character1' => new CharacterResource($nextCombination->character1), // <-- Usar Resource
                'character2' => new CharacterResource($nextCombination->character2), // <-- Usar Resource
            ]
        ], 200);
        
        // Devolver la combinación encontrada
        // Suponiendo que 'character1' y 'character2' están disponibles vía relaciones
        /* return response()->json([
            'combination' => [
                'combinatoric_id' => $nextCombination->id, // Usamos 'id' del modelo Combinatoric
                'character1' => [
                    'id' => $nextCombination->character1->id,
                    'fullname' => $nextCombination->character1->fullname, // Ajusta según los campos necesarios
                    'picture' => $nextCombination->character1->picture, // Ajusta según los campos necesarios
                    // ... otros campos necesarios de CharacterResource ...
                ],
                'character2' => [
                    'id' => $nextCombination->character2->id,
                    'fullname' => $nextCombination->character2->fullname, // Ajusta según los campos necesarios
                    'picture' => $nextCombination->character2->picture, // Ajusta según los campos necesarios
                    // ... otros campos necesarios de CharacterResource ...
                ],
            ]
        ], 200); */
    }
}