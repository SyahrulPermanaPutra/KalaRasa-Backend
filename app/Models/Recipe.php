<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
     * Get the additional ingredients for this recipe
     */
    public function additionalIngredients()
    {
        return $this->belongsToMany(Ingredient::class, 'recipe_ingredients')
                    ->withPivot('jumlah')
                    ->wherePivot('is_main', false)
                    ->withTimestamps();
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
     * Get the health condition suitabilities for this recipe
     */
    public function recipeSuitability()
    {
        return $this->hasMany(RecipeSuitability::class, 'recipe_id');
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