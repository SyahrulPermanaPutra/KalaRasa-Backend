<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\RecipeSuitability;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;


class Recipe extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'waktu_masak',
        'region',
        'deskripsi',
        'gambar',
        'kategori',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'avg_rating',
        'total_ratings',
        'view_count'
    ];

    protected $casts = [
        'waktu_masak' => 'integer',
        'avg_rating' => 'decimal:2',
        'total_ratings' => 'integer',
        'view_count' => 'integer',
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who created the recipe
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the admin who approved the recipe
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the ingredients for this recipe
     */
    public function ingredients()
    {
        return $this->belongsToMany(Ingredient::class, 'recipe_ingredients')
                    ->withPivot('is_main', 'jumlah')
                    ->withTimestamps();
    }

    /**
     * Get the main ingredients for this recipe
     */
    public function mainIngredients()
    {
        return $this->belongsToMany(Ingredient::class, 'recipe_ingredients')
                    ->withPivot('jumlah')
                    ->wherePivot('is_main', true)
                    ->withTimestamps();
    }

     /**
     * Get the health condition suitabilities for this recipe
     */
    public function suitabilities()
    {
        return $this->hasMany(\App\Models\RecipeSuitability::class, 'recipe_id');
    }

    public function healthConditions(): BelongsToMany
    {
    return $this->belongsToMany(
        HealthCondition::class, 
        'recipe_suitability', 
        'recipe_id', 
        'health_condition_id'
    )->using(RecipeSuitability::class); // optional: jika butuh akses ke pivot
}

    /**
     * Get the recipe ingredients relationship
     */
    // Di dalam class Recipe
    public function recipeIngredients()
    {
        return $this->hasMany(RecipeIngredient::class, 'recipe_id');
    }

    /**
     * Get the shopping lists for this recipe
     */
    public function shoppingLists()
    {
        return $this->hasMany(ShoppingList::class);
    }


    public function favoritedBy()
    {
        return $this->belongsToMany(\App\Models\User::class, 'favorite_recipes')
                    ->withTimestamps();
    }

    public function bookmarkedByUsers()
    {
        return $this->belongsToMany(User::class, 'bookmarks')
                    ->withTimestamps();
    }
    
    /**
     * Scope for approved recipes
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for pending recipes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for rejected recipes
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Get all ratings for this recipe
     */
    public function ratings()
    {
        return $this->hasMany(RecipeRating::class);
    }

    /**
     * Get user's rating for this recipe
     */
    public function userRating($userId)
    {
        return $this->ratings()->where('user_id', $userId)->first();
    }

    /**
     * Check if user has rated this recipe
     */
    public function isRatedByUser($userId)
    {
        return $this->ratings()->where('user_id', $userId)->exists();
    }

    /**
     * Update average rating and total ratings
     */
    public function updateRating()
    {
        $stats = $this->ratings()
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as total_ratings')
            ->first();

        $this->update([
            'avg_rating' => $stats->avg_rating ? round($stats->avg_rating, 2) : 0,
            'total_ratings' => $stats->total_ratings ?? 0
        ]);
    }

    /**
     * Scope for recipes by region
     */
    public function scopeFromRegion($query, $region)
    {
        return $query->where('region', $region);
    }

    /**
     * Scope for recipes by category
     */
    public function scopeInCategory($query, $kategori)
    {
        return $query->where('kategori', $kategori);
    }

    /**
     * Scope for recipes with maximum cooking time
     */
    public function scopeMaxCookingTime($query, $minutes)
    {
        return $query->where('waktu_masak', '<=', $minutes);
    }

    /**
     * Scope for recipes sorted by rating
     */
    public function scopePopular($query)
    {
        return $query->orderBy('avg_rating', 'desc')
                     ->orderBy('total_ratings', 'desc');
    }

    /**
     * Scope for most viewed recipes
     */
    public function scopeMostViewed($query)
    {
        return $query->orderBy('view_count', 'desc');
    }

    /**
     * Increment view count
     */
    public function incrementViewCount()
    {
        $this->increment('view_count');
    }

    /**
     * Check if recipe is approved
     */
    public function isApproved()
    {
        return $this->status === 'approved';
    }

    /**
     * Check if recipe is pending
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
     * Check if recipe is rejected
     */
    public function isRejected()
    {
        return $this->status === 'rejected';
    }
}