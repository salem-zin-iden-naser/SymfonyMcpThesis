<?php

namespace App\Bridge;

use PhpLlm\McpSdk\Capability\Tool\CollectionInterface;
use PhpLlm\McpSdk\Capability\Tool\ToolExecutorInterface;
use PhpLlm\McpSdk\Capability\Tool\ToolCall;

class MCPContextCollector
{
    public function __construct(
        private ToolExecutorInterface $toolExecutor,
        private CollectionInterface $toolCollection
    ) {}

    public function collectProjectContext(string $projectPath, array $toolIds = []): array
    {
        $context = [];

        // Automatically collect all tool IDs if not explicitly given
        if (empty($toolIds)) {
            $toolIds = array_map(fn($tool) => $tool->getName(), $this->toolCollection->getMetadata());
        }

        foreach ($toolIds as $toolName) {
            try {
                $toolCall = new ToolCall(
                    id: uniqid('tool_', true),
                    name: $toolName,
                    arguments: ['path' => $projectPath]
                );

                $result = $this->toolExecutor->call($toolCall);
                $context[$toolName] = json_decode($result->result, true) ?? $result->result;
            } catch (\Throwable $e) {
                $context[$toolName] = ['error' => $e->getMessage()];
            }
        }

        return $context;
    }
}
