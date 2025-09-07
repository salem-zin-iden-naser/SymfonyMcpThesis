<?php

namespace App\DTO;

class LLMRequest
{
    public function __construct(
        private string $prompt,
        private string $projectPath,
        private ?string $model = null,
        private ?string $taskType = 'general',
        private array $relevantTools = [],
        private array $options = []
    ) {}

    public function getPrompt(): string { return $this->prompt; }
    public function getProjectPath(): string { return $this->projectPath; }
    public function getModel(): ?string { return $this->model; }
    public function getTaskType(): string { return $this->taskType; }
    public function getRelevantTools(): array { return $this->relevantTools; }
    public function getOptions(): array { return $this->options; }

    public static function forController(string $prompt, string $projectPath): self
    {
        return new self(
            $prompt,
            $projectPath,
            null,
            'controller',
            ['Get Routes', 'Get Controllers', 'Get Entities', 'Get Project Structure']
        );
    }

    public static function forEntity(string $prompt, string $projectPath): self
    {
        return new self(
            $prompt,
            $projectPath,
            null,
            'entity',
            ['Get Entities', 'Get Project Structure']
        );
    }
}
