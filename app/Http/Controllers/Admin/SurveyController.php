<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSurveyRequest;
use App\Http\Requests\UpdateSurveyRequest;
use App\Http\Resources\SurveyShowResource;
use App\Models\Survey;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\LookupService;

class SurveyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $surveys = Survey::with(['category:id,name'])
                            ->when(request('search'), function ($query, $search) {
                                $query->where('title', 'like', '%' . $search . '%');
                            })
                            // orderBy('fullname', 'asc')
                            ->latest()
                            ->paginate($request->get('per_page', 15))
                            ->withQueryString();

        // dd($surveys);
        /*---------------------------------------------------------------------*/
        // Verificar si la página actual es mayor que la última página disponible - si es mayor, redirigir a la última página válida, manteniendo los parámetros de búsqueda
        if ($surveys->lastPage() > 0 && $request->get('page', 1) > $surveys->lastPage()) {
            // Redirigir a la última página válida, manteniendo los parámetros de búsqueda
            return redirect($surveys->url($surveys->lastPage()));
        }
        /*---------------------------------------------------------------------*/

        return Inertia::render('Admin/Surveys/Index', [
            'surveys' => SurveyResource::collection($surveys),
            'filters' => $request->only(['search', 'per_page', 'page']), // Ejemplo de filtro por categoría
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        // Puedes pasar datos auxiliares si es necesario (por ejemplo, lista de categorías)
        $categories = Category::query()->select('id', 'name', 'status')->get();
        $selectionStrategies = LookupService::getSelectionStrategies();
        return Inertia::render('Admin/Surveys/Create', [
            'categories' => $categories,
            'selectionStrategies' => $selectionStrategies,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSurveyRequest $request): RedirectResponse
    {
        $survey = Survey::create($request->validated());

        // Opcional: Cargar relación si se necesita en la redirección
        // $survey->load('category');

        return to_route('admin.surveys.index')->with('success', 'Survey created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Survey $survey): Response
    {
        $survey->load(['category', 'characters', 'votes']); // Carga relacional según sea necesario
        return Inertia::render('Admin/Surveys/Show', [
            'survey' => SurveyShowResource::make($survey)->resolve(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Survey $survey): Response
    {
        // Puedes pasar datos auxiliares si es necesario (por ejemplo, lista de categorías)
        // $categories = Category::all();
        return Inertia::render('Admin/Surveys/Edit', [
            'survey' => SurveyResource::make($survey)->resolve(),
            // 'categories' => CategoryResource::collection($categories), // Si se necesita en edición
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSurveyRequest $request, Survey $survey): RedirectResponse
    {
        $survey->update($request->validated());

        // Opcional: Recargar relación si cambió
        // $survey->load('category');

        return to_route('admin.surveys.index')->with('success', 'Survey updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Survey $survey): RedirectResponse
    {
        $survey->delete(); // Soft Delete

        return to_route('admin.surveys.index')->with('success', 'Survey deleted successfully.');
    }
}