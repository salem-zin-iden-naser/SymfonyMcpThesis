<?php

namespace App\Tool\ToolsExecutors;

use PhpLlm\McpSdk\Capability\Tool\ToolCall;
use PhpLlm\McpSdk\Capability\Tool\ToolCallResult;
use PhpLlm\McpSdk\Capability\Tool\ToolExecutorInterface;
use PhpLlm\McpSdk\Exception\ToolExecutionException;

class GetFileContentToolExecutor implements ToolExecutorInterface
{
    public function call(ToolCall $input): ToolCallResult
    {
        try {
        $filePath = $input->arguments['filePath'] ?? null;

        if (!$filePath || !is_string($filePath)) {
            throw new ToolExecutionException($input, null,"Missing or invalid 'filePath'.");
        }

        // Resolve base dir of the Symfony project
        $baseDir = realpath(__DIR__ . '/../../../../symfony-demo');
        $targetPath = realpath($baseDir . '/' . $filePath);

        // Security check: ensure the file is inside the base directory
        if (!$targetPath || !str_starts_with($targetPath, $baseDir)) {
            throw new ToolExecutionException($input, null,"Invalid or disallowed file path.");
        }

        if (!file_exists($targetPath) || !is_file($targetPath)) {
            throw new ToolExecutionException($input, null,"File not found: $filePath");
        }

        $content = file_get_contents($targetPath);

        return new ToolCallResult(
            result: $content ?: '',
            type: 'text',
            mimeType: 'text/plain',
            isError: false,
            uri: null
        );
    } catch (\Throwable $e) {
        // Log detailed error for debugging
        error_log("GetFileContentToolExecutor faileddd: " . $e->getMessage() . "\n" . $e->getTraceAsString());

        // Re-throw as MCP-level tool error
        throw new ToolExecutionException($input, $e, "Failed to read file.");
    }
}
}
