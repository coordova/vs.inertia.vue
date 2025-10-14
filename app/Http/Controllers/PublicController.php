<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Http\Resources\SurveyResource;
use App\Models\Category;
use App\Models\Survey;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicController extends Controller
{
    /**
     * Display the landing page.
     */
    public function index(Request $request): Response
    {
        // Cargar categorías destacadas (status=1, is_featured=1) y activas
        $featuredCategories = Category::where('status', true)
                                    ->where('is_featured', true)
                                    ->orderBy('sort_order', 'asc')
                                    ->orderBy('name', 'asc')
                                    ->limit(6) // O el número que desees mostrar
                                    ->get();

        // Cargar encuestas activas (status=1) que estén dentro del rango de fechas
        // y opcionalmente ordenar por alguna métrica (destacada, reciente, etc.)
        $activeSurveys = Survey::where('status', true)
                              ->where('date_start', '<=', now())
                              ->where('date_end', '>=', now())
                              ->with(['category']) // Cargar la categoría para mostrarla
                              ->orderBy('created_at', 'desc') // Por ejemplo, las más recientes
                              ->limit(6) // O el número que desees mostrar
                              ->get();

        return Inertia::render('Landing', [
            'featuredCategories' => CategoryResource::collection($featuredCategories)->resolve(),
            'activeSurveys' => SurveyResource::collection($activeSurveys)->resolve(),
        ]);
    }
}