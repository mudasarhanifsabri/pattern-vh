<x-dynamic-component component="layouts.maintainer-pwa">
    <header class="app-topbar">
        <a href="{{ route('maintainer.tasks.show', $task) }}" class="grid h-10 w-10 place-items-center rounded-xl" aria-label="Back"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg></a>
        <h1 class="font-black text-[#071a3b]">Add Cost</h1>
        <span class="h-10 w-10"></span>
    </header>

    <main class="app-scroll px-5 pt-5">
        @if($errors->any())<div class="mb-3 rounded-2xl bg-red-50 p-3 text-sm font-bold text-red-700">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('maintainer.tasks.costs.store', $task) }}" class="space-y-4">
            @csrf
            <div class="grid grid-cols-3 gap-2 rounded-2xl bg-slate-100 p-1 text-center text-xs font-black">
                @foreach(['labor' => 'Labor', 'material' => 'Material', 'other' => 'Other'] as $value => $label)
                    <label class="cursor-pointer rounded-xl has-[:checked]:bg-[#6d3be8] has-[:checked]:text-white">
                        <input type="radio" name="type" value="{{ $value }}" class="peer hidden" @checked(old('type', 'labor') === $value)>
                        <span class="block px-3 py-2">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
            <label class="block text-sm font-semibold text-slate-700">Worker / Item Name <span class="text-red-500">*</span><input name="label" required value="{{ old('label') }}" class="app-input mt-2 h-12 px-3"></label>
            <label class="block text-sm font-semibold text-slate-700">Worker Name<input name="worker" value="{{ old('worker') }}" class="app-input mt-2 h-12 px-3"></label>
            <div class="grid grid-cols-2 gap-3">
                <label class="block text-sm font-semibold text-slate-700">Labor Hours<input name="hours" type="number" step="0.01" value="{{ old('hours') }}" class="app-input mt-2 h-12 px-3"></label>
                <label class="block text-sm font-semibold text-slate-700">Labor Rate<input name="rate" type="number" step="0.01" value="{{ old('rate') }}" class="app-input mt-2 h-12 px-3"></label>
                <label class="block text-sm font-semibold text-slate-700">Quantity<input name="quantity" type="number" step="0.01" value="{{ old('quantity') }}" class="app-input mt-2 h-12 px-3"></label>
                <label class="block text-sm font-semibold text-slate-700">Unit Price<input name="unit_price" type="number" step="0.01" value="{{ old('unit_price') }}" class="app-input mt-2 h-12 px-3"></label>
            </div>
            <label class="block text-sm font-semibold text-slate-700">Other Amount<input name="amount" type="number" step="0.01" value="{{ old('amount') }}" class="app-input mt-2 h-12 px-3"></label>
            <button class="app-button bg-[#6d3be8] text-white shadow-lg shadow-purple-600/20">Save Cost</button>
        </form>

        <section class="mt-6">
            <h2 class="font-black text-[#071a3b]">Cost List</h2>
            <div class="mt-3 space-y-2">
                @forelse($task->costItems as $item)
                    <div class="app-card flex items-center justify-between p-3">
                        <div><p class="text-sm font-black text-[#071a3b]">{{ $item->label }}</p><p class="text-xs text-slate-500">{{ str($item->type)->headline() }} - AED {{ number_format((float)$item->amount, 2) }}</p></div>
                        <span class="text-sm font-black text-[#071a3b]">AED {{ number_format((float)$item->amount, 2) }}</span>
                    </div>
                @empty
                    <p class="rounded-2xl border border-dashed border-slate-200 p-5 text-center text-sm text-slate-500">No costs added yet.</p>
                @endforelse
            </div>
        </section>
    </main>
</x-dynamic-component>
