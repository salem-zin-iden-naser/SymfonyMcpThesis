<?php

namespace App\Bridge;

class PromptBuilder
{
    public function buildContextualPrompt(string $userPrompt, array $mcpContext, string $taskType = 'general'): string
    {
        $context = json_encode($mcpContext, JSON_PRETTY_PRINT);

        return <<<EOT
You are a Symfony expert.

## Project Context
$context

## User Request
$userPrompt

Provide a complete and clean solution.
EOT;
    }

    public function buildVanillaPrompt(string $userPrompt, string $taskType = 'general'): string
    {
        return <<<EOT
You are a Symfony expert.

## User Request
$userPrompt

Provide a complete and clean solution.
EOT;
    }
}
