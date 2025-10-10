<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Inertia\Response;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        // Cargar usuarios con paginación
        $users = User::query()
                            ->when(request('search'), function ($query, $search) {
                                $query->where('name', 'like', '%' . $search . '%');
                            })
                            // ->orderBy('sort_order', 'asc')
                            // ->orderBy('name', 'asc')
                            ->latest()
                            ->paginate($request->get('per_page', 15))
                            ->withQueryString();

        // Devolver la vista Inertia con los datos
        return Inertia::render('Admin/Users/Index', [
            'users' => UserResource::collection($users), // Pasamos la colección transformada
            'filters' => $request->only(['search', 'per_page', 'page']), // Opcional: pasar filtros para UI
        ]);
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
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $user->delete();           // soft-delete
        return back()->with('success', 'User deleted.');
    }
    
    /**
     * Restore the specified resource from storage.
     */
    public function restore(User $user)
    {
        $user->restore();          // restaurar
        return back()->with('success', 'User restored.');
    }

    /**
     * Force delete the specified resource from storage.
     */
    public function forceDelete(User $user)
    {
        $user->forceDelete();
        return back()->with('success', 'User force deleted.');
    }

    /**
     * Change status
     */
    public function changeStatus(User $user)
    {
        // $user->status = $user->status === 1 ? 0 : 1; // no funciona con Integers xq el valor definido en global.d.ts es (status: boolean;)
        $user->status = $user->status === true ? false : true;    // verificar el valor que esta definido en global.d.ts para UserResource (status: boolean;)
        $user->save();

        // return back()->with('success', 'User status changed.');
    }
}
