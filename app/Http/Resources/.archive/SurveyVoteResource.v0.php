<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use App\Http\Resources\SurveyBaseResource; // Asumiendo que SurveyShowResource extiende SurveyBaseResource

class SurveyVoteResource extends SurveyBaseResource // <-- Extender de SurveyBaseResource o SurveyShowResource
{
    /**
     * Transform the resource into an array.
     * Optimizado para la página de votación pública.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Reutilizar la lógica base del SurveyBaseResource o SurveyShowResource
        // $baseData = parent::toArray($request); // Si extiende SurveyBaseResource directamente
        // $baseData = $this->baseData($request); // Si usa el método baseData de SurveyBaseResource

        // Calcular datos específicos para votación
        $combinationsCount = $this->getCombinationsCount(); // Método del modelo o base
        $userVotesCount = $this->user_votes_count ?? 0; // Debe venir del SurveyProgressService
        $progressPercentage = $this->getProgressPercentage(); // Método del modelo o base, usando user_votes_count y combinations_count

        // Datos específicos de la encuesta para votación
        // return [
        return array_merge($this->baseData($request), [
            // Datos base de la encuesta (id, title, slug, status, dates, etc.)
            // Asumiendo que baseData o parent ya los incluye
            // Si no, añádelos aquí manualmente o asegura que SurveyBaseResource los tenga.
            // 'id' => $this->id,
            // 'title' => $this->title,
            // 'slug' => $this->slug,
            // 'status' => $this->status,
            // 'date_start' => $this->date_start?->format('Y-m-d'),
            // 'date_end' => $this->date_end?->format('Y-m-d'),
            // 'selection_strategy' => $this->selection_strategy,
            // 'tie_weight' => $this->tie_weight,
            // 'allow_ties' => $this->allow_ties,
            // 'category_id' => $this->category_id,
            // 'category' => $this->category ? [
            //     'id' => $this->category->id,
            //     'name' => $this->category->name,
            //     // ... otros campos necesarios ...
            // ] : null,
            // ... (resto de campos base de SurveyBaseResource) ...

            // Asegurarse de incluir los campos calculados por SurveyBaseResource/SurveyShowResource
            // o calcularlos aquí si es necesario y no están en baseData/parent.
            // 'total_combinations' => $combinationsCount, // <-- Añadir si no está en baseData
            // 'total_votes' => $userVotesCount, // <-- Añadir si no está en baseData
            // 'progress_percentage' => $progressPercentage, // <-- Añadir si no está en baseData

            // Datos optimizados para votación
            'character_count' => $this->characters_count ?? $this->characters->count(), // Conteo eficiente
            'total_combinations_expected' => $combinationsCount, // Alias más claro para votación
            'total_votes' => $userVotesCount, // Alias más claro para votación
            'progress_percentage' => $progressPercentage, // Ya calculado
            'is_completed' => $progressPercentage >= 100, // Ya calculado
            'remaining_combinations' => max(0, $combinationsCount - $userVotesCount), // Ya calculado
            // 'is_active' => $this->date_start <= now() && $this->date_end >= now(), // Ya calculado en base?
            
            // Añadir otros campos que puedan ser necesarios en PublicVote.vue
            // Por ejemplo, si se muestra la duración de la encuesta
            'duration_days' => $this->date_start?->diffInDays($this->date_end), 
            
            // Fechas formateadas si se usan
            'date_start_formatted' => $this->date_start?->utc()->format('d-m-Y'),
            'date_end_formatted' => $this->date_end?->utc()->format('d-m-Y'),
            'created_at_formatted' => $this->created_at->translatedFormat('d-m-Y H:i:s'),
            'updated_at_formatted' => $this->updated_at->translatedFormat('d-m-Y H:i:s'),
        ]);
    }
}