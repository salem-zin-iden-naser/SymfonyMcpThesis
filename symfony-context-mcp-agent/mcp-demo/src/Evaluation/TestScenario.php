<?php

namespace App\Evaluation;

class TestScenario
{
    public function __construct(
        private string $id,
        private string $category,
        private string $prompt,
        private string $taskType = 'general',
        private array $relevantTools = [],
        private array $expectedFiles = [],
        private array $evaluationCriteria = [],
        private string $complexity = 'medium'
    ) {}

    public function getId(): string { return $this->id; }
    public function getCategory(): string { return $this->category; }
    public function getPrompt(): string { return $this->prompt; }
    public function getTaskType(): string { return $this->taskType; }
    public function getRelevantTools(): array { return $this->relevantTools; }
    public function getExpectedFiles(): array { return $this->expectedFiles; }
    public function getEvaluationCriteria(): array { return $this->evaluationCriteria; }
    public function getComplexity(): string { return $this->complexity; }

    public static function fromArray(string $id, array $data): self
    {
        return new self(
            id: $id,
            category: $data['category'] ?? 'general',
            prompt: $data['prompt'] ?? '',
            taskType: $data['task_type'] ?? 'general',
            relevantTools: $data['relevant_tools'] ?? [],
            expectedFiles: $data['expected_files'] ?? [],
            evaluationCriteria: $data['evaluation_criteria'] ?? [],
            complexity: $data['complexity_level'] ?? 'medium'
        );
    }
}
