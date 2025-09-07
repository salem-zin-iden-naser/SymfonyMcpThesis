<?php

namespace App\Tool\ToolsExecutors;

use PhpLlm\McpSdk\Capability\Tool\ToolCall;
use PhpLlm\McpSdk\Capability\Tool\ToolCallResult;
use PhpLlm\McpSdk\Capability\Tool\ToolExecutorInterface;
use PhpLlm\McpSdk\Exception\ToolExecutionException;

class GetContextualCodeAnalysisToolExecutor implements ToolExecutorInterface
{
    public function call(ToolCall $input): ToolCallResult
    {
        try {
            $baseDir = realpath(__DIR__ . '/../../../../symfony-demo');
            if (!$baseDir) {
                throw new \RuntimeException("Base directory not found.");
            }

            $result = [
                'project_info' => $this->analyzeProjectInfo($baseDir),
                'entities' => $this->analyzeEntities($baseDir),
                'architecture' => $this->detectArchitecture($baseDir),
                'patterns' => $this->detectPatterns($baseDir),
                'conventions' => $this->analyzeConventions($baseDir),
            ];

            // Remove empty sections
            $result = array_filter($result, fn($section) => !empty($section));

            return new ToolCallResult(
                result: json_encode($result, JSON_PRETTY_PRINT),
                type: 'text',
                mimeType: 'application/json',
                isError: false,
                uri: null
            );

        } catch (\Throwable $e) {
            error_log("getContextualCodeAnalysis failed: " . $e->getMessage());
            throw new ToolExecutionException($input, $e, "Failed to generate context summary.");
        }
    }

    private function analyzeProjectInfo(string $baseDir): array
    {
        $info = [];
        
        // Get Symfony version and project type
        $composerFile = $baseDir . '/composer.json';
        if (file_exists($composerFile)) {
            $composer = json_decode(file_get_contents($composerFile), true);
            
            if (isset($composer['require']['symfony/framework-bundle'])) {
                $info['symfony_version'] = $composer['require']['symfony/framework-bundle'];
            }
            
            if (isset($composer['name'])) {
                $info['project_name'] = $composer['name'];
            }
            
            if (isset($composer['description'])) {
                $info['description'] = $composer['description'];
            }

            // Detect project type from dependencies
            $info['project_type'] = $this->detectProjectType($composer);
        }

        // Get PHP version
        if (file_exists($baseDir . '/composer.json')) {
            $composer = json_decode(file_get_contents($baseDir . '/composer.json'), true);
            if (isset($composer['require']['php'])) {
                $info['php_version'] = $composer['require']['php'];
            }
        }

        return $info;
    }

    private function detectProjectType(array $composer): string
    {
        $dependencies = array_merge(
            $composer['require'] ?? [],
            $composer['require-dev'] ?? []
        );

        // API Project
        if (isset($dependencies['api-platform/core']) || 
            isset($dependencies['symfony/serializer'])) {
            return 'API Platform / REST API';
        }

        // Full web application
        if (isset($dependencies['symfony/twig-bundle']) && 
            isset($dependencies['symfony/form'])) {
            return 'Full-stack web application';
        }

        // Console application
        if (isset($dependencies['symfony/console']) && 
            !isset($dependencies['symfony/twig-bundle'])) {
            return 'Console application';
        }

        // Microservice
        if (isset($dependencies['symfony/http-kernel']) && 
            count($dependencies) < 10) {
            return 'Microservice / Minimal application';
        }

        return 'Web application';
    }

    private function analyzeEntities(string $baseDir): array
    {
        $entityDir = $baseDir . '/src/Entity';
        if (!is_dir($entityDir)) {
            return [];
        }

        $entities = [];
        foreach (scandir($entityDir) as $file) {
            if (!str_ends_with($file, '.php')) continue;

            $path = $entityDir . '/' . $file;
            $contents = file_get_contents($path);
            $name = pathinfo($file, PATHINFO_FILENAME);

            $entityData = [
                'properties' => $this->extractProperties($contents),
                'relationships' => $this->extractRelationships($contents),
            ];

            // Only add if we found actual data
            if (!empty($entityData['properties']) || !empty($entityData['relationships'])) {
                $constraints = $this->extractConstraints($contents);
                if (!empty($constraints)) {
                    $entityData['validation'] = $constraints;
                }

                $businessRules = $this->inferBusinessRules($contents, $name);
                if (!empty($businessRules)) {
                    $entityData['inferred_purpose'] = $businessRules;
                }

                $entities[$name] = $entityData;
            }
        }

        return $entities;
    }

    private function extractProperties(string $contents): array
    {
        $properties = [];
        
        // Match properties with better accuracy
        if (preg_match_all('/(?:private|protected)\s+(?:\??\w+\s+)?\$(\w+)/m', $contents, $matches)) {
            foreach ($matches[1] as $prop) {
                // Skip common internal properties
                if (!in_array($prop, ['id', 'metadata', 'proxies'])) {
                    $properties[] = $prop;
                }
            }
        }

        return array_unique($properties);
    }

    private function extractRelationships(string $contents): array
    {
        $relations = [];

        // Modern attributes (PHP 8+)
        if (preg_match_all('/#\[ORM\\\\(ManyToOne|OneToMany|ManyToMany|OneToOne)\([^]]*targetEntity[:\s]*([A-Za-z\\\\]+)::class/i', $contents, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $type = $match[1];
                $target = basename(str_replace('\\', '/', $match[2]));
                $relations[] = "$target ($type)";
            }
        }

        // Legacy annotations
        if (preg_match_all('/@ORM\\\\(ManyToOne|OneToMany|ManyToMany|OneToOne)\([^)]*targetEntity[=:\s]*["\']?([A-Za-z\\\\]+)["\']?/i', $contents, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $type = $match[1];
                $target = basename(str_replace('\\', '/', $match[2]));
                $relations[] = "$target ($type)";
            }
        }

        return array_unique($relations);
    }

    private function extractConstraints(string $contents): array
    {
        $constraints = [];

        // Modern attributes
        if (preg_match_all('/#\[Assert\\\\(\w+)/i', $contents, $matches)) {
            $constraints = array_merge($constraints, $matches[1]);
        }

        // Legacy annotations
        if (preg_match_all('/@Assert\\\\(\w+)/i', $contents, $matches)) {
            $constraints = array_merge($constraints, $matches[1]);
        }

        return array_unique($constraints);
    }

    private function inferBusinessRules(string $contents, string $entityName): array
    {
        $rules = [];

        // Only add rules if we have strong evidence
        if (preg_match('/getAuthor|setAuthor|author.*User/i', $contents)) {
            $rules[] = 'Has authorship/ownership concept';
        }

        if (preg_match('/publishedAt|published|status.*publish/i', $contents)) {
            $rules[] = 'Has publication/status workflow';
        }

        if (preg_match('/email.*unique|UniqueEntity.*email/i', $contents)) {
            $rules[] = 'Email uniqueness required';
        }

        if (preg_match('/slug|Slug.*constraint/i', $contents)) {
            $rules[] = 'Uses slugs for URL generation';
        }

        if (preg_match('/createdAt|updatedAt|timestamp/i', $contents)) {
            $rules[] = 'Has timestamp tracking';
        }

        return $rules;
    }

    private function detectArchitecture(string $baseDir): array
    {
        $architecture = [];

        // Security
        $securityInfo = $this->analyzeSecuritySetup($baseDir);
        if ($securityInfo) {
            $architecture['security'] = $securityInfo;
        }

        // Database
        $dbInfo = $this->analyzeDatabaseSetup($baseDir);
        if ($dbInfo) {
            $architecture['database'] = $dbInfo;
        }

        // Forms
        $formInfo = $this->analyzeFormSetup($baseDir);
        if ($formInfo) {
            $architecture['forms'] = $formInfo;
        }

        // API
        $apiInfo = $this->analyzeApiSetup($baseDir);
        if ($apiInfo) {
            $architecture['api'] = $apiInfo;
        }

        // Templating
        $templateInfo = $this->analyzeTemplateSetup($baseDir);
        if ($templateInfo) {
            $architecture['templating'] = $templateInfo;
        }

        return $architecture;
    }

    private function analyzeSecuritySetup(string $baseDir): ?array
    {
        $securityFile = $baseDir . '/config/packages/security.yaml';
        if (!file_exists($securityFile)) {
            return null;
        }

        $content = file_get_contents($securityFile);
        $info = [];

        // Authentication methods
        if (str_contains($content, 'form_login')) {
            $info['authentication'] = 'Form login';
        } elseif (str_contains($content, 'json_login')) {
            $info['authentication'] = 'JSON login (API)';
        } elseif (str_contains($content, 'http_basic')) {
            $info['authentication'] = 'HTTP Basic';
        }

        // User provider
        if (preg_match('/entity:.*class:\s*([A-Za-z\\\\]+)/i', $content, $matches)) {
            $userClass = basename(str_replace('\\', '/', $matches[1]));
            $info['user_provider'] = "Doctrine entity ($userClass)";
        }

        // Role hierarchy
        if (str_contains($content, 'role_hierarchy')) {
            $info['authorization'] = 'Role-based with hierarchy';
        } elseif (preg_match_all('/ROLE_\w+/', $content, $matches)) {
            $roles = array_unique($matches[0]);
            $info['authorization'] = 'Role-based (' . implode(', ', $roles) . ')';
        }

        return empty($info) ? null : $info;
    }

    private function analyzeDatabaseSetup(string $baseDir): ?array
    {
        $doctrineFile = $baseDir . '/config/packages/doctrine.yaml';
        if (!file_exists($doctrineFile)) {
            return null;
        }

        $content = file_get_contents($doctrineFile);
        $info = [];

        // Database driver
        if (preg_match('/driver:\s*pdo_(\w+)/i', $content, $matches)) {
            $info['driver'] = strtoupper($matches[1]);
        }

        // Migrations
        if (is_dir($baseDir . '/migrations')) {
            $migrationCount = count(glob($baseDir . '/migrations/*.php'));
            if ($migrationCount > 0) {
                $info['migrations'] = "$migrationCount migration files";
            }
        }

        return empty($info) ? null : $info;
    }

    private function analyzeFormSetup(string $baseDir): ?array
    {
        $formDir = $baseDir . '/src/Form';
        if (!is_dir($formDir)) {
            return null;
        }

        $formFiles = glob($formDir . '/*Type.php');
        if (empty($formFiles)) {
            return null;
        }

        return [
            'form_classes' => count($formFiles),
            'pattern' => 'FormType classes'
        ];
    }

    private function analyzeApiSetup(string $baseDir): ?array
    {
        $composerFile = $baseDir . '/composer.json';
        if (!file_exists($composerFile)) {
            return null;
        }

        $composer = json_decode(file_get_contents($composerFile), true);
        $dependencies = array_merge(
            $composer['require'] ?? [],
            $composer['require-dev'] ?? []
        );

        $info = [];

        if (isset($dependencies['api-platform/core'])) {
            $info['platform'] = 'API Platform';
        }

        if (isset($dependencies['symfony/serializer'])) {
            $info['serialization'] = 'Symfony Serializer';
        }

        if (isset($dependencies['jms/serializer-bundle'])) {
            $info['serialization'] = 'JMS Serializer';
        }

        return empty($info) ? null : $info;
    }

    private function analyzeTemplateSetup(string $baseDir): ?array
    {
        $templateDir = $baseDir . '/templates';
        if (!is_dir($templateDir)) {
            return null;
        }

        $twigFiles = $this->findFilesRecursive($templateDir, '*.twig');
        if (empty($twigFiles)) {
            return null;
        }

        $info = [
            'template_count' => count($twigFiles),
            'engine' => 'Twig'
        ];

        if (file_exists($templateDir . '/base.html.twig')) {
            $info['inheritance'] = 'Base template found';
        }

        return $info;
    }

    private function detectPatterns(string $baseDir): array
    {
        $patterns = [];

        // Repository pattern
        $repoInfo = $this->analyzeRepositoryPattern($baseDir);
        if ($repoInfo) {
            $patterns['repository'] = $repoInfo;
        }

        // Service pattern
        $serviceInfo = $this->analyzeServicePattern($baseDir);
        if ($serviceInfo) {
            $patterns['services'] = $serviceInfo;
        }

        // Event system
        $eventInfo = $this->analyzeEventPattern($baseDir);
        if ($eventInfo) {
            $patterns['events'] = $eventInfo;
        }

        return $patterns;
    }

    private function analyzeRepositoryPattern(string $baseDir): ?array
    {
        $repoDir = $baseDir . '/src/Repository';
        if (!is_dir($repoDir)) {
            return null;
        }

        $repoFiles = glob($repoDir . '/*Repository.php');
        if (empty($repoFiles)) {
            return null;
        }

        $customMethodCount = 0;
        foreach ($repoFiles as $file) {
            $contents = file_get_contents($file);
            // Count methods that aren't standard Doctrine
            if (preg_match_all('/public function (?!find|__construct|createQueryBuilder)(\w+)/m', $contents, $matches)) {
                $customMethodCount += count($matches[1]);
            }
        }

        return [
            'repository_count' => count($repoFiles),
            'custom_methods' => $customMethodCount > 0 ? $customMethodCount : 'None detected'
        ];
    }

    private function analyzeServicePattern(string $baseDir): ?array
    {
        $serviceDir = $baseDir . '/src/Service';
        if (!is_dir($serviceDir)) {
            return null;
        }

        $serviceFiles = glob($serviceDir . '/*.php');
        return empty($serviceFiles) ? null : [
            'service_classes' => count($serviceFiles)
        ];
    }

    private function analyzeEventPattern(string $baseDir): ?array
    {
        $eventDir = $baseDir . '/src/EventListener';
        $subscriberDir = $baseDir . '/src/EventSubscriber';
        
        $listeners = is_dir($eventDir) ? glob($eventDir . '/*.php') : [];
        $subscribers = is_dir($subscriberDir) ? glob($subscriberDir . '/*.php') : [];
        
        if (empty($listeners) && empty($subscribers)) {
            return null;
        }

        $info = [];
        if (!empty($listeners)) {
            $info['listeners'] = count($listeners);
        }
        if (!empty($subscribers)) {
            $info['subscribers'] = count($subscribers);
        }

        return $info;
    }

    private function analyzeConventions(string $baseDir): array
    {
        $conventions = [];

        // Naming conventions
        $namingInfo = $this->analyzeNamingConventions($baseDir);
        if ($namingInfo) {
            $conventions['naming'] = $namingInfo;
        }

        // Code quality tools
        $qualityInfo = $this->analyzeCodeQuality($baseDir);
        if ($qualityInfo) {
            $conventions['code_quality'] = $qualityInfo;
        }

        return $conventions;
    }

    private function analyzeNamingConventions(string $baseDir): ?array
    {
        $info = [];

        // Check entities
        $entityFiles = glob($baseDir . '/src/Entity/*.php');
        if (!empty($entityFiles) && $this->allMatchPattern($entityFiles, '/^[A-Z][a-zA-Z0-9]+\.php$/')) {
            $info['entities'] = 'PascalCase';
        }

        // Check controllers
        $controllerFiles = glob($baseDir . '/src/Controller/*Controller.php');
        if (!empty($controllerFiles)) {
            $info['controllers'] = 'PascalCase with Controller suffix';
        }

        return empty($info) ? null : $info;
    }

    private function analyzeCodeQuality(string $baseDir): ?array
    {
        $tools = [];

        if (file_exists($baseDir . '/.php-cs-fixer.dist.php')) {
            $tools[] = 'PHP-CS-Fixer';
        }

        if (file_exists($baseDir . '/phpstan.neon') || file_exists($baseDir . '/phpstan.neon.dist')) {
            $tools[] = 'PHPStan';
        }

        if (file_exists($baseDir . '/rector.php')) {
            $tools[] = 'Rector';
        }

        if (file_exists($baseDir . '/phpunit.xml.dist')) {
            $tools[] = 'PHPUnit';
        }

        return empty($tools) ? null : $tools;
    }

    private function findFilesRecursive(string $dir, string $pattern): array
    {
        return glob($dir . '/**/' . $pattern, GLOB_BRACE) ?: [];
    }

    private function allMatchPattern(array $files, string $pattern): bool
    {
        return count($files) > 0 && array_reduce(
            $files,
            fn($carry, $f) => $carry && preg_match($pattern, basename($f)),
            true
        );
    }
}