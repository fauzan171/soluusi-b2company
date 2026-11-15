<?php

namespace App\Contracts;

interface AIService
{
    /**
     * Generate insight dari payload agregat ter-redaksi.
     *
     * @return array{summary: string, findings: array, dataGaps: array}
     */
    public function generateInsight(array $payload): array;
}
