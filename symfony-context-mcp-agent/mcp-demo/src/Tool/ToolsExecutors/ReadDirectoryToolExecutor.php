<?php

namespace App\Tool\ToolsExecutors;

use PhpLlm\McpSdk\Capability\Tool\ToolCall;
use PhpLlm\McpSdk\Capability\Tool\ToolCallResult;
use PhpLlm\McpSdk\Capability\Tool\ToolExecutorInterface;
use PhpLlm\McpSdk\Exception\ToolExecutionException;

class ReadDirectoryToolExecutor implements ToolExecutorInterface
{
    public function call(ToolCall $input): ToolCallResult
    {
        $dirPath = $input->arguments['dirPath'] ?? null;

        if (!$dirPath || !is_string($dirPath)) {
            throw new ToolExecutionException($input, null, "Missing or invalid 'dirPath'.");
        }

        try {
            $baseDir = realpath(__DIR__ . '/../../../../symfony-demo');
            if (!$baseDir) {
                throw new ToolExecutionException($input, null, "Project base directory not found.");
            }

            $targetDir = realpath($baseDir . '/' . $dirPath);

            if (!$targetDir || !str_starts_with($targetDir, $baseDir)) {
                throw new ToolExecutionException($input, null, "Invalid or restricted directory path.");
            }

            if (!is_dir($targetDir)) {
                throw new ToolExecutionException($input, null, "Not a valid directory: $dirPath");
            }

            $output = "Recursive contents of $dirPath:\n\n";
            $output .= $this->listDirectoryRecursively($targetDir, 0);

            return new ToolCallResult(
                result: $output,
                type: 'text',
                mimeType: 'text/plain',
                isError: false,
                uri: null
            );
        } catch (\Throwable $e) {
            error_log("readDirectory failed: " . $e->getMessage());
            throw new ToolExecutionException($input, $e, "Failed to read directory.");
        }
    }

    private function listDirectoryRecursively(string $path, int $depth = 0): string
    {
        $lines = [];
        $items = scandir($path);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;

            $fullPath = $path . DIRECTORY_SEPARATOR . $item;
            $prefix = str_repeat('  ', $depth);
            $type = is_dir($fullPath) ? '[DIR] ' : '[FILE] ';
            $lines[] = $prefix . $type . $item;

            if (is_dir($fullPath)) {
                $lines[] = $this->listDirectoryRecursively($fullPath, $depth + 1);
            }
        }

        return implode("\n", $lines);
    }
}
