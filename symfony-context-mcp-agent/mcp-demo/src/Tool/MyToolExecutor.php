<?php

namespace App\Tool;

use App\Tool\ToolsExecutors\GetEntitiesToolExecutor;
use App\Tool\ToolsExecutors\GetFileContentToolExecutor;
use App\Tool\ToolsExecutors\GetProjectStructureToolExecutor;
use App\Tool\ToolsExecutors\ReadDirectoryToolExecutor;
use App\Tool\ToolsExecutors\SearchCodeToolExecutor;
use App\Tool\ToolsExecutors\GetRoutesToolExecutor;
use App\Tool\ToolsExecutors\GetUserRolesToolExecutor;
use App\Tool\ToolsExecutors\GetContextualCodeAnalysisToolExecutor;
use App\Tool\ToolsExecutors\AddToolExecutor;
use PhpLlm\McpSdk\Capability\Tool\ToolExecutorInterface;
use PhpLlm\McpSdk\Capability\Tool\ToolCall;
use PhpLlm\McpSdk\Capability\Tool\ToolCallResult;
use PhpLlm\McpSdk\Exception\ToolExecutionException;
use PhpLlm\McpSdk\Exception\ToolNotFoundException;
use Symfony\Component\Routing\RouterInterface;
use App\Tool\ToolsExecutors\GetControllersToolExecutor;

class MyToolExecutor implements ToolExecutorInterface
{
    public function __construct(
        private readonly RouterInterface $router
    ) {}

    public function call(ToolCall $input): ToolCallResult
    {
        try {
            return match ($input->name) {
                'Get Entities' => (new GetEntitiesToolExecutor())->call($input),
                'Get File Content' => (new GetFileContentToolExecutor())->call($input),
                'Add' => (new AddToolExecutor())->call($input),
                'Get Project Structure' => (new GetProjectStructureToolExecutor())->call($input),
                'Read Directory' => (new ReadDirectoryToolExecutor())->call($input),
                'Search Code' => (new SearchCodeToolExecutor())->call($input),
                'Get Routes' => (new GetRoutesToolExecutor($this->router))->call($input),
                'Get User Roles' => (new GetUserRolesToolExecutor())->call($input),
                'Get Contextual Code Analysis' => (new GetContextualCodeAnalysisToolExecutor())->call($input),
                'Get Controllers' => (new GetControllersToolExecutor())->call($input),
                default => throw new ToolNotFoundException($input, null, "Unknown tool: " . $input->name),
            };
        } catch (\Throwable $e) {
            error_log("MyToolExecutor faileddd: " . $e->getMessage());
            throw new ToolNotFoundException($input, $e, "Tool failed: " . $input->name);
        }
    }
}
