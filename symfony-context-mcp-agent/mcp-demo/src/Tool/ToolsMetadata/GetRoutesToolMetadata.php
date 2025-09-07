<?php

namespace App\Tool\ToolsMetadata;

use PhpLlm\McpSdk\Capability\Tool\MetadataInterface;

class GetRoutesToolMetadata implements MetadataInterface
{
    public function getId(): string
    {
        return 'getRoutes';
    }

    public function getName(): string
    {
        return 'Get Routes';
    }

    public function getDescription(): string
    {
        return 'Returns all registered Symfony routes with path, controller, and methods.';
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
