<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSurveyRequest;
use App\Http\Requests\UpdateSurveyRequest;
use App\Http\Resources\SurveyResource;
use App\Models\Survey;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SurveyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $surveys = Survey::with(['category']) // Carga la categoría para mostrarla en la lista
                       ->orderBy('created_at', 'desc')
                       ->paginate($request->get('per_page', 15));

        // Opcional: Agregar búsqueda o filtrado por categoría
        // $categoryId = $request->get('category_id');
        // if ($categoryId) {
        //     $surveys = $surveys->where('category_id', $categoryId);
        // }

        return SurveyResource::collection($surveys);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSurveyRequest $request): JsonResponse
    {
        $survey = Survey::create($request->validated());

        // Opcional: Cargar relación para la respuesta
        $survey->load('category');

        return (new SurveyResource($survey))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Survey $survey): SurveyResource
    {
        $survey->load(['category', 'characters', 'votes']); // Carga relacional según sea necesario
        return new SurveyResource($survey);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Survey $survey)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSurveyRequest $request, Survey $survey): SurveyResource
    {
        $survey->update($request->validated());

        // Opcional: Recargar relación si cambió
        $survey->load('category');

        return new SurveyResource($survey);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Survey $survey): JsonResponse
    {
        $survey->delete(); // Soft Delete

        return response()->json(['message' => 'Survey deleted successfully'], 200);
    }
}
