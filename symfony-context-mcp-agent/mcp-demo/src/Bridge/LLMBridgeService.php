<?php

namespace App\Bridge;

use App\Client\OllamaClient;
use App\DTO\LLMRequest;
use App\DTO\LLMResponse;
use App\Bridge\PromptBuilder;
use App\Bridge\MCPContextCollector;
use App\Bridge\ResponseProcessor;

class LLMBridgeService
{
    private const DEFAULT_MODEL = 'deepseek-coder:6.7b';

    public function __construct(
        private OllamaClient $ollamaClient,
        private MCPContextCollector $contextCollector,
        private PromptBuilder $promptBuilder,
        private ResponseProcessor $responseProcessor
    ) {}

    public function queryWithMCP(LLMRequest $request): LLMResponse
    {
        $context = $this->contextCollector->collectProjectContext(
            $request->getProjectPath(),
            $request->getRelevantTools()
        );

        $prompt = $this->promptBuilder->buildContextualPrompt(
            $request->getPrompt(),
            $context,
            $request->getTaskType()
        );

        $raw = $this->ollamaClient->generate(
            $request->getModel() ?? self::DEFAULT_MODEL,
            $prompt,
            $request->getOptions()
        );

        return $this->responseProcessor->processResponse($raw, true, $context);
    }

    public function queryWithoutMCP(LLMRequest $request): LLMResponse
    {
        $prompt = $this->promptBuilder->buildVanillaPrompt(
            $request->getPrompt(),
            $request->getTaskType()
        );

        $raw = $this->ollamaClient->generate(
            $request->getModel() ?? self::DEFAULT_MODEL,
            $prompt,
            $request->getOptions()
        );

        return $this->responseProcessor->processResponse($raw, false);
    }

    public function compareResponses(LLMRequest $request): array
    {
        $mcpResponse = $this->queryWithMCP($request);
        $vanillaResponse = $this->queryWithoutMCP($request);

        return [
            'mcp_response' => $mcpResponse,
            'vanilla_response' => $vanillaResponse,
            'comparison_metrics' => $this->calculateComparisonMetrics($mcpResponse, $vanillaResponse)
        ];
    }

    private function calculateComparisonMetrics(LLMResponse $mcp, LLMResponse $vanilla): array
    {
        return [
            'response_time_diff' => $mcp->getResponseTime() - $vanilla->getResponseTime(),
            'token_count_diff' => $mcp->getTokenCount() - $vanilla->getTokenCount(),
            'length_diff' => strlen($mcp->getContent()) - strlen($vanilla->getContent()),
            'usedMCP' => $mcp->isUsedMCP(),
            'timestamp' => (new \DateTime())->format('c'),
        ];
    }
}
