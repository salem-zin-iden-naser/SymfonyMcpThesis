<?php

namespace App\Tool\ToolsMetadata;

use PhpLlm\McpSdk\Capability\Tool\MetadataInterface;

class GetFileContentToolMetadata implements MetadataInterface
{
    public function getId(): string
    {
        return 'getFileContent';
    }

    public function getName(): string
    {
        return 'Get File Content';
    }

    public function getDescription(): string
    {
        return 'Reads a specific file from the Symfony project and returns its raw content.';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => (object) [
                'filePath' => [
                    'type' => 'string',
                    'description' => 'Path to the file relative to the Symfony project root (e.g. src/Entity/Post.php)',
                ],
            ],
            'required' => ['filePath'],
        ];
    }
}
