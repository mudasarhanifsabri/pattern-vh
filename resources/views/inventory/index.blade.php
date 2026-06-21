<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">Operations stock</p>
            <h1 class="text-3xl font-black tracking-[-0.04em] text-[#071a3b]">Inventory management</h1>
            <p class="mt-2 text-sm text-slate-500">Track linen, guest supplies, maintenance parts, access cards, and stock movements.</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if (session('status'))<div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ session('status') }}</div>@endif

        <div class="grid gap-4 md:grid-cols-4">
            <div class="erp-card p-5"><p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Items</p><p class="mt-2 text-2xl font-black text-[#071a3b]">{{ $items->count() }}</p></div>
            <div class="erp-card p-5"><p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Low stock</p><p class="mt-2 text-2xl font-black text-amber-600">{{ $items->filter->is_low_stock->count() }}</p></div>
            <div class="erp-card p-5"><p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Value</p><p class="mt-2 text-2xl font-black text-[#071a3b]">AED {{ number_format((float) $items->sum(fn($item) => (float) $item->quantity * (float) $item->unit_cost), 0) }}</p></div>
            <div class="erp-card p-5"><p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Movements</p><p class="mt-2 text-2xl font-black text-[#071a3b]">{{ $items->sum(fn($item) => $item->movements->count()) }}</p></div>
        </div>

        <div class="erp-card p-5">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-[#071a3b]">Inventory register</h2>
                        <p class="mt-1 text-sm text-slate-500">Current stock levels and reorder health.</p>
                    </div>
                    @can('inventory.manage')<div class="flex gap-2"><button type="button" x-data x-on:click="$dispatch('open-modal', 'stock-movement')" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold">Stock movement</button><button type="button" x-data x-on:click="$dispatch('open-modal', 'add-inventory-item')" class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-bold text-white">+ Add item</button></div>@endcan
                </div>
                <div class="mt-5 overflow-hidden rounded-2xl border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-[11px] font-bold uppercase tracking-[0.16em] text-slate-500">
                            <tr><th class="px-4 py-3">Item</th><th class="px-4 py-3">Location</th><th class="px-4 py-3">Qty</th><th class="px-4 py-3">Reorder</th><th class="px-4 py-3">Status</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($items as $item)
                                <tr>
                                    <td class="px-4 py-4"><div class="font-bold text-[#071a3b]">{{ $item->name }}</div><div class="text-xs text-slate-500">{{ str($item->category)->headline() }} · {{ $item->sku ?: 'No SKU' }}</div></td>
                                    <td class="px-4 py-4 text-slate-600">{{ $item->storage_location ?: 'Main store' }}</td>
                                    <td class="px-4 py-4 font-bold text-[#071a3b]">{{ number_format((float) $item->quantity, 2) }}</td>
                                    <td class="px-4 py-4 text-slate-600">{{ number_format((float) $item->reorder_level, 2) }}</td>
                                    <td class="px-4 py-4"><span class="rounded-full {{ $item->is_low_stock ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' }} px-2.5 py-1 text-xs font-bold">{{ $item->is_low_stock ? 'Low stock' : 'Healthy' }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-10 text-center text-slate-500">No inventory items yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
        </div>

        @can('inventory.manage')
            <x-modal name="add-inventory-item" maxWidth="lg" focusable>
                    <form method="POST" action="{{ route('inventory.store') }}" class="p-6">
                        @csrf
                        <div class="flex items-center justify-between"><h2 class="text-lg font-bold text-[#071a3b]">Add inventory item</h2><button type="button" x-on:click="$dispatch('close')" class="rounded-xl bg-slate-100 px-3 py-2 text-sm font-bold">Close</button></div>
                        <div class="mt-4 space-y-3">
                            <input name="name" class="erp-focus h-11 w-full rounded-xl border border-slate-200 px-3 text-sm" placeholder="Item name" required>
                            <select name="category" class="erp-focus h-11 w-full rounded-xl border border-slate-200 px-3 text-sm">@foreach($categories as $category)<option value="{{ $category }}">{{ str($category)->replace('_',' ')->headline() }}</option>@endforeach</select>
                            <input name="sku" class="erp-focus h-11 w-full rounded-xl border border-slate-200 px-3 text-sm" placeholder="SKU">
                            <input name="storage_location" class="erp-focus h-11 w-full rounded-xl border border-slate-200 px-3 text-sm" placeholder="Storage location">
                            <div class="grid grid-cols-2 gap-3"><input name="quantity" class="erp-focus h-11 rounded-xl border border-slate-200 px-3 text-sm" placeholder="Quantity" required><input name="reorder_level" class="erp-focus h-11 rounded-xl border border-slate-200 px-3 text-sm" placeholder="Reorder level"></div>
                            <input name="unit_cost" class="erp-focus h-11 w-full rounded-xl border border-slate-200 px-3 text-sm" placeholder="Unit cost">
                            <input type="hidden" name="status" value="available">
                            <button class="w-full rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-bold text-white">Save item</button>
                        </div>
                    </form>
            </x-modal>
            <x-modal name="stock-movement" maxWidth="lg" focusable>
                    <form method="POST" action="{{ $items->first() ? route('inventory.movement', $items->first()) : '#' }}" class="p-6" data-inventory-movement-form>
                        @csrf
                        <div class="flex items-center justify-between"><h2 class="text-lg font-bold text-[#071a3b]">Stock movement</h2><button type="button" x-on:click="$dispatch('close')" class="rounded-xl bg-slate-100 px-3 py-2 text-sm font-bold">Close</button></div>
                        <div class="mt-4 space-y-3">
                            <select data-inventory-action class="erp-focus h-11 w-full rounded-xl border border-slate-200 px-3 text-sm" required>@foreach($items as $item)<option value="{{ route('inventory.movement', $item) }}">{{ $item->name }}</option>@endforeach</select>
                            <select name="movement_type" class="erp-focus h-11 w-full rounded-xl border border-slate-200 px-3 text-sm">@foreach($movementTypes as $type)<option value="{{ $type }}">{{ str($type)->replace('_',' ')->headline() }}</option>@endforeach</select>
                            <input name="quantity" class="erp-focus h-11 w-full rounded-xl border border-slate-200 px-3 text-sm" placeholder="Quantity" required>
                            <input name="reference" class="erp-focus h-11 w-full rounded-xl border border-slate-200 px-3 text-sm" placeholder="Reference / unit / booking">
                            <textarea name="notes" rows="3" class="erp-focus w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="Movement notes"></textarea>
                            <button class="w-full rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-bold text-white" @disabled($items->isEmpty())>Save movement</button>
                        </div>
                    </form>
            </x-modal>
        @endcan
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('[data-inventory-movement-form]');
            const select = document.querySelector('[data-inventory-action]');
            select?.addEventListener('change', () => form.action = select.value);
        });
    </script>
</x-app-layout>
