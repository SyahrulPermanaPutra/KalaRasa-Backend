<?php
// app/Http/Requests/Api/NlpProcessRequest.php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class NlpProcessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => 'required|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => 'Message is required',
            'message.string' => 'Message must be a string',
            'message.max' => 'Message cannot exceed 1000 characters',
        ];
    }
}