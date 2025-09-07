<?php

namespace App\Bridge;

use App\DTO\LLMResponse;

class ResponseProcessor
{
    public function processResponse(
        array $ollamaResponse,
        bool $usedMCP,
        ?array $mcpContext = null
    ): LLMResponse {
        $content = $ollamaResponse['response'] ?? '[No response]';
        $tokenCount = $ollamaResponse['eval_count'] ?? strlen($content) / 4; // Estimate if missing
        $responseTime = $ollamaResponse['load_duration'] ?? microtime(true); // Estimate fallback

        return new LLMResponse(
            content: trim($content),
            usedMCP: $usedMCP,
            responseTime: (float) $responseTime,
            tokenCount: (int) $tokenCount,
            mcpContext: $mcpContext,
            metadata: $ollamaResponse
        );
    }
}
