<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HealthCondition extends Model
{
    use HasFactory;

    protected $table = 'health_conditions';

    protected $fillable = [
        'nama',
        'description',
    ];

    public function restrictions(): HasMany
    {
        return $this->hasMany(HealthConditionRestriction::class);
    }
}