<?php

namespace App\Services\Survey\CombinationSelection;

use App\Models\Survey;
use App\Models\Combinatoric;
use App\Models\User; // Asumiendo que se pasa el modelo User

/**
 * Interfaz para las estrategias de selección de combinaciones.
 *
 * Este patrón permite cambiar dinámicamente el algoritmo de selección
 * de parejas de personajes para una encuesta específica.
 *
 * @package App\Services\Survey\CombinationSelection
 */
interface CombinationSelectionStrategy
{
    /**
     * Selecciona la próxima combinación de personajes para mostrar al usuario.
     *
     * @param Survey $survey La encuesta activa.
     * @param User $user El usuario que está votando.
     * @return Combinatoric|null La combinación seleccionada o null si no hay más combinaciones disponibles.
     */
    public function selectCombination(Survey $survey, User $user): ?Combinatoric;
}