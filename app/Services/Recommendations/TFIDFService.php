<?php

declare(strict_types=1);

namespace App\Services\Recommendations;

class TFIDFService
{
    /**
     * @param  list<string>  $tokens
     * @return array<string, float>
     */
    public function termFrequency(array $tokens): array
    {
        $totalTerms = count($tokens);

        if ($totalTerms === 0) {
            return [];
        }

        $counts = array_count_values($tokens);

        $frequencies = [];
        foreach ($counts as $term => $count) {
            $frequencies[$term] = $count / $totalTerms;
        }

        ksort($frequencies);

        return $frequencies;
    }

    /**
     * @param  list<array{tokens:list<string>}>  $documents
     * @return array<string, int>
     */
    public function documentFrequency(array $documents): array
    {
        $frequencies = [];

        foreach ($documents as $document) {
            $uniqueTokens = array_values(array_unique($document['tokens'] ?? []));

            foreach ($uniqueTokens as $term) {
                $frequencies[$term] = ($frequencies[$term] ?? 0) + 1;
            }
        }

        ksort($frequencies);

        return $frequencies;
    }

    /**
     * @param  list<array{tokens:list<string>}>  $documents
     * @return array<string, float>
     */
    public function inverseDocumentFrequency(array $documents): array
    {
        $documentCount = count($documents);
        if ($documentCount === 0) {
            return [];
        }

        $documentFrequency = $this->documentFrequency($documents);
        $idf = [];

        foreach ($documentFrequency as $term => $df) {
            $idf[$term] = log(($documentCount + 1) / ($df + 1)) + 1;
        }

        ksort($idf);

        return $idf;
    }

    /**
     * @param  list<string>  $tokens
     * @param  array<string, float>  $idf
     * @return array<string, float>
     */
    public function tfIdf(array $tokens, array $idf): array
    {
        $tf = $this->termFrequency($tokens);
        $vector = [];

        foreach ($idf as $term => $idfValue) {
            $vector[$term] = ($tf[$term] ?? 0.0) * $idfValue;
        }

        ksort($vector);

        return $vector;
    }

    /**
     * @param  array<string, float>  $vector
     */
    public function magnitude(array $vector): float
    {
        if ($vector === []) {
            return 0.0;
        }

        $sum = 0.0;
        foreach ($vector as $value) {
            $sum += $value * $value;
        }

        return sqrt($sum);
    }

    /**
     * @param  array<string, float>  $vector
     * @param  list<string>  $terms
     * @return array<string, float>
     */
    public function alignVector(array $vector, array $terms): array
    {
        $aligned = [];

        foreach ($terms as $term) {
            $aligned[$term] = (float) ($vector[$term] ?? 0.0);
        }

        return $aligned;
    }
}
