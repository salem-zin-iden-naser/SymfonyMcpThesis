<?php

namespace App\Evaluation;

use App\DTO\LLMResponse;

class EvaluationMetricsCalculator
{
    public function calculate(
        TestScenario $scenario,
        LLMResponse $mcp,
        LLMResponse $vanilla
    ): array {
        $lengthMcp = strlen($mcp->getContent());
        $lengthVanilla = strlen($vanilla->getContent());

        $responseTimeMcp = $mcp->getResponseTime();
        $responseTimeVanilla = $vanilla->getResponseTime();

        $tokenMcp = $mcp->getTokenCount();
        $tokenVanilla = $vanilla->getTokenCount();

        return [
            'response_time_diff' => $responseTimeMcp - $responseTimeVanilla,
            'token_count_diff' => $tokenMcp - $tokenVanilla,
            'content_length_diff' => $lengthMcp - $lengthVanilla,
            'used_context_effect' => $this->scoreEffect($lengthMcp, $lengthVanilla, $responseTimeMcp, $responseTimeVanilla),
            'timestamp' => (new \DateTime())->format(\DateTime::ATOM),
        ];
    }

    private function scoreEffect(
        int $lengthMcp,
        int $lengthVanilla,
        float $timeMcp,
        float $timeVanilla
    ): string {
        if ($lengthMcp > $lengthVanilla && $timeMcp <= $timeVanilla) {
            return 'longer answer in less or equal time';
        }
        if ($lengthMcp > $lengthVanilla) {
            return 'more content';
        }
        if ($timeMcp < $timeVanilla) {
            return 'faster response';
        }
        return 'no strong effect';
    }
}
