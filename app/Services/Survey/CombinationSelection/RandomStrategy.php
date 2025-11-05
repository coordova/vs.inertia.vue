<?php

namespace App\Services\Survey\CombinationSelection;

use App\Models\Survey;
use App\Models\Combinatoric;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Estrategia de selección de combinaciones aleatoria.
 *
 * Selecciona una combinación aleatoria entre las disponibles para el usuario,
 * es decir, aquellas que el usuario aún no ha votado en la encuesta.
 * Esta estrategia es simple pero puede llevar a repeticiones frecuentes
 * si el número de combinaciones es grande y el número de votos es bajo.
 *
 * @package App\Services\Survey\CombinationSelection
 */
class RandomStrategy implements CombinationSelectionStrategy
{
    /**
     * Selecciona una combinación aleatoria para el usuario en la encuesta.
     *
     * @param Survey $survey La encuesta activa.
     * @param User $user El usuario que está votando.
     * @return Combinatoric|null La combinación seleccionada o null si no hay más combinaciones disponibles.
     */
    public function selectCombination(Survey $survey, User $user): ?Combinatoric
    {
        $userId = $user->id;

        // Cargar combinaciones activas para la encuesta que el usuario NO HA VOTADO
        // Usamos 'whereDoesntHave' para excluir combinaciones con votos del usuario actual
        $availableCombination = $survey->combinatorics()
            ->where('status', true) // Solo combinaciones activas
            ->whereDoesntHave('votes', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->with(['character1', 'character2']) // Cargar los personajes de la combinación
            ->inRandomOrder() // Seleccionar aleatoriamente
            ->first(); // Obtener la primera (y única esperada) combinación seleccionada

        return $availableCombination;
    }
}