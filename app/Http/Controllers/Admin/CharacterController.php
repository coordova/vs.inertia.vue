<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCharacterRequest;
use App\Http\Requests\UpdateCharacterRequest;
use App\Http\Resources\CharacterResource;
use App\Models\Character;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;   
use Inertia\InertiaResponse;
use Illuminate\Support\Str;

class CharacterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response|InertiaResponse|RedirectResponse
    {
        $characters = Character::query()
                            ->when(request('search'), function ($query, $search) {
                                $query->where('name', 'like', '%' . $search . '%');
                            })
                            // orderBy('fullname', 'asc')
                            ->paginate($request->get('per_page', 15))
                            ->withQueryString();

        /*---------------------------------------------------------------------*/
        // TODO: Monitorear su funcionamiento - por ahora todo funciona correctamente
        // Verificar si la página actual es mayor que la última página disponible - si es mayor, redirigir a la última página válida, manteniendo los parámetros de búsqueda
        if ($characters->lastPage() > 0 && $request->get('page', 1) > $characters->lastPage()) {
            // Redirigir a la última página válida, manteniendo los parámetros de búsqueda
            return redirect($characters->url($characters->lastPage()));
        }
        /*---------------------------------------------------------------------*/

        return Inertia::render('Admin/Characters/Index', [
            'characters' => CharacterResource::collection($characters),
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
    public function store(StoreCharacterRequest $request): RedirectResponse
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
                \Log::error('Error al almacenar la imagen: '.$e->getMessage());

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
            \Log::error('Error al crear el personaje: '.$e->getMessage());

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
            'categories'           => $categories,
            'characterCategories'  => $characterCategories,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCharacterRequest $request, Character $character): RedirectResponse
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
                    \Storage::disk('public')->delete($character->picture);
                }
            } catch (\Exception $e) {
                // Manejar el error, por ejemplo, registrar en el log
                \Log::error('Error al almacenar la imagen: '.$e->getMessage());

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
}