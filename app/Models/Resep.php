<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resep extends Model
{
    use HasFactory;

    protected $table = 'recipes';

    protected $fillable = [
        'nama',
        'tingkat_kesulitan',
        'waktu_masak',
        'kalori_per_porsi',
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
        'view_count',
    ];

    protected $casts = [
        'bahan_makanan' => 'array',
        'approved_at'   => 'datetime',
        'avg_rating'    => 'decimal:2',
        'total_ratings' => 'integer',
        'view_count'    => 'integer',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function favoritedBy()
    {
        return $this->belongsToMany(User::class, 'favorite_reseps');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function ingredients()
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
