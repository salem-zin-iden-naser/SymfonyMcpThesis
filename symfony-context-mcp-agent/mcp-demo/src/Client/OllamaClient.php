<?php

namespace App\Client;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class OllamaClient
{
    private const DEFAULT_BASE_URL = 'http://localhost:11434';

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $baseUrl = self::DEFAULT_BASE_URL
    ) {}

    public function generate(
    string $model,
    string $prompt,
    array $options = [],
    bool $stream = false
): array {
    $response = $this->httpClient->request('POST', $this->baseUrl . '/api/generate', [
        'json' => [
            'model' => $model,
            'prompt' => $prompt,
            'stream' => $stream
        ],
        'timeout' => 720,
    ]);

    return $response->toArray(false); // false to disable throwing on non-200
}


    public function chat(
        string $model,
        array $messages,
        array $options = [],
        bool $stream = false
    ): array {
        $response = $this->httpClient->request('POST', $this->baseUrl . '/api/chat', [
            'json' => [
                'model' => $model,
                'messages' => $messages,
                'stream' => $stream,
                'options' => $options
            ],
            'timeout' => 720,
        ]);

        return $response->toArray();
    }

    public function listModels(): array
    {
        $response = $this->httpClient->request('GET', $this->baseUrl . '/api/tags');
        return $response->toArray();
    }
}
