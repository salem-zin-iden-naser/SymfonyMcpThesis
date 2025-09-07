<?php

namespace App\Tool\ToolsExecutors;

use PhpLlm\McpSdk\Capability\Tool\ToolCall;
use PhpLlm\McpSdk\Capability\Tool\ToolCallResult;
use PhpLlm\McpSdk\Capability\Tool\ToolExecutorInterface;
use PhpLlm\McpSdk\Exception\ToolExecutionException;

class GetProjectStructureToolExecutor implements ToolExecutorInterface
{
    public function call(ToolCall $input): ToolCallResult
    {
        try {
            $baseDir = realpath(__DIR__ . '/../../../../symfony-demo');
            if (!$baseDir) {
                throw new ToolExecutionException($input, null, "Project root not found.");
            }

            $structure = $this->readDirectory($baseDir, 0, 2); // max 2 levels deep

            return new ToolCallResult(
                result: $structure,
                type: 'text',
                mimeType: 'text/plain',
                isError: false,
                uri: null
            );
        } catch (\Throwable $e) {
            error_log("getProjectStructure failed: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            throw new ToolExecutionException($input, $e, "Could not read project structure.");
        }
    }

    private function readDirectory(string $path, int $depth, int $maxDepth): string
    {
        if ($depth > $maxDepth) return '';

        $output = '';
        $prefix = str_repeat('  ', $depth);
        $items = scandir($path);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $fullPath = $path . '/' . $item;
            $output .= $prefix . '- ' . $item . "\n";

            if (is_dir($fullPath)) {
                $output .= $this->readDirectory($fullPath, $depth + 1, $maxDepth);
            }
        }

        return $output;
    }
}
