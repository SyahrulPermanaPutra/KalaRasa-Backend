<?php
// app/Models/NlpLog.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NlpLog extends Model
{
    protected $fillable = [
        'user_id',
        'user_message',
        'intent',
        'confidence',
        'entities',
        'action',
        'needs_clarification',
        'clarification_question',
        'nlp_response',
    ];

    protected $casts = [
        'entities' => 'array',
        'nlp_response' => 'array',
        'needs_clarification' => 'boolean',
        'confidence' => 'decimal:4',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('intent', '!=', null);
    }

    public function scopeByIntent($query, string $intent)
    {
        return $query->where('intent', $intent);
    }
}