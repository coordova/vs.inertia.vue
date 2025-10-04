<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCharacterRequest;
use App\Http\Requests\UpdateCharacterRequest;
use App\Http\Resources\CharacterResource;
use App\Models\Character;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;   
use Inertia\InertiaResponse;

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
        return Inertia::render('Admin/Characters/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCharacterRequest $request): RedirectResponse
    {
        Character::create($request->validated());

        return to_route('admin.characters.index')->with('success', 'Character created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Character $character): Response
    {
        return Inertia::render('Admin/Characters/Show', [
            // 'character' => new CharacterResource($character),
            'character' => CharacterResource::make($character)->resolve(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Character $character): Response
    {
        return Inertia::render('Admin/Characters/Edit', [
            // 'character' => new CharacterResource($character),
            'character' => CharacterResource::make($character)->resolve(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCharacterRequest $request, Character $character): RedirectResponse
    {
        $character->update($request->validated());

        return to_route('admin.characters.index')->with('success', 'Character updated successfully.');
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