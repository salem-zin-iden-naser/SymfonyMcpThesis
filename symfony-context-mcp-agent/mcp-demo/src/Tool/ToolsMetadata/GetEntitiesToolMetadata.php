<?php

namespace App\Tool\ToolsMetadata;

use PhpLlm\McpSdk\Capability\Tool\MetadataInterface;

class GetEntitiesToolMetadata implements MetadataInterface
{
    public function getId(): string
    {
        return 'getEntities';
    }

    public function getName(): string
    {
        return 'Get Entities';
    }

    public function getDescription(): string
    {
        return 'Get Entities Information';
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
