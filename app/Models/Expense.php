<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'shopping_list_id', 'shopping_list_item_id',
        'actual_price', 'purchase_date', 'store_name', 'catatan',
    ];

    protected $casts = [
        'actual_price'  => 'decimal:2',
        'purchase_date' => 'date',
    ];
    
    /*
    | Relationships
    **--------------------------------------------------------------------------
    */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shoppingList()
    {
        return $this->belongsTo(ShoppingList::class);
    }

    public function shoppingListItem()
    {
        return $this->belongsTo(ShoppingListItem::class);
    }

    // Scope untuk filter berdasarkan tanggal
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('tanggal_transaksi', [$startDate, $endDate]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('tanggal_transaksi', now()->month)
                    ->whereYear('tanggal_transaksi', now()->year);
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('tanggal_transaksi', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('tanggal_transaksi', today());
    }
}
