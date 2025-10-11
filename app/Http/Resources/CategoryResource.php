<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
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
            'color' => $this->color,
            'icon' => $this->icon,
            'sort_order' => $this->sort_order,
            'status' => $this->status,
            'is_featured' => $this->is_featured,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'created_at_formatted' => $this->created_at ? $this->created_at->format('Y-m-d') : null,
            'updated_at_formatted' => $this->updated_at ? $this->updated_at->format('Y-m-d') : null,
            // 'created_at_formatted' => $this->created_at->translatedFormat('d-m-Y H:i'), // usarlo si se desea usar la fecha local
            
            // 'created_at' => $this->created_at,
            // 'updated_at' => $this->updated_at,
            // 'deleted_at' => $this->when($this->trashed(), $this->deleted_at), // Mostrar deleted_at solo si está borrado
        ];
    }
}