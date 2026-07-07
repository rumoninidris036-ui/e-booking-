<?php

declare(strict_types=1);

namespace App\Services\Recommendations;

class CosineSimilarityService
{
    /**
     * @param  array<string, float>  $vectorA
     * @param  array<string, float>  $vectorB
     */
    public function similarity(array $vectorA, array $vectorB): float
    {
        $dotProduct = $this->dotProduct($vectorA, $vectorB);
        $magnitudeA = $this->magnitude($vectorA);
        $magnitudeB = $this->magnitude($vectorB);

        if ($magnitudeA <= 0.0 || $magnitudeB <= 0.0) {
            return 0.0;
        }

        return $dotProduct / ($magnitudeA * $magnitudeB);
    }

    /**
     * @param  array<string, float>  $vectorA
     * @param  array<string, float>  $vectorB
     */
    public function dotProduct(array $vectorA, array $vectorB): float
    {
        $sum = 0.0;

        foreach ($vectorA as $term => $value) {
            $sum += $value * (float) ($vectorB[$term] ?? 0.0);
        }

        return $sum;
    }

    /**
     * @param  array<string, float>  $vector
     */
    public function magnitude(array $vector): float
    {
        $sum = 0.0;

        foreach ($vector as $value) {
            $sum += $value * $value;
        }

        return sqrt($sum);
    }
}
