<?php

namespace App\Tool\ToolsMetadata;

use PhpLlm\McpSdk\Capability\Tool\MetadataInterface;

class GetProjectStructureToolMetadata implements MetadataInterface
{
    public function getId(): string
    {
        return 'getProjectStructure';
    }

    public function getName(): string
    {
        return 'Get Project Structure';
    }

    public function getDescription(): string
    {
        return 'Returns a high-level directory structure of the Symfony project.';
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
