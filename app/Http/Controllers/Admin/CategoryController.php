<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\CharacterResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response; // Importar Response
use Inertia\InertiaResponse;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request):  Response|InertiaResponse|RedirectResponse
    {
        // dd($request->all());
        // print_r('<pre>'); print_r($request->all()); print_r('</pre>');exit;
        // Cargar categorías con paginación
        $categories = Category::query()
                            ->when(request('search'), function ($query, $search) {
                                $query->where('name', 'like', '%' . $search . '%');
                            })
                            // ->orderBy('sort_order', 'asc')
                            // ->orderBy('name', 'asc')
                            ->paginate($request->get('per_page', 10))
                            ->withQueryString();

        // $categories->appends($request->only(['search', 'per_page']));

        /*---------------------------------------------------------------------*/
        // TODO: Monitorear su funcionamiento - por ahora todo funciona correctamente
        // Verificar si la página actual es mayor que la última página disponible - si es mayor, redirigir a la última página válida, manteniendo los parámetros de búsqueda
        if ($categories->lastPage() > 0 && $request->get('page', 1) > $categories->lastPage()) {
            // Redirigir a la última página válida, manteniendo los parámetros de búsqueda
            return redirect($categories->url($categories->lastPage()));
        }
        /*---------------------------------------------------------------------*/

        // Devolver la vista Inertia con los datos
        return Inertia::render('Admin/Categories/Index', [
            'categories' => CategoryResource::collection($categories), // Pasamos la colección transformada
            'filters' => $request->only(['search', 'per_page', 'page']), // Opcional: pasar filtros para UI
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        // Devolver la vista Inertia para el formulario de creación
        return Inertia::render('Admin/Categories/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        Category::create($request->validated());

        // Redirigir al listado de categorías después de crear
        return to_route('admin.categories.index')->with('success', 'Category created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category): Response
    {
        // Carga relacional opcional si se necesita en la vista
        // $category->load(['characters', 'surveys']); // Ejemplo
        return Inertia::render('Admin/Categories/Show', [
            // 'category' => new CategoryResource($category), // Pasamos el modelo transformado
            'category' => CategoryResource::make($category)->resolve(),
            'characters' => CharacterResource::collection($category->characters)->resolve(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category): Response
    {
        // Devolver la vista Inertia para el formulario de edición
        return Inertia::render('Admin/Categories/Edit', [
            // 'category' => new CategoryResource($category), // Pasamos el modelo para pre-rellenar el form
            'category' => CategoryResource::make($category)->resolve(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        // Redirigir al listado o a la edición después de actualizar
        return to_route('admin.categories.show', $category)->with('success', 'Category updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Category $category): RedirectResponse
    {
        // dd($request->all());
        $category->delete(); // Soft Delete

        // Recoge parámetros opcionales de paginación y filtros: Solo pasa los parámetros relevantes para la lista (search, page, per_page)
        $redirectParams = $request->only(['search', 'page', 'per_page']);

        return to_route('admin.categories.index', $redirectParams)->with('success', 'Category deleted successfully.');
    }
}