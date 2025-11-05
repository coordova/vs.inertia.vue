<?php

namespace App\Services\Survey\CombinationSelection;

use App\Models\Survey;
use App\Models\Combinatoric;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Estrategia de selección de combinaciones basada en "cooldown".
 *
 * Selecciona la combinación que lleva más tiempo sin usarse (o que nunca se ha usado).
 * Esto ayuda a mostrar todas las combinaciones de forma más equilibrada y evitar
 * que ciertos enfrentamientos se repitan con mucha frecuencia.
 * Es ideal para encuestas donde se quiere cubrir todas las posibles comparaciones.
 *
 * @package App\Services\Survey\CombinationSelection
 */
class CooldownStrategy implements CombinationSelectionStrategy
{
    /**
     * Selecciona la combinación menos usada recientemente (o no usada) para el usuario en la encuesta.
     *
     * @param Survey $survey La encuesta activa.
     * @param User $user El usuario que está votando.
     * @return Combinatoric|null La combinación seleccionada o null si no hay más combinaciones disponibles.
     */
    public function selectCombination(Survey $survey, User $user): ?Combinatoric
    {
        $userId = $user->id;

        // Cargar combinaciones activas para la encuesta que el usuario NO HA VOTADO
        // Ordenarlas por 'last_used_at' ascendente (más antiguo o null primero)
        // Usamos COALESCE para manejar valores NULL de 'last_used_at' como una fecha muy antigua
        $availableCombination = $survey->combinatorics()
            ->where('status', true) // Solo combinaciones activas
            ->whereDoesntHave('votes', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->with(['character1', 'character2']) // Cargar los personajes de la combinación
            ->orderByRaw('COALESCE(last_used_at, "1970-01-01 00:00:00") ASC') // Ordenar por uso menos reciente
            ->first(); // Obtener la combinación menos usada recientemente

        return $availableCombination; // Devuelve el modelo Combinatoric con character1 y character2 cargados
    }
}