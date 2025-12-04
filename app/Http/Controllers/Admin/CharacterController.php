<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCharacterRequest;
use App\Http\Requests\UpdateCharacterRequest;
use App\Http\Resources\CharacterIndexResource;
use App\Http\Resources\CharacterResource;
use App\Models\Category;
use App\Models\Character;
use App\Services\ImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CharacterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $characters = Character::query()
            ->when(request('search'), function ($query, $search) {
                $query->where('name', 'like', '%'.$search.'%');
            })
                            // orderBy('fullname', 'asc')
            ->latest()
            ->paginate($request->get('per_page', 15))
            ->withQueryString();

        /* --------------------------------------------------------------------- */
        // TODO: Monitorear su funcionamiento - por ahora todo funciona correctamente
        // Verificar si la página actual es mayor que la última página disponible - si es mayor, redirigir a la última página válida, manteniendo los parámetros de búsqueda
        if ($characters->lastPage() > 0 && $request->get('page', 1) > $characters->lastPage()) {
            // Redirigir a la última página válida, manteniendo los parámetros de búsqueda
            return redirect($characters->url($characters->lastPage()));
        }
        /* --------------------------------------------------------------------- */

        return Inertia::render('Admin/Characters/Index', [
            'characters' => CharacterIndexResource::collection($characters),
            'filters' => $request->only(['search', 'per_page', 'page']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/Characters/Create', [
            // 'categories' => CategoryResource::collection(Category::all()),
            'categories' => Category::select('id', 'name', 'status')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCharacterRequest $request, ImageService $imageService): RedirectResponse
    {
        $validated = $request->validated();

        // Procesar imagen: canvas + thumbnail
        if ($request->hasFile('picture')) {
            $filenameBase = Str::slug($request->fullname).'-'.now()->timestamp;
            try {
                // Crea y guarda la imagen principal y el thumbnail
                $result = $imageService->makeCanvasWithThumb(
                    $request->file('picture'),  // UploadedFile
                    $filenameBase,              // nombre base (sin extensión)
                    600,                        // canvas width
                    600,                        // canvas height
                    180,                        // thumb width
                    180,                        // thumb height
                    'characters/',              // directorio principal
                    'characters/thumbs/'        // directorio thumbnails
                );

                $validated['picture'] = $result['main']; // ruta guardada de imagen principal
                $validated['picture_thumb'] = $result['thumb']; // (opcional, si tienes columna en la BD)
            } catch (\Exception $e) {
                Log::error('Error al generar la imagen/canvas: '.$e->getMessage());

                return to_route('admin.characters.index')
                    ->with('error', 'Error al procesar la imagen.');
            }
        }

        try {
            $character = Character::create($validated);
            $categoryIds = $request->input('category_ids', []);
            $character->categories()->attach($categoryIds);

            return to_route('admin.characters.show', $character)
                ->with('success', 'Character created successfully.');
        } catch (\Exception $e) {
            Log::error('Error al crear el personaje: '.$e->getMessage());

            return to_route('admin.characters.index')
                ->with('error', 'Error al crear el personaje.');
        }
    }

    public function store_original_ok_deprecated(StoreCharacterRequest $request): RedirectResponse
    {
        // 1. Obtén todos los datos que pasaron la validación.
        //    $request->validated() devuelve un array con solo los campos definidos en rules().
        $validated = $request->validated();
        // dd($validated);

        // 2. Procesa y almacena la imagen.
        //    $request->file('image') devuelve el objeto UploadedFile original.
        //    Guardamos la ruta del archivo en nuestra variable de datos validados.
        if ($request->hasFile('picture')) {
            // $validated['picture'] = $request->file('picture')->store('characters', 'public');
            // Almacenar la imagen con nombre personalizado
            $filename = Str::slug($request->fullname).'-'.now()->timestamp.'.'.$request->file('picture')->extension();
            try {
                $path = $request->file('picture')->storePubliclyAs('characters', $filename, 'public');
                $validated['picture'] = $path;
            } catch (\Exception $e) {
                // Manejar el error, por ejemplo, registrar en el log
                Log::error('Error al almacenar la imagen: '.$e->getMessage());

                // Opcional: redirigir con un mensaje de error
                return to_route('admin.characters.index')->with('error', 'Error al almacenar la imagen.');
            }
        }

        // 3. Genera el slug a partir del fullname validado.
        // $validated['slug'] = Str::slug($validated['fullname']);

        try {
            // 4. Crea el personaje con el array de datos completo y preparado.
            //    Este es un enfoque de "Mass Assignment" limpio y seguro.
            // Character::create($validated);
            $character = Character::create($validated);

            // id de las categorias elegidas del multiselect
            // 1. Obtén el array de objetos
            // $categories = $request->input('category_ids', []);
            // dd($categories);

            // 2. Extrae solo los IDs
            $categoryIds = $request->input('category_ids', []);  // collect($categories)->pluck('id')->toArray(); // [1, 2, 3]
            // dd($categoryIds);

            // 3. Relación con datos extra de la pivot
            $character->categories()->attach(
                $categoryIds/* ,
                [
                    'elo_rating' => 1400,
                    'status'     => 1,
                ] */
            );

            // 5. Redirige con un mensaje de éxito.
            return to_route('admin.characters.show', $character)->with('success', 'Character created successfully.');
        } catch (\Exception $e) {
            // Manejar el error, por ejemplo, registrar en el log
            Log::error('Error al crear el personaje: '.$e->getMessage());

            // Opcional: redirigir con un mensaje de error
            return to_route('admin.characters.index')->with('error', 'Error al crear el personaje.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Character $character): Response
    {
        // get character with categories
        // dd($character->categories()->get());

        return Inertia::render('Admin/Characters/Show', [
            // 'character' => new CharacterResource($character),
            'character' => CharacterResource::make($character)->resolve(),
            'categories' => $character->categories()->get(['id', 'name', 'slug'/* , 'category_character.elo_rating', 'category_character.status' */]), // get only name, id, and pivot elo_rating
            // 'categories' => $character->categories()->withPivot('elo_rating', 'status')->get(['id', 'name']),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Character $character): Response
    {
        // Objetos completos de las categorías que YA tiene el personaje
        $characterCategories = $character->categories()
            ->select('categories.id', 'categories.name', 'categories.status')
            ->get();                       // Collection de objetos

        // Objetos completos de las categorías disponibles
        $categories = Category::select('id', 'name', 'status')->get();

        // dd($categories, $characterCategories);

        return Inertia::render('Admin/Characters/Edit', [
            // 'character' => new CharacterResource($character),
            'character' => CharacterResource::make($character)->resolve(),
            'categories' => $categories,
            'characterCategories' => $characterCategories,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCharacterRequest $request, Character $character, ImageService $imageService): RedirectResponse
    {
        $validated = $request->validated();

        // Procesar y reemplazar imagen si se sube una nueva
        if ($request->hasFile('picture')) {
            $filenameBase = Str::slug($validated['fullname']).'-'.now()->timestamp;

            try {
                $result = $imageService->makeCanvasWithThumb(
                    $request->file('picture'),
                    $filenameBase,
                    600, 600, // canvas dims
                    180, 180  // thumb dims
                );

                // Eliminar antiguo archivo principal y thumb si existen
                if ($character->picture) {
                    Storage::disk('public')->delete($character->picture);
                }
                if ($character->picture_thumb ?? false) {
                    Storage::disk('public')->delete($character->picture_thumb);
                }

                $validated['picture'] = $result['main'];
                $validated['picture_thumb'] = $result['thumb'];
            } catch (\Exception $e) {
                Log::error('Error al procesar la imagen: '.$e->getMessage());

                return to_route('admin.characters.show', $character)
                    ->with('error', 'Error al actualizar la imagen.');
            }
        } else {
            unset($validated['picture'], $validated['picture_thumb']);
        }

        try {
            $character->update($validated);
            $categoryIds = $request->input('category_ids', []);
            $character->categories()->sync($categoryIds);

            return to_route('admin.characters.show', $character)
                ->with('success', 'Character updated successfully.');
        } catch (\Exception $e) {
            Log::error('Error al actualizar el personaje: '.$e->getMessage());

            return to_route('admin.characters.index')
                ->with('error', 'Error al actualizar el personaje.');
        }
    }

    public function update_original_ok_deprecated(UpdateCharacterRequest $request, Character $character): RedirectResponse
    {
        // La validación ya ocurrió automáticamente por el UpdateCharacterRequest, aqui solo obtenemos los datos validados
        $validated = $request->validated();

        // dd($validated);

        if ($request->hasFile('picture')) {
            // Almacenar la imagen con nombre personalizado
            $filename = Str::slug($request->fullname).'-'.now()->timestamp.'.'.$request->file('picture')->extension();
            try {
                $path = $request->file('picture')->storePubliclyAs('characters', $filename, 'public');
                $validated['picture'] = $path;
                // Elimina la imagen anterior si existe
                if ($character->picture) {
                    Storage::disk('public')->delete($character->picture);
                }
            } catch (\Exception $e) {
                // Manejar el error, por ejemplo, registrar en el log
                Log::error('Error al almacenar la imagen: '.$e->getMessage());

                // Opcional: redirigir con un mensaje de error
                return to_route('admin.characters.show', $character)->with('error', 'Error al almacenar la imagen.');
            }
        } else {
            // Si no hay archivo, no se actualiza la imagen
            // $validated['picture'] = $character->picture;
            unset($validated['picture']);
        }

        /* $character->update([
            ...$validated,
            'slug' => Str::slug($validated['fullname']),
        ]); */

        $character->update($validated);

        // Relación con datos extra de la pivot
        $character->categories()->sync($validated['category_ids']);

        // Sincronizar las categorías (attach/detach automático)
        /* $character->categories()->sync(
            $request->input('category_id', [])
        ); */

        return to_route('admin.characters.show', $character)->with('success', 'Character updated successfully.');

        // $character->update($request->validated());

        // return to_route('admin.characters.index')->with('success', 'Character updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Character $character): RedirectResponse
    {
        $character->delete(); // Soft Delete

        return to_route('admin.characters.index')->with('success', 'Character deleted successfully.');
    }

    /**
     * Get characters by category for AJAX.
     */
    public function getCharactersByCategoryAjax(Category $category)
    {
        $characters = $category
            ->characters()
            ->select('characters.id', 'characters.fullname', 'characters.status')
            ->where('characters.status', true)     // ✅ Solo personajes activos
            ->get()
            ->map(fn ($char) => [
                'value' => $char->id,
                'label' => $char->fullname,
            ]);

        return response()->json($characters)->header('X-Inertia', 'true');
    }
}
