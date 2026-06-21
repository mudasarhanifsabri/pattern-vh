<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InventoryController extends Controller
{
    public function index()
    {
        return view('inventory.index', [
            'items' => InventoryItem::with('movements')->orderBy('name')->get(),
            'categories' => InventoryItem::CATEGORIES,
            'movementTypes' => InventoryMovement::TYPES,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'category' => ['required', Rule::in(InventoryItem::CATEGORIES)],
            'sku' => ['nullable', 'string', 'max:100', 'unique:inventory_items,sku'],
            'storage_location' => ['nullable', 'string', 'max:191'],
            'quantity' => ['required', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['available', 'low_stock', 'inactive'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $validated['created_by'] = $request->user()->id;
        $validated['updated_by'] = $request->user()->id;

        $item = InventoryItem::create($validated);
        ActivityLogger::log('inventory.created', "Created inventory item {$item->name}.", $item);

        return redirect()->route('inventory.index')->with('status', 'Inventory item added.');
    }

    public function movement(Request $request, InventoryItem $inventoryItem)
    {
        $validated = $request->validate([
            'movement_type' => ['required', Rule::in(InventoryMovement::TYPES)],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'reference' => ['nullable', 'string', 'max:191'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $validated['created_by'] = $request->user()->id;
        $inventoryItem->movements()->create($validated);

        $change = in_array($validated['movement_type'], ['stock_out', 'assigned_to_unit'], true)
            ? -1 * (float) $validated['quantity']
            : (float) $validated['quantity'];

        $inventoryItem->update([
            'quantity' => max(0, (float) $inventoryItem->quantity + $change),
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('inventory.index')->with('status', 'Inventory movement saved.');
    }
}
