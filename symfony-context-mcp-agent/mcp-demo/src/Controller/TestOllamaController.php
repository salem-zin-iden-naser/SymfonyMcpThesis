<?php

namespace App\Controller;

use App\Client\OllamaClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Tool\MyToolCollection;
use PhpLlm\McpSdk\Capability\Tool\CollectionInterface;

class TestOllamaController extends AbstractController
{
    #[Route('/test/ollama', name: 'test_ollama')]
    public function test(OllamaClient $ollamaClient): JsonResponse
    {
        ini_set('max_execution_time', 520);

        $prompt = 'Create a Symfony controller for BlogPost';

        try {
            $response = $ollamaClient->generate('deepseek-coder:6.7b', $prompt);

            return $this->json([
                'success' => true,
                'response' => $response,
            ]);
        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    #[Route('/debug', name: 'debug')]
    public function test_debug(CollectionInterface $toolCollection): JsonResponse
    {
        // # test with http://localhost:8000/debug
        // dump(array_map(fn($t) => $t->getName(), $this->toolCollection->getMetadata()));
        $toolNames = array_map(fn($t) => $t->getName(), $toolCollection->getMetadata());
        return $this->json(['tools' => $toolNames]);
    }
}
