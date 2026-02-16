<?php
// app/Services/ChatbotService.php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatbotService
{
    protected $baseUrl;
    protected $apiKey;
    protected $timeout;

    public function __construct()
    {
        $this->baseUrl = config('nlp.api_url');
        $this->apiKey = config('nlp.api_key');
        $this->timeout = config('nlp.timeout', 10);
    }

    /**
     * Call Chatbot NLP API
     */
    protected function callApi(string $endpoint, string $method = 'GET', array $data = [])
    {
        $url = $this->baseUrl . $endpoint;
        $headers = [
            'X-API-Key' => $this->apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        try {
            $response = match ($method) {
                'GET' => Http::withHeaders($headers)
                    ->timeout($this->timeout)
                    ->get($url, $data),
                'POST' => Http::withHeaders($headers)
                    ->timeout($this->timeout)
                    ->post($url, $data),
                'PUT' => Http::withHeaders($headers)
                    ->timeout($this->timeout)
                    ->put($url, $data),
                'DELETE' => Http::withHeaders($headers)
                    ->timeout($this->timeout)
                    ->delete($url, $data),
                default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}")
            };

            Log::info('Chatbot API Call', [
                'endpoint' => $endpoint,
                'method' => $method,
                'status' => $response->status(),
                'data' => $data,
            ]);

            return $response;

        } catch (\Exception $e) {
            Log::error('Chatbot API Error', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new \RuntimeException("NLP service unavailable: {$e->getMessage()}");
        }
    }

    /**
     * Health Check
     */
    public function healthCheck(): array
    {
        $response = $this->callApi(config('nlp.endpoints.health'), 'GET');
        
        if ($response->successful()) {
            return [
                'status' => 'healthy',
                'Chatbot_service' => true,
                'data' => $response->json(),
            ];
        }

        return [
            'status' => 'unhealthy',
            'Chatbot_service' => false,
            'error' => $response->body(),
        ];
    }

    /**
     * Process Single NLP Message
     */
    public function processNlp(int $userId, string $message): array
    {
        $endpoint = str_replace('{user_id}', $userId, config('nlp.endpoints.process'));
        
        $response = $this->callApi($endpoint, 'POST', [
            'message' => $message,
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        throw new \RuntimeException(
            "NLP processing failed: {$response->status()} - {$response->body()}"
        );
    }

    /**
     * Batch NLP Processing
     */
    public function batchProcessNlp(int $userId, array $messages): array
    {
        $endpoint = str_replace('{user_id}', $userId, config('nlp.endpoints.batch'));
        
        $response = $this->callApi($endpoint, 'POST', [
            'messages' => $messages,
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        throw new \RuntimeException(
            "Batch NLP processing failed: {$response->status()}"
        );
    }

    /**
     * Get User Context
     */
    public function getContext(int $userId): array
    {
        $endpoint = str_replace('{user_id}', $userId, config('nlp.endpoints.context'));
        
        $response = $this->callApi($endpoint, 'GET');

        if ($response->successful()) {
            return $response->json();
        }

        throw new \RuntimeException(
            "Failed to get context: {$response->status()}"
        );
    }

    /**
     * Clear User Context
     */
    public function clearContext(int $userId): bool
    {
        $endpoint = str_replace('{user_id}', $userId, config('nlp.endpoints.context_clear'));
        
        $response = $this->callApi($endpoint, 'POST');

        return $response->successful();
    }
}