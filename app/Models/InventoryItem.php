<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name', 'category', 'sku', 'storage_location', 'quantity', 'reorder_level',
    'unit_cost', 'status', 'notes', 'created_by', 'updated_by',
])]
class InventoryItem extends Model
{
    use SoftDeletes;

    public const CATEGORIES = ['linen', 'amenities', 'cleaning', 'maintenance', 'guest_supplies', 'keys_cards', 'general'];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'reorder_level' => 'decimal:2',
            'unit_cost' => 'decimal:2',
        ];
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function getIsLowStockAttribute(): bool
    {
        return (float) $this->quantity <= (float) $this->reorder_level;
    }
}
