<?php
namespace App\Tool\ToolsExecutors;
require __DIR__ . '/../../../vendor/autoload.php';
use PhpLlm\McpSdk\Capability\Tool\ToolExecutorInterface;
use PhpLlm\McpSdk\Capability\Tool\ToolCall;
use PhpLlm\McpSdk\Capability\Tool\ToolCallResult;
use PhpLlm\McpSdk\Exception\ToolExecutionException;

class GetEntitiesToolExecutor implements ToolExecutorInterface
{
    public function call(ToolCall $input): ToolCallResult
    {
        $entityDir = __DIR__ . '/../../../../symfony-demo/src/Entity';
        if (!is_dir($entityDir)) {
            throw new ToolExecutionException(
                $input,
                new \RuntimeException("Entity directory not found: $entityDir")
            );
        }

        $files = scandir($entityDir);
        $entitiesInfo = [];

        foreach ($files as $file) {
            if (str_ends_with($file, '.php')) {
                $entityName = pathinfo($file, PATHINFO_FILENAME);
                $filePath = $entityDir . '/' . $file;
                
                $entityInfo = $this->analyzeEntity($filePath, $entityName);
                $entitiesInfo[] = $entityInfo;
            }
        }

        $resultText = $this->formatEntitiesInfo($entitiesInfo);
        
        return new ToolCallResult(
            (string) $resultText,
            'text',
            'text/plain',
            false,
            null
        );
    }

    private function analyzeEntity(string $filePath, string $entityName): array
    {
        $content = file_get_contents($filePath);
        
        return [
            'name' => $entityName,
            'properties' => $this->extractProperties($content),
            'relationships' => $this->extractRelationships($content),
            'table_name' => $this->extractTableName($content)
        ];
    }

    private function extractProperties(string $content): array
    {
        $properties = [];
        
        // Match private/protected properties with their types
        preg_match_all('/private\s+\??([\\w\\\\]+)\s+\$(\w+)/', $content, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $type = $match[1];
            $name = $match[2];
            
            // Skip collection properties (they're relationships)
            if ($type === 'Collection') {
                continue;
            }
            
            $properties[] = [
                'name' => $name,
                'type' => $this->simplifyType($type)
            ];
        }
        
        return $properties;
    }

    private function extractRelationships(string $content): array
    {
        $relationships = [];
        
        // OneToMany relationships
        if (preg_match_all('/#\[ORM\\\\OneToMany\(targetEntity:\s*(\w+)::class,\s*mappedBy:\s*[\'"](\w+)[\'"].*?\]\s*(?:#\[ORM\\\\OrderBy.*?\]\s*)?private\s+Collection\s+\$(\w+)/s', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $relationships[] = [
                    'type' => 'OneToMany',
                    'property' => $match[3],
                    'target_entity' => $match[1],
                    'mapped_by' => $match[2]
                ];
            }
        }

        // ManyToOne relationships
        if (preg_match_all('/#\[ORM\\\\ManyToOne\(targetEntity:\s*(\w+)::class[^)]*\).*?private\s+\??(\w+)\s+\$(\w+)/s', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $relationships[] = [
                    'type' => 'ManyToOne',
                    'property' => $match[3],
                    'target_entity' => $match[1]
                ];
            }
        }

        // ManyToMany relationships
        if (preg_match_all('/#\[ORM\\\\ManyToMany\(targetEntity:\s*(\w+)::class.*?\]\s*(?:#\[ORM\\\\[^]]+\]\s*)*private\s+Collection\s+\$(\w+)/s', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $relationships[] = [
                    'type' => 'ManyToMany',
                    'property' => $match[2],
                    'target_entity' => $match[1]
                ];
            }
        }

        return $relationships;
    }

    private function extractTableName(string $content): ?string
    {
        if (preg_match('/#\[ORM\\\\Table\(name:\s*[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
            return $matches[1];
        }
        return null;
    }

    private function simplifyType(string $type): string
    {
        // Remove namespace prefixes and make common types more readable
        $type = basename(str_replace('\\', '/', $type));
        
        $typeMap = [
            'Types::INTEGER' => 'int',
            'Types::STRING' => 'string',
            'Types::TEXT' => 'text',
            'DateTimeImmutable' => 'datetime',
            'DateTime' => 'datetime'
        ];

        return $typeMap[$type] ?? $type;
    }

    private function formatEntitiesInfo(array $entitiesInfo): string
    {
        $output = "DOCTRINE ENTITIES OVERVIEW\n";
        $output .= str_repeat("=", 50) . "\n\n";

        foreach ($entitiesInfo as $entity) {
            $output .= "Entity: {$entity['name']}\n";
            
            if ($entity['table_name']) {
                $output .= "Table: {$entity['table_name']}\n";
            }
            
            $output .= "\nProperties:\n";
            foreach ($entity['properties'] as $prop) {
                $output .= "  - {$prop['name']} ({$prop['type']})\n";
            }
            
            if (!empty($entity['relationships'])) {
                $output .= "\nRelationships:\n";
                foreach ($entity['relationships'] as $rel) {
                    $output .= "  - {$rel['property']}: {$rel['type']} -> {$rel['target_entity']}\n";
                    if (isset($rel['mapped_by'])) {
                        $output .= "    (mapped by: {$rel['mapped_by']})\n";
                    }
                }
            }
            
            $output .= "\n" . str_repeat("-", 40) . "\n\n";
        }

        return $output;
    }
}