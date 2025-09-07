<?php

namespace App\Evaluation;

use Symfony\Component\Yaml\Yaml;
use App\Evaluation\TestScenario;

class TestScenarioRepository
{
    private array $scenarios = [];

    public function __construct(string $configPath)
    {
        $yamlData = Yaml::parseFile($configPath);

        foreach ($yamlData['scenarios'] ?? [] as $key => $data) {
            $this->scenarios[$key] = TestScenario::fromArray($key, $data);
        }
    }

    public function getAllScenarios(): array
    {
        return array_values($this->scenarios);
    }

    public function getByCategory(string $category): array
    {
        return array_filter(
            $this->scenarios,
            fn(TestScenario $s) => $s->getCategory() === $category
        );
    }

    public function getByKey(string $key): ?TestScenario
    {
        return $this->scenarios[$key] ?? null;
    }
}
