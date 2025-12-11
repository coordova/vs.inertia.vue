<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Resource para representar una categoría en la vista de listado público.
 * Incluye datos básicos y conteo de personajes asociados.
 */
class CategoryIndexResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'image' => $this->image,
            'image_url' => $this->image ? Storage::url($this->image) : null,
            'color' => $this->color,
            'icon' => $this->icon,
            'sort_order' => $this->sort_order,
            'status' => $this->status,
            'is_featured' => $this->is_featured,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'created_at_formatted' => $this->created_at->translatedFormat('d-m-Y'),
            'updated_at_formatted' => $this->updated_at->translatedFormat('d-m-Y'),

            // --- Campo específico para la vista de listado ---
            // Conteo de personajes activos en la categoría (calculado con withCount en el controlador)
            'character_count' => $this->characters_count ?? 0, // Nombre del campo generado por withCount

            // Opcional: Conteo de encuestas activas en la categoría
            'survey_count' => $this->surveys_count ?? 0, // Requiere cargar con 'withCount(['surveys' => function($q) { $q->where(...); }])'
        ];
    }
}