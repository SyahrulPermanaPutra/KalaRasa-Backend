<?php
// app/Http/Requests/Api/NlpBatchRequest.php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class NlpBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'messages' => 'required|array|min:1|max:50',
            'messages.*' => 'required|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'messages.required' => 'Messages array is required',
            'messages.array' => 'Messages must be an array',
            'messages.min' => 'At least one message is required',
            'messages.max' => 'Maximum 50 messages allowed',
            'messages.*.required' => 'Each message is required',
            'messages.*.string' => 'Each message must be a string',
            'messages.*.max' => 'Message cannot exceed 1000 characters',
        ];
    }
}