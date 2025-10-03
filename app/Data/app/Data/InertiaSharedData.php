<?php
// app/Data/InertiaSharedData.php
namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\DataCollectionOf;

class InertiaSharedData extends Data
{
    public function __construct(
        public ?InertiaAuthData $auth = null, // Datos del usuario autenticado
        // Puedes añadir otros datos globales aquí si es necesario
        // Ej: public ?string $appName = null,
    ) {}
}