<?php

namespace App\Tool\ToolsExecutors;

use PhpLlm\McpSdk\Capability\Tool\ToolCall;
use PhpLlm\McpSdk\Capability\Tool\ToolCallResult;
use PhpLlm\McpSdk\Capability\Tool\ToolExecutorInterface;
use PhpLlm\McpSdk\Exception\ToolExecutionException;

class SearchCodeToolExecutor implements ToolExecutorInterface
{
    private array $extensions = ['php', 'yaml', 'twig', 'json', 'xml', 'env'];

    public function call(ToolCall $input): ToolCallResult
    {
        $pattern = $input->arguments['pattern'] ?? null;

        if (!$pattern || !is_string($pattern)) {
            throw new ToolExecutionException($input, null, "Missing or invalid 'pattern'.");
        }

        try {
            $baseDir = realpath(__DIR__ . '/../../../../symfony-demo');
            if (!$baseDir) {
                throw new ToolExecutionException($input, null, "Project directory not found.");
            }

            $matches = [];
            $this->searchInDirectory($baseDir, $pattern, $matches);

            $result = empty($matches)
                ? "No matches found for pattern \"$pattern\"."
                : "Matches for \"$pattern\":\n\n" . implode("\n", $matches);

            return new ToolCallResult(
                result: $result,
                type: 'text',
                mimeType: 'text/plain',
                isError: false,
                uri: null
            );
        } catch (\Throwable $e) {
            error_log("searchCode failed: " . $e->getMessage());
            throw new ToolExecutionException($input, $e, "Failed to search code.");
        }
    }

    private function searchInDirectory(string $dir, string $pattern, array &$matches): void
    {
        foreach (scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..') continue;

            $path = $dir . '/' . $entry;
            if (is_dir($path)) {
                $this->searchInDirectory($path, $pattern, $matches);
            } elseif ($this->isValidExtension($path)) {
                $lines = @file($path);
                if (!$lines) continue;

                foreach ($lines as $i => $line) {
                    if (stripos($line, $pattern) !== false) {
                        $relative = str_replace(realpath(__DIR__ . '/../../../../symfony-demo') . '/', '', $path);
                        $matches[] = "$relative:" . ($i + 1) . ": " . trim($line);
                    }
                }
            }
        }
    }

    private function isValidExtension(string $file): bool
    {
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        return in_array($ext, $this->extensions, true);
    }
}
