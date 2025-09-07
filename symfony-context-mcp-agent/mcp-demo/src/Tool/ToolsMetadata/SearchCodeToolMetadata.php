<?php

namespace App\Tool\ToolsMetadata;

use PhpLlm\McpSdk\Capability\Tool\MetadataInterface;

class SearchCodeToolMetadata implements MetadataInterface
{
    public function getId(): string
    {
        return 'searchCode';
    }

    public function getName(): string
    {
        return 'Search Code';
    }

    public function getDescription(): string
    {
        return 'Searches the codebase for lines matching a given pattern.';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => (object) [
                'pattern' => [
                    'type' => 'string',
                    'description' => 'Search pattern (plain text or regex).',
                ],
            ],
            'required' => ['pattern'],
        ];
    }
}
