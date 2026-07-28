<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">Procurement</p>
            <h1 class="text-2xl font-bold text-[#071a3b]">Vendors & suppliers</h1>
            <p class="mt-1 text-sm text-slate-500">Registered service providers, compliance documents, and contact details.</p>
        </div>
    </x-slot>

    <div class="space-y-5">
        @if(session('status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">{{ session('status') }}</div>
        @endif

        <section class="erp-card p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div><h2 class="text-lg font-bold text-[#071a3b]">Supplier registry</h2><p class="mt-1 text-sm text-slate-500">Keep trade licences, tax certificates, insurance, bank letters, and other supplier documents together.</p></div>
                @can('vendors.manage')
                    <a href="{{ route('vendors.create') }}" class="inline-flex h-11 items-center justify-center rounded-xl bg-blue-600 px-4 text-sm font-bold text-white shadow-lg shadow-blue-600/20 hover:bg-blue-700">Register vendor</a>
                @endcan
            </div>
            <form method="GET" class="mt-5 grid gap-3 md:grid-cols-[1fr_190px_190px_auto]">
                <input name="search" value="{{ request('search') }}" placeholder="Search supplier, contact, licence..." class="erp-focus h-11 rounded-xl border border-slate-200 bg-[#f8faff] px-4 text-sm">
                <select name="category" class="erp-focus h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm"><option value="">All categories</option>@foreach($categories as $category)<option value="{{ $category }}" @selected(request('category') === $category)>{{ str($category)->replace('_', ' ')->headline() }}</option>@endforeach</select>
                <select name="status" class="erp-focus h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm"><option value="">All statuses</option>@foreach($statuses as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ str($status)->replace('_', ' ')->headline() }}</option>@endforeach</select>
                <button class="rounded-xl bg-slate-900 px-4 text-sm font-bold text-white">Filter</button>
            </form>
        </section>

        <section class="erp-card overflow-hidden">
            <div class="space-y-3 p-4 md:hidden">
                @forelse($vendors as $vendor)
                    <a href="{{ route('vendors.show', $vendor) }}" class="block rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-3"><div><p class="text-xs font-bold uppercase tracking-[0.15em] text-blue-600">{{ $vendor->supplier_no }}</p><h2 class="mt-1 text-lg font-black text-[#071a3b]">{{ $vendor->company_name }}</h2><p class="mt-1 text-sm text-slate-500">{{ str($vendor->category)->replace('_', ' ')->headline() }}</p></div><span class="rounded-full {{ $vendor->status === 'active' ? 'bg-emerald-50 text-emerald-700' : ($vendor->status === 'inactive' ? 'bg-slate-100 text-slate-600' : 'bg-amber-50 text-amber-700') }} px-2.5 py-1 text-[11px] font-bold">{{ str($vendor->status)->replace('_', ' ')->headline() }}</span></div>
                        <div class="mt-4 grid grid-cols-2 gap-3 text-xs"><div class="rounded-xl bg-slate-50 p-3"><p class="font-bold text-slate-400">Contact</p><p class="mt-1 font-bold text-[#071a3b]">{{ $vendor->contact_person ?: '-' }}</p></div><div class="rounded-xl bg-slate-50 p-3"><p class="font-bold text-slate-400">Documents</p><p class="mt-1 font-bold text-[#071a3b]">{{ $vendor->documents_count }}</p></div></div>
                    </a>
                @empty
                    <p class="rounded-2xl border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">No vendors or suppliers found.</p>
                @endforelse
            </div>
            <div class="hidden overflow-x-auto md:block">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-[11px] font-bold uppercase tracking-[0.16em] text-slate-500"><tr><th class="px-4 py-3">Supplier</th><th class="px-4 py-3">Category</th><th class="px-4 py-3">Contact</th><th class="px-4 py-3">Trade licence</th><th class="px-4 py-3">Documents</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Actions</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($vendors as $vendor)
                            <tr>
                                <td class="px-4 py-4"><p class="font-bold text-[#071a3b]">{{ $vendor->company_name }}</p><p class="text-xs text-slate-500">{{ $vendor->supplier_no }}</p></td>
                                <td class="px-4 py-4 text-slate-600">{{ str($vendor->category)->replace('_', ' ')->headline() }}</td>
                                <td class="px-4 py-4"><p class="font-bold text-[#071a3b]">{{ $vendor->contact_person ?: '-' }}</p><p class="text-xs text-slate-500">{{ $vendor->mobile_no ?: $vendor->email ?: '-' }}</p></td>
                                <td class="px-4 py-4"><p class="font-bold text-[#071a3b]">{{ $vendor->trade_license_no ?: '-' }}</p><p class="text-xs {{ $vendor->trade_license_expiry_date?->isPast() ? 'text-rose-600' : 'text-slate-500' }}">{{ $vendor->trade_license_expiry_date?->format('M d, Y') ?? 'No expiry recorded' }}</p></td>
                                <td class="px-4 py-4 font-bold text-[#071a3b]">{{ $vendor->documents_count }}</td>
                                <td class="px-4 py-4"><span class="rounded-full {{ $vendor->status === 'active' ? 'bg-emerald-50 text-emerald-700' : ($vendor->status === 'inactive' ? 'bg-slate-100 text-slate-600' : 'bg-amber-50 text-amber-700') }} px-2.5 py-1 text-xs font-bold">{{ str($vendor->status)->replace('_', ' ')->headline() }}</span></td>
                                <td class="px-4 py-4"><div class="flex justify-end gap-2"><a href="{{ route('vendors.show', $vendor) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600">View</a>@can('vendors.manage')<a href="{{ route('vendors.edit', $vendor) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600">Edit</a>@endcan</div></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-10 text-center text-sm text-slate-500">No vendors or suppliers found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div>{{ $vendors->links() }}</div>
    </div>
</x-app-layout>
