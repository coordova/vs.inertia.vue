<?php

namespace App\Services\Ranking;

use App\Models\Category;
use App\Models\CategoryCharacter; // Modelo pivote
use App\Models\CharacterSurvey; // Modelo pivote
use App\Models\Character;
use App\Models\Survey;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator; // Importar Paginator

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
                                 ->with(['character:id,fullname,nickname,picture,slug']) // Cargar datos básicos del personaje
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

        // Devolver el objeto Paginator *con* la colección modificada
        // Inertia lo entenderá y lo serializará como { data: [...], meta: {...}, links: [...] }
        return $paginatedRanking; // <-- Devolver el objeto Paginator
    }

    /**
     * Obtiene el ranking de personajes para una encuesta específica.
     * Calcula el ranking basado en elo_rating en category_character para la categoría de la encuesta.
     * Y estadísticas de la tabla pivote character_survey.
     * Utiliza Query Builder directamente para mayor eficiencia y control sobre la consulta.
     *
     * @param Survey $survey La encuesta para la cual calcular el ranking.
     * @param array $filters Filtros opcionales (search, sort, page, per_page).
     * @return LengthAwarePaginator La colección paginada de objetos CharacterSurvey (con relación character y ELO de la categoría, y posición calculada).
     */
    public function getSurveyRanking(Survey $survey, array $filters = []): LengthAwarePaginator
    {
        $categoryId = $survey->category_id;

        // Usar CharacterSurvey como punto de partida para obtener los personajes de la encuesta
        // y sus estadísticas específicas de encuesta.
        // Luego JOIN con category_character para obtener el ELO en la categoría de la encuesta.
        $query = CharacterSurvey::where('survey_id', $survey->id)
                               ->join('category_character', function ($join) use ($categoryId) {
                                   $join->on('character_survey.character_id', '=', 'category_character.character_id')
                                        ->where('category_character.category_id', $categoryId); // Filtrar por la categoría de la encuesta
                               })
                               ->join('characters', 'character_survey.character_id', '=', 'characters.id') // JOIN con characters para obtener fullname, etc.
                               ->select(
                                   'character_survey.*', // Todos los campos de character_survey
                                   'category_character.elo_rating', // El rating ELO del personaje en la categoría de la encuesta
                                   'category_character.matches_played', // Opcional: Mostrar matches totales en la categoría
                                   'category_character.wins', // Opcional: Mostrar wins totales en la categoría
                                   'category_character.losses', // Opcional: Mostrar losses totales en la categoría
                                   'category_character.ties', // Opcional: Mostrar ties totales en la categoría
                                   'category_character.win_rate', // Opcional: Mostrar win rate total en la categoría
                                   'characters.fullname', // Datos del personaje
                                   'characters.nickname',
                                   'characters.picture',
                                   'characters.slug'
                                   // Añadir otros campos de 'characters' si se necesitan en la UI
                               )
                               ->where('character_survey.is_active', true); // Solo personajes activos en la encuesta

        // --- Aplicar Filtros Opcionales ---
        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where(function ($subQuery) use ($searchTerm) {
                $subQuery->where('characters.fullname', 'like', "%{$searchTerm}%")
                         ->orWhere('characters.nickname', 'like', "%{$searchTerm}%");
            });
        }

        // --- Definir Criterio de Ordenamiento ---
        // El ranking principal será por elo_rating en la categoría
        $sortBy = $filters['sort'] ?? 'elo_rating'; // Campo por defecto para ordenar
        $sortDirection = strtolower($filters['direction'] ?? 'desc'); // Dirección por defecto

        // Validar campos de ordenamiento si es necesario
        // Incluir elo_rating de category_character y campos de character_survey
        $allowedSortFields = ['elo_rating', 'survey_wins', 'survey_losses', 'survey_ties', 'survey_matches'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'elo_rating'; // Volver a ELO como valor por defecto
        }

        // Asegurar que la dirección sea 'asc' o 'desc'
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc'; // Volver a valor por defecto
        }

        // Ordenar por elo_rating (o campo elegido) de la categoría, descendente por defecto (mayor ELO primero)
        // y luego por character_id para desempatar consistentemente
        $query->orderBy($sortBy, $sortDirection)
              ->orderBy('character_survey.character_id', 'asc'); // Orden secundario para desempatar si los campos de orden son iguales


        // --- Paginación ---
        $perPage = min((int) ($filters['per_page'] ?? self::DEFAULT_PAGE_SIZE), self::MAX_PAGE_SIZE);

        // Cargar los resultados paginados
        $paginatedRanking = $query->paginate($perPage)->withQueryString();

        // --- Procesar la colección paginada para incluir la posición ---
        // La paginación no incluye automáticamente la posición global.
        // La posición debe calcularse considerando la página actual y el ordenamiento.
        $currentPage = $paginatedRanking->currentPage();
        $perPageValue = $paginatedRanking->perPage();
        $startPosition = ($currentPage - 1) * $perPageValue + 1;

        // Usamos un índice para manejar empates en la clasificación basada en elo_rating
        $previousRating = null;
        $currentPosition = $startPosition;

        $rankingWithPositions = $paginatedRanking->getCollection()->map(function ($characterSurveyRow) use (&$currentPosition, &$previousRating) { // characterSurveyRow es un stdClass resultado del JOIN
            $currentRating = $characterSurveyRow->elo_rating;

            // Verificar si hay empate con el personaje anterior (mismo elo_rating)
            $isTieWithPrevious = ($previousRating !== null && $previousRating === $currentRating);

            if (!$isTieWithPrevious) {
                // Si no hay empate, actualizar la posición actual
                $characterSurveyRow->survey_position = $currentPosition;
                // Actualizar valor anterior para la próxima iteración
                $previousRating = $currentRating;
            } else {
                // Si hay empate, mantener la misma posición que el anterior
                $characterSurveyRow->survey_position = $currentPosition - 1; // La posición no cambia
                // No se actualiza $previousRating ni $currentPosition en este caso
            }

            $currentPosition++; // Aumentar la posición para el próximo elemento (o para el próximo no-empate)
            return $characterSurveyRow; // Devolver el objeto modificado (stdClass con survey_position añadido)
        });

        // Volver a colocar la colección modificada en el objeto Paginator
        $paginatedRanking->setCollection($rankingWithPositions);

        dd($paginatedRanking);

        // Devolver el objeto Paginator *con* la colección modificada que incluye la posición
        // Inertia lo entenderá y lo serializará como {  [...], meta: {...}, links: [...] }
        return $paginatedRanking; // <-- Devolver el objeto Paginator
    }

    /**
     * Obtiene el ranking de personajes para una encuesta específica.
     * Calcula el ranking basado en estadísticas de la tabla pivote character_survey.
     * Utiliza Query Builder directamente para mayor eficiencia y control sobre la consulta.
     *
     * @param Survey $survey La encuesta para la cual calcular el ranking.
     * @param array $filters Filtros opcionales (search, sort, page, per_page).
     * @return LengthAwarePaginator La colección paginada de objetos CharacterSurvey (con relación character y posición calculada).
     */
    public function getSurveyRanking_wo_elo_ok(Survey $survey, array $filters = []): LengthAwarePaginator
    {
        // Usar el modelo CharacterSurvey como punto de partida para la consulta
        $query = CharacterSurvey::where('survey_id', $survey->id) // Filtrar por la encuesta específica
                               ->with(['character:id,fullname,nickname,picture,slug']) // Cargar datos básicos del personaje relacionado
                               ->where('is_active', true); // Solo personajes activos en la encuesta

        // --- Aplicar Filtros Opcionales ---
        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->whereHas('character', function ($subQuery) use ($searchTerm) {
                $subQuery->where('fullname', 'like', "%{$searchTerm}%")
                         ->orWhere('nickname', 'like', "%{$searchTerm}%");
            });
        }

        // --- Definir Criterio de Ordenamiento ---
        $sortBy = $filters['sort'] ?? 'survey_wins'; // Campo por defecto para ordenar
        $sortDirection = strtolower($filters['direction'] ?? 'desc'); // Dirección por defecto

        // Validar campos de ordenamiento si es necesario
        $allowedSortFields = ['survey_wins', 'survey_losses', 'survey_ties', 'survey_matches'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'survey_wins'; // Volver a valor por defecto si no es válido
        }

        // Asegurar que la dirección sea 'asc' o 'desc'
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc'; // Volver a valor por defecto
        }

        // Ordenar por el campo del pivote CharacterSurvey
        $query->orderBy($sortBy, $sortDirection)
              ->orderBy('character_id', 'asc'); // Orden secundario para desempatar si los campos de orden son iguales


        // --- Paginación ---
        $perPage = min((int) ($filters['per_page'] ?? self::DEFAULT_PAGE_SIZE), self::MAX_PAGE_SIZE);

        // Cargar los resultados paginados
        $paginatedRanking = $query->paginate($perPage)->withQueryString();

        // --- Procesar la colección paginada para incluir la posición ---
        // La paginación no incluye automáticamente la posición global.
        // La posición debe calcularse considerando la página actual y el ordenamiento.
        $currentPage = $paginatedRanking->currentPage();
        $perPageValue = $paginatedRanking->perPage();
        $startPosition = ($currentPage - 1) * $perPageValue + 1;

        // Usamos un índice para manejar empates en la clasificación
        $previousWins = null;
        $previousTies = null;
        $previousLosses = null;
        $previousMatches = null;
        $currentPosition = $startPosition;

        $rankingWithPositions = $paginatedRanking->getCollection()->map(function ($characterSurveyPivot, $index) use (&$currentPosition, &$previousWins, &$previousTies, &$previousLosses, &$previousMatches) {
            $currentWins = $characterSurveyPivot->survey_wins;
            $currentTies = $characterSurveyPivot->survey_ties;
            $currentLosses = $characterSurveyPivot->survey_losses;
            $currentMatches = $characterSurveyPivot->survey_matches;

            // Verificar si hay empate con el personaje anterior (misma cantidad de wins, ties, losses, matches)
            $isTieWithPrevious = (
                $previousWins === $currentWins &&
                $previousTies === $currentTies &&
                $previousLosses === $currentLosses &&
                $previousMatches === $currentMatches
            );

            if (!$isTieWithPrevious) {
                // Si no hay empate, actualizar la posición actual
                $characterSurveyPivot->setAttribute('survey_position', $currentPosition);
                // Actualizar valores anteriores para la próxima iteración
                $previousWins = $currentWins;
                $previousTies = $currentTies;
                $previousLosses = $currentLosses;
                $previousMatches = $currentMatches;
            } else {
                // Si hay empate, mantener la misma posición que el anterior
                $characterSurveyPivot->setAttribute('survey_position', $currentPosition - 1);
                // No se actualizan $previous... ni $currentPosition en este caso
            }

            $currentPosition++; // Aumentar la posición para el próximo elemento (o para el próximo no-empate)
            return $characterSurveyPivot; // Devolver el objeto CharacterSurvey modificado
        });

        // Volver a colocar la colección modificada en el objeto Paginator
        $paginatedRanking->setCollection($rankingWithPositions);

        // Devolver el objeto Paginator *con* la colección modificada que incluye la posición
        // Inertia lo entenderá y lo serializará como { data: [...], meta: {...}, links: [...] }
        return $paginatedRanking; // <-- Devolver el objeto Paginator
    }

    /**
     * Obtiene el ranking de personajes para una encuesta específica.
     * Calcula el ranking basado en estadísticas de la tabla pivote character_survey.
     *
     * @param Survey $survey La encuesta para la cual calcular el ranking.
     * @param array $filters Filtros opcionales (search, sort, page, per_page).
     * @return \Illuminate\Pagination\LengthAwarePaginator La colección paginada de personajes con sus estadísticas de ranking en la encuesta.
     */
    public function getSurveyRanking_deprecated(Survey $survey, array $filters = [])
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

        $query->orderBy(/* $pivotField, */ $sortDirection) // <-- ORDENAR POR CAMPO DEL PIVOTE
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