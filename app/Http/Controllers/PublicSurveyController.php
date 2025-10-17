<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Survey;
use App\Services\Survey\SurveyProgressService; // Inyectamos el servicio
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Auth; // Para obtener el usuario autenticado

class PublicSurveyController extends Controller
{
    public function __construct(
        protected SurveyProgressService $surveyProgressService,
    ) {
        // Aplicar middleware de autenticación si es necesario para todas las acciones de este controlador
        // $this->middleware('auth');
    }

    /**
     * Muestra la página de una encuesta específica para votar.
     *
     * @param Survey $survey El modelo de encuesta, inyectado por Laravel gracias al route model binding.
     * @return Response
     */
    public function show(Survey $survey): Response
    {
        // Verificar si la encuesta está activa
        if (!$survey->status || $survey->date_start > now() || $survey->date_end < now()) {
            abort(404, 'Survey not found or not active.');
        }

        // Obtener el usuario autenticado
        $user = Auth::user();
        if (!$user) {
            // Opcional: Redirigir a login o mostrar mensaje si es necesario
            abort(401, 'Authentication required to view this survey.');
        }

        // Cargar datos necesarios: detalles de la encuesta, personajes participantes, estado del usuario
        // Cargamos la categoría de la encuesta
        $survey->load('category');

        // Cargamos los personajes activos en esta encuesta (usando la relación pivote character_survey)
        $activeCharacters = $survey->characters()->wherePivot('is_active', true)->get();

        // Verificar el estado del progreso del usuario en esta encuesta
        $progressStatus = $this->surveyProgressService->getUserSurveyStatus($survey, $user);

        // Pasar datos a la vista Inertia
        return Inertia::render('Surveys/PublicShow', [
            'survey' => $survey, // Se asume que SurveyResource se usa aquí o se formatea directamente
            'characters' => $activeCharacters, // Se asume que CharacterResource se usa aquí o se formatea directamente
            'userProgress' => $progressStatus, // Información del progreso del usuario
            // Puedes pasar otros datos necesarios aquí
        ]);
    }

    // Opcional: Método index para listar encuestas públicas
    // public function index(Request $request): Response { ... }
}