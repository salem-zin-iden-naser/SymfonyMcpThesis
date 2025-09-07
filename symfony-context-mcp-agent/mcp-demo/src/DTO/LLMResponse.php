<?php

namespace App\DTO;

class LLMResponse
{
    public function __construct(
        private string $content,
        private bool $usedMCP,
        private float $responseTime,
        private int $tokenCount,
        private ?array $mcpContext = null,
        private ?array $metadata = null
    ) {}

    public function getContent(): string { return $this->content; }
    public function isUsedMCP(): bool { return $this->usedMCP; }
    public function getResponseTime(): float { return $this->responseTime; }
    public function getTokenCount(): int { return $this->tokenCount; }
    public function getMcpContext(): ?array { return $this->mcpContext; }
    public function getMetadata(): ?array { return $this->metadata; }
}
