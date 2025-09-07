<?php

namespace App\Evaluation;

use App\Bridge\LLMBridgeService;
use App\DTO\LLMRequest;
use App\Evaluation\LLMTestResult;

class TestScenarioRunner
{
    public function __construct(
        private LLMBridgeService $bridgeService,
        private TestScenarioRepository $scenarioRepository,
        private EvaluationMetricsCalculator $metricsCalculator
    ) {}

    public function runAllScenarios(): array
    {
        $scenarios = $this->scenarioRepository->getAllScenarios();
        $results = [];

        foreach ($scenarios as $scenario) {
            $results[] = $this->runSingleScenario($scenario);
        }

        return $results;
    }

    public function runScenariosByCategory(string $category): array
    {
        $scenarios = $this->scenarioRepository->getByCategory($category);
        $results = [];

        foreach ($scenarios as $scenario) {
            $results[] = $this->runSingleScenario($scenario);
        }

        return $results;
    }

    public function runSingleScenario(TestScenario $scenario): LLMTestResult
    {
        $request = new LLMRequest(
            prompt: $scenario->getPrompt(),
            projectPath: $_ENV['MCP_PROJECT_PATH'] ?? '',
            taskType: $scenario->getTaskType(),
            relevantTools: $scenario->getRelevantTools()
        );

        $mcpResponse = $this->bridgeService->queryWithMCP($request);
        $vanillaResponse = $this->bridgeService->queryWithoutMCP($request);
        $metrics = $this->metricsCalculator->calculate($scenario, $mcpResponse, $vanillaResponse);

        return new LLMTestResult($scenario, $mcpResponse, $vanillaResponse, $metrics);
    }

    public function runByKey(string $key): ?LLMTestResult
    {
        $scenario = $this->scenarioRepository->getByKey($key);
        return $scenario ? $this->runSingleScenario($scenario) : null;
    }
}
