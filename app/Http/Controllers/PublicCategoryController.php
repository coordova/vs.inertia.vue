<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource; // Asumiendo que existe y es adecuado para 'show'
use App\Http\Resources\CategoryIndexResource; // Nuevo recurso para 'index'
use App\Http\Resources\CharacterResource;
use App\Models\Category;
use App\Models\Character; // Modelo para contar personajes
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     * Lista pública de categorías con conteo de personajes asociados.
     * Optimizado para evitar N+1.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        // Cargar categorías activas con conteo de personajes asociados
        // Usamos withCount para obtener el número de personajes eficientemente en una sola consulta
        $categories = Category::query()
                            ->where('status', true) // Solo categorías activas
                            ->withCount(['characters' => function ($query) { // Contar personajes en la categoría
                                $query->wherePivot('status', true); // Opcional: Contar solo personajes activos en la categoría (category_character)
                            }])
                            ->withCount('surveys')
                            // ->orderBy('sort_order', 'asc') // Ordenar por orden de clasificación
                            // ->orderBy('name', 'asc')       // Luego por nombre
                            ->paginate($request->get('per_page', 15))
                            ->withQueryString(); // Mantener parámetros de consulta (search, per_page, etc.)
                             
        // dump($categories);
        // dd(CategoryIndexResource::collection($categories));
        // Renderizar la vista Inertia con el recurso específico para la lista
        return Inertia::render('Public/Categories/Index', [
            // 'categories' => CategoryIndexResource::collection($categories)->resolve(), // Usar el recurso para la lista
            'categories' => CategoryIndexResource::collection($categories), // Usar el recurso para la lista
            'filters' => $request->only(['search', 'per_page', 'page']), // Pasar filtros si se usan en la UI
        ]);
    }

    /**
     * Display the specified resource.
     * Vista pública de detalle de una categoría específica.
     * Muestra información de la categoría y una lista de personajes asociados.
     * Optimizado para evitar N+1.
     *
     * @param Category $category El modelo de categoría, inyectado por route model binding.
     * @return Response
     */
    public function show(Category $category): Response
    {
        // Verificar autenticación (si es requerida para ver detalles)
        /* $user = Auth::user();
        if (!$user) {
            // Opcional: abort(401) o permitir ver detalles públicos sin login
            // abort(401, 'Authentication required to view category details.');
        } */

        // Verificar si la categoría está activa
        if (!$category->status) {
            abort(404, 'Category not found or not active.');
        }

        // Cargar datos de la categoría
        // Cargar la categoría con sus datos y la relación 'characters' (con datos pivote y datos del personaje)
        // Usamos with() para cargar la relación y withPivot() para campos específicos del pivote
        // Aplicamos un límite de 100 personajes por ejemplo, o paginación si hay muchos
        $category->loadMissing(['characters' => function ($query) {
            $query->wherePivot('status', true) // Cargar solo personajes activos en la categoría
                  ->withPivot(['elo_rating', 'matches_played', 'wins', 'losses', 'ties', 'win_rate', 'highest_rating', 'lowest_rating', 'last_match_at', 'is_featured', 'sort_order']) // Cargar campos pivote relevantes
                  ->orderByPivot('sort_order', 'asc') // Ordenar por sort_order del pivote
                  ->orderBy('fullname', 'asc')       // Luego por nombre del personaje
                  ->limit(100); // Limitar resultados para evitar sobrecarga si hay muchos personajes
        }]);

        /* $category->loadMissing([
            'characters:id,fullname,nickname,slug,picture,status,created_at,updated_at' // Cargar solo campos necesarios de los personajes
            // ->wherePivot('status', true) // <-- Opcional: Filtrar aquí también en el controlador
        ]); */


        // Contar el número total de personajes activos en la categoría (para UI)
        $activeCharacterCount = $category->characters()->wherePivot('status', true)->count();

        // Renderizar la vista Inertia con el recurso específico para el detalle
        return Inertia::render('Public/Categories/Show', [
            'category' => CategoryResource::make($category->load('characters'))->resolve(), // Usar el recurso para el detalle de la categoría
            'characters' => CharacterResource::collection($category->characters)->resolve(), // <-- Pasar la colección de personajes como prop separada
            'activeCharacterCount' => $activeCharacterCount, // <-- Pasar el conteo a la UI
        ]);
    }
}