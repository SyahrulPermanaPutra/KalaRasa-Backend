<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
{
    protected $fillable = ['nama', 'kategori', 'sub_kategori', 'created_at', 'updated_at'];

    public function recipes()
    {
        return $this->belongsToMany(
            Ingredient::class,
            'recipe_ingredients',
            'recipe_id',
            'ingredient_id'
        )->withPivot('is_main','jumlah')
         ->withTimestamps();
    }
}
