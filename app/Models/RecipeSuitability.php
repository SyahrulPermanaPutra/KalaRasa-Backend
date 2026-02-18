<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipeSuitability extends Model
{
    // ✅ Tambahkan ini untuk specify table name
    protected $table = 'recipe_suitability';

    protected $fillable = [
        'recipe_id',
        'health_condition_id',
        'is_suitable',
        'notes',
    ];

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function healthCondition(): BelongsTo
    {
        return $this->belongsTo(HealthCondition::class);
    }
}