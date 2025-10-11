<?php

namespace App\Services;

use App\Models\LookupValue;

class LookupService
{
    public static function getSelectionStrategies(): array
    {
        return LookupValue::byCategory('selection_strategies')
            ->get()
            ->map(fn($item) => [
                'value' => $item->code,
                'label' => $item->name,
                'description' => $item->description,
                'metadata' => $item->metadata
                // 'metadata' => self::parseMetadata($item->metadata)
            ])
            ->toArray();
    }

    public static function getLookupByCategory(string $category): array
    {
        return LookupValue::byCategory($category)
            ->get()
            ->map(fn($item) => [
                'value' => $item->code,
                'label' => $item->name,
                'description' => $item->description
            ])
            ->toArray();
    }

    public static function isValidSelectionStrategy(string $code): bool
    {
        return LookupValue::byCategory('selection_strategies')
            ->byCode($code)
            ->exists();
    }

    /*----------------------------------------------------*/

    public static function parseMetadata($metadata)
    {
        if (is_string($metadata)) {
            try {
                return json_decode($metadata, true) ?: [];
            } catch (\Exception $e) {
                return [];
            }
        }
        
        return is_array($metadata) ? $metadata : [];
    }
}