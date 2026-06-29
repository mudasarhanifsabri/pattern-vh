@php
    $nights = max(1, $booking->check_in_date->diffInDays($booking->check_out_date));
    $lock = $booking->unit->ttLock;
    $refund = $booking->depositRefund;
    $invoices = $booking->invoices->sortBy('due_date');
    $primaryInvoice = $invoices->first();
    $depositReceipt = $booking->depositReceiptRecord;
    $pendingInvoices = $invoices->filter(fn ($invoice) => (float) $invoice->balance_amount > 0 && $invoice->status !== 'cancelled');
    $balanceDue = $pendingInvoices->sum(fn ($invoice) => (float) $invoice->balance_amount);
    $paidAmount = $invoices->sum(fn ($invoice) => (float) $invoice->paid_amount);
    $issueCount = $booking->checkInInspectionItems->whereIn('condition_status', ['damaged', 'missing', 'needs_attention'])->count();
    $checkoutDone = $booking->booking_status === 'checked_out';
    $paymentDone = $invoices->isNotEmpty() ? $balanceDue <= 0 : in_array($booking->booking_status, ['confirmed', 'checked_in', 'checkout_requested', 'checked_out'], true);
    $dtcmDone = $booking->dtcmCheckin?->status === 'registered';
    $accessDone = (bool) $booking->smart_lock_code;
    $inspectionDone = (bool) $refund?->inspection_completed_at;
    $refundDone = $refund?->status === 'refunded';
    $statusClass = match ($booking->booking_status) {
        'confirmed', 'checked_in' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
        'checkout_requested' => 'bg-amber-50 text-amber-700 ring-amber-100',
        'checked_out' => 'bg-blue-50 text-blue-700 ring-blue-100',
        'cancelled' => 'bg-rose-50 text-rose-700 ring-rose-100',
        default => 'bg-slate-50 text-slate-600 ring-slate-100',
    };
    $timeline = [
        ['label' => 'Booking Confirmed', 'date' => $booking->created_at, 'done' => true, 'state' => 'complete'],
        ['label' => 'Payment Received', 'date' => $paymentDone ? $booking->updated_at : null, 'done' => $paymentDone, 'state' => $paymentDone ? 'complete' : 'pending'],
        ['label' => 'DTCM Check-in', 'date' => $booking->dtcmCheckin?->registered_at, 'done' => $dtcmDone, 'state' => $dtcmDone ? 'complete' : 'pending'],
        ['label' => 'Building Access', 'date' => $accessDone ? $booking->smart_lock_code_valid_from : null, 'done' => $accessDone, 'state' => $accessDone ? 'complete' : 'pending'],
        ['label' => 'Inspection', 'date' => $refund?->inspection_completed_at, 'done' => $inspectionDone, 'state' => $inspectionDone ? 'complete' : 'pending'],
        ['label' => 'Deposit Refund', 'date' => $refund?->refund_processed_at, 'done' => $refundDone, 'state' => $refundDone ? 'complete' : 'pending'],
        ['label' => 'Check Out', 'date' => $checkoutDone ? $booking->updated_at : null, 'done' => $checkoutDone, 'state' => $checkoutDone ? 'complete' : 'upcoming'],
    ];
    $refundFlow = [
        ['label' => 'Checkout', 'note' => $checkoutDone ? 'Completed' : 'Waiting checkout', 'done' => $checkoutDone],
        ['label' => 'Inspection', 'note' => $inspectionDone ? 'Report ready' : ($refund ? 'Pending inspection' : 'Starts after checkout'), 'done' => $inspectionDone],
        ['label' => 'Tenant review', 'note' => in_array($refund?->status, ['accepted', 'refund_processing', 'refunded'], true) ? 'Accepted' : ($refund?->status === 'tenant_review' ? 'Waiting tenant' : 'Not ready'), 'done' => in_array($refund?->status, ['accepted', 'refund_processing', 'refunded'], true)],
        ['label' => 'Refund', 'note' => $refundDone ? 'Processed' : 'Pending', 'done' => $refundDone],
    ];
    $activeTasks = $booking->tasks->where('status', '!=', 'completed');
    $cleaningTask = $booking->tasks->firstWhere('task_type', 'checkout_cleaning');
    $inspectionTask = $booking->tasks->firstWhere('task_type', 'checkout_inspection');
    $documentFields = [
        ['Passport', $booking->tenant->passport_emirates_id_no ?? null],
        ['Emirates ID', $booking->tenant->emirates_id_no ?? $booking->tenant->passport_emirates_id_no ?? null],
    ];
    $activityRows = collect()
        ->merge($booking->notificationLogs->take(4)->map(fn ($log) => [
            'title' => str($log->subject ?: $log->channel)->headline(),
            'body' => $log->message,
            'date' => $log->sent_at ?: $log->created_at,
            'by' => $log->sent_at ? 'System' : 'Pending',
            'tone' => $log->sent_at ? 'emerald' : 'amber',
        ]))
        ->merge($booking->tasks->take(3)->map(fn ($task) => [
            'title' => $task->title,
            'body' => str($task->status)->headline().' task',
            'date' => $task->due_at,
            'by' => $task->assignee?->full_name ?? 'Unassigned',
            'tone' => 'blue',
        ]));
@endphp

<div x-data="{ modal: null }" class="space-y-6">
    @if (session('status'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ $errors->first() }}</div>
    @endif

    <section class="border-b border-slate-200 pb-5">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div>
                <a href="{{ route('bookings.index') }}" class="inline-flex items-center gap-2 text-sm font-black text-slate-500 hover:text-blue-600">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7" /></svg>
                    Back to bookings
                </a>
                <div class="mt-3 flex flex-wrap items-center gap-3">
                    <h1 class="text-3xl font-black tracking-tight text-[#071a3b]">{{ $booking->booking_no }}</h1>
                    <span class="rounded-full px-3 py-1 text-xs font-black ring-1 {{ $statusClass }}">{{ str($booking->booking_status)->replace('_', ' ')->headline() }}</span>
                </div>
                <h2 class="mt-2 text-xl font-black text-[#071a3b]">{{ $booking->unit->building->name }} <span class="text-slate-300">•</span> Unit {{ $booking->unit->unit_no }}</h2>
                <p class="mt-1 flex items-center gap-2 text-sm font-semibold text-slate-500">
                    <svg class="h-4 w-4 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 21s7-5.2 7-12a7 7 0 1 0-14 0c0 6.8 7 12 7 12z" /><circle cx="12" cy="9" r="2" /></svg>
                    {{ $booking->unit->building->area ?: 'Dubai, UAE' }}
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                @can('bookings.manage')
                    <a href="{{ route('bookings.edit', $booking) }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-700 shadow-sm hover:border-blue-200 hover:text-blue-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="m16.8 3.2 4 4L7 21H3v-4L16.8 3.2z" /></svg>
                        Edit Booking
                    </a>
                @endcan
                @can('invoices.manage')
                    <a href="{{ route('invoices.create', ['booking_id' => $booking->id]) }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-700 shadow-sm hover:border-blue-200 hover:text-blue-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6M7 3h7l5 5v13H7z" /></svg>
                        Generate Invoice
                    </a>
                @endcan
                <a href="{{ route('bookings.confirmation-pdf', $booking) }}" target="_blank" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-3 text-sm font-black text-white shadow-xl shadow-blue-600/20">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" /><path d="M14 2v6h6" /></svg>
                    Booking Confirmation PDF
                </a>
            </div>
        </div>

        <div class="mt-6 grid gap-3 md:grid-cols-3 xl:grid-cols-6">
            @foreach([
                ['Check In', $booking->check_in_date->format('M d, Y'), $booking->check_in_time ?: '03:00 PM', 'blue'],
                ['Check Out', $booking->check_out_date->format('M d, Y'), $booking->check_out_time ?: '11:00 AM', 'rose'],
                ['Guests', ($booking->guest_count ?: 1).' guest(s)', '', 'violet'],
                ['Nights', $nights.' Night'.($nights === 1 ? '' : 's'), '', 'cyan'],
                ['Source', $booking->source ?: 'Direct', '', 'pink'],
                ['Created On', $booking->created_at->format('M d, Y'), '', 'indigo'],
            ] as [$label, $value, $sub, $tone])
                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <p class="text-[11px] font-black uppercase tracking-[0.14em] text-slate-400">{{ $label }}</p>
                    <p class="mt-2 text-sm font-black text-[#071a3b]">{{ $value }}</p>
                    @if($sub)<p class="mt-1 text-xs font-bold text-slate-500">{{ $sub }}</p>@endif
                </div>
            @endforeach
        </div>
    </section>

    <nav class="sticky top-[72px] z-20 -mx-1 overflow-x-auto border-b border-slate-200 bg-[#f3f7fc]/95 px-1 py-2 backdrop-blur">
        <div class="flex min-w-max gap-2">
            @foreach(['Overview', 'Guest', 'Finance', 'Operations', 'Housekeeping', 'Inspection', 'Smart Lock', 'Documents', 'Activity Log'] as $tab)
                <a href="#{{ str($tab)->slug() }}" class="rounded-xl px-4 py-2 text-sm font-black text-slate-600 hover:bg-white hover:text-blue-600 focus:bg-white focus:text-blue-600">{{ $tab }}</a>
            @endforeach
        </div>
    </nav>

    <div class="grid gap-5 xl:grid-cols-[1fr_360px]">
        <main class="space-y-5">
            <section id="overview" class="grid scroll-mt-32 gap-5 lg:grid-cols-2">
                <div id="guest" class="rounded-[1.4rem] border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex gap-5">
                        <div class="grid h-28 w-28 shrink-0 place-items-center rounded-full bg-blue-100 text-3xl font-black text-blue-700">
                            {{ str($booking->tenant->full_name)->explode(' ')->map(fn ($part) => str($part)->substr(0,1))->take(2)->implode('') }}
                        </div>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-xl font-black text-[#071a3b]">{{ $booking->tenant->full_name }}</h2>
                                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-black text-emerald-700">Verified</span>
                            </div>
                            <div class="mt-3 space-y-2 text-sm font-semibold text-slate-600">
                                <p>{{ $booking->tenant->nationality ?: 'Nationality not set' }}</p>
                                <p>{{ $booking->tenant->mobile_no ?: 'No mobile' }}</p>
                                <p>{{ $booking->tenant->email ?: 'No email' }}</p>
                                @foreach($documentFields as [$label, $number])
                                    <p>{{ $label }}: {{ $number ?: 'Not recorded' }}</p>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        <button type="button" @click="modal = 'documents'" class="rounded-xl border border-blue-100 px-4 py-2.5 text-sm font-black text-blue-700">View documents</button>
                        <a href="{{ route('tenants.show', $booking->tenant) }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-center text-sm font-black text-slate-700">Tenant profile</a>
                    </div>
                </div>

                <div class="overflow-hidden rounded-[1.4rem] border border-slate-200 bg-white shadow-sm">
                    <div class="h-36 bg-[linear-gradient(135deg,rgba(15,23,42,.12),rgba(37,99,235,.22)),url('https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=900&q=80')] bg-cover bg-center"></div>
                    <div class="p-5">
                        <h2 class="text-xl font-black text-[#071a3b]">{{ $booking->unit->building->name }} - Unit {{ $booking->unit->unit_no }}</h2>
                        <div class="mt-4 grid grid-cols-2 gap-3 text-sm font-semibold text-slate-600 sm:grid-cols-4">
                            <span>{{ $booking->unit->bedrooms ?: '-' }} Bedrooms</span>
                            <span>{{ $booking->unit->bathrooms ?: '-' }} Bathrooms</span>
                            <span>{{ $booking->guest_count ?: 1 }} Guests</span>
                            <span>{{ $booking->unit->size_sqft ?: '-' }} Sqft</span>
                        </div>
                        <a href="{{ route('units.show', $booking->unit) }}" class="mt-5 inline-flex rounded-xl border border-blue-100 px-4 py-2.5 text-sm font-black text-blue-700">View property</a>
                    </div>
                </div>
            </section>

            <section class="rounded-[1.4rem] border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-black text-[#071a3b]">Booking timeline</h2>
                    <button type="button" @click="modal = 'timeline'" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-black text-blue-700">View full timeline</button>
                </div>
                <div class="mt-6 overflow-x-auto pb-2">
                    <div class="relative flex min-w-[760px] justify-between">
                        <div class="absolute left-8 right-8 top-4 h-0.5 bg-slate-200"></div>
                        @foreach($timeline as $step)
                            <div class="relative z-10 w-[14%] text-center">
                                <span class="mx-auto grid h-8 w-8 place-items-center rounded-full border-2 {{ $step['done'] ? 'border-emerald-600 bg-emerald-600 text-white' : ($step['state'] === 'upcoming' ? 'border-slate-300 bg-white text-slate-400' : 'border-amber-500 bg-white text-amber-600') }} text-xs font-black">
                                    {{ $step['done'] ? '✓' : '○' }}
                                </span>
                                <p class="mt-3 text-xs font-black text-[#071a3b]">{{ $step['label'] }}</p>
                                <p class="mt-1 text-[11px] font-semibold text-slate-500">{{ $step['date'] ? $step['date']->format('M d, Y') : str($step['state'])->headline() }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section id="finance" class="scroll-mt-32 rounded-[1.4rem] border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-lg font-black text-[#071a3b]">Financial summary</h2>
                    <div class="flex flex-wrap gap-2">
                        @if($depositReceipt)
                            <a href="{{ route('receipts.pdf', $depositReceipt) }}" target="_blank" class="rounded-xl bg-emerald-600 px-3 py-2 text-xs font-black text-white">Deposit receipt</a>
                        @else
                            <span class="rounded-xl bg-amber-50 px-3 py-2 text-xs font-black text-amber-700">Deposit receipt pending</span>
                        @endif
                        <a href="{{ route('invoices.index', ['booking_id' => $booking->id]) }}" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-black text-blue-700">View statement</a>
                    </div>
                </div>
                <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
                    @foreach([
                        ['Rent', $booking->rent_amount, 'slate'],
                        ['VAT (5%)', $booking->vat_amount, 'blue'],
                        ['DTCM fee', $booking->dtcm_fee, 'slate'],
                        ['Deposit', $booking->deposit_amount, 'slate'],
                        ['Total', $booking->total_amount, 'slate'],
                        ['Balance Due', $balanceDue, $balanceDue > 0 ? 'amber' : 'emerald'],
                    ] as [$label, $amount, $tone])
                        <div class="rounded-2xl {{ $tone === 'emerald' ? 'bg-emerald-50 text-emerald-700' : ($tone === 'amber' ? 'bg-amber-50 text-amber-700' : ($tone === 'blue' ? 'bg-blue-50 text-blue-700' : 'bg-slate-50 text-[#071a3b]')) }} p-4">
                            <p class="text-xs font-black uppercase tracking-[0.12em] opacity-70">{{ $label }}</p>
                            <p class="mt-2 text-base font-black">AED {{ number_format((float) $amount, 2) }}</p>
                        </div>
                    @endforeach
                </div>
                <div class="mt-5 overflow-hidden rounded-2xl border border-slate-200">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3">
                        <h3 class="font-black text-[#071a3b]">Pending and attached invoices</h3>
                        <span class="rounded-full bg-white px-3 py-1 text-xs font-black text-slate-600">{{ $pendingInvoices->count() }} pending</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead class="bg-white text-[11px] uppercase tracking-[0.16em] text-slate-400">
                                <tr>
                                    <th class="px-4 py-3">Invoice</th>
                                    <th class="px-4 py-3">Due</th>
                                    <th class="px-4 py-3">Total</th>
                                    <th class="px-4 py-3">Balance</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($invoices as $invoice)
                                    <tr>
                                        <td class="px-4 py-3 font-black text-[#071a3b]">{{ $invoice->invoice_no }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ $invoice->due_date?->format('M d, Y') ?: '-' }}</td>
                                        <td class="px-4 py-3 font-semibold text-slate-700">AED {{ number_format((float) $invoice->total_amount, 2) }}</td>
                                        <td class="px-4 py-3 font-semibold text-slate-700">AED {{ number_format((float) $invoice->balance_amount, 2) }}</td>
                                        <td class="px-4 py-3"><span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-black text-blue-700">{{ str($invoice->status)->replace('_', ' ')->headline() }}</span></td>
                                        <td class="px-4 py-3 text-right"><a href="{{ route('invoices.show', $invoice) }}" class="font-black text-blue-600">View</a></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="px-4 py-8 text-center font-semibold text-slate-500">No invoices attached yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section id="operations" class="scroll-mt-32 rounded-[1.4rem] border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-black text-[#071a3b]">Checkout, inspection, and deposit refund</h2>
                <p class="mt-1 text-sm font-semibold text-slate-500">After checkout, cleaning and technician inspection are created. Refund is calculated from inspection result and shared with tenant.</p>
                <div class="mt-5 grid gap-3 md:grid-cols-4">
                    @foreach($refundFlow as $item)
                        <div class="rounded-2xl border {{ $item['done'] ? 'border-emerald-100 bg-emerald-50' : 'border-slate-200 bg-white' }} p-4">
                            <div class="flex items-center gap-2">
                                <span class="grid h-7 w-7 place-items-center rounded-full {{ $item['done'] ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-400' }} text-xs font-black">{{ $item['done'] ? '✓' : '○' }}</span>
                                <h3 class="font-black text-[#071a3b]">{{ $item['label'] }}</h3>
                            </div>
                            <p class="mt-3 text-sm font-semibold text-slate-500">{{ $item['note'] }}</p>
                        </div>
                    @endforeach
                </div>
                @if($refund)
                    <dl class="mt-5 grid gap-3 md:grid-cols-4">
                        <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-xs font-black uppercase text-slate-400">Deposit</dt><dd class="mt-1 font-black text-[#071a3b]">AED {{ number_format((float) $refund->deposit_amount, 2) }}</dd></div>
                        <div class="rounded-2xl bg-slate-50 p-4"><dt class="text-xs font-black uppercase text-slate-400">Damage</dt><dd class="mt-1 font-black text-[#071a3b]">AED {{ number_format((float) $refund->damage_amount, 2) }}</dd></div>
                        <div class="rounded-2xl bg-emerald-50 p-4"><dt class="text-xs font-black uppercase text-emerald-500">Refund</dt><dd class="mt-1 font-black text-emerald-700">AED {{ number_format((float) $refund->refund_amount, 2) }}</dd></div>
                        <div class="rounded-2xl bg-blue-50 p-4"><dt class="text-xs font-black uppercase text-blue-500">Status</dt><dd class="mt-1 font-black text-blue-700">{{ str($refund->status)->replace('_', ' ')->headline() }}</dd></div>
                    </dl>
                @endif
                <div class="mt-5 flex flex-wrap gap-3">
                    @can('bookings.manage')
                        @unless($checkoutDone)
                            <button type="button" @click="modal = 'checkout'" class="rounded-xl bg-slate-900 px-4 py-3 text-sm font-black text-white">Complete checkout</button>
                        @endunless
                        @if($refund?->status === 'pending_inspection')
                            <button type="button" @click="modal = 'inspection'" class="rounded-xl bg-amber-600 px-4 py-3 text-sm font-black text-white">Complete inspection report</button>
                        @endif
                        @if(in_array($refund?->status, ['accepted', 'refund_processing'], true))
                            <button type="button" @click="modal = 'refund'" class="rounded-xl bg-emerald-600 px-4 py-3 text-sm font-black text-white">Mark refund processed</button>
                        @endif
                    @endcan
                    <a href="{{ route('bookings.inspection', $booking) }}" class="rounded-xl border border-blue-200 px-4 py-3 text-sm font-black text-blue-700">Open apartment inspection</a>
                </div>
            </section>

            <section id="housekeeping" class="scroll-mt-32 rounded-[1.4rem] border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-black text-[#071a3b]">Housekeeping and operations status</h2>
                    <a href="{{ route('tasks.index', ['booking_id' => $booking->id]) }}" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-black text-blue-700">Open task board</a>
                </div>
                <div class="mt-4 divide-y divide-slate-100">
                    @foreach([
                        ['Pre Arrival Cleaning', 'completed'],
                        ['Mid Stay Cleaning', 'not scheduled'],
                        ['Check Out Cleaning', $cleaningTask?->status ?: 'pending'],
                        ['Checkout Inspection', $inspectionTask?->status ?: 'pending'],
                    ] as [$label, $state])
                        <div class="flex items-center justify-between gap-3 py-3">
                            <span class="font-bold text-[#071a3b]">{{ $label }}</span>
                            <span class="rounded-full {{ $state === 'completed' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }} px-2.5 py-1 text-xs font-black">{{ str($state)->headline() }}</span>
                        </div>
                    @endforeach
                </div>
            </section>

            <section id="inspection" class="scroll-mt-32 rounded-[1.4rem] border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-black text-[#071a3b]">Apartment inspection</h2>
                        <p class="mt-1 text-sm font-semibold text-slate-500">Grouped condition report for check-in, checkout, damages, and deposit decision.</p>
                    </div>
                    <span class="rounded-full {{ $issueCount ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' }} px-3 py-1 text-xs font-black">{{ $issueCount }} issue(s)</span>
                </div>
                <div class="mt-5 grid gap-3 md:grid-cols-3">
                    <div class="rounded-2xl bg-slate-50 p-4"><p class="text-xs font-black uppercase text-slate-400">Unit type</p><p class="mt-1 font-black text-[#071a3b]">{{ str($booking->unit->unit_type)->headline() }}</p></div>
                    <div class="rounded-2xl bg-slate-50 p-4"><p class="text-xs font-black uppercase text-slate-400">Saved checks</p><p class="mt-1 font-black text-[#071a3b]">{{ $booking->checkInInspectionItems->count() }}</p></div>
                    <div class="rounded-2xl bg-slate-50 p-4"><p class="text-xs font-black uppercase text-slate-400">Deposit decision</p><p class="mt-1 font-black text-[#071a3b]">{{ $refund ? str($refund->status)->replace('_', ' ')->headline() : 'Not started' }}</p></div>
                </div>
            </section>

            <section id="smart-lock" class="scroll-mt-32 rounded-[1.4rem] border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-black text-[#071a3b]">Smart Lock</h2>
                        <p class="mt-1 text-sm font-semibold text-slate-500">Lock code and booking access details.</p>
                    </div>
                    <span class="rounded-full {{ $booking->smart_lock_code ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }} px-3 py-1 text-xs font-black">{{ $booking->smart_lock_code ? 'Ready' : 'Code pending' }}</span>
                </div>
                <div class="mt-5 grid gap-3 md:grid-cols-4">
                    <div class="rounded-2xl bg-slate-50 p-4 md:col-span-2"><p class="text-xs font-black uppercase text-slate-400">Attached Lock</p><p class="mt-1 font-black text-[#071a3b]">{{ $lock?->lock_name ?: 'No lock attached' }}</p><p class="mt-1 text-xs font-semibold text-slate-500">{{ $lock ? 'TTLock ID '.$lock->lock_id.' / Gateway '.($lock->gateway_id ?: 'Bluetooth only') : 'Attach lock on unit page' }}</p></div>
                    <div class="rounded-2xl bg-blue-50 p-4"><p class="text-xs font-black uppercase text-blue-500">Code</p><p class="mt-1 font-black tracking-[0.22em] text-blue-700">{{ $booking->smart_lock_code ?: 'Pending' }}</p></div>
                    <div class="rounded-2xl bg-slate-50 p-4"><p class="text-xs font-black uppercase text-slate-400">Mode</p><p class="mt-1 font-black text-[#071a3b]">{{ str($booking->smart_lock_code_mode ?: 'auto')->headline() }}</p></div>
                </div>
                @can('bookings.manage')
                    <button type="button" @click="modal = 'smart-lock'" class="mt-5 rounded-xl bg-blue-600 px-4 py-3 text-sm font-black text-white">Manage access code</button>
                @endcan
            </section>

            <section id="documents" class="scroll-mt-32 rounded-[1.4rem] border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-black text-[#071a3b]">Documents</h2>
                <div class="mt-4 grid gap-3 md:grid-cols-3">
                    @foreach(['Booking confirmation', 'Invoice / receipt', 'Tenant documents', 'DTCM check-in', 'Title deed / permit', 'Inspection report'] as $document)
                        <div class="rounded-2xl border border-slate-200 p-4">
                            <p class="font-black text-[#071a3b]">{{ $document }}</p>
                            <p class="mt-1 text-xs font-semibold text-slate-500">Available from related workflow.</p>
                        </div>
                    @endforeach
                </div>
            </section>

            <section id="activity-log" class="scroll-mt-32 rounded-[1.4rem] border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-black text-[#071a3b]">Activity feed</h2>
                    <span class="rounded-full bg-slate-50 px-3 py-1 text-xs font-black text-slate-500">{{ $activityRows->count() }} item(s)</span>
                </div>
                <div class="mt-4 divide-y divide-slate-100">
                    @forelse($activityRows as $row)
                        <div class="grid gap-3 py-4 md:grid-cols-[1fr_190px] md:items-center">
                            <div class="flex gap-3">
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl {{ $row['tone'] === 'emerald' ? 'bg-emerald-50 text-emerald-700' : ($row['tone'] === 'amber' ? 'bg-amber-50 text-amber-700' : 'bg-blue-50 text-blue-700') }}">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" /></svg>
                                </span>
                                <div>
                                    <p class="font-black text-[#071a3b]">{{ $row['title'] }}</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-500">{{ $row['body'] }}</p>
                                </div>
                            </div>
                            <p class="text-right text-xs font-semibold text-slate-500">{{ $row['date']?->format('M d, Y h:i A') ?: '-' }}<br>by {{ $row['by'] }}</p>
                        </div>
                    @empty
                        <p class="rounded-2xl border border-dashed border-slate-200 px-4 py-8 text-center font-semibold text-slate-500">No activity yet.</p>
                    @endforelse
                </div>
            </section>
        </main>

        <aside class="space-y-5 xl:sticky xl:top-28 xl:self-start">
            <section class="rounded-[1.4rem] border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-black text-[#071a3b]">Quick Actions</h2>
                <div class="mt-4 grid grid-cols-2 gap-3">
                    @can('bookings.manage')
                        <button type="button" @click="modal = 'dtcm'" class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2.5 text-xs font-black text-emerald-700">Check In</button>
                        <button type="button" @click="modal = 'checkout'" class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2.5 text-xs font-black text-rose-700">Check Out</button>
                    @endcan
                    <a href="#operations" class="rounded-xl border border-blue-200 bg-blue-50 px-3 py-2.5 text-center text-xs font-black text-blue-700">Extend Stay</a>
                    @can('invoices.manage')
                        <a href="{{ route('invoices.create', ['booking_id' => $booking->id]) }}" class="rounded-xl border border-violet-200 bg-violet-50 px-3 py-2.5 text-center text-xs font-black text-violet-700">Generate Invoice</a>
                    @endcan
                    <a href="https://wa.me/{{ preg_replace('/\D+/', '', (string) $booking->tenant->mobile_no) }}" target="_blank" class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2.5 text-center text-xs font-black text-emerald-700">Send WhatsApp</a>
                    <a href="mailto:{{ $booking->tenant->email }}" class="rounded-xl border border-blue-200 bg-blue-50 px-3 py-2.5 text-center text-xs font-black text-blue-700">Send Email</a>
                    <a href="{{ route('tasks.index', ['booking_id' => $booking->id]) }}" class="rounded-xl border border-orange-200 bg-orange-50 px-3 py-2.5 text-center text-xs font-black text-orange-700">Create Maintenance</a>
                    <button type="button" @click="modal = 'note'" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs font-black text-slate-700">Add Note</button>
                </div>
            </section>

            <section class="rounded-[1.4rem] border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-black text-[#071a3b]">Smart Lock</h2>
                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-black text-emerald-700">{{ $booking->smart_lock_code ? 'Ready' : 'Pending' }}</span>
                </div>
                <p class="mt-4 text-3xl font-black tracking-[0.18em] text-[#071a3b]">{{ $booking->smart_lock_code ?: '------' }}</p>
                <div class="mt-4 grid grid-cols-2 gap-3 text-xs font-semibold text-slate-500">
                    <div class="rounded-2xl bg-slate-50 p-3">Valid From<br><span class="font-black text-[#071a3b]">{{ $booking->smart_lock_code_valid_from?->format('M d, H:i') ?: 'After confirmation' }}</span></div>
                    <div class="rounded-2xl bg-slate-50 p-3">Valid Until<br><span class="font-black text-[#071a3b]">{{ $booking->smart_lock_code_valid_until?->format('M d, H:i') ?: 'Checkout' }}</span></div>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-3">
                    <a href="https://wa.me/{{ preg_replace('/\D+/', '', (string) $booking->tenant->mobile_no) }}?text={{ urlencode('Your Pattern RMS access code for '.$booking->booking_no.' is '.($booking->smart_lock_code ?: 'pending').'.') }}" target="_blank" class="rounded-xl bg-emerald-600 px-3 py-2.5 text-center text-xs font-black text-white">Send via WhatsApp</a>
                    <button type="button" @click="modal = 'smart-lock'" class="rounded-xl border border-blue-200 px-3 py-2.5 text-xs font-black text-blue-700">Access Logs</button>
                </div>
            </section>

            <section class="rounded-[1.4rem] border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-black text-[#071a3b]">Deposit Status</h2>
                    <span class="text-xs font-black text-emerald-700">{{ $refund ? str($refund->status)->replace('_', ' ')->headline() : 'Not started' }}</span>
                </div>
                <div class="mt-4 rounded-2xl border border-slate-200 p-4">
                    <p class="text-xl font-black text-[#071a3b]">AED {{ number_format((float) ($refund?->deposit_amount ?? $booking->deposit_amount), 2) }}</p>
                    <p class="mt-1 text-sm font-semibold text-amber-600">{{ $inspectionDone ? 'Inspection complete' : 'Pending inspection' }}</p>
                </div>
                <div class="mt-3 grid gap-2">
                    @if($depositReceipt)
                        <a href="{{ route('receipts.pdf', $depositReceipt) }}" target="_blank" class="w-full rounded-xl bg-emerald-600 px-4 py-2.5 text-center text-sm font-black text-white">Deposit Receipt</a>
                    @endif
                    <button type="button" @click="modal = 'deposit'" class="w-full rounded-xl border border-blue-100 px-4 py-2.5 text-sm font-black text-blue-700">View Deposit Details</button>
                </div>
            </section>

            <section class="rounded-[1.4rem] border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-black text-[#071a3b]">Notifications</h2>
                    <span class="text-xs font-black text-blue-600">{{ $booking->notificationLogs->count() }}</span>
                </div>
                <div class="mt-4 divide-y divide-slate-100">
                    @forelse($booking->notificationLogs->take(5) as $log)
                        <div class="py-3">
                            <div class="flex items-center justify-between gap-3">
                                <p class="font-black text-[#071a3b]">{{ str($log->subject ?: $log->channel)->headline() }}</p>
                                <span class="h-2 w-2 rounded-full {{ $log->sent_at ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                            </div>
                            <p class="mt-1 text-xs font-semibold text-slate-500">{{ $log->message }}</p>
                        </div>
                    @empty
                        <p class="rounded-2xl border border-dashed border-slate-200 px-4 py-6 text-center text-sm font-semibold text-slate-500">No notifications yet.</p>
                    @endforelse
                </div>
            </section>
        </aside>
    </div>

    <template x-if="modal">
        <div class="fixed inset-0 z-50 grid place-items-center bg-slate-950/50 p-4" @keydown.escape.window="modal = null">
            <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-[1.6rem] bg-white p-5 shadow-2xl" @click.outside="modal = null">
                <div class="mb-5 flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.18em] text-blue-600">Booking action</p>
                        <h2 class="mt-1 text-xl font-black text-[#071a3b]" x-text="{
                            'checkout': 'Complete checkout',
                            'inspection': 'Complete inspection report',
                            'refund': 'Process deposit refund',
                            'smart-lock': 'Manage smart lock access',
                            'extension': 'Extension request',
                            'deposit': 'Deposit details',
                            'documents': 'Tenant documents',
                            'timeline': 'Full timeline',
                            'dtcm': 'DTCM check-in',
                            'note': 'Add note'
                        }[modal]"></h2>
                    </div>
                    <button type="button" @click="modal = null" class="grid h-10 w-10 place-items-center rounded-full border border-slate-200 text-slate-500">×</button>
                </div>

                <div x-show="modal === 'checkout'" class="space-y-4">
                    <p class="rounded-2xl bg-amber-50 p-4 text-sm font-semibold text-amber-700">This will mark the booking checked out, cancel future unpaid invoices, create checkout cleaning and technician inspection tasks, and start the deposit refund workflow.</p>
                    <form method="POST" action="{{ route('bookings.complete-checkout', $booking) }}">
                        @csrf
                        <button class="w-full rounded-xl bg-slate-900 px-4 py-3 text-sm font-black text-white">Confirm checkout and start deposit workflow</button>
                    </form>
                </div>

                <div x-show="modal === 'inspection'" class="space-y-4">
                    @if($refund)
                        <form method="POST" action="{{ route('booking-deposit-refunds.complete-inspection', $refund) }}" class="space-y-3">
                            @csrf
                            <label class="block text-sm font-black text-[#071a3b]">Damage amount
                                <input name="damage_amount" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 px-3" placeholder="0.00" value="{{ old('damage_amount', $refund->damage_amount) }}">
                            </label>
                            <label class="block text-sm font-black text-[#071a3b]">Inspection notes
                                <textarea name="inspection_notes" rows="3" class="erp-focus mt-1 w-full rounded-xl border border-slate-200 px-3 py-2" placeholder="Technician notes">{{ old('inspection_notes', $refund->inspection_notes) }}</textarea>
                            </label>
                            <label class="block text-sm font-black text-[#071a3b]">Damage report shared with tenant
                                <textarea name="damage_report" rows="4" class="erp-focus mt-1 w-full rounded-xl border border-slate-200 px-3 py-2" placeholder="Clear report for tenant review">{{ old('damage_report', $refund->damage_report) }}</textarea>
                            </label>
                            <button class="w-full rounded-xl bg-amber-600 px-4 py-3 text-sm font-black text-white">Send report to tenant</button>
                        </form>
                    @else
                        <p class="rounded-2xl bg-slate-50 p-4 text-sm font-semibold text-slate-600">Complete checkout first to create the deposit refund inspection record.</p>
                    @endif
                </div>

                <div x-show="modal === 'refund'" class="space-y-4">
                    @if($refund)
                        <p class="rounded-2xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-700">Refund amount: AED {{ number_format((float) $refund->refund_amount, 2) }}. Mark this only after bank transfer/payment is processed.</p>
                        <form method="POST" action="{{ route('booking-deposit-refunds.process', $refund) }}">
                            @csrf
                            <button class="w-full rounded-xl bg-emerald-600 px-4 py-3 text-sm font-black text-white">Mark refund processed</button>
                        </form>
                    @endif
                </div>

                <div x-show="modal === 'smart-lock'">
                    @can('bookings.manage')
                        <form method="POST" action="{{ route('bookings.smart-lock-access.update', $booking) }}" class="space-y-3">
                            @csrf
                            <label class="block text-sm font-black text-[#071a3b]">Code mode
                                <select name="smart_lock_code_mode" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 px-3">
                                    <option value="auto" @selected($accessMode === 'auto')>Auto generate after confirmation</option>
                                    <option value="manual" @selected($accessMode === 'manual')>Manual code</option>
                                </select>
                            </label>
                            <label class="block text-sm font-black text-[#071a3b]">Manual code
                                <input name="smart_lock_code" value="{{ old('smart_lock_code', $booking->smart_lock_code) }}" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 px-3" placeholder="Enter code if manual">
                            </label>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <label class="block text-sm font-black text-[#071a3b]">Valid from
                                    <input name="smart_lock_code_valid_from" type="datetime-local" value="{{ $accessValidFrom }}" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 px-3">
                                </label>
                                <label class="block text-sm font-black text-[#071a3b]">Valid until
                                    <input name="smart_lock_code_valid_until" type="datetime-local" value="{{ $accessValidUntil }}" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 px-3">
                                </label>
                            </div>
                            <label class="block text-sm font-black text-[#071a3b]">Internal note
                                <textarea name="smart_lock_code_note" rows="3" class="erp-focus mt-1 w-full rounded-xl border border-slate-200 px-3 py-2">{{ old('smart_lock_code_note', $booking->smart_lock_code_note) }}</textarea>
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm font-black text-slate-600">
                                <input type="checkbox" name="regenerate" value="1" class="rounded border-slate-300">
                                Regenerate auto code now
                            </label>
                            <button class="w-full rounded-xl bg-blue-600 px-4 py-3 text-sm font-black text-white">Save access code</button>
                        </form>
                    @endcan
                </div>

                <div x-show="modal === 'extension'" class="space-y-4">
                    <form method="POST" action="{{ route('bookings.request-extension', $booking) }}" class="space-y-3">
                        @csrf
                        <label class="block text-sm font-black text-[#071a3b]">Requested checkout date
                            <input name="requested_check_out_date" type="date" class="erp-focus mt-1 h-11 w-full rounded-xl border border-slate-200 px-3">
                        </label>
                        <label class="block text-sm font-black text-[#071a3b]">Reason / notes
                            <textarea name="tenant_notes" rows="3" class="erp-focus mt-1 w-full rounded-xl border border-slate-200 px-3 py-2"></textarea>
                        </label>
                        <button class="w-full rounded-xl bg-blue-600 px-4 py-3 text-sm font-black text-white">Send extension request</button>
                    </form>
                </div>

                <div x-show="modal === 'deposit'" class="space-y-3">
                    @if($refund)
                        <div class="grid gap-3 sm:grid-cols-3">
                            <div class="rounded-2xl bg-slate-50 p-4"><p class="text-xs font-black uppercase text-slate-400">Deposit</p><p class="mt-1 font-black text-[#071a3b]">AED {{ number_format((float) $refund->deposit_amount, 2) }}</p></div>
                            <div class="rounded-2xl bg-slate-50 p-4"><p class="text-xs font-black uppercase text-slate-400">Damage</p><p class="mt-1 font-black text-[#071a3b]">AED {{ number_format((float) $refund->damage_amount, 2) }}</p></div>
                            <div class="rounded-2xl bg-emerald-50 p-4"><p class="text-xs font-black uppercase text-emerald-500">Refund</p><p class="mt-1 font-black text-emerald-700">AED {{ number_format((float) $refund->refund_amount, 2) }}</p></div>
                        </div>
                        <p class="rounded-2xl bg-slate-50 p-4 text-sm font-semibold text-slate-600">{{ $refund->damage_report ?: 'No damage report recorded yet.' }}</p>
                    @else
                        <p class="rounded-2xl bg-slate-50 p-4 text-sm font-semibold text-slate-600">Deposit refund starts after checkout.</p>
                    @endif
                </div>

                <div x-show="modal === 'documents'" class="grid gap-3 sm:grid-cols-2">
                    @foreach(['Passport / Emirates ID', 'Booking confirmation', 'Invoice', 'Receipt', 'DTCM check-in', 'Inspection report'] as $doc)
                        <div class="rounded-2xl border border-slate-200 p-4">
                            <p class="font-black text-[#071a3b]">{{ $doc }}</p>
                            <p class="mt-1 text-xs font-semibold text-slate-500">Open from linked record when uploaded.</p>
                        </div>
                    @endforeach
                </div>

                <div x-show="modal === 'timeline'" class="space-y-3">
                    @foreach($timeline as $step)
                        <div class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 p-4">
                            <div>
                                <p class="font-black text-[#071a3b]">{{ $step['label'] }}</p>
                                <p class="text-xs font-semibold text-slate-500">{{ $step['date'] ? $step['date']->format('M d, Y h:i A') : str($step['state'])->headline() }}</p>
                            </div>
                            <span class="rounded-full {{ $step['done'] ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }} px-3 py-1 text-xs font-black">{{ $step['done'] ? 'Complete' : 'Pending' }}</span>
                        </div>
                    @endforeach
                </div>

                <div x-show="modal === 'dtcm'">
                    <p class="rounded-2xl bg-blue-50 p-4 text-sm font-semibold text-blue-700">Open the DTCM workflow after payment confirmation. Booking is checked-in only after guest registration is completed in DTCM.</p>
                    <a href="{{ route('dtcm-checkins.index') }}" class="mt-4 inline-flex w-full justify-center rounded-xl bg-blue-600 px-4 py-3 text-sm font-black text-white">Open DTCM check-ins</a>
                </div>

                <div x-show="modal === 'note'">
                    <p class="rounded-2xl bg-slate-50 p-4 text-sm font-semibold text-slate-600">Notes are currently saved from booking edit and activity modules. This popup keeps space ready for quick notes without stretching the page.</p>
                    <a href="{{ route('bookings.edit', $booking) }}" class="mt-4 inline-flex w-full justify-center rounded-xl bg-slate-900 px-4 py-3 text-sm font-black text-white">Open booking edit</a>
                </div>
            </div>
        </div>
    </template>
</div>
