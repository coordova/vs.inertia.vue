<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSurveyRequest;
use App\Http\Requests\UpdateSurveyRequest;
use App\Http\Resources\SurveyResource;
use App\Models\Survey;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SurveyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        // Carga la categoría para mostrarla en la lista
        $surveys = Survey::with(['category'])
                       ->orderBy('created_at', 'desc')
                       ->paginate($request->get('per_page', 15));

        // Opcional: Agregar búsqueda o filtrado por categoría
        // $categoryId = $request->get('category_id');
        // if ($categoryId) {
        //     $surveys = $surveys->where('category_id', $categoryId);
        // }

        return Inertia::render('Admin/Surveys/Index', [
            'surveys' => SurveyResource::collection($surveys),
            'filters' => $request->only(['search', 'category_id', 'per_page']), // Ejemplo de filtro por categoría
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        // Puedes pasar datos auxiliares si es necesario (por ejemplo, lista de categorías)
        // $categories = Category::all(); // Asumiendo un modelo Category
        return Inertia::render('Admin/Surveys/Create');
        // O si necesitas categorías:
        // return Inertia::render('Admin/Surveys/Create', ['categories' => CategoryResource::collection($categories)]);
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
            'survey' => new SurveyResource($survey),
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
            'survey' => new SurveyResource($survey),
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