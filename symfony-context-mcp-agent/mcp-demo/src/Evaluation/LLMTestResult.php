<?php

namespace App\Evaluation;

use App\DTO\LLMResponse;

class LLMTestResult
{
    public function __construct(
        private TestScenario $scenario,
        private LLMResponse $mcpResponse,
        private LLMResponse $vanillaResponse,
        private array $metrics = []
    ) {}

    public function getScenario(): TestScenario { return $this->scenario; }
    public function getMcpResponse(): LLMResponse { return $this->mcpResponse; }
    public function getVanillaResponse(): LLMResponse { return $this->vanillaResponse; }
    public function getMetrics(): array { return $this->metrics; }

    public function toArray(): array
    {
        return [
            'scenario' => [
                'id' => $this->scenario->getId(),
                'category' => $this->scenario->getCategory(),
                'prompt' => $this->scenario->getPrompt(),
                'task_type' => $this->scenario->getTaskType(),
                'tools' => $this->scenario->getRelevantTools(),
                'complexity' => $this->scenario->getComplexity(),
            ],
            'mcp_response' => [
                'content' => nl2br($this->mcpResponse->getContent()),
                'token_count' => $this->mcpResponse->getTokenCount(),
                'response_time' => $this->mcpResponse->getResponseTime(),
            ],
            'vanilla_response' => [
                'content' => nl2br($this->vanillaResponse->getContent()),
                'token_count' => $this->vanillaResponse->getTokenCount(),
                'response_time' => $this->vanillaResponse->getResponseTime(),
            ],
            'metrics' => $this->metrics,
        ];
    }
}
