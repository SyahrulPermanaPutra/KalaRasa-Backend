<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShoppingListItem extends Model
{
    protected $fillable = [
        'shopping_list_id', 'ingredient_id', 'nama_item',
        'jumlah', 'satuan', 'estimated_price',
        'actual_price', 'is_purchased', 'purchased_at', 'catatan',
    ];

    protected $casts = [
        'estimated_price' => 'decimal:2',
        'actual_price'    => 'decimal:2',
        'is_purchased'    => 'boolean',
        'purchased_at'    => 'datetime',
    ];

    /*
    | Relationships
    **--------------------------------------------------------------------------
    */
    public function shoppingList()
    {
        return $this->belongsTo(ShoppingList::class);
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function expense()
    {
        return $this->hasOne(Expense::class);
    }
}
