<?php

namespace App\Controller;

use App\Evaluation\TestScenarioRunner;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/eval')]
class EvaluationController extends AbstractController
{
    public function __construct(private TestScenarioRunner $runner) {}

    #[Route('/all', name: 'eval_all', methods: ['GET'])]
    public function runAll(): JsonResponse
    {
        # test with http://localhost:8000/api/eval/all
        ini_set('max_execution_time', 1820);
        $results = $this->runner->runAllScenarios();
        return $this->json($results);
    }

    #[Route('/category/{category}', name: 'eval_category', methods: ['GET'])]
    public function runByCategory(string $category): JsonResponse
    {
        # test with http://localhost:8000/api/eval/category/controller
        ini_set('max_execution_time', 720);
        $results = $this->runner->runScenariosByCategory($category);
        return $this->json($results);
    }

    #[Route('/scenario/{key}', name: 'eval_key', methods: ['GET'])]
    public function runByKey(string $key): JsonResponse
    {
        # test with http://localhost:8000/api/eval/scenario/controller_crud
        # test with http://localhost:8000/api/eval/scenario/add_like_feature
        ini_set('max_execution_time', 720);
        $result = $this->runner->runByKey($key);
        return $this->json($result);
    }
}
