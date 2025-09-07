<?php

namespace App\Controller;

use App\Bridge\LLMBridgeService;
use App\DTO\LLMRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class BridgeTestController extends AbstractController
{
    #[Route('/api/test-bridge', name: 'test_bridge')]
    public function testBridge(LLMBridgeService $bridge): JsonResponse
    {
        ini_set('max_execution_time', 520);
        $request = new LLMRequest(
            prompt: 'Create a Symfony controller for blog posts',
            projectPath: realpath(__DIR__ . '/../../../symfony-demo'),
            model: 'deepseek-coder:6.7b',
            taskType: 'controller',
            relevantTools: ['getEntities', 'getRoutes'] // TODO test others later!
        );

        $response = $bridge->queryWithMCP($request);

        return $this->json([
            'used_mcp' => $response->isUsedMCP(),
            'content' => $response->getContent(),
            'tokens' => $response->getTokenCount(),
            'context_included' => array_keys($response->getMcpContext() ?? []),
        ]);
    }
}
