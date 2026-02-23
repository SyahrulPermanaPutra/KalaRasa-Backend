<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Log;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'address',
        'avatar',
        'gender',
        'birth_date',
        'points',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'points' => 'integer'
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Add points to user
     */
    public function addPoints(int $points, string $reason = null)
    {
        $this->increment('points', $points);
        
        // Optional: Log activity
        Log::info('User points added', [
            'user_id' => $this->id,
            'user_name' => $this->name,
            'points' => $points,
            'reason' => $reason,
            'total_points' => $this->fresh()->points
        ]);

        return $this;
    }

    public function shoppingLists()
    {
        return $this->hasMany(ShoppingList::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function favoriteRecipes()
    {
        return $this->belongsToMany(Recipe::class, 'favorite_recipes');
    }

    public function createdRecipes()
    {
        return $this->hasMany(Recipe::class, 'created_by');
    }

    public function approvedRecipes()
    {
        return $this->hasMany(Recipe::class, 'approved_by');
    }

    public function bookmarks()
    {
        // Parameter ke-2 adalah nama tabel pivot ('bookmarks')
        return $this->belongsToMany(Recipe::class, 'bookmarks')
                    ->withTimestamps();
    }
}
