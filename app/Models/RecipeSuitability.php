<?php
// app/Models/RecipeSuitability.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecipeSuitability extends Model
{
    protected $table = 'recipe_suitability';
    
    protected $fillable = [
        'recipe_id', 'health_condition_id', 'is_suitable', 'notes'
    ];

    protected $casts = [
        'is_suitable' => 'boolean',
    ];

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }

    public function healthCondition()
    {
        return $this->belongsTo(HealthCondition::class);
    }
}