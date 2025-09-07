<?php

namespace App\Tool\ToolsMetadata;

use PhpLlm\McpSdk\Capability\Tool\MetadataInterface;

class GetUserRolesToolMetadata implements MetadataInterface
{
    public function getId(): string
    {
        return 'getUserRoles';
    }

    public function getName(): string
    {
        return 'Get User Roles';
    }

    public function getDescription(): string
    {
        return 'Lists available user roles used by the Symfony application.';
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
