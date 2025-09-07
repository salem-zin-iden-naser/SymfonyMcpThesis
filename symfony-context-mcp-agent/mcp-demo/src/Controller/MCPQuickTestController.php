<?php

namespace App\Controller;

use App\Bridge\LLMBridgeService;
use App\DTO\LLMRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class MCPQuickTestController extends AbstractController
{
    #[Route('/api/mcp-test', name: 'mcp_test', methods: ['GET'])]
    public function test(LLMBridgeService $bridge): JsonResponse
    {
        ini_set('max_execution_time', 520);

        $request = new LLMRequest(
            prompt: 'Can u tell what is this program about according to those entites?',
            projectPath: realpath(__DIR__ . '/../../../symfony-demo'),
            model: 'deepseek-coder:6.7b',
            taskType: 'general',
            relevantTools: ['Get Entities']
        );

        $response = $bridge->queryWithMCP($request);

        return $this->json([
            'used_mcp' => $response->isUsedMCP(),
            'content' => $response->getContent(),
            'tokens' => $response->getTokenCount(),
            'context' => $response->getMcpContext(),
        ]);
    }
}
