<?php
// app/Models/RecipeIngredient.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecipeIngredient extends Model
{
    protected $table = 'recipe_ingredients';
    
    protected $fillable = [
        'recipe_id', 'ingredient_id', 'is_main', 'jumlah', 'satuan'
    ];

    protected $casts = [
        'is_main' => 'boolean',
        'jumlah' => 'decimal:2',
    ];

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }
}