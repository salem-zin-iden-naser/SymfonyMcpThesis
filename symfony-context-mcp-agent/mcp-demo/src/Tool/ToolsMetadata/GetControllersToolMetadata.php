<?php

namespace App\Tool\ToolsMetadata;

use PhpLlm\McpSdk\Capability\Tool\MetadataInterface;

class GetControllersToolMetadata implements MetadataInterface
{
    public function getId(): string
    {
        return 'getControllers';
    }

    public function getName(): string
    {
        return 'Get Controllers';
    }

    public function getDescription(): string
    {
        return 'Lists all Symfony controller class names in the Controller directory.';
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
