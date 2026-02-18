<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthConditionRestriction extends Model
{
    use HasFactory;

    protected $table = 'health_condition_restrictions';

    protected $fillable = [
        'health_condition_id',
        'ingredient_id',
        'severity',
        'notes',
    ];

    /**
     * Relasi balik ke HealthCondition
     */
    public function healthCondition(): BelongsTo
    {
        return $this->belongsTo(HealthCondition::class);
    }

    /**
     * Relasi balik ke Ingredient
     */
    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }
}