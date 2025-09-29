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

class CharacterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $characters = Character::orderBy('fullname', 'asc')
                              ->paginate($request->get('per_page', 15));

        // Opcional: Agregar búsqueda
        // $search = $request->get('search');
        // if ($search) {
        //     $characters = $characters->where('fullname', 'like', "%{$search}%");
        //     // o $characters->where('nickname', 'like', "%{$search}%");
        // }

        return Inertia::render('Admin/Characters/Index', [
            'characters' => CharacterResource::collection($characters),
            'filters' => $request->only(['search', 'per_page']),
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
            'character' => new CharacterResource($character),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Character $character): Response
    {
        return Inertia::render('Admin/Characters/Edit', [
            'character' => new CharacterResource($character),
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