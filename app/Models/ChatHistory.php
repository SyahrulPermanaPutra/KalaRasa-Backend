<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatHistory extends Model
{
    use HasFactory;

    protected $table = 'user_queries';

    protected $fillable = [
        'user_id',
        'query_text',
        'intent',
        'confidence',
        'status',
        'entities',
    ];

    protected $casts = [
        'entities' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the chat history
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}