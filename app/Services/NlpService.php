<?php
// app/Services/NlpService.php

namespace App\Services;

use App\Services\ChatbotService;
use Illuminate\Support\Facades\Log;

class NlpService
{
    protected $ChatbotApi;

    public function __construct(ChatbotService $chatbotApi)
    {
        $this->ChatbotApi = $chatbotApi;
    }

    /**
     * Process user message dengan NLP
     */
    public function processMessage(int $userId, string $message): array
    {
        try {
            // Call Chatbot NLP API
            $result = $this->ChatbotApi->processNlp($userId, $message);

            if (!isset($result['success']) || !$result['success']) {
                throw new \Exception('NLP processing failed');
            }

            // Log untuk analytics
            Log::info('NLP Processed', [
                'user_id' => $userId,
                'message' => $message,
                'intent' => $result['nlp_result']['intent'] ?? 'unknown',
                'confidence' => $result['nlp_result']['confidence'] ?? 0,
            ]);

            return $this->formatResponse($result);

        } catch (\Exception $e) {
            Log::error('NLP Processing Error', [
                'user_id' => $userId,
                'message' => $message,
                'error' => $e->getMessage(),
            ]);

            return $this->fallbackResponse($message, $e->getMessage());
        }
    }

    /**
     * Batch processing
     */
    public function processBatch(int $userId, array $messages): array
    {
        try {
            $result = $this->ChatbotApi->batchProcessNlp($userId, $messages);

            return [
                'success' => true,
                'batch_results' => $result['results'] ?? [],
                'total_processed' => $result['total'] ?? count($messages),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Format NLP response untuk Laravel
     */
    protected function formatResponse(array $nlpResult): array
    {
        $nlp = $nlpResult['nlp_result'] ?? [];

        return [
            'success' => true,
            'nlp' => [
                'intent' => $nlp['intent'] ?? null,
                'confidence' => $nlp['confidence'] ?? 0,
                'entities' => $nlp['entities'] ?? [],
                'action' => $nlp['action'] ?? 'unknown',
                'status' => $nlp['status'] ?? 'unknown',
            ],
            'needs_clarification' => $nlpResult['needs_clarification'] ?? false,
            'clarification_question' => $nlpResult['clarification_question'] ?? null,
            'context' => $nlpResult['context_summary'] ?? null,
            'original_response' => $nlpResult,
        ];
    }

    /**
     * Fallback response jika NLP error
     */
    protected function fallbackResponse(string $message, string $error): array
    {
        return [
            'success' => false,
            'error' => 'NLP service temporarily unavailable',
            'fallback' => [
                'message' => $message,
                'suggestion' => 'Please try again later or contact support',
                'technical_error' => $error,
            ],
        ];
    }

    /**
     * Get conversation context
     */
    public function getContext(int $userId): array
    {
        try {
            $result = $this->ChatbotApi->getContext($userId);
            
            return [
                'success' => true,
                'context' => $result['context'] ?? [],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Clear conversation context
     */
    public function clearContext(int $userId): array
    {
        try {
            $success = $this->ChatbotApi->clearContext($userId);
            
            return [
                'success' => $success,
                'message' => $success ? 'Context cleared successfully' : 'Failed to clear context',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}