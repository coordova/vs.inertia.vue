<?php

namespace App\Services\Rating;

use App\Models\CategoryCharacter; // Modelo pivote
use Illuminate\Support\Collection; // Importar Collection
use Illuminate\Support\Facades\DB; // Para transacciones y consultas directas
use Illuminate\Support\Facades\Log; // Para registro de errores
use Illuminate\Database\QueryException; // Para manejar errores de consulta específicos

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
     * Optimizado para tablas pivote con clave primaria compuesta usando DB::table.
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
            // --- Cargar ratings ELO actuales de los personajes en la categoría de la encuesta ---
            // Usamos una sola consulta para obtener ambos ratings
            $characterIds = [$character1Id, $character2Id];
            // Cargar los datos actuales de ambos registros pivote
            $currentRatingsCollection = CategoryCharacter::where('category_id', $categoryId)
                                                        ->whereIn('character_id', $characterIds)
                                                        ->get()
                                                        ->keyBy('character_id'); // Clave: character_id, Valor: instancia de CategoryCharacter

            // Verificar que se encontraron ambos registros
            if ($currentRatingsCollection->count() !== 2) {
                // Esto podría suceder si uno de los personajes no está registrado en la categoría
                // Registrar error interno crítico
                Log::critical("EloRatingService: Ratings not found for one or both characters ({$character1Id}, {$character2Id}) in category {$categoryId} during applyRatings.");
                // Lanzar una excepción específica para que el controlador la capture
                throw new \Exception("Ratings not found for one or both characters ({$character1Id}, {$character2Id}) in category {$categoryId}.");
            }

            // Obtener los objetos pivote usando la colección
            $pivot1 = $currentRatingsCollection[$character1Id];
            $pivot2 = $currentRatingsCollection[$character2Id];

            // --- Calcular nuevas estadísticas para character1 ---
            $newMatchesPlayed1 = $pivot1->matches_played + 1;
            $newWins1 = $pivot1->wins;
            $newLosses1 = $pivot1->losses;
            $newTies1 = $pivot1->ties; // Usar la nueva columna

            if ($result === 'win') {
                $newWins1 += 1;
            } elseif ($result === 'loss') {
                $newLosses1 += 1;
            } elseif ($result === 'draw') {
                $newTies1 += 1;
            }

            $newWinRate1 = 0.0;
            if ($newMatchesPlayed1 > 0) {
                $newWinRate1 = ($newWins1 / $newMatchesPlayed1) * 100;
            }
            $newHighestRating1 = max($pivot1->highest_rating, $newRating1);
            $newLowestRating1 = min($pivot1->lowest_rating, $newRating1);
            $newLastMatchAt1 = now();

            // --- Calcular nuevas estadísticas para character2 ---
            $newMatchesPlayed2 = $pivot2->matches_played + 1;
            $newWins2 = $pivot2->wins;
            $newLosses2 = $pivot2->losses;
            $newTies2 = $pivot2->ties; // Usar la nueva columna

            // Invertir lógica para character2
            if ($result === 'loss') { // Si char1 ganó, char2 perdió
                $newWins2 += 1;
            } elseif ($result === 'win') { // Si char1 perdió, char2 ganó
                $newLosses2 += 1;
            } elseif ($result === 'draw') {
                $newTies2 += 1;
            }

            $newWinRate2 = 0.0;
            if ($newMatchesPlayed2 > 0) {
                $newWinRate2 = ($newWins2 / $newMatchesPlayed2) * 100;
            }
            $newHighestRating2 = max($pivot2->highest_rating, $newRating2);
            $newLowestRating2 = min($pivot2->lowest_rating, $newRating2);
            $newLastMatchAt2 = now();


            // --- Aplicar los nuevos ratings y estadísticas a character1 en `category_character` ---
            // Usar DB::table()->where()->update() para evitar problemas con claves compuestas
            $updatedRows1 = DB::table('category_character')
                ->where('category_id', $categoryId)
                ->where('character_id', $character1Id)
                ->update([
                    'elo_rating' => $newRating1,
                    'matches_played' => $newMatchesPlayed1,
                    'wins' => $newWins1,
                    'losses' => $newLosses1,
                    'ties' => $newTies1, // Actualizar la nueva columna
                    'win_rate' => $newWinRate1,
                    'highest_rating' => $newHighestRating1,
                    'lowest_rating' => $newLowestRating1,
                    'last_match_at' => $newLastMatchAt1,
                    'updated_at' => now(), // Actualizar updated_at
                ]);

            // Verificar que se actualizó exactamente una fila
            if ($updatedRows1 !== 1) {
                Log::error("EloRatingService: Expected to update 1 row for character {$character1Id} in category {$categoryId}, but updated {$updatedRows1} rows.");
                throw new \Exception("Failed to update rating for character {$character1Id} in category {$categoryId}.");
            }

            // --- Aplicar los nuevos ratings y estadísticas a character2 en `category_character` ---
            // Usar DB::table()->where()->update() para evitar problemas con claves compuestas
            $updatedRows2 = DB::table('category_character')
                ->where('category_id', $categoryId)
                ->where('character_id', $character2Id)
                ->update([
                    'elo_rating' => $newRating2,
                    'matches_played' => $newMatchesPlayed2,
                    'wins' => $newWins2,
                    'losses' => $newLosses2,
                    'ties' => $newTies2, // Actualizar la nueva columna
                    'win_rate' => $newWinRate2,
                    'highest_rating' => $newHighestRating2,
                    'lowest_rating' => $newLowestRating2,
                    'last_match_at' => $newLastMatchAt2,
                    'updated_at' => now(), // Actualizar updated_at
                ]);

            // Verificar que se actualizó exactamente una fila
            if ($updatedRows2 !== 1) {
                Log::error("EloRatingService: Expected to update 1 row for character {$character2Id} in category {$categoryId}, but updated {$updatedRows2} rows.");
                throw new \Exception("Failed to update rating for character {$character2Id} in category {$categoryId}.");
            }

        }); // --- Fin de la transacción DB::transaction ---
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