<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'inventory_item_id', 'movement_type', 'quantity', 'related_type', 'related_id',
    'reference', 'notes', 'created_by',
])]
class InventoryMovement extends Model
{
    use SoftDeletes;

    public const TYPES = ['stock_in', 'stock_out', 'adjustment', 'assigned_to_unit', 'returned'];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
        ];
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }
}
