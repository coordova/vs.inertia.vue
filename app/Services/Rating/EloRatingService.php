<?php

namespace App\Services\Rating;

use App\Models\CategoryCharacter; // Modelo pivote
use Illuminate\Support\Collection; // Importar Collection
use Illuminate\Support\Facades\DB; // Para transacciones si es necesario

class EloRatingService
{
    private float $K_FACTOR_DEFAULT = 32.0; // Constante K estándar, se podría hacer configurable
    private float $K_FACTOR_NEW_PLAYER = 40.0; // K factor inicial para nuevos jugadores, opcional
    private int $MATCHES_FOR_K_FACTOR_REDUCTION = 30; // Número de partidas para reducir K

    /**
     * Calcula los nuevos ratings ELO para dos personajes basado en el resultado de un enfrentamiento.
     *
     * @param float $rating1 Rating ELO actual del personaje 1.
     * @param float $rating2 Rating ELO actual del personaje 2.
     * @param string $result Resultado: 'win' (personaje 1 gana), 'loss' (personaje 1 pierde), 'draw' (empate).
     * @param float $tieWeight Peso del empate (0.5 para empate estándar, configurable).
     * @return array [newRating1, newRating2]
     */
    public function calculateNewRatings(float $rating1, float $rating2, string $result, float $tieWeight = 0.5): array
    {
        $expected1 = $this->calculateExpectedScore($rating2, $rating1);
        $expected2 = $this->calculateExpectedScore($rating1, $rating2);

        $score1 = match ($result) {
            'win' => 1.0,
            'loss' => 0.0,
            'draw' => $tieWeight, // Permitir peso configurable para empates
            default => 0.5, // Valor por defecto, aunque debería validarse antes
        };
        $score2 = 1 - $score1; // Si 1 gana (score1=1), 2 pierde (score2=0). Si empate (score1=0.5), score2=0.5.

        // Obtener K factor (podría depender del número de partidas o ser fijo)
        $k1 = $this->getKFactorForCharacter($rating1); // TODO: Implementar lógica de K factor dinámico si es necesario
        $k2 = $this->getKFactorForCharacter($rating2);

        $newRating1 = $rating1 + $k1 * ($score1 - $expected1);
        $newRating2 = $rating2 + $k2 * ($score2 - $expected2);

        // Asegurar que los ratings no sean negativos
        $newRating1 = max(0, $newRating1);
        $newRating2 = max(0, $newRating2);

        return [$newRating1, $newRating2];
    }

    /**
     * Aplica los nuevos ratings calculados a los registros CategoryCharacter.
     * Actualiza también estadísticas como partidas jugadas, victorias, derrotas, win rate, etc.
     *
     * @param int $categoryId ID de la categoría.
     * @param int $character1Id ID del personaje 1.
     * @param int $character2Id ID del personaje 2.
     * @param float $newRating1 Nuevo rating para personaje 1.
     * @param float $newRating2 Nuevo rating para personaje 2.
     * @param string $result Resultado del enfrentamiento ('win', 'loss', 'draw').
     * @return void
     */
    public function applyRatings(int $categoryId, int $character1Id, int $character2Id, float $newRating1, float $newRating2, string $result): void
    {
        DB::transaction(function () use ($categoryId, $character1Id, $character2Id, $newRating1, $newRating2, $result) {
            // --- Mejora 1: Cargar ambos pivotes en una sola consulta ---
            $characterIds = [$character1Id, $character2Id];
            // Usamos with(['category', 'character']) si necesitáramos relaciones, pero aquí solo accedemos a los campos pivote.
            $pivotsCollection = CategoryCharacter::where('category_id', $categoryId)
                                                 ->whereIn('character_id', $characterIds)
                                                 // ->lockForUpdate() // Opcional: Descomentar si se anticipa alta concurrencia para prevenir race conditions
                                                 ->get()
                                                 ->keyBy('character_id'); // Clave: character_id, Valor: instancia de CategoryCharacter

            // Verificar que se encontraron ambos registros
            if ($pivotsCollection->count() !== 2) {
                // Esto podría suceder si uno de los personajes no está registrado en la categoría
                throw new \Exception("Ratings not found for one or both characters ({$character1Id}, {$character2Id}) in category {$categoryId}.");
            }

            // Obtener los objetos pivote usando la colección
            $pivot1 = $pivotsCollection[$character1Id];
            $pivot2 = $pivotsCollection[$character2Id];

            // --- Actualizar en memoria ---
            // Actualizar personaje 1
            $pivot1->elo_rating = $newRating1;
            $pivot1->matches_played += 1;
            if ($result === 'win') {
                $pivot1->wins += 1;
            } elseif ($result === 'loss') {
                $pivot1->losses += 1;
            }
            $pivot1->win_rate = $pivot1->matches_played > 0 ? ($pivot1->wins / $pivot1->matches_played) * 100 : 0.00;
            $pivot1->highest_rating = max($pivot1->highest_rating, $newRating1);
            $pivot1->lowest_rating = min($pivot1->lowest_rating, $newRating1);
            $pivot1->last_match_at = now();

            // Actualizar personaje 2
            $pivot2->elo_rating = $newRating2;
            $pivot2->matches_played += 1;
            if ($result === 'loss') { // Si 1 ganó, 2 perdió
                $pivot2->wins += 1;
            } elseif ($result === 'win') { // Si 1 perdió, 2 ganó
                $pivot2->losses += 1;
            }
            $pivot2->win_rate = $pivot2->matches_played > 0 ? ($pivot2->wins / $pivot2->matches_played) * 100 : 0.00;
            $pivot2->highest_rating = max($pivot2->highest_rating, $newRating2);
            $pivot2->lowest_rating = min($pivot2->lowest_rating, $newRating2);
            $pivot2->last_match_at = now();

            // --- Guardar ambos cambios ---
            $pivot1->save();
            $pivot2->save();
        });
    }

    /**
     * Calcula la puntuación esperada para un jugador basado en la diferencia de rating.
     *
     * @param float $opponentRating Rating del oponente.
     * @param float $playerRating Rating del jugador.
     * @return float Puntuación esperada (0.0 a 1.0).
     */
    private function calculateExpectedScore(float $opponentRating, float $playerRating): float
    {
        return 1.0 / (1.0 + pow(10, ($opponentRating - $playerRating) / 400.0));
    }

    /**
     * Determina el K factor para un personaje (podría ser dinámico basado en partidas jugadas).
     * Por ahora, devuelve un valor fijo.
     *
     * @param float $currentRating El rating actual del personaje (podría usarse para lógica futura).
     * @return float El K factor a usar.
     */
    private function getKFactorForCharacter(float $currentRating): float
    {
        // TODO: Implementar lógica de K factor dinámico (ej: K=40 para primeras X partidas, K=32 después)
        // Por ahora, usar K factor estándar
        return $this->K_FACTOR_DEFAULT;
    }
}