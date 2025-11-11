<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para la vista de votación pública de una encuesta.
 * Contiene solo los datos necesarios para mostrar la encuesta, el progreso del usuario y la próxima combinación.
 * No incluye listas grandes de personajes o votos.
 */
class SurveyVoteResource extends JsonResource
{
    public function __construct($resource, $extras = [])
    {
        parent::__construct($resource);
        $this->extras = $extras;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // dd($this->extras);
        // Asumiendo que 'category' se carga en el controlador si es necesario para mostrarla o para cálculos internos del recurso
        // Asumiendo que 'characters' (activos para la encuesta) se carga en el controlador si es necesario para generar combinaciones futuras (aunque no se serialice aquí)
        // Asumiendo que 'userProgress' (relación pivote survey_user) se carga o se calcula en el controlador y se pasa como prop adicional o se calcula aquí si se carga el modelo

        return [
            // Datos base de la encuesta (heredados de SurveyBaseResource si extiende de él)
            // 'id', 'title', 'slug', 'status', 'date_start', 'date_end', 'selection_strategy', etc.
            ...parent::toArray($request),

            // Datos específicos de la vista de votación
            'description' => $this->description,
            'image_url' => $this->image ? \Storage::url($this->image) : null, // Generar URL si es necesario
            'allow_ties' => $this->allow_ties,
            'tie_weight' => $this->tie_weight,

            // Relación con la categoría (solo datos necesarios, asumiendo que se carga en el controlador)
            /* 'category' => $this->whenLoaded('category', fn() => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
                'color' => $this->category->color,
                'icon' => $this->category->icon,
            ]), */

            // --- Datos de Progreso del Usuario (deben venir del SurveyProgressService o calcularse aquí si se carga el pivote) ---
            // Asumiendo que el controlador llama a SurveyProgressService->getUserSurveyStatus y pasa los datos aquí
            // o que se carga la relación 'users' con pivote y se calcula aquí.
            // Por simplicidad y claridad, se asume que el controlador pasa estos datos.
            // Si se carga la relación pivote, se podría hacer:
            // $userProgressPivot = $this->users->firstWhere('pivot.user_id', $request->user()->id)?->pivot;
            // $userProgress = $userProgressPivot ? [
            //     'progress_percentage' => $userProgressPivot->progress_percentage,
            //     'total_votes' => $userProgressPivot->total_votes,
            //     'total_combinations_expected' => $userProgressPivot->total_combinations_expected,
            //     'is_completed' => $userProgressPivot->is_completed,
            //     'started_at' => $userProgressPivot->started_at,
            //     'completed_at' => $userProgressPivot->completed_at,
            //     'last_activity_at' => $userProgressPivot->last_activity_at,
            // ] : null;

            // Por ahora, asumiremos que el controlador calcula estos valores y los adjunta al modelo o los pasa como una prop separada.
            // Si se adjuntan al modelo (por ejemplo, con un accessor o un scope que lo haga), se accedería así:
            // 'user_progress' => [
            //     'progress_percentage' => $this->user_progress_percentage, // Accesor calculado
            //     'total_votes' => $this->user_total_votes, // Accesor calculado
            //     'total_combinations_expected' => $this->user_total_combinations_expected, // Accesor calculado o columna en survey_user
            //     'is_completed' => $this->user_is_completed, // Accesor calculado o columna en survey_user
            // ]

            // O mejor, si el controlador los pasa como atributos adicionales al recurso:
            // 'user_progress' => $this->user_progress_data ?? null, // Datos inyectados por el controlador

            // Para evitar confusiones, el controlador debería inyectar directamente los campos necesarios:
            // 'progress_percentage' => $this->user_progress_percentage ?? 0.00, // Inyectado o calculado aquí si se carga el pivote
            // 'total_votes' => $this->user_total_votes ?? 0, // Inyectado o calculado aquí
            // 'total_combinations_expected' => $this->user_total_combinations_expected ?? ($this->total_combinations ?? 0), // Inyectado o calculado aquí o fallback a la encuesta
            // 'is_completed' => $this->user_is_completed ?? false, // Inyectado o calculado aquí
            // 'started_at' => $this->user_started_at ?? null, // Inyectado o calculado aquí
            // 'completed_at' => $this->user_completed_at ?? null, // Inyectado o calculado aquí
            // 'last_activity_at' => $this->user_last_activity_at ?? null, // Inyectado o calculado aquí

            'userProgress' => $this->extras['userProgress'],

            // 'progress' => $this->extras['userProgress']['progress'],
            // 'total_votes' => $this->extras['userProgress']['total_votes'],
            // // 'total_combinations_expected' => $this->extras['userProgress']['total_expected'],
            // 'is_completed' => $this->extras['userProgress']['is_completed'],
            // 'started_at' => $this->extras['userProgress']['started_at'],
            // 'completed_at' => $this->extras['userProgress']['completed_at'],
            // 'last_activity_at' => $this->extras['userProgress']['last_activity_at'],

            // --- Datos de la Próxima Combinación ---
            // Asumiendo que la próxima combinación se pasa como una relación 'nextCombination' o se calcula aquí si se carga la relación
            // NO se incluye aquí. Se pasa como una prop separada al componente Inertia o se obtiene en otro recurso.
            // 'next_combination' => new CombinatoricResource($this->whenLoaded('nextCombination')),
            // La próxima combinación se manejará en el controlador y se pasará como prop aparte a Inertia.
        ];
    }

     /**
     * Get additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'userProgress' => $this->extras['userProgress'],
        ];
    }
}