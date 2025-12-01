<?php

// app/Models/CharacterSurvey.php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot; // <-- EXTENDER Pivot, no Model
// use Illuminate\Database\Eloquent\Model; // <-- COMENTAR ESTO

class CharacterSurvey extends Pivot // <-- EXTENDER Pivot
{
    protected $table = 'character_survey'; // <-- Asegurar el nombre de la tabla pivote

    public $timestamps = true; // <-- Asumiendo que tiene timestamps

    protected $fillable = [
        'character_id',
        'survey_id',
        'survey_matches',
        'survey_wins',
        'survey_losses',
        'survey_ties', // Asegurar que 'survey_ties' esté incluido
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'character_id' => 'integer',
        'survey_id' => 'integer',
        'survey_matches' => 'integer',
        'survey_wins' => 'integer',
        'survey_losses' => 'integer',
        'survey_ties' => 'float', // Asegurar que 'survey_ties' esté correctamente tipado
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Opcional: Definir claves foráneas explícitamente si no se deducen correctamente
    // protected $foreignKey = 'character_id'; // <-- Clave foránea hacia Character (en la tabla pivote)
    // protected $relatedKey = 'survey_id';   // <-- Clave foránea hacia Survey (en la tabla pivote)

    // --- Relaciones desde el pivote (Opcional) ---
    // public function character(): BelongsTo { ... }
    // public function survey(): BelongsTo { ... }

    // --- Métodos o Accesores/Modificadores (Opcional) ---
    // public function getSomeCustomAttributeAttribute() { ... }
}