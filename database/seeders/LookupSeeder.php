<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Lookup;

class LookupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /* $selectionStrategies = [
            [
                'category' => 'selection_strategies',
                'code' => 'basic',
                'name' => 'Basic Selection',
                'description' => 'Simple random selection avoiding already voted combinations',
                'sort_order' => 1,
                'is_active' => true,
                'metadata' => json_encode([
                    'recommended_for' => 'Small surveys (< 20 characters)',
                    'performance' => 'High',
                    'complexity' => 'Low'
                ])
            ],
            [
                'category' => 'selection_strategies',
                'code' => 'cooldown',
                'name' => 'Cooldown Selection',
                'description' => 'Avoids recently voted combinations (24h cooldown)',
                'sort_order' => 2,
                'is_active' => true,
                'metadata' => json_encode([
                    'recommended_for' => 'Medium surveys (20-50 characters)',
                    'performance' => 'Medium',
                    'complexity' => 'Medium'
                ])
            ],
            [
                'category' => 'selection_strategies',
                'code' => 'weighted',
                'name' => 'Weighted Selection',
                'description' => 'Intelligent weighted selection for balanced voting',
                'sort_order' => 3,
                'is_active' => true,
                'metadata' => json_encode([
                    'recommended_for' => 'Large surveys (> 50 characters)',
                    'performance' => 'Low',
                    'complexity' => 'High'
                ])
            ],
        ]; */

        // database/seeders/LookupValuesSeeder.php
        $selectionStrategies = [
            [           
                'category' => 'selection_strategies',
                'code' => 'basic',
                'name' => 'Basic Selection',
                'description' => 'Simple random selection avoiding already voted combinations',
                'sort_order' => 1,
                'is_active' => true,
                'metadata' => [
                    'recommended for' => 'Small surveys (< 20 characters)',  // ✅ Sin guiones
                    'performance' => 'High',
                    'complexity' => 'Low'
                ]
            ],
            [
                'category' => 'selection_strategies',
                'code' => 'cooldown',
                'name' => 'Cooldown Selection',
                'description' => 'Avoids recently voted combinations (24h cooldown)',
                'sort_order' => 2,
                'is_active' => true,
                'metadata' => [
                    'recommended for' => 'Medium surveys (20-50 characters)', // ✅ Sin guiones
                    'performance' => 'Medium',
                    'complexity' => 'Medium'
                ]
            ],
            [
                'category' => 'selection_strategies',
                'code' => 'weighted',
                'name' => 'Weighted Selection',
                'description' => 'Intelligent weighted selection for balanced voting',
                'sort_order' => 3,
                'is_active' => true,
                'metadata' => [
                    'recommended for' => 'Large surveys (> 50 characters)',   // ✅ Sin guiones
                    'performance' => 'Low',
                    'complexity' => 'High'
                ]
            ],
        ];

        foreach ($selectionStrategies as $strategy) {
            Lookup::updateOrCreate(
                ['category' => $strategy['category'], 'code' => $strategy['code']],
                $strategy
            );
        }
    }
}
