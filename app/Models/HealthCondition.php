<?php

// app/Models/HealthCondition.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HealthCondition extends Model
{
    protected $table = 'health_conditions';
    protected $fillable = ['nama', 'description'];
    
    public function recipeSuitability()
    {
        return $this->hasMany(RecipeSuitability::class);
    }
}