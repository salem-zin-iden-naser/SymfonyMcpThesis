<?php

namespace App\Tool\ToolsMetadata;

use PhpLlm\McpSdk\Capability\Tool\MetadataInterface;
use PhpLlm\McpSdk\Capability\Tool\ToolExecutorInterface;
use PhpLlm\McpSdk\Capability\Tool\ToolCall;
use PhpLlm\McpSdk\Capability\Tool\ToolCallResult;
use PhpLlm\McpSdk\Exception\ToolExecutionException;
use PhpLlm\McpSdk\Exception\ToolNotFoundException;

class AddToolMetadata implements MetadataInterface
{
    public function getId(): string { return 'add'; }
    public function getName(): string { return 'Add'; }
    public function getDescription(): string { return 'Adds two numbers.'; }
    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'a' => ['type' => 'number', 'description' => 'First number'],
                'b' => ['type' => 'number', 'description' => 'Second number'],
            ],
            'required' => ['a', 'b'],
        ];
    }
}
