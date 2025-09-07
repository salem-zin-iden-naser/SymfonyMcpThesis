<?php
namespace App\Evaluation;

use App\DTO\LLMResponse;
use App\Evaluation\TestScenario;

class AdvancedEvaluationMetricsCalculator
{
    public function calculate(
        TestScenario $scenario,
        LLMResponse $mcp,
        LLMResponse $vanilla
    ): array {
        return [
            // Basic metrics (existing)
            'basic_metrics' => $this->calculateBasicMetrics($mcp, $vanilla),
            
            // Code quality metrics
            'code_quality' => $this->analyzeCodeQuality($mcp, $vanilla),
            
            // Context utilization metrics
            'context_utilization' => $this->analyzeContextUtilization($mcp, $scenario),
            
            // Symfony-specific metrics
            'symfony_compliance' => $this->analyzeSymfonyCompliance($mcp, $vanilla),
            
            // Functional correctness
            'functional_correctness' => $this->analyzeFunctionalCorrectness($mcp, $vanilla, $scenario),
            
            // Overall scoring
            'overall_score' => $this->calculateOverallScore($mcp, $vanilla, $scenario),
            
            'timestamp' => (new \DateTime())->format(\DateTime::ATOM),
        ];
    }

    private function calculateBasicMetrics(LLMResponse $mcp, LLMResponse $vanilla): array
    {
        return [
            'response_time_diff' => $mcp->getResponseTime() - $vanilla->getResponseTime(),
            'token_count_diff' => $mcp->getTokenCount() - $vanilla->getTokenCount(),
            'content_length_diff' => strlen($mcp->getContent()) - strlen($vanilla->getContent()),
            'response_time_improvement' => $this->calculateImprovement($vanilla->getResponseTime(), $mcp->getResponseTime()),
        ];
    }

    private function analyzeCodeQuality(LLMResponse $mcp, LLMResponse $vanilla): array
    {
        return [
            'mcp_quality' => $this->assessCodeQuality($mcp->getContent()),
            'vanilla_quality' => $this->assessCodeQuality($vanilla->getContent()),
            'quality_improvement' => $this->compareQuality($mcp->getContent(), $vanilla->getContent()),
        ];
    }

    private function assessCodeQuality(string $code): array
    {
        return [
            'syntax_correctness' => $this->checkSyntax($code),
            'psr_compliance' => $this->checkPSRCompliance($code),
            'complexity_score' => $this->calculateComplexity($code),
            'documentation_score' => $this->checkDocumentation($code),
            'security_score' => $this->checkSecurity($code),
            'maintainability_score' => $this->checkMaintainability($code),
        ];
    }

    private function analyzeContextUtilization(LLMResponse $mcp, TestScenario $scenario): array
    {
        $mcpContext = $mcp->getMcpContext();
        if (!$mcpContext) {
            return ['context_used' => false, 'utilization_score' => 0];
        }

        $content = $mcp->getContent();
        $contextUtilization = [];

        // Check if entities from context are mentioned in the generated code
        if (isset($mcpContext['raw_context']['Get Entities']['data'])) {
            $entities = $mcpContext['raw_context']['Get Entities']['data'];
            $entityUsage = $this->checkEntityUsage($content, $entities);
            $contextUtilization['entities'] = $entityUsage;
        }

        // Check if routes from context are referenced
        if (isset($mcpContext['raw_context']['Get Routes']['data'])) {
            $routes = $mcpContext['raw_context']['Get Routes']['data'];
            $routeUsage = $this->checkRouteUsage($content, $routes);
            $contextUtilization['routes'] = $routeUsage;
        }

        // Check if controller patterns are followed
        if (isset($mcpContext['raw_context']['Get Controllers']['data'])) {
            $controllers = $mcpContext['raw_context']['Get Controllers']['data'];
            $controllerPatterns = $this->checkControllerPatterns($content, $controllers);
            $contextUtilization['controller_patterns'] = $controllerPatterns;
        }

        return [
            'context_used' => !empty($contextUtilization),
            'utilization_details' => $contextUtilization,
            'utilization_score' => $this->calculateUtilizationScore($contextUtilization),
            'context_relevance' => $this->assessContextRelevance($mcpContext, $scenario),
        ];
    }

    private function analyzeSymfonyCompliance(LLMResponse $mcp, LLMResponse $vanilla): array
    {
        return [
            'mcp_compliance' => $this->assessSymfonyCompliance($mcp->getContent()),
            'vanilla_compliance' => $this->assessSymfonyCompliance($vanilla->getContent()),
            'compliance_improvement' => $this->compareSymfonyCompliance($mcp->getContent(), $vanilla->getContent()),
        ];
    }

    private function assessSymfonyCompliance(string $code): array
    {
        return [
            'service_pattern_usage' => $this->checkServicePatterns($code),
            'dependency_injection' => $this->checkDependencyInjection($code),
            'attribute_usage' => $this->checkAttributeUsage($code),
            'event_dispatcher_usage' => $this->checkEventDispatcherUsage($code),
            'form_component_usage' => $this->checkFormComponentUsage($code),
            'security_component_usage' => $this->checkSecurityComponentUsage($code),
            'doctrine_integration' => $this->checkDoctrineIntegration($code),
            'controller_best_practices' => $this->checkControllerBestPractices($code),
            'response_handling' => $this->checkResponseHandling($code),
            'validation_usage' => $this->checkValidationUsage($code),
        ];
    }

    private function analyzeFunctionalCorrectness(LLMResponse $mcp, LLMResponse $vanilla, TestScenario $scenario): array
    {
        return [
            'mcp_correctness' => $this->assessFunctionalCorrectness($mcp->getContent(), $scenario),
            'vanilla_correctness' => $this->assessFunctionalCorrectness($vanilla->getContent(), $scenario),
            'correctness_improvement' => $this->compareFunctionalCorrectness($mcp->getContent(), $vanilla->getContent(), $scenario),
        ];
    }

    private function assessFunctionalCorrectness(string $code, TestScenario $scenario): array
    {
        return [
            'requirement_coverage' => $this->checkRequirementCoverage($code, $scenario),
            'edge_case_handling' => $this->checkEdgeCaseHandling($code),
            'error_handling' => $this->checkErrorHandling($code),
            'input_validation' => $this->checkInputValidation($code),
            'business_logic_accuracy' => $this->checkBusinessLogicAccuracy($code, $scenario),
            'api_contract_compliance' => $this->checkApiContractCompliance($code, $scenario),
        ];
    }

    private function calculateOverallScore(LLMResponse $mcp, LLMResponse $vanilla, TestScenario $scenario): array
    {
        $mcpQuality = $this->assessCodeQuality($mcp->getContent());
        $vanillaQuality = $this->assessCodeQuality($vanilla->getContent());
        
        $mcpSymfony = $this->assessSymfonyCompliance($mcp->getContent());
        $vanillaSymfony = $this->assessSymfonyCompliance($vanilla->getContent());
        
        $mcpCorrectness = $this->assessFunctionalCorrectness($mcp->getContent(), $scenario);
        $vanillaCorrectness = $this->assessFunctionalCorrectness($vanilla->getContent(), $scenario);
        
        $contextUtilization = $this->analyzeContextUtilization($mcp, $scenario);

        $mcpScore = $this->calculateCompositeScore($mcpQuality, $mcpSymfony, $mcpCorrectness, $contextUtilization);
        $vanillaScore = $this->calculateCompositeScore($vanillaQuality, $vanillaSymfony, $vanillaCorrectness, []);

        return [
            'mcp_score' => $mcpScore,
            'vanilla_score' => $vanillaScore,
            'improvement' => $mcpScore - $vanillaScore,
            'improvement_percentage' => $vanillaScore > 0 ? (($mcpScore - $vanillaScore) / $vanillaScore) * 100 : 0,
        ];
    }

    // Helper methods for code quality assessment
    private function checkSyntax(string $code): array
    {
        $errors = [];
        $score = 1.0;
        
        // Basic PHP syntax check
        if (strpos($code, '<?php') === false && strpos($code, '<?=') === false) {
            $errors[] = 'Missing PHP opening tag';
            $score -= 0.1;
        }
        
        // Check for common syntax issues
        $syntaxPatterns = [
            '/[^;]\s*\}/' => 'Missing semicolon before closing brace',
            '/\$[a-zA-Z_][a-zA-Z0-9_]*\s*=\s*[^;]*\n/' => 'Missing semicolon at end of assignment',
            '/\bfunction\s+[a-zA-Z_][a-zA-Z0-9_]*\s*\([^)]*\)\s*[^{]/' => 'Missing opening brace for function',
        ];
        
        foreach ($syntaxPatterns as $pattern => $error) {
            if (preg_match($pattern, $code)) {
                $errors[] = $error;
                $score -= 0.1;
            }
        }

        return [
            'score' => max(0, $score),
            'errors' => $errors,
            'is_valid' => empty($errors),
        ];
    }

    private function checkPSRCompliance(string $code): array
    {
        $violations = [];
        $score = 1.0;
        
        // PSR-12 checks
        $psrChecks = [
            '/^[^\r\n]*\r\n/' => 'Should use Unix line endings (LF)',
            '/\t/' => 'Should use 4 spaces instead of tabs',
            '/\bclass\s+[a-z]/' => 'Class names should start with uppercase',
            '/\bfunction\s+[A-Z]/' => 'Method names should be camelCase',
            '/[^\s]\{/' => 'Opening brace should be on new line for classes/methods',
        ];
        
        foreach ($psrChecks as $pattern => $violation) {
            if (preg_match($pattern, $code)) {
                $violations[] = $violation;
                $score -= 0.15;
            }
        }

        return [
            'score' => max(0, $score),
            'violations' => $violations,
            'compliance_level' => $score > 0.8 ? 'high' : ($score > 0.5 ? 'medium' : 'low'),
        ];
    }

    private function calculateComplexity(string $code): array
    {
        $cyclomaticComplexity = 1; // Base complexity
        
        // Count decision points
        $complexityPatterns = [
            '/\bif\s*\(/' => 1,
            '/\belse\s*if\s*\(/' => 1,
            '/\bfor\s*\(/' => 1,
            '/\bforeach\s*\(/' => 1,
            '/\bwhile\s*\(/' => 1,
            '/\bdo\s+/' => 1,
            '/\bswitch\s*\(/' => 1,
            '/\bcase\s+/' => 1,
            '/\bcatch\s*\(/' => 1,
            '/\?\s*.*\s*:/' => 1, // Ternary operator
            '/&&/' => 1,
            '/\|\|/' => 1,
        ];
        
        foreach ($complexityPatterns as $pattern => $weight) {
            $matches = preg_match_all($pattern, $code);
            $cyclomaticComplexity += $matches * $weight;
        }
        
        $complexityRating = $cyclomaticComplexity <= 10 ? 'low' : 
                           ($cyclomaticComplexity <= 20 ? 'medium' : 'high');
        
        return [
            'cyclomatic_complexity' => $cyclomaticComplexity,
            'rating' => $complexityRating,
            'score' => max(0, 1 - ($cyclomaticComplexity / 30)), // Normalize to 0-1
        ];
    }

    private function checkDocumentation(string $code): array
    {
        $docScore = 0;
        $totalMethods = preg_match_all('/\bfunction\s+[a-zA-Z_][a-zA-Z0-9_]*\s*\(/', $code);
        $documentedMethods = preg_match_all('/\/\*\*[\s\S]*?\*\/\s*\bfunction/', $code);
        
        if ($totalMethods > 0) {
            $docScore = $documentedMethods / $totalMethods;
        }
        
        $hasClassDoc = preg_match('/\/\*\*[\s\S]*?\*\/\s*class/', $code) > 0;
        $hasPropertyDoc = preg_match('/\/\*\*[\s\S]*?\*\/\s*(?:private|protected|public)\s+\$/', $code) > 0;
        
        return [
            'method_documentation_ratio' => $docScore,
            'has_class_documentation' => $hasClassDoc,
            'has_property_documentation' => $hasPropertyDoc,
            'score' => ($docScore + ($hasClassDoc ? 0.2 : 0) + ($hasPropertyDoc ? 0.1 : 0)) / 1.3,
        ];
    }

    private function checkSecurity(string $code): array
    {
        $vulnerabilities = [];
        $score = 1.0;
        
        $securityChecks = [
            '/\$_GET\[.*\](?!\s*\))/' => 'Direct $_GET usage without validation',
            '/\$_POST\[.*\](?!\s*\))/' => 'Direct $_POST usage without validation',
            '/eval\s*\(/' => 'Use of eval() function',
            '/exec\s*\(/' => 'Use of exec() function',
            '/system\s*\(/' => 'Use of system() function',
            '/shell_exec\s*\(/' => 'Use of shell_exec() function',
            '/mysql_query\s*\(/' => 'Use of deprecated mysql_query',
            '/md5\s*\(.*password/' => 'Weak password hashing with MD5',
        ];
        
        foreach ($securityChecks as $pattern => $vulnerability) {
            if (preg_match($pattern, $code)) {
                $vulnerabilities[] = $vulnerability;
                $score -= 0.2;
            }
        }
        
        return [
            'score' => max(0, $score),
            'vulnerabilities' => $vulnerabilities,
            'security_level' => $score > 0.8 ? 'high' : ($score > 0.5 ? 'medium' : 'low'),
        ];
    }

    private function checkMaintainability(string $code): array
    {
        $issues = [];
        $score = 1.0;
        
        // Check for maintainability issues
        $lines = explode("\n", $code);
        $methodLengths = [];
        $currentMethodLines = 0;
        $inMethod = false;
        
        foreach ($lines as $line) {
            if (preg_match('/\bfunction\s+/', $line)) {
                if ($inMethod && $currentMethodLines > 0) {
                    $methodLengths[] = $currentMethodLines;
                }
                $inMethod = true;
                $currentMethodLines = 1;
            } elseif ($inMethod) {
                $currentMethodLines++;
                if (trim($line) === '}' && preg_match('/^\s*}\s*$/', $line)) {
                    $methodLengths[] = $currentMethodLines;
                    $inMethod = false;
                    $currentMethodLines = 0;
                }
            }
        }
        
        $longMethods = array_filter($methodLengths, fn($length) => $length > 20);
        if (!empty($longMethods)) {
            $issues[] = 'Methods too long (>20 lines)';
            $score -= 0.1 * count($longMethods);
        }
        
        // Check for code duplication patterns
        $duplicatedLines = $this->findDuplicatedCode($code);
        if ($duplicatedLines > 0) {
            $issues[] = "Code duplication detected ({$duplicatedLines} similar lines)";
            $score -= 0.1;
        }
        
        return [
            'score' => max(0, $score),
            'issues' => $issues,
            'average_method_length' => !empty($methodLengths) ? array_sum($methodLengths) / count($methodLengths) : 0,
            'max_method_length' => !empty($methodLengths) ? max($methodLengths) : 0,
        ];
    }

    // Symfony-specific compliance checks
    private function checkServicePatterns(string $code): array
    {
        $patterns = [
            'constructor_injection' => preg_match('/public\s+function\s+__construct\([^)]*[A-Z][a-zA-Z]*Interface/', $code) > 0,
            'service_autowiring' => preg_match('/#\[Autowire\]/', $code) > 0,
            'service_attributes' => preg_match('/#\[AsService\]/', $code) > 0,
        ];
        
        $score = array_sum($patterns) / count($patterns);
        
        return [
            'patterns_detected' => $patterns,
            'score' => $score,
            'recommendation' => $score < 0.5 ? 'Consider using more Symfony service patterns' : 'Good service pattern usage',
        ];
    }

    private function checkDependencyInjection(string $code): array
    {
        $diPatterns = [
            'constructor_di' => preg_match('/public\s+function\s+__construct/', $code) > 0,
            'typed_parameters' => preg_match('/function\s+__construct\([^)]*[A-Z][a-zA-Z]*\s+\$/', $code) > 0,
            'readonly_properties' => preg_match('/readonly\s+/', $code) > 0,
            'private_properties' => preg_match('/private\s+[A-Z][a-zA-Z]*\s+\$/', $code) > 0,
        ];
        
        return [
            'patterns' => $diPatterns,
            'score' => array_sum($diPatterns) / count($diPatterns),
        ];
    }

    private function checkAttributeUsage(string $code): array
    {
        $attributes = [
            'route_attributes' => preg_match('/#\[Route\(/', $code),
            'autowire_attributes' => preg_match('/#\[Autowire\]/', $code),
            'validation_attributes' => preg_match('/#\[Assert\\\\/', $code),
            'serializer_attributes' => preg_match('/#\[Groups\(/', $code),
        ];
        
        return [
            'attributes_used' => array_sum($attributes),
            'modern_attribute_syntax' => array_sum($attributes) > 0,
            'score' => min(1.0, array_sum($attributes) / 2), // Normalize
        ];
    }

    // Context utilization methods
    private function checkEntityUsage(string $content, array $entities): array
    {
        $usedEntities = [];
        $entityNames = array_column($entities, 'name');
        
        foreach ($entityNames as $entityName) {
            if (stripos($content, $entityName) !== false) {
                $usedEntities[] = $entityName;
            }
        }
        
        return [
            'total_entities' => count($entityNames),
            'used_entities' => $usedEntities,
            'usage_count' => count($usedEntities),
            'usage_ratio' => count($entityNames) > 0 ? count($usedEntities) / count($entityNames) : 0,
        ];
    }

    private function checkRouteUsage(string $content, array $routes): array
    {
        $usedRoutes = [];
        
        foreach ($routes as $route) {
            $routeName = $route['name'] ?? '';
            $routePath = $route['path'] ?? '';
            
            if (($routeName && stripos($content, $routeName) !== false) ||
                ($routePath && stripos($content, $routePath) !== false)) {
                $usedRoutes[] = $route;
            }
        }
        
        return [
            'total_routes' => count($routes),
            'used_routes' => count($usedRoutes),
            'usage_ratio' => count($routes) > 0 ? count($usedRoutes) / count($routes) : 0,
        ];
    }

    private function checkControllerPatterns(string $content, array $controllers): array
    {
        $patterns = [];
        $commonPatterns = ['index', 'show', 'create', 'edit', 'delete', 'update'];
        
        foreach ($commonPatterns as $pattern) {
            $patterns[$pattern] = stripos($content, $pattern) !== false;
        }
        
        return [
            'detected_patterns' => $patterns,
            'pattern_coverage' => array_sum($patterns) / count($patterns),
        ];
    }

    private function calculateUtilizationScore(array $utilization): float
    {
        $scores = [];
        
        if (isset($utilization['entities']['usage_ratio'])) {
            $scores[] = $utilization['entities']['usage_ratio'];
        }
        
        if (isset($utilization['routes']['usage_ratio'])) {
            $scores[] = $utilization['routes']['usage_ratio'];
        }
        
        if (isset($utilization['controller_patterns']['pattern_coverage'])) {
            $scores[] = $utilization['controller_patterns']['pattern_coverage'];
        }
        
        return !empty($scores) ? array_sum($scores) / count($scores) : 0;
    }

    private function assessContextRelevance(array $mcpContext, TestScenario $scenario): array
    {
        // Analyze how relevant the provided context is to the scenario
        $relevanceScore = 0.5; // Base relevance
        
        $scenarioType = $scenario->getTaskType();
        $contextTypes = array_keys($mcpContext['raw_context'] ?? []);
        
        $relevantContexts = [
            'controller' => ['Get Controllers', 'Get Routes'],
            'entity' => ['Get Entities', 'Get Database Schema'],
            'service' => ['Get Services', 'Get Controllers'],
            'form' => ['Get Entities', 'Get Forms'],
        ];
        
        if (isset($relevantContexts[$scenarioType])) {
            $expectedContexts = $relevantContexts[$scenarioType];
            $availableContexts = array_intersect($expectedContexts, $contextTypes);
            $relevanceScore = count($availableContexts) / count($expectedContexts);
        }
        
        return [
            'score' => $relevanceScore,
            'expected_contexts' => $relevantContexts[$scenarioType] ?? [],
            'available_contexts' => $contextTypes,
            'is_relevant' => $relevanceScore > 0.5,
        ];
    }

    // Additional Symfony and functional correctness methods
    private function checkEventDispatcherUsage(string $code): array
    {
        return [
            'uses_events' => preg_match('/EventDispatcher|dispatch\(/', $code) > 0,
            'custom_events' => preg_match('/extends\s+Event/', $code) > 0,
            'score' => (preg_match('/EventDispatcher|dispatch\(/', $code) > 0 ? 0.5 : 0) + 
                      (preg_match('/extends\s+Event/', $code) > 0 ? 0.5 : 0),
        ];
    }

    private function checkFormComponentUsage(string $code): array
    {
        return [
            'uses_form_builder' => preg_match('/FormBuilderInterface|createForm/', $code) > 0,
            'form_validation' => preg_match('/handleRequest|isSubmitted|isValid/', $code) > 0,
            'score' => (preg_match('/FormBuilderInterface|createForm/', $code) > 0 ? 0.5 : 0) + 
                      (preg_match('/handleRequest|isSubmitted|isValid/', $code) > 0 ? 0.5 : 0),
        ];
    }

    private function checkSecurityComponentUsage(string $code): array
    {
        return [
            'uses_security' => preg_match('/Security|IsGranted|denyAccessUnlessGranted/', $code) > 0,
            'csrf_protection' => preg_match('/csrf|CsrfToken/', $code) > 0,
            'score' => (preg_match('/Security|IsGranted|denyAccessUnlessGranted/', $code) > 0 ? 0.6 : 0) + 
                      (preg_match('/csrf|CsrfToken/', $code) > 0 ? 0.4 : 0),
        ];
    }

    private function checkDoctrineIntegration(string $code): array
    {
        return [
            'uses_entity_manager' => preg_match('/EntityManagerInterface|EntityManager/', $code) > 0,
            'uses_repository' => preg_match('/Repository|findBy|find\(/', $code) > 0,
            'uses_orm_attributes' => preg_match('/#\[ORM\\\\/', $code) > 0,
            'score' => (preg_match('/EntityManagerInterface|EntityManager/', $code) > 0 ? 0.4 : 0) + 
                      (preg_match('/Repository|findBy|find\(/', $code) > 0 ? 0.4 : 0) + 
                      (preg_match('/#\[ORM\\\\/', $code) > 0 ? 0.2 : 0),
        ];
    }

    private function checkControllerBestPractices(string $code): array
    {
        return [
            'extends_abstract_controller' => preg_match('/extends\s+AbstractController/', $code) > 0,
            'returns_response' => preg_match('/:\s*Response|return\s+\$this->/', $code) > 0,
            'uses_route_attributes' => preg_match('/#\[Route\(/', $code) > 0,
            'proper_method_names' => preg_match('/public\s+function\s+(index|show|new|edit|delete)/', $code) > 0,
            'score' => (preg_match('/extends\s+AbstractController/', $code) > 0 ? 0.25 : 0) + 
                      (preg_match('/:\s*Response|return\s+\$this->/', $code) > 0 ? 0.25 : 0) + 
                      (preg_match('/#\[Route\(/', $code) > 0 ? 0.25 : 0) + 
                      (preg_match('/public\s+function\s+(index|show|new|edit|delete)/', $code) > 0 ? 0.25 : 0),
        ];
    }

    private function checkResponseHandling(string $code): array
    {
        return [
            'json_response' => preg_match('/JsonResponse|json\(/', $code) > 0,
            'redirect_response' => preg_match('/RedirectResponse|redirectToRoute/', $code) > 0,
            'template_response' => preg_match('/render\(|renderView/', $code) > 0,
            'score' => min(1.0, (preg_match('/JsonResponse|json\(/', $code) > 0 ? 0.33 : 0) + 
                              (preg_match('/RedirectResponse|redirectToRoute/', $code) > 0 ? 0.33 : 0) + 
                              (preg_match('/render\(|renderView/', $code) > 0 ? 0.34 : 0)),
        ];
    }

    private function checkValidationUsage(string $code): array
    {
        return [
            'uses_validation' => preg_match('/Assert\\\\|@Assert|#\[Assert/', $code) > 0,
            'validation_groups' => preg_match('/groups\s*=|Groups\(/', $code) > 0,
            'score' => (preg_match('/Assert\\\\|@Assert|#\[Assert/', $code) > 0 ? 0.7 : 0) + 
                      (preg_match('/groups\s*=|Groups\(/', $code) > 0 ? 0.3 : 0),
        ];
    }

    // Functional correctness methods
    private function checkRequirementCoverage(string $code, TestScenario $scenario): array
    {
        $requirements = $scenario->getEvaluationCriteria() ?? [];
        $coverage = [];
        
        foreach ($requirements as $requirement) {
            $covered = $this->checkRequirementInCode($code, $requirement);
            $coverage[$requirement] = $covered;
        }
        
        $coverageRatio = !empty($coverage) ? array_sum($coverage) / count($coverage) : 0;
        
        return [
            'requirements' => $requirements,
            'coverage_details' => $coverage,
            'coverage_ratio' => $coverageRatio,
            'score' => $coverageRatio,
        ];
    }

    private function checkEdgeCaseHandling(string $code): array
    {
        $edgeCases = [
            'null_checks' => preg_match('/===\s*null|!==\s*null|null ===|null !==/', $code),
            'empty_checks' => preg_match('/empty\s*\(|!\s*\w+/', $code),
            'boundary_checks' => preg_match('/<=|>=|<|>/', $code),
            'try_catch_blocks' => preg_match('/try\s*\{[\s\S]*?\}\s*catch\s*\(/', $code),
        ];

        $score = array_sum($edgeCases) / count($edgeCases);

        return [
            'patterns_detected' => $edgeCases,
            'score' => $score,
            'coverage_level' => $score > 0.75 ? 'high' : ($score > 0.5 ? 'medium' : 'low'),
        ];
    }

    private function checkErrorHandling(string $code): array
    {
        $hasTryCatch = preg_match('/try\s*\{.*?\}\s*catch\s*\(/s', $code);
        $logsErrors = preg_match('/logger->(error|critical|warning)/', $code);

        $score = 0;
        if ($hasTryCatch) $score += 0.5;
        if ($logsErrors) $score += 0.5;

        return [
            'score' => $score,
            'uses_try_catch' => $hasTryCatch > 0,
            'logs_errors' => $logsErrors > 0,
        ];
    }

    private function checkInputValidation(string $code): array
    {
        $usesValidator = preg_match('/validate|Assert|Constraints/', $code);
        $sanitizesInput = preg_match('/htmlspecialchars|strip_tags|filter_var/', $code);

        $score = 0;
        if ($usesValidator) $score += 0.5;
        if ($sanitizesInput) $score += 0.5;

        return [
            'score' => $score,
            'uses_validator' => $usesValidator > 0,
            'sanitizes_input' => $sanitizesInput > 0,
        ];
    }

    private function checkBusinessLogicAccuracy(string $code, TestScenario $scenario): float
    {
        // Stub logic — can be replaced with actual test harnesses or AST comparison later
        return 0.75; // Assume partial correctness for now
    }

    private function checkApiContractCompliance(string $code, TestScenario $scenario): float
    {
        // Simplified compliance check
        return preg_match('/JsonResponse|@Route|@Method|Request/', $code) ? 1.0 : 0.0;
    }

   private function compareQuality(string $mcpCode, string $vanillaCode): float
{
    $mcpQuality = $this->assessCodeQuality($mcpCode);
    $vanillaQuality = $this->assessCodeQuality($vanillaCode);
    return $this->extractAvgScore($mcpQuality) - $this->extractAvgScore($vanillaQuality);
}

    private function compareSymfonyCompliance(string $mcpCode, string $vanillaCode): float
{
    $mcpCompliance = $this->assessSymfonyCompliance($mcpCode);
    $vanillaCompliance = $this->assessSymfonyCompliance($vanillaCode);
    return $this->extractAvgScore($mcpCompliance) - $this->extractAvgScore($vanillaCompliance);
}

    private function compareFunctionalCorrectness(string $mcpCode, string $vanillaCode, TestScenario $scenario): float
{
    $mcpCorrectness = $this->assessFunctionalCorrectness($mcpCode, $scenario);
    $vanillaCorrectness = $this->assessFunctionalCorrectness($vanillaCode, $scenario);
    return $this->extractAvgScore($mcpCorrectness) - $this->extractAvgScore($vanillaCorrectness);
}

    private function calculateCompositeScore(array $quality, array $symfony, array $correctness, array $contextUtilization): float
{
    $q = $this->extractAvgScore($quality);
    $s = $this->extractAvgScore($symfony);
    $c = $this->extractAvgScore($correctness);
    $u = $contextUtilization['utilization_score'] ?? 0;

    return round(($q + $s + $c + $u) / 4, 3);
}

    private function calculateImprovement(float $before, float $after): float
    {
        if ($before === 0.0) {
            return 0.0;
        }
        return round((($before - $after) / $before) * 100, 2);
    }

    private function findDuplicatedCode(string $code): int
    {
        // TODO: Use hash-based line matching or token stream analysis
        return 0; // Stub returns no duplicates
    }

    private function extractAvgScore(array $section): float
    {
        $scores = array_filter($section, fn($v) => is_array($v) && isset($v['score']));
        return !empty($scores)
            ? array_sum(array_column($scores, 'score')) / count($scores)
            : 0.0;
    }

    private function checkRequirementInCode(string $code, string $requirement): bool
    {
        // Simple keyword matching - you may want to make this more sophisticated
        return stripos($code, $requirement) !== false;
    }

}
