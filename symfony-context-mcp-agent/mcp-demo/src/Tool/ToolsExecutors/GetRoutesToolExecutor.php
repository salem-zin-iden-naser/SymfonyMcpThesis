<?php
namespace App\Tool\ToolsExecutors;

use PhpLlm\McpSdk\Capability\Tool\ToolCall;
use PhpLlm\McpSdk\Capability\Tool\ToolCallResult;
use PhpLlm\McpSdk\Capability\Tool\ToolExecutorInterface;
use PhpLlm\McpSdk\Exception\ToolExecutionException;

class GetRoutesToolExecutor implements ToolExecutorInterface
{
    public function call(ToolCall $input): ToolCallResult
    {
        try {
            $projectDir = __DIR__ . '/../../../../symfony-demo';
            
            // Look for route files in common locations
            $routeFiles = [
                $projectDir . '/config/routes.yaml',
                $projectDir . '/config/routes.yml',
                $projectDir . '/config/routes.php',
                $projectDir . '/config/routes/',
            ];
            
            $output = "SYMFONY-DEMO ROUTES OVERVIEW\n";
            $output .= "==================================================\n\n";
            
            $foundRoutes = false;
            
            // Check for YAML route files
            foreach ($routeFiles as $routeFile) {
                if (is_file($routeFile) && (str_ends_with($routeFile, '.yaml') || str_ends_with($routeFile, '.yml'))) {
                    $content = file_get_contents($routeFile);
                    $output .= "Routes from: " . basename($routeFile) . "\n";
                    $output .= "----------------------------------------\n";
                    $output .= $content . "\n\n";
                    $foundRoutes = true;
                }
            }
            
            // Check routes directory
            $routesDir = $projectDir . '/config/routes';
            if (is_dir($routesDir)) {
                $routeFiles = glob($routesDir . '/*.{yaml,yml}', GLOB_BRACE);
                foreach ($routeFiles as $routeFile) {
                    $content = file_get_contents($routeFile);
                    $output .= "Routes from: " . basename($routeFile) . "\n";
                    $output .= "----------------------------------------\n";
                    $output .= $content . "\n\n";
                    $foundRoutes = true;
                }
            }
            
            // Also scan controllers for route annotations/attributes
            $controllerDir = $projectDir . '/src/Controller';
            if (is_dir($controllerDir)) {
                $output .= "CONTROLLER ROUTE ANNOTATIONS/ATTRIBUTES\n";
                $output .= "==================================================\n\n";
                
                $controllerFiles = glob($controllerDir . '/*Controller.php');
                foreach ($controllerFiles as $controllerFile) {
                    $content = file_get_contents($controllerFile);
                    $className = basename($controllerFile, '.php');
                    
                    // Extract route annotations/attributes
                    if (preg_match_all('/#\[Route\([^\]]*\)\]|@Route\([^\)]*\)/m', $content, $matches)) {
                        $output .= "Controller: $className\n";
                        $output .= "----------------------------------------\n";
                        foreach ($matches[0] as $route) {
                            $output .= $route . "\n";
                        }
                        $output .= "\n";
                        $foundRoutes = true;
                    }
                }
            }
            
            if (!$foundRoutes) {
                $output .= "No route files found in symfony-demo project.\n";
                $output .= "Searched in:\n";
                foreach ($routeFiles as $file) {
                    $output .= "- $file\n";
                }
                $output .= "- $routesDir/\n";
                $output .= "- $controllerDir/ (for annotations)\n";
            }
            
            return new ToolCallResult(
                result: $output,
                type: 'text',
                mimeType: 'text/plain',
                isError: false,
                uri: null
            );
            
        } catch (\Throwable $e) {
            error_log("getRoutes failed: " . $e->getMessage());
            throw new ToolExecutionException($input, $e, "Failed to read routes from symfony-demo project.");
        }
    }
}