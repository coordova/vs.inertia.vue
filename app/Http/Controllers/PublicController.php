<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Http\Resources\SurveyIndexResource;
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
                                    ->limit(3) // O el número que desees mostrar
                                    ->get();

        // Cargar encuestas activas (status=1) que estén dentro del rango de fechas
        // y opcionalmente ordenar por alguna métrica (destacada, reciente, etc.)
        $recentSurveys = Survey::where('status', true)
                              ->where('date_start', '<=', now())
                              ->where('date_end', '>=', now())
                              ->with(['category:id,name,slug,color', 'characters']) // Cargar la categoría para mostrarla
                              ->orderBy('created_at', 'desc') // Por ejemplo, las más recientes
                              ->limit(6) // O el número que desees mostrar
                              ->get();
// dd(SurveyIndexResource::collection($recentSurveys)->resolve());
        // Opcional: Cargar encuestas populares o más votadas (requiere joins/stats)
        // $popularSurveys = DB::table('surveys')
        //                     ->join('survey_user', 'surveys.id', '=', 'survey_user.survey_id')
        //                     ->where('surveys.status', true)
        //                     ->where('surveys.date_start', '<=', now())
        //                     ->where('surveys.date_end', '>=', now())
        //                     ->select('surveys.*', DB::raw('SUM(survey_user.total_votes) as total_votes'))
        //                     ->groupBy('surveys.id')
        //                     ->orderBy('total_votes', 'desc')
        //                     ->limit(6)
        //                     ->get();

        // Opcional: Cargar encuestas que están próximas a finalizar
        // $endingSoonSurveys = Survey::where(...)->orderBy('date_end', 'asc')->get();

        return Inertia::render('LandingPage', [
            'featuredCategories' => CategoryResource::collection($featuredCategories)->resolve(),
            'recentSurveys' => SurveyIndexResource::collection($recentSurveys)->resolve(),
            // 'popularSurveys' => SurveyIndexResource::collection($popularSurveys)->resolve(), // Si se implementa
            // 'endingSoonSurveys' => SurveyIndexResource::collection($endingSoonSurveys)->resolve(), // Si se implementa
        ]);
    }
}