<?php
namespace App\Bridge;

class AdvancedPromptBuilder
{
    private array $taskTemplates = [];
    private array $examples = [];

    public function __construct()
    {
        $this->initializeTemplates();
        $this->loadExamples();
    }

    public function buildContextualPrompt(
        string $userPrompt, 
        array $mcpContext, 
        string $taskType = 'general',
        array $options = []
    ): string {
        $template = $this->getTaskTemplate($taskType);
        $contextSummary = $mcpContext['context_summary'] ?? 'No context available';
        $constraints = $mcpContext['constraints'] ?? [];
        $patterns = $mcpContext['architectural_patterns'] ?? [];
        
        $prompt = $template;
        $prompt = str_replace('{USER_PROMPT}', $userPrompt, $prompt);
        $prompt = str_replace('{CONTEXT_SUMMARY}', $contextSummary, $prompt);
        $prompt = str_replace('{CONSTRAINTS}', $this->formatConstraints($constraints), $prompt);
        $prompt = str_replace('{PATTERNS}', $this->formatPatterns($patterns), $prompt);
        $prompt = str_replace('{EXAMPLES}', $this->getRelevantExamples($taskType), $prompt);
        $prompt = str_replace('{CONTEXT_DATA}', $this->formatContextData($mcpContext), $prompt);
        
        return $prompt;
    }

    private function initializeTemplates(): void
    {
        $this->taskTemplates = [
            'controller' => <<<EOT
You are a Symfony expert developer. Generate clean, production-ready code following Symfony best practices.

## Project Context
{CONTEXT_SUMMARY}

## Architectural Constraints
{CONSTRAINTS}

## Detected Patterns
{PATTERNS}

## Your Task
{USER_PROMPT}

## Guidelines
1. Follow Symfony naming conventions
2. Use proper type hints and return types
3. Implement proper error handling
4. Add appropriate validation
5. Use dependency injection correctly
6. Follow PSR standards
7. Add proper PHPDoc comments

## Examples of Good Code
{EXAMPLES}

## Available Project Data
{CONTEXT_DATA}

## Output Requirements
- Provide complete, working code
- Include proper namespace and use statements
- Add comments explaining complex logic
- Ensure code is ready for production use
- Follow the existing project structure and patterns

Generate the code now:
EOT,

            'entity' => <<<EOT
You are a Symfony expert developer. Create a robust Doctrine entity following best practices.

## Project Context
{CONTEXT_SUMMARY}

## Existing Entities
{CONTEXT_DATA}

## Your Task
{USER_PROMPT}

## Entity Requirements
1. Proper Doctrine annotations/attributes
2. Correct field types and constraints
3. Proper relationships (OneToMany, ManyToOne, etc.)
4. Getters and setters
5. Constructor for collections
6. toString method if appropriate
7. Validation constraints

## Example Pattern
{EXAMPLES}

Generate a complete entity class:
EOT,

            'api' => <<<EOT
You are a Symfony API expert. Create a robust REST API endpoint following best practices.

## Project Context
{CONTEXT_SUMMARY}

## Security Requirements
{CONSTRAINTS}

## Your Task
{USER_PROMPT}

## API Requirements
1. Proper HTTP status codes
2. JSON responses with consistent structure
3. Input validation
4. Error handling with proper error responses
5. Authentication/authorization if needed
6. Rate limiting considerations
7. API documentation annotations

## Response Format Example
{EXAMPLES}

## Available Data
{CONTEXT_DATA}

Generate the complete API controller:
EOT,

            'service' => <<<EOT
You are a Symfony service expert. Create a clean, testable service class.

## Project Context
{CONTEXT_SUMMARY}

## Your Task
{USER_PROMPT}

## Service Requirements
1. Single responsibility principle
2. Dependency injection
3. Interface segregation
4. Proper error handling
5. Logging where appropriate
6. Testable design
7. Clear method signatures

## Pattern Example
{EXAMPLES}

## Available Context
{CONTEXT_DATA}

Generate the service class:
EOT,

            'general' => <<<EOT
You are a Symfony expert developer.

## Project Context
{CONTEXT_SUMMARY}

## Your Task
{USER_PROMPT}

## Available Information
{CONTEXT_DATA}

## Requirements
- Follow Symfony best practices
- Write clean, maintainable code
- Use proper error handling
- Follow PSR standards

Generate the solution:
EOT
        ];
    }

    private function loadExamples(): void
    {
        $this->examples = [
            'controller' => <<<EOT
```php
#[Route('/api/blog', name: 'api_blog_')]
class BlogApiController extends AbstractController
{
    public function __construct(
        private BlogRepository $blogRepository,
        private SerializerInterface $serializer
    ) {}

    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $blogs = $this->blogRepository->findAll();
        return $this->json($blogs, 200);
    }

    #[Route('/{id}', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        $blog = $this->blogRepository->find($id);
        if (!$blog) {
            return $this->json(['error' => 'Blog not found'], 404);
        }
        return $this->json($blog);
    }
}
```
EOT,

            'entity' => <<<EOT
```php
#[ORM\Entity(repositoryClass: BlogRepository::class)]
#[ORM\Table(name: 'blog_posts')]
class BlogPost
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private ?string $title = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    private ?string $content = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // Getters and setters...
}
```
EOT,

            'api' => <<<EOT
```php
// Consistent API response structure
return $this->json([
    'success' => true,
    'data' => $data,
    'message' => 'Operation completed successfully'
], 200);

// Error response structure
return $this->json([
    'success' => false,
    'error' => [
        'code' => 'VALIDATION_ERROR',
        'message' => 'Invalid input data',
        'details' => $validationErrors
    ]
], 400);
```
EOT,

            'service' => <<<EOT
```php
class BlogService
{
    public function __construct(
        private BlogRepository $repository,
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger
    ) {}

    public function createBlog(CreateBlogRequest $request): Blog
    {
        try {
            $blog = new Blog();
            $blog->setTitle($request->title);
            $blog->setContent($request->content);
            
            $this->repository->save($blog);
            
            $this->eventDispatcher->dispatch(new BlogCreatedEvent($blog));
            $this->logger->info('Blog created', ['id' => $blog->getId()]);
            
            return $blog;
        } catch (\Exception $e) {
            $this->logger->error('Failed to create blog', ['error' => $e->getMessage()]);
            throw new BlogCreationException('Could not create blog', 0, $e);
        }
    }
}
```
EOT
        ];
    }

    private function getTaskTemplate(string $taskType): string
    {
        return $this->taskTemplates[$taskType] ?? $this->taskTemplates['general'];
    }

    private function formatConstraints(array $constraints): string
    {
        if (empty($constraints)) {
            return "No specific constraints detected.";
        }

        $formatted = [];
        foreach ($constraints as $key => $value) {
            if (is_array($value)) {
                $formatted[] = "- " . ucfirst(str_replace('_', ' ', $key)) . ": " . implode(', ', $value);
            } else {
                $formatted[] = "- " . ucfirst(str_replace('_', ' ', $key)) . ": " . $value;
            }
        }

        return implode("\n", $formatted);
    }

    private function formatPatterns(array $patterns): string
    {
        if (empty($patterns)) {
            return "Standard Symfony MVC pattern detected.";
        }

        $formatted = [];
        foreach ($patterns as $pattern => $detected) {
            if ($detected) {
                $formatted[] = "✓ " . ucfirst(str_replace('_', ' ', $pattern));
            }
        }

        return empty($formatted) ? "Standard patterns" : implode("\n", $formatted);
    }

    private function getRelevantExamples(string $taskType): string
    {
        return $this->examples[$taskType] ?? $this->examples['general'] ?? '';
    }

    private function formatContextData(array $mcpContext): string
    {
        $formatted = [];
        
        if (isset($mcpContext['relevant_files'])) {
            $formatted[] = "## Relevant Files\n" . implode("\n", array_map(fn($f) => "- $f", $mcpContext['relevant_files']));
        }

        if (isset($mcpContext['raw_context'])) {
            foreach ($mcpContext['raw_context'] as $toolName => $data) {
                if ($data['success'] && !empty($data['data'])) {
                    $formatted[] = "## $toolName";
                    if (is_array($data['data'])) {
                        $formatted[] = "```\n" . implode("\n", array_map(fn($item) => "- $item", $data['data'])) . "\n```";
                    } else {
                        $formatted[] = "```\n" . $data['data'] . "\n```";
                    }
                }
            }
        }

        return implode("\n\n", $formatted);
    }

    public function buildVanillaPrompt(string $userPrompt, string $taskType = 'general'): string
    {
        $basePrompt = "You are a Symfony expert developer.\n\n";
        $basePrompt .= "## Your Task\n{$userPrompt}\n\n";
        $basePrompt .= "## Requirements\n";
        $basePrompt .= "- Follow Symfony best practices\n";
        $basePrompt .= "- Write clean, maintainable code\n";
        $basePrompt .= "- Use proper error handling\n";
        $basePrompt .= "- Follow PSR standards\n\n";
        $basePrompt .= "Generate the solution:";

        return $basePrompt;
    }
}