<?php

namespace App\Services\Math;

class CombinatoricsService
{
    /**
     * Calcular combinaciones C(n,k) = n! / (k! * (n-k)!)
     */
    public static function combinations(int $n, int $k): int
    {
        if ($k > $n || $k < 0 || $n < 0) {
            return 0;
        }
        
        if ($k == 0 || $k == $n) {
            return 1;
        }
        
        // Optimización: C(n,k) = C(n, n-k)
        if ($k > $n - $k) {
            $k = $n - $k;
        }
        
        $result = 1;
        for ($i = 0; $i < $k; $i++) {
            $result *= ($n - $i);
            $result /= ($i + 1);
        }
        
        return (int) $result;
    }
    
    /**
     * Calcular factorial de un número
     */
    public static function factorial(int $n): int
    {
        if ($n < 0) {
            throw new \InvalidArgumentException('Factorial is not defined for negative numbers');
        }
        
        if ($n <= 1) {
            return 1;
        }
        
        $result = 1;
        for ($i = 2; $i <= $n; $i++) {
            $result *= $i;
        }
        
        return $result;
    }
    
    /**
     * Calcular permutaciones P(n,k) = n! / (n-k)!
     */
    public static function permutations(int $n, int $k): int
    {
        if ($k > $n || $k < 0 || $n < 0) {
            return 0;
        }
        
        $result = 1;
        for ($i = $n; $i > $n - $k; $i--) {
            $result *= $i;
        }
        
        return $result;
    }
    
    /**
     * Calcular combinaciones con repetición CR(n,k) = C(n+k-1,k)
     */
    public static function combinationsWithRepetition(int $n, int $k): int
    {
        return self::combinations($n + $k - 1, $k);
    }
}