<?php

namespace App\Tool\ToolsExecutors;

use PhpLlm\McpSdk\Capability\Tool\ToolCall;
use PhpLlm\McpSdk\Capability\Tool\ToolCallResult;
use PhpLlm\McpSdk\Capability\Tool\ToolExecutorInterface;
use PhpLlm\McpSdk\Exception\ToolExecutionException;

class GetControllersToolExecutor implements ToolExecutorInterface
{
    public function call(ToolCall $input): ToolCallResult
    {
        $controllerDir = __DIR__ . '/../../../../symfony-demo/src/Controller';

        if (!is_dir($controllerDir)) {
            throw new ToolExecutionException("Controller directory not found: $controllerDir");
        }

        $files = scandir($controllerDir);
        $controllers = [];

        foreach ($files as $file) {
            if (str_ends_with($file, 'Controller.php')) {
                $controllers[] = pathinfo($file, PATHINFO_FILENAME);
            }
        }

        $result = implode("\n", $controllers);

        return new ToolCallResult(
            $result,
            'text',
            'text/plain',
            false,
            null
        );
    }
}
