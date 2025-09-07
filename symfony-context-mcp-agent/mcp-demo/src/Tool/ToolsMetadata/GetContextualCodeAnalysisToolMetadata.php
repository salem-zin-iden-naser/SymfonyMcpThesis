<?php

namespace App\Tool\ToolsMetadata;

use PhpLlm\McpSdk\Capability\Tool\MetadataInterface;

class GetContextualCodeAnalysisToolMetadata implements MetadataInterface
{
    public function getId(): string
    {
        return 'getContextualCodeAnalysis';
    }

    public function getName(): string
    {
        return 'Get Contextual Code Analysis';
    }

    public function getDescription(): string
    {
        return 'Returns a compact JSON summary of your project’s entities, relationships, business rules, and architecture patterns.';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => (object) [],
            'required' => [],
        ];
    }
}
