<x-app-layout>
<x-slot name="header">
    <div>
        <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-blue-600">Finance operations</p>
        <h1 class="text-2xl font-bold text-[#071a3b]">Payment collection requests</h1>
    </div>
</x-slot>

@php
    $statusClasses = [
        'requested' => 'bg-blue-50 text-blue-700',
        'scheduled' => 'bg-amber-50 text-amber-700',
        'collected_pending_verification' => 'bg-purple-50 text-purple-700',
        'approved' => 'bg-emerald-50 text-emerald-700',
        'rejected' => 'bg-rose-50 text-rose-700',
        'cancelled' => 'bg-slate-100 text-slate-600',
    ];
@endphp

<div class="space-y-5">
    @if (session('status'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ session('status') }}</div>
    @endif

    <div class="erp-card p-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-lg font-bold text-[#071a3b]">Doorstep cash / card machine collection</h2>
                <p class="mt-1 text-sm text-slate-500">Requests from tenant PWA. After collection, record proof as pending payment, then finance approves.</p>
            </div>
            <form method="GET" class="flex gap-2">
                <select name="status" class="erp-focus h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm">
                    <option value="">All statuses</option>
                    @foreach (\App\Models\PaymentCollectionRequest::STATUSES as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ str($status)->replace('_', ' ')->headline() }}</option>
                    @endforeach
                </select>
                <button class="rounded-xl bg-slate-900 px-4 text-sm font-bold text-white">Filter</button>
            </form>
        </div>
    </div>

    <div class="space-y-4">
        @forelse ($requests as $requestRecord)
            <div class="erp-card overflow-hidden">
                <div class="grid gap-4 border-b border-slate-100 p-5 xl:grid-cols-[1fr_340px]">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-lg font-black text-[#071a3b]">{{ $requestRecord->request_no }}</h3>
                            <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $statusClasses[$requestRecord->status] ?? 'bg-slate-100 text-slate-600' }}">{{ str($requestRecord->status)->replace('_', ' ')->headline() }}</span>
                            <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700">{{ str($requestRecord->collection_method)->replace('_', ' ')->headline() }}</span>
                        </div>
                        <p class="mt-2 text-sm text-slate-500">{{ $requestRecord->tenant->full_name }} requested AED {{ number_format((float) $requestRecord->amount, 2) }} for {{ $requestRecord->invoice->invoice_no }}.</p>
                        <div class="mt-4 grid gap-3 text-sm md:grid-cols-3">
                            <div class="rounded-2xl bg-slate-50 p-3">
                                <span class="block text-xs font-bold uppercase text-slate-400">Unit</span>
                                <span class="font-bold text-[#071a3b]">{{ $requestRecord->booking->unit->building->name }} / {{ $requestRecord->booking->unit->unit_no }}</span>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-3">
                                <span class="block text-xs font-bold uppercase text-slate-400">Preferred</span>
                                <span class="font-bold text-[#071a3b]">{{ $requestRecord->preferred_date?->format('M d, Y') ?? 'Any date' }} {{ $requestRecord->preferred_time_window }}</span>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-3">
                                <span class="block text-xs font-bold uppercase text-slate-400">Contact</span>
                                <span class="font-bold text-[#071a3b]">{{ $requestRecord->contact_mobile }}</span>
                            </div>
                        </div>
                        <p class="mt-3 rounded-2xl bg-blue-50 p-3 text-sm text-slate-600">{{ $requestRecord->collection_address }}</p>
                        @if ($requestRecord->tenant_notes || $requestRecord->office_notes)
                            <div class="mt-3 grid gap-3 md:grid-cols-2">
                                @if ($requestRecord->tenant_notes)<p class="rounded-2xl bg-slate-50 p-3 text-xs text-slate-600"><span class="font-bold">Tenant:</span> {{ $requestRecord->tenant_notes }}</p>@endif
                                @if ($requestRecord->office_notes)<p class="rounded-2xl bg-slate-50 p-3 text-xs text-slate-600"><span class="font-bold">Office:</span> {{ $requestRecord->office_notes }}</p>@endif
                            </div>
                        @endif
                    </div>

                    <div class="space-y-3">
                        @if (! in_array($requestRecord->status, ['approved', 'rejected', 'cancelled'], true))
                            <form method="POST" action="{{ route('payment-collection-requests.schedule', $requestRecord) }}" class="rounded-2xl border border-slate-200 p-3">
                                @csrf
                                <div class="grid gap-2">
                                    <select name="assigned_to_id" class="erp-focus h-10 rounded-xl border border-slate-200 bg-white px-3 text-xs">
                                        <option value="">Assign team member</option>
                                        @foreach ($teamMembers as $member)
                                            <option value="{{ $member->id }}" @selected($requestRecord->assigned_to_id === $member->id)>{{ $member->full_name }} - {{ str($member->team_role)->headline() }}</option>
                                        @endforeach
                                    </select>
                                    <input name="scheduled_at" type="datetime-local" value="{{ $requestRecord->scheduled_at?->format('Y-m-d\TH:i') }}" class="erp-focus h-10 rounded-xl border border-slate-200 px-3 text-xs">
                                    <textarea name="office_notes" rows="2" class="erp-focus rounded-xl border border-slate-200 px-3 py-2 text-xs" placeholder="Schedule notes">{{ $requestRecord->office_notes }}</textarea>
                                    <button class="rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-bold text-white">Save schedule</button>
                                </div>
                            </form>
                        @endif

                        @if (! $requestRecord->payment_id && ! in_array($requestRecord->status, ['cancelled', 'rejected'], true))
                            <form method="POST" action="{{ route('payment-collection-requests.collect', $requestRecord) }}" enctype="multipart/form-data" class="rounded-2xl border border-emerald-100 bg-emerald-50 p-3">
                                @csrf
                                <div class="grid gap-2">
                                    <input name="amount" value="{{ $requestRecord->amount }}" class="erp-focus h-10 rounded-xl border border-emerald-100 bg-white px-3 text-xs" placeholder="Collected amount">
                                    <input name="collected_at" type="datetime-local" value="{{ now()->format('Y-m-d\TH:i') }}" class="erp-focus h-10 rounded-xl border border-emerald-100 bg-white px-3 text-xs">
                                    <input name="reference_no" class="erp-focus h-10 rounded-xl border border-emerald-100 bg-white px-3 text-xs" placeholder="Receipt/reference no">
                                    <input name="payment_proof" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp" class="rounded-xl border border-emerald-100 bg-white p-2 text-xs">
                                    <textarea name="office_notes" rows="2" class="erp-focus rounded-xl border border-emerald-100 bg-white px-3 py-2 text-xs" placeholder="Collection notes"></textarea>
                                    <button class="rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white">Record collection as pending payment</button>
                                </div>
                            </form>
                        @elseif ($requestRecord->payment)
                            <a href="{{ route('invoices.show', $requestRecord->invoice) }}" class="block rounded-2xl border border-emerald-100 bg-emerald-50 p-3 text-center text-xs font-bold text-emerald-700">Open linked payment {{ $requestRecord->payment->payment_no }}</a>
                        @endif

                        @if (! in_array($requestRecord->status, ['approved', 'rejected', 'cancelled'], true))
                            <form method="POST" action="{{ route('payment-collection-requests.cancel', $requestRecord) }}" class="rounded-2xl border border-rose-100 p-3">
                                @csrf
                                <textarea name="office_notes" rows="2" class="erp-focus w-full rounded-xl border border-rose-100 px-3 py-2 text-xs" placeholder="Cancel reason"></textarea>
                                <button class="mt-2 w-full rounded-xl bg-rose-600 px-4 py-2.5 text-xs font-bold text-white">Cancel request</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="erp-card px-4 py-12 text-center text-sm text-slate-500">No collection requests yet.</div>
        @endforelse
    </div>

    {{ $requests->links() }}
</div>
</x-app-layout>
