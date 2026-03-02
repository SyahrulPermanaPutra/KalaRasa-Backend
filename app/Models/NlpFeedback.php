<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

 class NlpFeedback extends Model
 {
     protected $table = 'nlp_feedback';

     protected $fillable = [
         'user_id', 'session_id', 'user_query_id', 'recipe_id',
         'rank_shown', 'rating', 'feedback_type',
         'query_text', 'query_hash', 'matched_score',
     ];
     protected $casts = [
         'rating'         => 'integer',
         'rank_shown'     => 'integer',
         'matched_score'  => 'float',
     ];

    public function recipe(): BelongsTo
     {
         return $this->belongsTo(Recipe::class);
     }

     public function user(): BelongsTo
     {
         return $this->belongsTo(User::class);
     }

     public function userQuery(): BelongsTo
     {
         return $this->belongsTo(UserQuery::class);
     }
 }