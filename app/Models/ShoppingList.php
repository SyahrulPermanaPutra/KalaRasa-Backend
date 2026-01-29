<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShoppingList extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nama_item',
        'jumlah',
        'satuan',
        'harga',
        'sudah_dibeli',
        'tanggal_dibeli',
        'kategori',
        'catatan',
    ];

    protected $casts = [
        'harga' => 'decimal:2',
        'sudah_dibeli' => 'boolean',
        'tanggal_dibeli' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeBelumDibeli($query)
    {
        return $query->where('sudah_dibeli', false);
    }

    public function scopeSudahDibeli($query)
    {
        return $query->where('sudah_dibeli', true);
    }
}
