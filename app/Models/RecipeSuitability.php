<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecipeSuitability extends Model
{
    protected $fillable = [
        'recipe_id',
        'health_condition_id',
        'is_suitable',
        'notes',
        'created_at',
        'updated_at',
    ];

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }
}
