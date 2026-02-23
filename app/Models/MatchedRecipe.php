<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MatchedRecipe extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'matched_recipes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_query_id',
        'recipe_id',
        'match_score',
        'rank_position'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'match_score' => 'decimal:2',
        'rank_position' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the user query that owns the matched recipe.
     */
    public function userQuery()
    {
        return $this->belongsTo(UserQuery::class, 'user_query_id');
    }

    /**
     * Get the recipe that owns the matched recipe.
     */
    public function recipe()
    {
        return $this->belongsTo(Recipe::class, 'recipe_id');
    }

    /**
     * Scope a query to only include recipes with high match score.
     */
    public function scopeHighMatch($query, $threshold = 80)
    {
        return $query->where('match_score', '>=', $threshold);
    }

    /**
     * Scope a query to order by rank position.
     */
    public function scopeRanked($query)
    {
        return $query->orderBy('rank_position', 'asc');
    }
}