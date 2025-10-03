<?php
// app/Data/InertiaAuthData.php
namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Mappers\StudlyCaseMapper;

class InertiaAuthData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public int $type, // o un DTO de rol si se implementa
        // Añadir otros campos relevantes del usuario
    ) {}
}