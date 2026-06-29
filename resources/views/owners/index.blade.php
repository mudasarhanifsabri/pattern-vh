<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">People</p>
            <h1 class="text-2xl font-bold text-[#071a3b]">Owners</h1>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if (session('status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ session('status') }}</div>
        @endif

        <div class="erp-card p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-lg font-bold text-[#071a3b]">Owner registry</h2>
                    <p class="mt-1 text-sm text-slate-500">Store owner identity, contact, bank, document, blacklist, and notes history.</p>
                </div>
                @can('owners.manage')
                    <a href="{{ route('owners.create') }}" class="inline-flex h-11 items-center justify-center rounded-xl bg-blue-600 px-4 text-sm font-bold text-white shadow-lg shadow-blue-600/20 hover:bg-blue-700">Add owner</a>
                @endcan
            </div>

            <form method="GET" class="mt-5 grid gap-3 md:grid-cols-[1fr_190px_auto]">
                <input name="search" value="{{ request('search') }}" placeholder="Search name, mobile, email, ID..." class="erp-focus h-11 rounded-xl border border-slate-200 bg-[#f8faff] px-4 text-sm">
                <select name="status" class="erp-focus h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700">
                    <option value="">All owners</option>
                    <option value="blacklisted" @selected(request('status') === 'blacklisted')>Blacklisted only</option>
                </select>
                <button class="rounded-xl bg-slate-900 px-4 text-sm font-bold text-white hover:bg-slate-800">Filter</button>
            </form>

            <div class="mt-5 overflow-hidden rounded-2xl border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-[11px] font-bold uppercase tracking-[0.16em] text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Owner</th>
                            <th class="px-4 py-3">Identity</th>
                            <th class="px-4 py-3">Bank</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($owners as $owner)
                            <tr>
                                <td class="px-4 py-4">
                                    <div class="font-bold text-[#071a3b]">{{ $owner->full_name }}</div>
                                    <div class="text-xs text-slate-500">{{ $owner->mobile_no }} @if ($owner->mobile_has_whatsapp)<span class="text-emerald-600">WhatsApp</span>@endif</div>
                                    <div class="text-xs text-slate-400">{{ $owner->email ?: 'No email' }}</div>
                                </td>
                                <td class="px-4 py-4 text-xs text-slate-600">
                                    <div class="font-bold text-slate-700">{{ str($owner->identity_type)->replace('_', ' ')->headline() }}</div>
                                    <div>{{ $owner->identity_no ?: 'Not added' }}</div>
                                    <div>Expires: {{ $owner->identity_expiry_date?->format('M d, Y') ?? 'Not set' }}</div>
                                </td>
                                <td class="px-4 py-4 text-xs text-slate-600">
                                    <div class="font-bold text-slate-700">{{ $owner->bank_name ?: 'No bank' }}</div>
                                    <div>{{ $owner->iban ?: $owner->bank_account_no }}</div>
                                </td>
                                <td class="px-4 py-4">
                                    @if ($owner->is_blacklisted)
                                        <span class="rounded-full bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-700">Blacklisted</span>
                                    @else
                                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700">Active</span>
                                    @endif
                                    <div class="mt-2 text-xs text-slate-400">{{ $owner->notes_count }} notes</div>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex justify-end gap-2">
                                        @if(auth()->user()?->hasRole('Super Admin'))
                                            <a href="{{ route('admin.portal-preview.start', ['owner', $owner]) }}" target="_blank" rel="noopener" class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-bold text-white hover:bg-blue-700">Open portal</a>
                                        @endif
                                        <a href="{{ route('owners.show', $owner) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50">View</a>
                                        @can('owners.manage')
                                            <a href="{{ route('owners.edit', $owner) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50">Edit</a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">No owners found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-5">{{ $owners->links() }}</div>
        </div>
    </div>
</x-app-layout>
