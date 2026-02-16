<?php
// app/Http/Controllers/Api/NlpController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\NlpProcessRequest;
use App\Http\Requests\Api\NlpBatchRequest;
use App\Services\NlpService;
use App\Models\NlpLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class NlpController extends Controller
{
    protected $nlpService;

    public function __construct(NlpService $nlpService)
    {
        $this->nlpService = $nlpService;
        $this->middleware('auth:sanctum')->except(['health']);
    }

    /**
     * Health check endpoint
     */
    public function health(): JsonResponse
    {
        try {
            // Test connection to Flask NLP API
            $flaskService = app(\App\Services\FlaskApiService::class);
            $health = $flaskService->healthCheck();

            return response()->json([
                'success' => true,
                'status' => 'healthy',
                'services' => [
                    'laravel' => true,
                    'nlp_api' => $health['flask_service'],
                ],
                'nlp_api_data' => $health['data'] ?? null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ], 503);
        }
    }

    /**
     * Process single NLP message
     */
    public function process(NlpProcessRequest $request): JsonResponse
    {
        $userId = auth()->id();
        $message = $request->input('message');

        try {
            // Process dengan NLP service
            $result = $this->nlpService->processMessage($userId, $message);

            // Log ke database
            $this->logNlpRequest($userId, $message, $result);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('NLP Process Error', [
                'user_id' => $userId,
                'message' => $message,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to process message',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Batch NLP processing
     */
    public function batchProcess(NlpBatchRequest $request): JsonResponse
    {
        $userId = auth()->id();
        $messages = $request->input('messages');

        try {
            $result = $this->nlpService->processBatch($userId, $messages);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user conversation context
     */
    public function getContext(): JsonResponse
    {
        $userId = auth()->id();

        try {
            $result = $this->nlpService->getContext($userId);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear user conversation context
     */
    public function clearContext(): JsonResponse
    {
        $userId = auth()->id();

        try {
            $result = $this->nlpService->clearContext($userId);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Log NLP request to database
     */
    protected function logNlpRequest(int $userId, string $message, array $result): void
    {
        if (!isset($result['success']) || !$result['success']) {
            return;
        }

        NlpLog::create([
            'user_id' => $userId,
            'user_message' => $message,
            'intent' => $result['nlp']['intent'] ?? null,
            'confidence' => $result['nlp']['confidence'] ?? 0,
            'entities' => $result['nlp']['entities'] ?? [],
            'action' => $result['nlp']['action'] ?? null,
            'needs_clarification' => $result['needs_clarification'] ?? false,
            'clarification_question' => $result['clarification_question'] ?? null,
            'nlp_response' => $result,
        ]);
    }
}