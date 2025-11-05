<?php

namespace App\Services\Survey\CombinationSelection;

use App\Models\Survey;
use App\Models\Combinatoric;
use App\Models\User;
use InvalidArgumentException; // Excepción estándar de PHP

/**
 * Servicio coordinador para la selección de combinaciones.
 *
 * Este servicio implementa el patrón "Strategy" al delegar la selección
 * de la próxima combinación a una estrategia concreta basada en la
 * configuración de la encuesta.
 *
 * @package App\Services\Survey\CombinationSelection
 */
class CombinationSelector
{
    /**
     * Mapa de estrategias disponibles.
     * Relaciona el nombre de la estrategia (como se guarda en la BD) con su clase concreta.
     *
     * @var array<string, string>
     */
    private array $strategies = [
        'random' => RandomStrategy::class,
        'cooldown' => CooldownStrategy::class,
        // 'elo_based' => EloBasedStrategy::class, // Añadir cuando se cree
        // 'weighted' => WeightedStrategy::class, // Añadir cuando se cree
        // ... otras estrategias
    ];

    /**
     * Selecciona una combinación usando la estrategia especificada.
     *
     * Coordina la selección de combinaciones delegando a la estrategia apropiada
     * basada en la configuración de la encuesta. Si la estrategia especificada
     * no existe, se utiliza 'cooldown' como estrategia por defecto.
     *
     * @param Survey $survey La encuesta para la cual seleccionar combinación.
     * @param User $user El usuario que está votando.
     * @param string|null $strategy Nombre de la estrategia a usar (p. ej., 'cooldown', 'random'). Si es null, se usa la del modelo $survey.
     * @return Combinatoric|null La combinación seleccionada o null si no hay más combinaciones disponibles.
     */
    public function selectCombination(Survey $survey, User $user, ?string $strategy = null): ?Combinatoric
    {
        // Si no se proporciona una estrategia, usar la definida en el modelo de la encuesta
        $strategyName = $strategy ?? $survey->selection_strategy;

        // Verificar si la estrategia solicitada existe en el mapa
        $strategyClass = $this->strategies[$strategyName] ?? $this->strategies['cooldown']; // Usar 'cooldown' como fallback

        if (!$strategyClass) {
            // Si ni siquiera 'cooldown' es una estrategia válida, algo anda mal
            throw new InvalidArgumentException("Strategy '{$strategyName}' not found and no default fallback available.");
        }

        // Instanciar la estrategia concreta
        $strategyInstance = new $strategyClass();

        // Verificar que la instancia implemente la interfaz correcta
        if (!$strategyInstance instanceof CombinationSelectionStrategy) {
            throw new InvalidArgumentException("Strategy class '{$strategyClass}' must implement CombinationSelectionStrategy interface.");
        }

        // Delegar la selección a la estrategia concreta
        return $strategyInstance->selectCombination($survey, $user);
    }
}