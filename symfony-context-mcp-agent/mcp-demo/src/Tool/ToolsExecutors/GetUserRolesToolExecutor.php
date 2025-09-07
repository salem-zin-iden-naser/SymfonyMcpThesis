<?php

namespace App\Tool\ToolsExecutors;

use PhpLlm\McpSdk\Capability\Tool\ToolCall;
use PhpLlm\McpSdk\Capability\Tool\ToolCallResult;
use PhpLlm\McpSdk\Capability\Tool\ToolExecutorInterface;
use PhpLlm\McpSdk\Exception\ToolExecutionException;

class GetUserRolesToolExecutor implements ToolExecutorInterface
{
    public function call(ToolCall $input): ToolCallResult
    {
        try {
            // These are hardcoded based on the demo app User.php
            $roles = [
                'ROLE_USER' => 'Default role for all users',
                'ROLE_ADMIN' => 'Admin role for content management',
            ];

            $output = "Available User Roles:\n\n";
            foreach ($roles as $code => $desc) {
                $output .= "- $code: $desc\n";
            }

            return new ToolCallResult(
                result: $output,
                type: 'text',
                mimeType: 'text/plain',
                isError: false,
                uri: null
            );
        } catch (\Throwable $e) {
            error_log("getUserRoles failed: " . $e->getMessage());
            throw new ToolExecutionException($input, $e, "Failed to fetch user roles.");
        }
    }
}
