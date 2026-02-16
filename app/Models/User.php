<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'gender',
        'birth_date',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function shoppingLists()
    {
        return $this->hasMany(ShoppingList::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function favoriteReseps()
    {
        return $this->belongsToMany(Resep::class, 'favorite_reseps');
    }

    public function createdReseps()
    {
        return $this->hasMany(Resep::class, 'created_by');
    }

    public function approvedReseps()
    {
        return $this->hasMany(Resep::class, 'approved_by');
    }
}
