<?php
namespace App\Bridge;

use PhpLlm\McpSdk\Capability\Tool\ToolExecutorInterface;
use PhpLlm\McpSdk\Capability\Tool\CollectionInterface;
use PhpLlm\McpSdk\Capability\Tool\ToolCall;

class EnhancedMCPContextCollector
{
    private array $contextStrategies = [];
    
    public function __construct(
        private ToolExecutorInterface $toolExecutor,
        private CollectionInterface $toolCollection
    ) {
        $this->initializeStrategies();
    }

    public function collectSmartContext(string $prompt, string $taskType, array $explicitTools = []): array
    {
        // 1. Analyze prompt to understand what's needed
        $requiredContext = $this->analyzePromptNeeds($prompt, $taskType);
        
        // 2. Select relevant tools intelligently
        $toolsToRun = $this->selectRelevantTools($requiredContext, $explicitTools);
        
        // 3. Execute tools with dependency awareness
        $rawContext = $this->executeToolsWithDependencies($toolsToRun);
        
        // 4. Filter and rank context by relevance
        $filteredContext = $this->filterContextByRelevance($rawContext, $requiredContext);
        
        // 5. Structure context for optimal LLM consumption
        return $this->structureContext($filteredContext, $taskType);
    }

    private function analyzePromptNeeds(string $prompt, string $taskType): array
    {
        $needs = [];
        $prompt = strtolower($prompt);
        
        // Entity analysis
        if (preg_match('/\b(entity|entities|model|database)\b/', $prompt)) {
            $needs['entities'] = ['priority' => 'high', 'reason' => 'Entity references detected'];
        }
        
        // Controller analysis
        if (preg_match('/\b(controller|route|endpoint|api)\b/', $prompt)) {
            $needs['controllers'] = ['priority' => 'high', 'reason' => 'Controller/routing references'];
            $needs['routes'] = ['priority' => 'high', 'reason' => 'Route structure needed'];
        }
        
        // Specific entity mentions
        if (preg_match('/\b(blog|post|user|product|category)\b/', $prompt)) {
            $needs['specific_entities'] = ['priority' => 'high', 'entities' => $this->extractEntityNames($prompt)];
        }
        
        // CRUD operations
        if (preg_match('/\b(crud|create|update|delete|list|manage)\b/', $prompt)) {
            $needs['crud_patterns'] = ['priority' => 'medium', 'reason' => 'CRUD operations mentioned'];
        }
        
        // Authentication/Security
        if (preg_match('/\b(auth|login|security|permission|role)\b/', $prompt)) {
            $needs['security'] = ['priority' => 'high', 'reason' => 'Security context needed'];
        }
        
        return $needs;
    }

    private function selectRelevantTools(array $needs, array $explicitTools): array
    {
        $toolMap = [
            'entities' => ['Get Entities', 'Get File Content'],
            'controllers' => ['Get Controllers', 'Get Routes'],
            'routes' => ['Get Routes'],
            'security' => ['Get User Roles', 'Get File Content'],
            'crud_patterns' => ['Get Controllers', 'Get Entities', 'Get Routes'],
            'specific_entities' => ['Get Entities', 'Get File Content'],
        ];
        
        $selectedTools = $explicitTools;
        
        foreach ($needs as $needType => $needData) {
            if (isset($toolMap[$needType])) {
                $priority = $needData['priority'] ?? 'medium';
                foreach ($toolMap[$needType] as $tool) {
                    $selectedTools[$tool] = $priority;
                }
            }
        }
        
        return $selectedTools;
    }

    private function executeToolsWithDependencies(array $tools): array
    {
        $context = [];
        $executed = [];
        
        // Define tool dependencies
        $dependencies = [
            'Get File Content' => ['Get Entities', 'Get Controllers'], // Needs to know what files to get
            'Get Contextual Code Analysis' => ['Get Entities', 'Get Controllers', 'Get Routes'],
        ];
        
        // Execute tools in dependency order
        foreach ($tools as $toolName => $priority) {
            if (in_array($toolName, $executed)) continue;
            
            // Execute dependencies first
            if (isset($dependencies[$toolName])) {
                foreach ($dependencies[$toolName] as $dep) {
                    if (isset($tools[$dep]) && !in_array($dep, $executed)) {
                        $context[$dep] = $this->executeTool($dep);
                        $executed[] = $dep;
                    }
                }
            }
            
            // Execute the tool
            $context[$toolName] = $this->executeTool($toolName, $context);
            $executed[] = $toolName;
        }
        
        return $context;
    }

    private function executeTool(string $toolName, array $existingContext = []): array
    {
        try {
            $toolCall = new ToolCall(
                id: uniqid('tool_', true),
                name: $toolName,
                arguments: $this->buildToolArguments($toolName, $existingContext)
            );
            
            $result = $this->toolExecutor->call($toolCall);
            $data = json_decode($result->result, true) ?? $result->result;
            
            return [
                'data' => $data,
                'success' => true,
                'execution_time' => microtime(true),
                'token_count' => $this->estimateTokens($data)
            ];
        } catch (\Throwable $e) {
            return [
                'error' => $e->getMessage(),
                'success' => false,
                'execution_time' => 0,
                'token_count' => 0
            ];
        }
    }

    private function filterContextByRelevance(array $rawContext, array $needs): array
    {
        $filtered = [];
        $tokenBudget = 8000; // Reserve tokens for response
        $usedTokens = 0;
        
        // Sort by priority and relevance
        $prioritizedContext = $this->prioritizeContext($rawContext, $needs);
        
        foreach ($prioritizedContext as $toolName => $data) {
            if (!$data['success']) continue;
            
            $tokens = $data['token_count'];
            if ($usedTokens + $tokens > $tokenBudget) {
                // Try to summarize or truncate
                $data['data'] = $this->summarizeContext($data['data'], $tokenBudget - $usedTokens);
                $tokens = $this->estimateTokens($data['data']);
            }
            
            if ($usedTokens + $tokens <= $tokenBudget) {
                $filtered[$toolName] = $data;
                $usedTokens += $tokens;
            }
        }
        
        return $filtered;
    }

    private function structureContext(array $context, string $taskType): array
    {
        return [
            'context_summary' => $this->generateContextSummary($context),
            'relevant_files' => $this->extractRelevantFiles($context),
            'architectural_patterns' => $this->identifyPatterns($context),
            'constraints' => $this->identifyConstraints($context),
            'raw_context' => $context,
            'token_usage' => [
                'total_tokens' => array_sum(array_column($context, 'token_count')),
                'tools_executed' => count($context),
                'context_efficiency' => $this->calculateEfficiency($context)
            ]
        ];
    }

    private function generateContextSummary(array $context): string
    {
        $summary = [];
        
        if (isset($context['Get Entities']['data'])) {
            $entities = is_array($context['Get Entities']['data']) 
                ? $context['Get Entities']['data'] 
                : explode("\n", $context['Get Entities']['data']);
            $summary[] = "Available entities: " . implode(', ', array_filter($entities));
        }
        
        if (isset($context['Get Controllers']['data'])) {
            $controllers = is_array($context['Get Controllers']['data']) 
                ? $context['Get Controllers']['data'] 
                : explode("\n", $context['Get Controllers']['data']);
            $summary[] = "Existing controllers: " . implode(', ', array_filter($controllers));
        }
        
        if (isset($context['Get Routes']['data'])) {
            $summary[] = "Route structure analyzed";
        }
        
        return implode(". ", $summary);
    }

    private function extractEntityNames(string $prompt): array
    {
        // Extract potential entity names from prompt
        preg_match_all('/\b([A-Z][a-zA-Z]*(?:Post|User|Product|Category|Entity))\b/', $prompt, $matches);
        return $matches[1] ?? [];
    }

    private function estimateTokens($data): int
    {
        if (is_array($data)) {
            $data = json_encode($data, JSON_PRETTY_PRINT);
        }
        return (int) (strlen($data) / 4); // Rough estimation
    }

    private function prioritizeContext(array $rawContext, array $needs): array
    {
        // Sort context by priority and relevance score
        uksort($rawContext, function($a, $b) use ($needs) {
            $scoreA = $this->calculateRelevanceScore($a, $needs);
            $scoreB = $this->calculateRelevanceScore($b, $needs);
            return $scoreB <=> $scoreA; // Descending order
        });
        
        return $rawContext;
    }

    private function calculateRelevanceScore(string $toolName, array $needs): int
    {
        $score = 0;
        foreach ($needs as $need => $data) {
            if (strpos(strtolower($toolName), strtolower($need)) !== false) {
                $score += ($data['priority'] === 'high') ? 10 : 5;
            }
        }
        return $score;
    }

    private function summarizeContext($data, int $maxTokens): array
    {
        // Implement context summarization logic
        if (is_array($data)) {
            return array_slice($data, 0, max(1, $maxTokens / 100));
        }
        return substr($data, 0, $maxTokens * 4);
    }

    private function extractRelevantFiles(array $context): array
    {
        // Extract file paths that might be relevant
        $files = [];
        foreach ($context as $toolData) {
            if (isset($toolData['data']) && is_array($toolData['data'])) {
                // Look for file paths in the data
                array_walk_recursive($toolData['data'], function($value) use (&$files) {
                    if (is_string($value) && preg_match('/\.php$/', $value)) {
                        $files[] = $value;
                    }
                });
            }
        }
        return array_unique($files);
    }

    private function identifyPatterns(array $context): array
    {
        // Identify architectural patterns from context
        return [
            'mvc_pattern' => $this->detectMVCPattern($context),
            'repository_pattern' => $this->detectRepositoryPattern($context),
            'service_layer' => $this->detectServiceLayer($context),
        ];
    }

    private function identifyConstraints(array $context): array
    {
        // Identify constraints and requirements
        return [
            'symfony_version' => $this->detectSymfonyVersion($context),
            'required_bundles' => $this->detectRequiredBundles($context),
            'security_requirements' => $this->detectSecurityRequirements($context),
        ];
    }

    private function calculateEfficiency(array $context): float
    {
        $totalTools = count($context);
        $successfulTools = count(array_filter($context, fn($c) => $c['success']));
        return $totalTools > 0 ? ($successfulTools / $totalTools) : 0;
    }

    private function buildToolArguments(string $toolName, array $context): array
    {
        $args = [];
        
        // Customize arguments based on existing context
        if ($toolName === 'Get File Content' && isset($context['Get Entities'])) {
            // Use entity information to get specific files
            $entities = $context['Get Entities']['data'] ?? [];
            if (is_array($entities) && !empty($entities)) {
                $args['files'] = array_slice($entities, 0, 3); // Limit to avoid overwhelming
            }
        }
        
        return $args;
    }

    private function initializeStrategies(): void
    {
        $this->contextStrategies = [
            'controller' => ['Get Entities', 'Get Routes', 'Get Controllers'],
            'entity' => ['Get Entities', 'Get Project Structure'],
            'service' => ['Get Controllers', 'Get Project Structure'],
            'form' => ['Get Entities', 'Get Controllers'],
            'api' => ['Get Routes', 'Get Controllers', 'Get User Roles'],
        ];
    }

    // Placeholder methods for pattern detection
    private function detectMVCPattern(array $context): bool { return true; }
    private function detectRepositoryPattern(array $context): bool { return false; }
    private function detectServiceLayer(array $context): bool { return false; }
    private function detectSymfonyVersion(array $context): string { return '6.4'; }
    private function detectRequiredBundles(array $context): array { return []; }
    private function detectSecurityRequirements(array $context): array { return []; }
}