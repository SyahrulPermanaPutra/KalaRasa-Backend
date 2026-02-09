<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RecipeChatbotService
{
    protected $client;
    protected $baseUrl;
    protected $token;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30,
            'connect_timeout' => 10,
        ]);
        
        $this->baseUrl = config('services.recipe_chatbot.api_url', 'http://localhost:5000');
        $this->token = $this->getAuthToken();
    }

    /**
     * Get authentication token
     */
    protected function getAuthToken()
    {
        // Check cache first
        $cacheKey = 'recipe_chatbot_token_' . auth()->id();
        
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = $this->client->post($this->baseUrl . '/api/auth/token', [
                'json' => [
                    'user_id' => auth()->id() ?? 'guest_' . uniqid()
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            $token = $data['token'];

            // Cache for 23 hours (token expires in 24h)
            Cache::put($cacheKey, $token, now()->addHours(23));

            return $token;
        } catch (RequestException $e) {
            Log::error('Failed to get chatbot token: ' . $e->getMessage());
            throw new \Exception('Unable to authenticate with chatbot service');
        }
    }

    /**
     * Send message to chatbot
     * 
     * @param string $message
     * @return array
     */
    public function sendMessage(string $message): array
    {
        try {
            $response = $this->client->post($this->baseUrl . '/api/chat', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                    'Accept' => 'application/json',
                ],
                'json' => [
                    'message' => $message
                ]
            ]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            Log::error('Chatbot request failed: ' . $e->getMessage());
            
            // Retry with new token if unauthorized
            if ($e->getResponse() && $e->getResponse()->getStatusCode() === 401) {
                Cache::forget('recipe_chatbot_token_' . auth()->id());
                $this->token = $this->getAuthToken();
                return $this->sendMessage($message); // Retry once
            }

            return [
                'success' => false,
                'response' => 'Maaf, chatbot sedang mengalami gangguan. Coba lagi nanti.',
                'recipes' => [],
                'suggestions' => []
            ];
        }
    }

    /**
     * Search recipes
     */
    public function searchRecipes(array $filters): array
    {
        try {
            $response = $this->client->post($this->baseUrl . '/api/recipes/search', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                ],
                'json' => $filters
            ]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            Log::error('Recipe search failed: ' . $e->getMessage());
            return [
                'success' => false,
                'recipes' => [],
                'count' => 0
            ];
        }
    }

    /**
     * Get recipe details
     */
    public function getRecipe(int $recipeId): ?array
    {
        try {
            $response = $this->client->get($this->baseUrl . '/api/recipes/' . $recipeId, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            return $data['success'] ? $data['recipe'] : null;
        } catch (RequestException $e) {
            Log::error('Get recipe failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get conversation history
     */
    public function getHistory(): array
    {
        try {
            $response = $this->client->get($this->baseUrl . '/api/chat/history', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                ]
            ]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            Log::error('Get history failed: ' . $e->getMessage());
            return [
                'success' => false,
                'history' => []
            ];
        }
    }

    /**
     * Clear conversation
     */
    public function clearConversation(): bool
    {
        try {
            $response = $this->client->post($this->baseUrl . '/api/chat/clear', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            return $data['success'] ?? false;
        } catch (RequestException $e) {
            Log::error('Clear conversation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all ingredients
     */
    public function getIngredients(string $category = null): array
    {
        try {
            $url = $this->baseUrl . '/api/ingredients';
            if ($category) {
                $url .= '?category=' . urlencode($category);
            }

            $response = $this->client->get($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            return $data['ingredients'] ?? [];
        } catch (RequestException $e) {
            Log::error('Get ingredients failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get health conditions
     */
    public function getHealthConditions(): array
    {
        try {
            $response = $this->client->get($this->baseUrl . '/api/health-conditions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            return $data['conditions'] ?? [];
        } catch (RequestException $e) {
            Log::error('Get health conditions failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get popular recipes
     */
    public function getPopularRecipes(int $limit = 10): array
    {
        try {
            $response = $this->client->get(
                $this->baseUrl . '/api/analytics/popular-recipes?limit=' . $limit, 
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->token,
                    ]
                ]
            );

            $data = json_decode($response->getBody(), true);
            return $data['popular_recipes'] ?? [];
        } catch (RequestException $e) {
            Log::error('Get popular recipes failed: ' . $e->getMessage());
            return [];
        }
    }
}