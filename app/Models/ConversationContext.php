<?php
// app/Models/ConversationContext.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationContext extends Model
{
    protected $fillable = [
        'user_id',
        'context_data',
        'conversation_turns',
    ];

    protected $casts = [
        'context_data' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function incrementTurns(): void
    {
        $this->conversation_turns++;
        $this->save();
    }
}