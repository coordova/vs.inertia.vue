<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCharacterRequest;
use App\Http\Requests\UpdateCharacterRequest;
use App\Http\Resources\CharacterResource;
use App\Models\Character;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CharacterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $characters = Character::orderBy('fullname', 'asc')
                              ->paginate($request->get('per_page', 15));

        // Opcional: Agregar búsqueda
        // $search = $request->get('search');
        // if ($search) {
        //     $characters = $characters->where('fullname', 'like', "%{$search}%");
        //     // o $characters->where('nickname', 'like', "%{$search}%");
        // }

        return CharacterResource::collection($characters);
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
    public function store(StoreCharacterRequest $request): JsonResponse
    {
        $character = Character::create($request->validated());

        return (new CharacterResource($character))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Character $character): CharacterResource
    {
        return new CharacterResource($character);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Character $character)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCharacterRequest $request, Character $character): CharacterResource
    {
        $character->update($request->validated());

        return new CharacterResource($character);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Character $character): JsonResponse
    {
        $character->delete(); // Soft Delete

        return response()->json(['message' => 'Character deleted successfully'], 200);
    }
}
