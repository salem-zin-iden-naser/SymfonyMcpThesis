<?php

namespace App\Tool\ToolsMetadata;

use PhpLlm\McpSdk\Capability\Tool\MetadataInterface;

class ReadDirectoryToolMetadata implements MetadataInterface
{
    public function getId(): string
    {
        return 'readDirectory';
    }

    public function getName(): string
    {
        return 'Read Directory';
    }

    public function getDescription(): string
    {
        return 'Lists the files and folders inside a given directory of the Symfony project.';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => (object) [
                'dirPath' => [
                    'type' => 'string',
                    'description' => 'Directory path relative to the Symfony project root (e.g. src/Entity)',
                ],
            ],
            'required' => ['dirPath'],
        ];
    }
}
