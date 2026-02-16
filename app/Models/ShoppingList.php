<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShoppingList extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'recipe_id',
        'nama_list',
        'shopping_date',
        'status',
        'catatan',
        'harga',
        'total_estimated_price',
        'total_actual_price',
    ];

    protected $casts = [
        'shopping_date' => 'date',
        'total_estimated_price' => 'decimal:2',
        'total_actual_price' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function recipe()
    {
        return $this->belongsTo(Recipe::class, 'recipe_id');
    }

    public function items()
    {
        return $this->hasMany(ShoppingListItem::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    /*
    | Helper Methods
    **--------------------------------------------------------------------------
    Recalculate the total estimated & actual prices of all items.
    */
    public function recalculateTotals(): void
    {
        $this->update([
            'total_estimated_price' => $this->items()->sum('estimated_price'),
            'total_actual_price' => $this->items()->whereNotNull('actual_price')->sum('actual_price'),
        ]);
    }
    
    /*
    | Check if all items have been purchased, then update the status
    **--------------------------------------------------------------------------
    */
    public function checkAndCompleteStatus(): void
    {
        $totalItems     = $this->items()->count();
        $purchasedItems = $this->items()->where('is_purchased', true)->count();

        if ($totalItems > 0 && $totalItems === $purchasedItems) {
            $this->update(['status' => 'completed']);
        }
    }

    /**
     * Scopes
     * -------------------------------------------------------------------------
     */
    public function scopePending($q)   { return $q->where('status', 'pending'); }
    public function scopeCompleted($q) { return $q->where('status', 'completed'); }
    
}
