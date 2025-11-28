<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage; // Para generar URLs de imágenes

/**
 * Resource para representar un personaje en contextos como la interfaz de votación.
 * Contiene solo los datos esenciales para mostrar al personaje.
 */
class CharacterResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fullname' => $this->fullname,
            'nickname' => $this->nickname,
            'slug' => $this->slug,
            'bio' => $this->bio,
            // 'dob' => $this->dob, // Fecha de nacimiento (puede formatearse aquí o en el frontend)
            'dob_formatted' => $this->dob?->format('d-m-Y'),
            'dob_for_humans' => $this->dob?->diffForHumans(['parts' => 2, 'join' => ', '], true),    // 2 = 2 palabras
            'gender' => $this->gender, // 0=otro, 1=masculino, 2=femenino, 3=no-binario
            'nationality' => $this->nationality,
            'occupation' => $this->occupation,
            // 'picture' => $this->picture, // Ruta relativa
            'picture_url' => $this->picture ? Storage::url($this->picture) : null, // URL pública generada
            'status' => $this->status,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            // 'created_at' => $this->created_at,
            // 'updated_at' => $this->updated_at,
            // 'created_at_formatted' => $this->created_at->translatedFormat('d-m-Y H:i'),
            // 'updated_at_formatted' => $this->updated_at->translatedFormat('d-m-Y H:i'),
            // No incluimos estadísticas ELO aquí, a menos que se necesiten para mostrar en la interfaz de votación
            // 'elo_rating' => $this->elo_rating, // <-- Solo si se muestra en la UI de votación
        ];
    }
}
