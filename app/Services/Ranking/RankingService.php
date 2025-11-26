<?php

namespace App\Services\Ranking;

use App\Models\Category;
use App\Models\CategoryCharacter; // Modelo pivote
use App\Models\Character;
use App\Models\Survey;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RankingService
{
    // Constantes para configuración
    const MIN_MATCHES_FOR_RANKING = 1; // Mínimo de partidas para aparecer en ranking
    const DEFAULT_PAGE_SIZE = 50; // Tamaño de página por defecto
    const MAX_PAGE_SIZE = 100; // Máximo tamaño de página

    /**
     * Obtiene el ranking de personajes para una categoría específica.
     * Calcula el ranking basado en elo_rating (u otra métrica) y estadísticas de la tabla pivote category_character.
     *
     * @param Category $category La categoría para la cual calcular el ranking.
     * @param array $filters Filtros opcionales (search, sort, page, per_page).
     * @return \Illuminate\Pagination\LengthAwarePaginator La colección paginada de personajes con sus estadísticas de ranking.
     */
    public function getCategoryRanking(Category $category, array $filters = [])
    {
        $query = CategoryCharacter::where('category_id', $category->id)
                                 ->with(['character:id,fullname,nickname,picture']) // Cargar datos básicos del personaje
                                 ->where('status', true); // Solo personajes activos en la categoría

        // --- Aplicar Filtros Opcionales ---
        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->whereHas('character', function ($subQuery) use ($searchTerm) {
                $subQuery->where('fullname', 'like', "%{$searchTerm}%")
                         ->orWhere('nickname', 'like', "%{$searchTerm}%");
            });
        }

        // --- Definir Criterio de Ordenamiento ---
        $sortBy = $filters['sort'] ?? 'elo_rating'; // Campo por defecto
        $sortDirection = strtolower($filters['direction'] ?? 'desc'); // Dirección por defecto

        // Validar campos de ordenamiento si es necesario
        $allowedSortFields = ['elo_rating', 'matches_played', 'wins', 'losses', 'win_rate', 'highest_rating', 'lowest_rating'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'elo_rating'; // Volver a valor por defecto si no es válido
        }

        // Asegurar que la dirección sea 'asc' o 'desc'
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc'; // Volver a valor por defecto
        }

        $query->orderBy($sortBy, $sortDirection)
              ->orderBy('character_id', 'asc'); // Orden secundario para desempatar


        // --- Paginación ---
        $perPage = min((int) ($filters['per_page'] ?? self::DEFAULT_PAGE_SIZE), self::MAX_PAGE_SIZE);

        // Cargar los resultados paginados
        $paginatedRanking = $query->paginate($perPage)->withQueryString();

        // --- Procesar la colección paginada para incluir la posición ---
        // La paginación no incluye automáticamente la posición global.
        // La posición debe calcularse considerando la página actual.
        $currentPage = $paginatedRanking->currentPage();
        $perPageValue = $paginatedRanking->perPage();
        $startPosition = ($currentPage - 1) * $perPageValue + 1;

        $rankingWithPositions = $paginatedRanking->getCollection()->map(function ($pivot, $index) use ($startPosition) {
            // Añadir la posición al objeto pivote
            $pivot->setAttribute('position', $startPosition + $index);
            return $pivot;
        });

        // Volver a colocar la colección modificada en el objeto Paginator
        $paginatedRanking->setCollection($rankingWithPositions);
        dd($paginatedRanking);

        return $rankingWithPositions; // Devolver la colección paginada con posiciones
    }

    /**
     * Obtiene el ranking de personajes para una encuesta específica.
     * Calcula el ranking basado en estadísticas de la tabla pivote character_survey.
     *
     * @param Survey $survey La encuesta para la cual calcular el ranking.
     * @param array $filters Filtros opcionales (search, sort, page, per_page).
     * @return \Illuminate\Pagination\LengthAwarePaginator La colección paginada de personajes con sus estadísticas de ranking en la encuesta.
     */
    public function getSurveyRanking(Survey $survey, array $filters = [])
    {
        // Similar a getCategoryRanking, pero usando character_survey
        $query = $survey->characters() // Relación muchos a muchos
                       ->wherePivot('is_active', true) // Solo personajes activos en la encuesta
                       ->withPivot(['survey_matches', 'survey_wins', 'survey_losses', 'survey_ties']) // Cargar estadísticas específicas de la encuesta
                       ->join('character_survey', 'characters.id', '=', 'character_survey.character_id') // Join con la tabla pivote
                       ->select('characters.*', 'character_survey.*') // Seleccionar campos del personaje y del pivote
                       ->where('character_survey.survey_id', $survey->id); // Asegurar que el join sea correcto

        // Aplicar filtros (search, etc.)
        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where('characters.fullname', 'like', "%{$searchTerm}%")
                  ->orWhere('characters.nickname', 'like', "%{$searchTerm}%");
        }

        // Definir criterio de ordenamiento (p. ej., survey_wins, survey_ties, survey_losses, survey_matches)
        $sortBy = $filters['sort'] ?? 'survey_wins'; // Campo por defecto
        $sortDirection = strtolower($filters['direction'] ?? 'desc'); // Dirección por defecto

        $allowedSortFields = ['survey_wins', 'survey_losses', 'survey_ties', 'survey_matches'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'survey_wins';
        }
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        $query->orderBy($pivotField, $sortDirection) // <-- ORDENAR POR CAMPO DEL PIVOTE
              ->orderBy('characters.id', 'asc'); // Orden secundario


        // Paginación
        $perPage = min((int) ($filters['per_page'] ?? self::DEFAULT_PAGE_SIZE), self::MAX_PAGE_SIZE);
        $paginatedRanking = $query->paginate($perPage)->withQueryString();

        // Calcular posición (similar a getCategoryRanking)
        $currentPage = $paginatedRanking->currentPage();
        $perPageValue = $paginatedRanking->perPage();
        $startPosition = ($currentPage - 1) * $perPageValue + 1;

        $rankingWithPositions = $paginatedRanking->getCollection()->map(function ($character) use ($startPosition, &$index) {
            // Asumiendo que $character es un modelo Character con atributos pivot
            // Añadir la posición al modelo
            $character->setAttribute('survey_position', $startPosition + ($index++)); // Aumentar índice
            return $character;
        });

        $paginatedRanking->setCollection($rankingWithPositions);

        return $rankingWithPositions;
    }

    // Otros métodos de ranking pueden añadirse aquí
    // getGlobalRanking(), getCharacterHistory($characterId), etc.
}