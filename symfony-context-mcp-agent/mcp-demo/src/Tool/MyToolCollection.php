<?php

namespace App\Tool;

use App\Tool\ToolsMetadata\GetEntitiesToolMetadata;
use App\Tool\ToolsMetadata\GetFileContentToolMetadata;
use App\Tool\ToolsMetadata\GetProjectStructureToolMetadata;
use App\Tool\ToolsMetadata\ReadDirectoryToolMetadata;
use App\Tool\ToolsMetadata\SearchCodeToolMetadata;
use App\Tool\ToolsMetadata\GetRoutesToolMetadata;
use App\Tool\ToolsMetadata\GetUserRolesToolMetadata;
use App\Tool\ToolsMetadata\GetContextualCodeAnalysisToolMetadata;
use App\Tool\ToolsMetadata\AddToolMetadata;
use PhpLlm\McpSdk\Capability\Tool\CollectionInterface;
use PhpLlm\McpSdk\Capability\Tool\MetadataInterface;
use App\Tool\ToolsMetadata\GetControllersToolMetadata;

class MyToolCollection implements CollectionInterface
{
    public function getMetadata(): array
    {
        return [
            new AddToolMetadata(),
            new GetEntitiesToolMetadata(),
            new GetFileContentToolMetadata(),
            new GetProjectStructureToolMetadata(),
            new ReadDirectoryToolMetadata(),
            new SearchCodeToolMetadata(),
            new GetRoutesToolMetadata(),
            new GetUserRolesToolMetadata(),
            new GetContextualCodeAnalysisToolMetadata(),
            new GetControllersToolMetadata(),
        ];
    }
}