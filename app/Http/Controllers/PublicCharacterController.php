<?php

namespace App\Http\Controllers;

use App\Http\Resources\CharacterResource;
use App\Models\Character;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\JsonResponse;

class PublicCharacterController extends Controller
{
    /**
     * Display the specified resource.
     */
    public function show(Character $character): Response
    {
        // get character with categories
        // dd($character->categories()->get());

        return Inertia::render('Public/Characters/Show', [
            // 'character' => new CharacterResource($character),
            'character' => CharacterResource::make($character)->resolve(),
            'categories' => $character->categories()->get(['id', 'name', 'slug'/* , 'category_character.elo_rating', 'category_character.status' */]), // get only name, id, and pivot elo_rating
            // 'categories' => $character->categories()->withPivot('elo_rating', 'status')->get(['id', 'name']),
        ]);
    }

    public function getAjaxCharacterInfo(Character $character)
    {
        // dd(CharacterResource::make($character)->resolve());
        return response()->json([
            'character' => CharacterResource::make($character)->resolve(),
        ]);
    }
}
