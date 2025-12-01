<?php

// app/Models/CategoryCharacter.php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot; // <-- EXTENDER Pivot, no Model
// use Illuminate\Database\Eloquent\Model; // <-- COMENTAR ESTO

class CategoryCharacter extends Pivot // <-- EXTENDER Pivot
{
    // use HasFactory; // <-- COMENTAR ESTO: Las tablas pivote no suelen tener factories directas

    protected $table = 'category_character'; // <-- Asegurar el nombre de la tabla pivote

    public $timestamps = true; // <-- Asumiendo que tiene timestamps (created_at, updated_at)

    protected $fillable = [
        'category_id',
        'character_id',
        'elo_rating',
        'matches_played',
        'wins',
        'losses',
        'ties', // Asegurar que 'ties' esté incluido
        'win_rate',
        'highest_rating',
        'lowest_rating',
        'rating_deviation',
        'last_match_at',
        'is_featured',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'elo_rating' => 'integer',
        'matches_played' => 'integer',
        'wins' => 'integer',
        'losses' => 'integer',
        'ties' => 'integer', // Asegurar que 'ties' esté correctamente tipado
        'win_rate' => 'decimal:2',
        'highest_rating' => 'integer',
        'lowest_rating' => 'integer',
        'rating_deviation' => 'decimal:2',
        'last_match_at' => 'datetime',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
        'status' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Opcional: Definir claves foráneas explícitamente si no se deducen correctamente
    // o si se usan nombres no convencionales. Generalmente no es necesario si los nombres son estándar.
    // protected $foreignKey = 'character_id'; // <-- Clave foránea hacia Character (en la tabla pivote)
    // protected $relatedKey = 'category_id'; // <-- Clave foránea hacia Category (en la tabla pivote)

    // --- Relaciones desde el pivote (Opcional, pero útil para acceso directo si se carga el pivote como modelo independiente) ---
    // public function character(): BelongsTo { ... }
    // public function category(): BelongsTo { ... }

    // --- Métodos o Accesores/Modificadores (Opcional) ---
    // public function getSomeCustomAttributeAttribute() { ... }
}