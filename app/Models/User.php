<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Support\Facades\Log;


class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'gender',
        'birthdate',
        'birth_date',
        'points',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

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