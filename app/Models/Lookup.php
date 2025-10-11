<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // Añadido para soft deletes (opcional según decisión previa)

class Lookup extends Model
{
    /** @use HasFactory<\Database\Factories\LookupFactory> */
    use HasFactory, SoftDeletes; // Añadido SoftDeletes (si aplica)

    protected $fillable = [
        'category',
        'code',
        'name',
        'description',
        'metadata',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'metadata' => 'array', // Laravel maneja JSON como array automáticamente
    ];

    // Scope para categoría específica
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category)
                    ->where('is_active', true)
                    ->orderBy('sort_order');
    }

    // Scope para código específico
    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
    }
}
