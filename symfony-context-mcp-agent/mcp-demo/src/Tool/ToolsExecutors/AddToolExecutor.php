<?php

namespace App\Tool\ToolsExecutors;

use PhpLlm\McpSdk\Capability\Tool\ToolExecutorInterface;
use PhpLlm\McpSdk\Capability\Tool\ToolCall;
use PhpLlm\McpSdk\Capability\Tool\ToolCallResult;
use PhpLlm\McpSdk\Exception\ToolExecutionException;

/**
 * Executes the 'add' tool, summing two numbers.
 */
class AddToolExecutor implements ToolExecutorInterface
{
    /**
     * Handles the tool call to perform addition.
     *
     * @param ToolCall $input The incoming tool call object containing arguments.
     * @return ToolCallResult The result of the addition, formatted for MCP.
     * @throws ToolExecutionException If required parameters 'a' or 'b' are missing.
     */
    public function call(ToolCall $input): ToolCallResult
    {
        $params = $input->arguments;

        if (!isset($params['a'], $params['b'])) {
            throw new ToolExecutionException('Missing parameters "a" or "b".');
        }

        $numA = (float) $params['a'];
        $numB = (float) $params['b'];
        $sum = $numA + $numB;

        return new ToolCallResult(
            (string) $sum,
            'text',
            'text/plain',  // mimeType
            false,         // isError
            null );
    }
}