<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para representar una combinación específica de personajes.
 * Optimizado para la vista de votación, contiene solo los datos necesarios de los personajes enfrentados.
 */
class CombinatoricResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Verificar integridad de datos
        // if (!$this->exists || !$this->character1 || !$this->character2) {
        //     // Opcional: Devolver un array de error o null si los datos son inválidos
        //     return ['error' => 'Combination data is incomplete or invalid.'];
        // }

        return [
            'id' => $this->id, // ID de la combinación (clave primaria)
            'combinatoric_id' => $this->id, // Alias común si se prefiere
            'survey_id' => $this->survey_id,
            'total_comparisons' => $this->total_comparisons,
            'last_used_at' => $this->last_used_at,
            'status' => $this->status,
            'foo' => 'bar',
            // 'created_at' => $this->created_at,
            // 'updated_at' => $this->updated_at,

            // Datos de los personajes involucrados en la combinación
            // Asumiendo que 'character1' y 'character2' se cargan en el controlador o servicio que llama a este recurso
            'character1' => $this->whenLoaded('character1', fn () => CharacterResource::make($this->character1)->resolve()), // Usar CharacterResource para datos del personaje
            'character2' => $this->whenLoaded('character2', fn () => CharacterResource::make($this->character2)->resolve()), // Usar CharacterResource para datos del personaje
        ];
    }
}
