<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response; // Importar Response

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        // Cargar categorías con paginación
        $categories = Category::when(request('search'), function ($query, $search) {
                                $query->where('name', 'like', '%' . $search . '%');
                            })
                            ->orderBy('sort_order', 'asc')
                            ->orderBy('name', 'asc')
                            ->paginate($request->get('per_page', 15));

        // Opcional: Agregar búsqueda
        // $search = $request->get('search');
        // if ($search) {
        //     $categories = $categories->where('name', 'like', "%{$search}%");
        // }

        // Devolver la vista Inertia con los datos
        return Inertia::render('Admin/Categories/Index', [
            'categories' => CategoryResource::collection($categories), // Pasamos la colección transformada
            'filters' => $request->only(['search', 'per_page']), // Opcional: pasar filtros para UI
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
            'category' => new CategoryResource($category), // Pasamos el modelo transformado
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category): Response
    {
        // Devolver la vista Inertia para el formulario de edición
        return Inertia::render('Admin/Categories/Edit', [
            'category' => new CategoryResource($category), // Pasamos el modelo para pre-rellenar el form
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        // Redirigir al listado o a la edición después de actualizar
        return to_route('admin.categories.index')->with('success', 'Category updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category): RedirectResponse
    {
        $category->delete(); // Soft Delete

        return to_route('admin.categories.index')->with('success', 'Category deleted successfully.');
    }
}