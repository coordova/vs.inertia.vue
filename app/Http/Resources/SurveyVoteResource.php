<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Survey; // Asegúrate de importar el modelo Survey

class SurveyVoteResource extends SurveyBaseResource
{
    /**
     * Transform the resource into an array.
     * Optimizado para la página de votación pública.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Obtener el usuario autenticado (asumiendo que está disponible en el contexto)
        $user = $request->user();
        
        // Calcular progreso del usuario si el usuario está autenticado
        $userProgress = null;
        if ($user) {
            // Asumiendo que tienes un servicio para obtener el progreso del usuario
            $surveyProgressService = app(\App\Services\Survey\SurveyProgressService::class);
            $userProgress = $surveyProgressService->getUserSurveyStatus($this->resource, $user);
            
            // O, si prefieres calcularlo aquí directamente (menos recomendado si el servicio ya existe):
            // $userProgress = $this->calculateUserProgress($user);
        }

        return array_merge(parent::toArray($request), [
            // Datos base de la encuesta (heredados de SurveyBaseResource::baseData)
            // 'id', 'title', 'status', 'type', 'category_id', 'date_start', 'date_end',
            // 'selection_strategy', 'slug', 'is_active',
            // 'date_start_formatted', 'date_end_formatted'
            
            // Datos adicionales específicos de la encuesta para votación
            'description' => $this->description,
            'image' => $this->image,
            'reverse' => (bool) $this->reverse,
            'max_votes_per_user' => $this->max_votes_per_user,
            'allow_ties' => (bool) $this->allow_ties,
            'tie_weight' => $this->tie_weight,
            'is_featured' => (bool) $this->is_featured,
            'sort_order' => $this->sort_order,
            'counter' => $this->counter,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'created_at_formatted' => $this->created_at->translatedFormat('d-m-Y H:i:s'),
            'updated_at_formatted' => $this->updated_at->translatedFormat('d-m-Y H:i:s'),
            
            // Relación con la categoría (ya incluida en baseData si se carga)
            'category' => $this->whenLoaded('category', fn() => new CategoryResource($this->category)),
            
            // --- Datos de Progreso del Usuario ---
            // Estos datos deben venir del SurveyProgressService o calcularse aquí
            // Se asume que el controlador los pasa explícitamente si es necesario
            // 'user_progress' => $userProgress ? new UserProgressResource($userProgress) : null,
            
            // --- Datos Calculados de Progreso (usando columnas nuevas en BD) ---
            // Asumiendo que estas columnas existen y se llenan correctamente
            'total_combinations' => $this->total_combinations ?? 0, // De la tabla surveys
            'progress_percentage' => $userProgress['progress'] ?? 0.00, // Del SurveyProgressService
            'total_votes' => $userProgress['total_votes'] ?? 0, // Del SurveyProgressService
            'total_combinations_expected' => $userProgress['total_expected'] ?? ($this->total_combinations ?? 0), // Del SurveyProgressService o fallback
            'is_completed' => $userProgress['is_completed'] ?? false, // Del SurveyProgressService
            'started_at' => $userProgress['started_at'] ?? null, // Del SurveyProgressService
            'completed_at' => $userProgress['completed_at'] ?? null, // Del SurveyProgressService
            'last_activity_at' => $userProgress['last_activity_at'] ?? null, // Del SurveyProgressService
            'completion_time' => $userProgress['completion_time'] ?? null, // Del SurveyProgressService
            
            // --- Datos de la Próxima Combinación ---
            // Se asume que el controlador la pasa explícitamente
            // 'next_combination' => $nextCombination ? new CombinatoricResource($nextCombination) : null,
        ]);
    }
    
    /**
     * Calcular el progreso del usuario en esta encuesta.
     * (Opcional si no se usa el servicio)
     * @param \App\Models\User $user
     * @return array
     */
    /* protected function calculateUserProgress($user): array
    {
        $surveyUserPivot = $this->users()->where('user_id', $user->id)->first(); // Cargar relación pivote
        
        if (!$surveyUserPivot) {
            return [
                'exists' => false,
                'is_completed' => false,
                'progress' => 0.0,
                'total_votes' => 0,
                'total_expected' => $this->total_combinations ?? 0,
                'started_at' => null,
                'completed_at' => null,
                'last_activity_at' => null,
                'completion_time' => null,
                'pivot_id' => null,
            ];
        }
        
        $totalExpected = $surveyUserPivot->pivot->total_combinations_expected ?? ($this->total_combinations ?? 0);
        $progress = 0.0;
        if ($totalExpected && $totalExpected > 0) {
            $progress = min(100.0, ($surveyUserPivot->pivot->total_votes / $totalExpected) * 100);
        }
        
        return [
            'exists' => true,
            'is_completed' => $surveyUserPivot->pivot->is_completed,
            'progress' => $progress,
            'total_votes' => $surveyUserPivot->pivot->total_votes,
            'total_expected' => $totalExpected,
            'started_at' => $surveyUserPivot->pivot->started_at,
            'completed_at' => $surveyUserPivot->pivot->completed_at,
            'last_activity_at' => $surveyUserPivot->pivot->last_activity_at,
            'completion_time' => $surveyUserPivot->pivot->completion_time,
            'pivot_id' => $surveyUserPivot->pivot->id,
        ];
    } */
}