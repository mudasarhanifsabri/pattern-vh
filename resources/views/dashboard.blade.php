<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-[11px] font-black uppercase tracking-[0.22em] text-blue-600">{{ $tenant ? 'Tenant app' : ($owner ? 'Owner portal' : 'Admin dashboard') }}</p>
            <h1 class="mt-2 text-3xl font-black tracking-[-0.04em] text-[#071a3b]">{{ $tenant ? 'Home' : ($owner ? 'Owner overview' : 'Operations overview') }}</h1>
            <p class="mt-2 max-w-3xl text-sm text-slate-500">
                {{ $tenant ? 'Manage your active stay, payments, checkout, and refund from one mobile-friendly view.' : ($owner ? 'Track units, occupancy status, account statements, and payouts.' : 'A live view of revenue, occupancy, arrivals, payments, service tasks, and operational alerts.') }}
            </p>
        </div>
    </x-slot>

    @if ($tenant)
        @php
            $booking = $currentBooking;
            $nights = $booking ? $booking->check_in_date->diffInDays($booking->check_out_date) : 0;
            $balanceDue = (float) $tenantBalanceDue;
            $openRefund = $tenantOpenRefund;
        @endphp

        <div class="tenant-app-screen space-y-5">
            @if (session('status'))
                <div class="rounded-3xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">{{ session('status') }}</div>
            @endif

            @if ($booking)
                <section class="overflow-hidden rounded-[1.6rem] bg-white shadow-[0_18px_45px_rgba(15,23,42,0.08)]">
                    <div class="relative h-48 bg-gradient-to-br from-slate-950 via-slate-800 to-blue-700 p-6 text-white">
                        <div class="absolute inset-0 opacity-35" style="background-image: radial-gradient(circle at 80% 10%, rgba(255,255,255,.55), transparent 22%), linear-gradient(135deg, rgba(255,255,255,.08) 0 25%, transparent 25% 50%, rgba(255,255,255,.06) 50% 75%, transparent 75%); background-size: auto, 42px 42px;"></div>
                        <div class="relative flex h-full flex-col justify-end">
                            <h2 class="max-w-[260px] text-3xl font-black leading-tight tracking-[-0.04em]">{{ $booking->unit->building->name }}<br>Unit {{ $booking->unit->unit_no }}</h2>
                            <p class="mt-2 text-sm font-bold text-white/80">Dubai, UAE</p>
                            <span class="mt-4 inline-flex w-fit items-center gap-2 rounded-full bg-emerald-500/90 px-3 py-1.5 text-sm font-black"><span class="grid h-5 w-5 place-items-center rounded-full bg-white/20">✓</span>{{ str($booking->booking_status)->replace('_', ' ')->headline() }}</span>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 divide-x divide-slate-100 px-3 py-4 text-center">
                        <div><p class="text-sm font-semibold text-slate-500">Check-in</p><p class="mt-1 text-base font-black text-blue-600">{{ $booking->check_in_date->format('d M Y') }}</p><p class="text-sm font-semibold text-slate-500">{{ $booking->check_in_time ? \Illuminate\Support\Carbon::parse($booking->check_in_time)->format('h:i A') : '03:00 PM' }}</p></div>
                        <div><p class="text-sm font-semibold text-slate-500">Check-out</p><p class="mt-1 text-base font-black text-blue-600">{{ $booking->check_out_date->format('d M Y') }}</p><p class="text-sm font-semibold text-slate-500">{{ $booking->check_out_time ? \Illuminate\Support\Carbon::parse($booking->check_out_time)->format('h:i A') : '11:00 AM' }}</p></div>
                        <div><p class="text-sm font-semibold text-slate-500">Booking ID</p><p class="mt-1 text-base font-black text-blue-600">{{ $booking->booking_no }}</p><p class="text-sm font-semibold text-slate-500">{{ $nights }} Nights</p></div>
                    </div>
                </section>

                <section class="rounded-[1.6rem] bg-white p-5 shadow-[0_18px_45px_rgba(15,23,42,0.08)]">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                        <h2 class="text-lg font-black text-[#071a3b]">Smart Lock Access</h2>
                        <a href="{{ route('support.index') }}" class="text-sm font-black text-blue-600">How it works?</a>
                    </div>
                    <div class="mt-5 grid grid-cols-[130px_1fr] gap-5">
                        <div class="text-center">
                            <div class="mx-auto grid h-28 w-28 place-items-center rounded-full bg-blue-50 shadow-[inset_0_0_0_18px_rgba(37,99,235,0.04)]">
                                <div class="grid h-16 w-16 place-items-center rounded-3xl bg-blue-600 text-white shadow-xl shadow-blue-600/30">
                                    <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                                </div>
                            </div>
                            <p class="mt-3 text-lg font-black text-blue-600">Tap to Unlock</p>
                            <p class="text-xs font-semibold text-slate-500">Ensure you are near the door</p>
                        </div>
                        <div>
                            <div class="flex items-center justify-between"><h3 class="text-lg font-black text-[#071a3b]">Main Door</h3><span class="rounded-xl bg-emerald-50 px-3 py-1 text-sm font-black text-emerald-700">Active</span></div>
                            <p class="mt-4 text-sm font-semibold text-slate-500">Access Code</p>
                            <div class="mt-2 flex items-center justify-between rounded-2xl bg-blue-50 px-4 py-3 text-3xl font-black tracking-[0.35em] text-blue-600">784512 <span class="text-base tracking-normal">⧉</span></div>
                            <p class="mt-4 text-sm font-semibold text-slate-500">Valid From</p>
                            <p class="font-black text-blue-600">{{ $booking->check_in_date->format('d M Y') }}, {{ $booking->check_in_time ? \Illuminate\Support\Carbon::parse($booking->check_in_time)->format('h:i A') : '03:00 PM' }}</p>
                            <p class="mt-3 text-sm font-semibold text-slate-500">Valid Until</p>
                            <p class="font-black text-blue-600">{{ $booking->check_out_date->format('d M Y') }}, {{ $booking->check_out_time ? \Illuminate\Support\Carbon::parse($booking->check_out_time)->format('h:i A') : '11:00 AM' }}</p>
                        </div>
                    </div>
                    <p class="mt-5 border-t border-slate-100 pt-4 text-sm font-semibold text-slate-600">This code is only valid during your stay.</p>
                </section>
            @else
                <section class="rounded-[1.6rem] bg-white px-5 py-7 text-center shadow-[0_18px_45px_rgba(15,23,42,0.08)]">
                    <h2 class="text-[1.7rem] font-black leading-tight text-[#071a3b]">No active stay</h2>
                    <p class="mx-auto mt-2 max-w-[280px] text-sm leading-6 text-slate-500">Your current booking will appear here once confirmed.</p>
                </section>
            @endif

            <section class="grid grid-cols-2 gap-3">
                @foreach([
                    ['label' => 'Check-in Guide', 'note' => 'Step by step instructions', 'route' => $booking ? route('bookings.show', $booking) : route('bookings.index'), 'icon' => 'M7 4h10v16H7zM10 8h4M10 12h4M10 16h4'],
                    ['label' => 'Wi-Fi Details', 'note' => 'Get apartment Wi-Fi information', 'route' => $booking ? route('bookings.show', $booking) : route('support.index'), 'icon' => 'M5 12a10 10 0 0 1 14 0M8.5 15.5a5 5 0 0 1 7 0M12 19h.01'],
                    ['label' => 'House Rules', 'note' => 'Important rules to follow', 'route' => $booking ? route('bookings.show', $booking) : route('support.index'), 'icon' => 'M12 3 5 6v5c0 5 3 8 7 10 4-2 7-5 7-10V6l-7-3z'],
                    ['label' => 'Need Help?', 'note' => 'Contact support 24/7', 'route' => route('support.index'), 'icon' => 'M4 12a8 8 0 0 1 16 0v4a2 2 0 0 1-2 2h-2v-6h4M4 16a2 2 0 0 0 2 2h2v-6H4v4z'],
                ] as $tile)
                    <a href="{{ $tile['route'] }}" class="relative min-h-[156px] rounded-[1.35rem] bg-white p-4 shadow-[0_14px_30px_rgba(15,23,42,0.07)] [&>.ml-auto]:hidden max-[380px]:min-h-[142px] max-[380px]:p-3">
                        <span class="grid h-14 w-14 place-items-center rounded-2xl bg-blue-50 text-blue-600 max-[380px]:h-12 max-[380px]:w-12"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="{{ $tile['icon'] }}"/></svg></span>
                        <span class="mt-4 block text-[15px] font-black leading-tight text-[#071a3b] max-[380px]:mt-3 max-[380px]:text-sm">{{ $tile['label'] }}</span>
                        <span class="mt-1 block pr-4 text-[13px] font-semibold leading-5 text-slate-500 max-[380px]:text-xs max-[380px]:leading-4">{{ $tile['note'] }}</span>
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-2xl text-slate-300 max-[380px]:right-3">›</span>
                        <span class="ml-auto text-2xl text-slate-400">›</span>
                    </a>
                @endforeach
            </section>

            <section class="overflow-hidden rounded-[1.6rem] bg-gradient-to-br from-blue-50 to-white p-5 shadow-[0_18px_45px_rgba(15,23,42,0.08)]">
                <h2 class="text-xl font-black leading-tight text-[#071a3b]">Enhance Your Stay</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">Book services, request collection, or contact our team.</p>
                <div class="mt-5 grid grid-cols-2 gap-3">
                    <a href="{{ route('tenant.payment-requests.index') }}" class="rounded-2xl bg-blue-600 px-4 py-3 text-center text-sm font-black text-white shadow-lg shadow-blue-600/25">Request Collection</a>
                    <a href="{{ route('support.index') }}" class="rounded-2xl bg-white px-4 py-3 text-center text-sm font-black text-blue-600">Support</a>
                </div>
                <div class="mt-5 grid grid-cols-2 gap-3 text-sm">
                    <div class="rounded-2xl bg-white/80 p-3"><p class="font-semibold text-slate-500">Balance due</p><p class="mt-1 text-lg font-black text-[#071a3b]">AED {{ number_format($balanceDue, 0) }}</p></div>
                    <div class="rounded-2xl bg-white/80 p-3"><p class="font-semibold text-slate-500">Deposit status</p><p class="mt-1 text-lg font-black text-[#071a3b]">{{ $openRefund ? str($openRefund->status)->replace('_', ' ')->headline() : 'Clear' }}</p></div>
                </div>
            </section>
        </div>
    @elseif ($operationsDashboard)
        @php
            $toneClasses = [
                'blue' => 'bg-blue-50 text-blue-700',
                'cyan' => 'bg-cyan-50 text-cyan-700',
                'violet' => 'bg-violet-50 text-violet-700',
                'amber' => 'bg-amber-50 text-amber-700',
                'rose' => 'bg-rose-50 text-rose-700',
            ];
        @endphp

        <div class="-mt-3 mb-5 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 text-xs font-bold text-[#071a3b]">
                    <svg class="h-4 w-4 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg>
                    {{ $operationsDashboard['periodLabel'] }}
                </span>
                <span class="inline-flex h-10 overflow-hidden rounded-xl border border-slate-200 bg-white p-1 text-xs font-bold">
                    <span class="rounded-lg bg-blue-50 px-4 py-2 text-blue-700">Monthly</span>
                    <span class="px-4 py-2 text-slate-500">Daily</span>
                </span>
            </div>
            <span class="inline-flex items-center gap-2 text-xs font-semibold text-slate-500"><span class="h-2 w-2 rounded-full bg-emerald-400"></span>Updated {{ $operationsDashboard['updatedLabel'] }}</span>
        </div>

        <div class="mb-5 grid gap-3 xl:grid-cols-3">
            @foreach($operationsDashboard['alertStrip'] as $alert)
                <a href="{{ route($alert['route']) }}" class="rounded-2xl border p-4 transition hover:-translate-y-0.5 {{ $alert['tone'] === 'rose' ? 'border-rose-200 bg-rose-50 text-rose-700' : ($alert['tone'] === 'cyan' ? 'border-cyan-200 bg-cyan-50 text-cyan-700' : 'border-amber-200 bg-amber-50 text-amber-700') }}">
                    <div class="flex items-center justify-between gap-3">
                        <span class="inline-flex items-center gap-2 text-sm font-black">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 9v4m0 4h.01M10.3 4.3 2 19h20L13.7 4.3a2 2 0 0 0-3.4 0z"/></svg>
                            {{ $alert['label'] }}
                        </span>
                        <span class="text-lg font-black">{{ $alert['value'] }}</span>
                    </div>
                    <p class="mt-2 text-xs opacity-80">{{ $alert['value'] }} {{ $alert['note'] }}</p>
                </a>
            @endforeach
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach($operationsDashboard['cards'] as $card)
                <article class="erp-card p-5 sm:p-6">
                    <div class="flex items-start justify-between">
                        <span class="grid h-11 w-11 place-items-center rounded-2xl {{ $toneClasses[$card['tone']] ?? $toneClasses['blue'] }}">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="{{ $card['icon'] }}"/></svg>
                        </span>
                        <span class="text-xl font-black leading-none text-[#071a3b]">...</span>
                    </div>
                    <p class="mt-5 text-xs font-medium text-slate-500">{{ $card['label'] }}</p>
                    <p class="mt-2 text-3xl font-black tracking-[-0.05em] text-[#071a3b]">{{ $card['value'] }}</p>
                    <p class="mt-4 text-xs text-slate-500"><span class="font-bold text-emerald-600">{{ str($card['note'])->before(' ') }}</span> {{ str($card['note'])->after(' ') }}</p>
                </article>
            @endforeach
        </div>

        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            @foreach($operationsDashboard['miniCards'] as $card)
                <article class="erp-card p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-bold text-slate-500">{{ $card['label'] }}</p>
                            <p class="mt-2 text-2xl font-black tracking-[-0.05em] text-[#071a3b]">{{ $card['value'] }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $card['note'] }}</p>
                        </div>
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl {{ $toneClasses[$card['tone']] ?? $toneClasses['blue'] }}">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 19V5m0 14h16M8 16v-5m4 5V8m4 8v-8"/></svg>
                        </span>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-5 grid gap-5 xl:grid-cols-[1.35fr_0.8fr]">
            <section class="erp-card p-5 sm:p-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="text-lg font-black text-[#071a3b]">Revenue performance</h2>
                        <p class="mt-1 text-sm text-slate-500">Booking revenue and net operating income</p>
                    </div>
                    <div class="flex items-center gap-4 text-xs text-slate-500">
                        <span class="inline-flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-blue-600"></span>Revenue</span>
                        <span class="inline-flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-cyan-300"></span>Net income</span>
                    </div>
                </div>
                <div class="mt-7 flex items-end gap-3">
                    <div>
                        <p class="text-3xl font-black tracking-[-0.05em] text-[#071a3b]">AED {{ number_format($operationsDashboard['revenueTotal'], 0) }}</p>
                        <p class="mt-1 text-xs font-bold text-emerald-600">+12.4% from May</p>
                    </div>
                </div>
                <div class="mt-8 flex h-52 items-end gap-3 border-b border-slate-200">
                    @foreach($operationsDashboard['monthSeries'] as $month)
                        <div class="flex flex-1 flex-col items-center justify-end gap-2">
                            <div class="flex h-44 w-full items-end justify-center gap-1 border-t border-slate-100">
                                <span class="w-4 rounded-t-md bg-blue-600/85" style="height: {{ max(10, ($month['gross'] / $operationsDashboard['maxChartValue']) * 100) }}%"></span>
                                <span class="w-4 rounded-t-md bg-cyan-300" style="height: {{ max(8, ($month['net'] / $operationsDashboard['maxChartValue']) * 100) }}%"></span>
                            </div>
                            <span class="text-[11px] font-semibold text-slate-400">{{ $month['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="erp-card p-5 sm:p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-lg font-black text-[#071a3b]">Occupancy</h2>
                        <p class="mt-1 text-sm text-slate-500">By booking source</p>
                    </div>
                    <span class="text-xl font-black leading-none text-[#071a3b]">...</span>
                </div>
                <div class="mt-7 grid gap-6 md:grid-cols-[180px_1fr] xl:grid-cols-1 2xl:grid-cols-[180px_1fr]">
                    <div class="mx-auto grid h-40 w-40 place-items-center rounded-full bg-[conic-gradient(#2563eb_0_58%,#6d5ce7_58%_76%,#61cdda_76%_100%)] p-5">
                        <div class="grid h-full w-full place-items-center rounded-full bg-white text-center">
                            <div>
                                <p class="text-2xl font-black text-[#071a3b]">{{ $operationsDashboard['occupancy'] }}%</p>
                                <p class="text-[11px] text-slate-500">occupied</p>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-4 self-center">
                        @forelse($operationsDashboard['sourceSplit'] as $source)
                            <div class="flex items-center justify-between gap-3 text-xs">
                                <span class="inline-flex items-center gap-2 font-bold text-[#071a3b]"><span class="h-2.5 w-2.5 rounded-full bg-blue-600"></span>{{ $source['label'] }}</span>
                                <span class="text-slate-500">{{ $source['percent'] }}%</span>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">No source data for this month yet.</p>
                        @endforelse
                    </div>
                </div>
                <div class="mt-7 grid grid-cols-2 border-t border-slate-200 pt-5">
                    <div><p class="text-lg font-black text-[#071a3b]">{{ $operationsDashboard['occupiedNights'] }}</p><p class="text-xs text-slate-500">occupied nights</p></div>
                    <div><p class="text-lg font-black text-[#071a3b]">{{ $operationsDashboard['availableNights'] }}</p><p class="text-xs text-slate-500">available nights</p></div>
                </div>
            </section>
        </div>

        <div class="mt-5 grid gap-5 xl:grid-cols-[1.05fr_0.82fr_0.72fr]">
            <section class="erp-card p-5 sm:p-6">
                <div class="flex items-start justify-between">
                    <div><h2 class="text-lg font-black text-[#071a3b]">Today's movement</h2><p class="mt-1 text-sm text-slate-500">{{ now()->format('F j, Y') }}</p></div>
                    <a href="{{ route('bookings.index') }}" class="text-xs font-black text-blue-600">View bookings</a>
                </div>
                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    <div class="rounded-2xl bg-slate-50 p-4"><p class="text-[10px] font-bold uppercase text-slate-400">Check-ins today</p><p class="mt-1 text-2xl font-black text-[#071a3b]">{{ $operationsDashboard['checkinsToday'] }}</p></div>
                    <div class="rounded-2xl bg-slate-50 p-4"><p class="text-[10px] font-bold uppercase text-slate-400">Check-outs today</p><p class="mt-1 text-2xl font-black text-[#071a3b]">{{ $operationsDashboard['checkoutsToday'] }}</p></div>
                </div>
                <div class="mt-5 space-y-3">
                    @forelse($operationsDashboard['todayMovements'] as $booking)
                        <a href="{{ route('bookings.show', $booking) }}" class="flex items-center justify-between gap-3 rounded-2xl border border-slate-100 px-3 py-3 hover:bg-slate-50">
                            <div class="flex items-center gap-3">
                                <span class="grid h-10 w-10 place-items-center rounded-2xl bg-blue-100 text-xs font-black text-blue-700">{{ str($booking->tenant->full_name)->explode(' ')->map(fn($part) => str($part)->substr(0, 1))->take(2)->implode('') }}</span>
                                <div><p class="text-sm font-black text-[#071a3b]">{{ $booking->tenant->full_name }}</p><p class="text-xs text-slate-500">{{ $booking->unit->building->name }} / {{ $booking->unit->unit_no }}</p></div>
                            </div>
                            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700">{{ now()->isSameDay($booking->check_in_date) ? 'Arrival' : 'Checkout' }}</span>
                        </a>
                    @empty
                        <p class="rounded-2xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-500">No arrivals or checkouts today.</p>
                    @endforelse
                </div>
            </section>

            <section class="erp-card p-5 sm:p-6">
                <div class="flex items-start justify-between">
                    <div><h2 class="text-lg font-black text-[#071a3b]">Needs attention</h2><p class="mt-1 text-sm text-slate-500">Operational alerts</p></div>
                    <span class="rounded-full bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-600">{{ collect($operationsDashboard['alerts'])->count() }} open</span>
                </div>
                <div class="mt-5 divide-y divide-slate-100">
                    @foreach($operationsDashboard['alerts'] as $alert)
                        <a href="{{ route($alert['route']) }}" class="flex items-center justify-between gap-3 py-3">
                            <div class="flex items-center gap-3">
                                <span class="grid h-10 w-10 place-items-center rounded-2xl {{ $toneClasses[$alert['tone']] ?? $toneClasses['blue'] }}">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3 4 7v10l8 4 8-4V7zM12 3v18M4 7l8 4 8-4"/></svg>
                                </span>
                                <span><span class="block text-sm font-black text-[#071a3b]">{{ $alert['label'] }}</span><span class="block text-xs text-slate-500">{{ $alert['note'] }}</span></span>
                            </div>
                            <span class="text-xl text-slate-400">›</span>
                        </a>
                    @endforeach
                </div>
            </section>

            <section class="erp-card p-5 sm:p-6">
                <h2 class="text-lg font-black text-[#071a3b]">Quick actions</h2>
                <p class="mt-1 text-sm text-slate-500">Common workflows</p>
                <div class="mt-5 divide-y divide-slate-100">
                    @foreach($quickActions as $action)
                        @if (! isset($action['can']) || auth()->user()->can($action['can']))
                            <a href="{{ route($action['route']) }}" class="flex items-center justify-between gap-3 py-3">
                                <span class="flex items-center gap-3">
                                    <span class="grid h-10 w-10 place-items-center rounded-2xl bg-blue-50 text-blue-700">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 5v14M5 12h14"/></svg>
                                    </span>
                                    <span><span class="block text-sm font-black text-[#071a3b]">{{ $action['label'] }}</span><span class="block text-xs text-slate-500">{{ $action['note'] }}</span></span>
                                </span>
                                <span class="text-slate-400">↗</span>
                            </a>
                        @endif
                    @endforeach
                </div>
            </section>
        </div>

        <div class="mt-5 grid gap-5 xl:grid-cols-[1.1fr_0.9fr]">
            <section class="erp-card p-5 sm:p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-lg font-black text-[#071a3b]">Financial summary</h2>
                        <p class="mt-1 text-sm text-slate-500">This month's financial overview</p>
                    </div>
                    <a href="{{ route('accounting.index') }}" class="text-xs font-black text-blue-600">Accounting</a>
                </div>
                <div class="mt-6 grid gap-3 md:grid-cols-4">
                    <div class="rounded-3xl bg-slate-50 p-5 text-center">
                        <p class="text-xs font-bold text-slate-400">Net income</p>
                        <p class="mt-2 text-2xl font-black {{ $operationsDashboard['financialSummary']['netIncome'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">AED {{ number_format($operationsDashboard['financialSummary']['netIncome'], 0) }}</p>
                        <p class="mt-1 text-xs text-slate-500">This month</p>
                    </div>
                    <div class="rounded-3xl bg-amber-50 p-5 text-center">
                        <p class="text-xs font-bold text-amber-600">Outstanding</p>
                        <p class="mt-2 text-2xl font-black text-amber-600">AED {{ number_format($operationsDashboard['financialSummary']['outstanding'], 0) }}</p>
                        <p class="mt-1 text-xs text-amber-600/75">Pending invoices</p>
                    </div>
                    <div class="rounded-3xl bg-rose-50 p-5 text-center">
                        <p class="text-xs font-bold text-rose-500">Expenses</p>
                        <p class="mt-2 text-2xl font-black text-rose-600">AED {{ number_format($operationsDashboard['financialSummary']['expenses'], 0) }}</p>
                        <p class="mt-1 text-xs text-rose-500/75">This month</p>
                    </div>
                    <div class="rounded-3xl bg-blue-50 p-5 text-center">
                        <p class="text-xs font-bold text-blue-600">Revenue change</p>
                        <p class="mt-2 text-2xl font-black text-blue-700">{{ number_format($operationsDashboard['financialSummary']['revenueChange'], 1) }}%</p>
                        <p class="mt-1 text-xs text-blue-500">vs last month</p>
                    </div>
                </div>
            </section>

            <section class="erp-card p-5 sm:p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-lg font-black text-[#071a3b]">Owner transfers</h2>
                        <p class="mt-1 text-sm text-slate-500">Owner payout status after approved collections</p>
                    </div>
                    <a href="{{ route('owner-payouts.index') }}" class="text-xs font-black text-blue-600">Open manager</a>
                </div>
                <div class="mt-6 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-3xl bg-emerald-50 p-4">
                        <p class="text-xs font-bold text-emerald-700">Ready estimate</p>
                        <p class="mt-2 text-xl font-black text-emerald-700">AED {{ number_format($operationsDashboard['ownerTransfers']['ready'], 0) }}</p>
                    </div>
                    <div class="rounded-3xl bg-violet-50 p-4">
                        <p class="text-xs font-bold text-violet-700">Transferred</p>
                        <p class="mt-2 text-xl font-black text-violet-700">AED {{ number_format($operationsDashboard['ownerTransfers']['transferred'], 0) }}</p>
                    </div>
                    <div class="rounded-3xl bg-slate-50 p-4">
                        <p class="text-xs font-bold text-slate-500">Transfers</p>
                        <p class="mt-2 text-xl font-black text-[#071a3b]">{{ $operationsDashboard['ownerTransfers']['count'] }}</p>
                    </div>
                </div>
                <p class="mt-4 rounded-2xl bg-blue-50 p-4 text-xs leading-5 text-blue-700">When finance marks a payout transferred, the bank reference and transfer date are saved for owner account history.</p>
            </section>
        </div>

        <div class="mt-5 grid gap-5 xl:grid-cols-[0.9fr_0.9fr_1fr]">
            <section class="erp-card p-5 sm:p-6">
                <h2 class="text-lg font-black text-[#071a3b]">Property distribution</h2>
                <p class="mt-1 text-sm text-slate-500">Distribution by unit type</p>
                <div class="mt-6 grid place-items-center">
                    <div class="grid h-44 w-44 place-items-center rounded-full bg-[conic-gradient(#22c55e_0_45%,#0ea5e9_45%_68%,#f97316_68%_84%,#ef4444_84%_100%)] p-6">
                        <div class="grid h-full w-full place-items-center rounded-full bg-white text-center">
                            <div><p class="text-xs text-slate-500">Total</p><p class="text-2xl font-black text-[#071a3b]">{{ $operationsDashboard['propertyDistribution']->sum('total') }}</p></div>
                        </div>
                    </div>
                </div>
                <div class="mt-5 flex flex-wrap gap-3 text-xs text-slate-500">
                    @foreach($operationsDashboard['propertyDistribution'] as $row)
                        <span class="inline-flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-blue-500"></span>{{ $row->type_name }}: {{ $row->total }}</span>
                    @endforeach
                </div>
            </section>

            <section class="erp-card p-5 sm:p-6">
                <h2 class="text-lg font-black text-[#071a3b]">Payment status</h2>
                <p class="mt-1 text-sm text-slate-500">Current month payment collection</p>
                @php($paymentTotal = max($operationsDashboard['paymentStatus']['collected'] + $operationsDashboard['paymentStatus']['pending'] + $operationsDashboard['paymentStatus']['overdue'], 1))
                <div class="mt-7">
                    <div class="flex justify-between text-sm font-black"><span class="text-slate-600">Collected</span><span class="text-emerald-600">AED {{ number_format($operationsDashboard['paymentStatus']['collected'], 0) }}</span></div>
                    <div class="mt-3 h-3 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-emerald-500" style="width: {{ ($operationsDashboard['paymentStatus']['collected'] / $paymentTotal) * 100 }}%"></div>
                    </div>
                </div>
                <div class="mt-6 grid grid-cols-2 gap-3">
                    <div class="rounded-3xl bg-amber-50 p-5 text-center"><p class="text-xl font-black text-amber-600">AED {{ number_format($operationsDashboard['paymentStatus']['pending'], 0) }}</p><p class="text-xs text-amber-600/80">Pending</p></div>
                    <div class="rounded-3xl bg-rose-50 p-5 text-center"><p class="text-xl font-black text-rose-600">AED {{ number_format($operationsDashboard['paymentStatus']['overdue'], 0) }}</p><p class="text-xs text-rose-600/80">Overdue</p></div>
                </div>
            </section>

            <section class="space-y-5">
                <div class="erp-card p-5 sm:p-6">
                    <h2 class="text-lg font-black text-[#071a3b]">Recent activity</h2>
                    <p class="mt-1 text-sm text-slate-500">Latest updates across the workspace</p>
                    <div class="mt-5 space-y-3">
                        @forelse($operationsDashboard['recentActivity'] as $activity)
                            <div class="flex gap-3 rounded-2xl border border-slate-100 p-3">
                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-2xl bg-blue-50 text-blue-700">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h16v12H5.5L4 18z"/></svg>
                                </span>
                                <div class="min-w-0"><p class="truncate text-sm font-black text-[#071a3b]">{{ $activity->subject ?: str($activity->channel)->headline() }}</p><p class="mt-1 text-xs text-slate-500">{{ $activity->created_at->diffForHumans() }}</p></div>
                            </div>
                        @empty
                            <p class="rounded-2xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-500">No recent activity yet.</p>
                        @endforelse
                    </div>
                </div>

                <div class="erp-card p-5 sm:p-6">
                    <h2 class="text-lg font-black text-[#071a3b]">Upcoming tasks</h2>
                    <p class="mt-1 text-sm text-slate-500">Important tasks requiring attention</p>
                    <div class="mt-5 space-y-3">
                        @forelse($operationsDashboard['upcomingTasks'] as $task)
                            <a href="{{ route('tasks.index') }}" class="flex items-center justify-between gap-3 rounded-2xl border border-slate-100 p-3 hover:bg-slate-50">
                                <span><span class="block text-sm font-black text-[#071a3b]">{{ $task->title }}</span><span class="text-xs text-slate-500">{{ $task->due_at?->format('M d, Y') ?? 'No due date' }}</span></span>
                                <span class="rounded-full {{ $task->priority === 'urgent' ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700' }} px-2.5 py-1 text-xs font-bold">{{ str($task->priority)->headline() }}</span>
                            </a>
                        @empty
                            <p class="rounded-2xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-500">No open tasks.</p>
                        @endforelse
                    </div>
                </div>
            </section>
        </div>
    @else
        @if ($tenant)
            <section class="mb-5 overflow-hidden rounded-[2rem] bg-gradient-to-br from-[#061a38] via-[#0d2b5c] to-[#2563eb] p-5 text-white shadow-2xl shadow-blue-950/20 sm:p-7">
                <div class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.22em] text-blue-200">My stay</p>
                        <h2 class="mt-3 text-3xl font-black tracking-[-0.04em]">Welcome, {{ $tenant->full_name }}</h2>
                        <p class="mt-2 max-w-2xl text-sm text-blue-100">One active stay at a time. Manage extension, payment collection, check-out, and deposit refund from your mobile.</p>
                    </div>
                    <a href="{{ route('tenant.payment-requests.index') }}" class="inline-flex h-14 items-center justify-center rounded-2xl bg-white px-5 text-sm font-black text-[#061a38]">Request payment collection</a>
                </div>
            </section>

            @if ($currentBooking)
                <section class="mb-5 rounded-[1.75rem] border border-blue-100 bg-white p-5 shadow-xl shadow-blue-950/5">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Current stay</p>
                            <h3 class="mt-2 text-2xl font-black tracking-[-0.04em] text-[#071a3b]">{{ $currentBooking->unit->building->name }} / Unit {{ $currentBooking->unit->unit_no }}</h3>
                            <p class="mt-1 text-sm text-slate-500">{{ $currentBooking->check_in_date->format('M d') }} to {{ $currentBooking->check_out_date->format('M d, Y') }} - {{ str($currentBooking->booking_status)->replace('_', ' ')->headline() }}</p>
                        </div>
                        <a href="{{ route('bookings.show', $currentBooking) }}" class="inline-flex items-center justify-center rounded-2xl bg-blue-600 px-5 py-3 text-sm font-black text-white">Open stay</a>
                    </div>
                </section>
            @endif
        @endif

        @if ($owner)
            <section class="mb-5 overflow-hidden rounded-[2rem] bg-gradient-to-br from-[#061a38] via-[#0d2b5c] to-[#2563eb] p-5 text-white shadow-2xl shadow-blue-950/20 sm:p-7">
                <div class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.22em] text-blue-200">Pattern owner portal</p>
                        <h2 class="mt-3 text-3xl font-black tracking-[-0.04em]">Welcome, {{ $owner->full_name }}</h2>
                        <p class="mt-2 max-w-2xl text-sm text-blue-100">Track units, occupancy status, owner expenses, account statements, and payouts.</p>
                    </div>
                    <a href="{{ route('owner-statements.index') }}" class="inline-flex h-14 items-center justify-center rounded-2xl bg-white px-5 text-sm font-black text-[#061a38]">View statement</a>
                </div>
            </section>
        @endif

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($stats as $card)
                <article class="erp-card p-5">
                    <p class="text-xs font-medium text-slate-500">{{ $card['label'] }}</p>
                    <p class="mt-2 truncate text-2xl font-black tracking-[-0.04em] text-[#071a3b]">{{ $card['value'] }}</p>
                    <p class="mt-3 text-[11px] leading-5 text-slate-500">{{ $card['note'] }}</p>
                </article>
            @endforeach
        </div>

        <section class="mt-5 erp-card p-5">
            <h2 class="text-lg font-black text-[#071a3b]">{{ $tenant ? 'Mobile app actions' : 'Owner actions' }}</h2>
            <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($quickActions as $action)
                    <a href="{{ route($action['route']) }}" class="rounded-3xl bg-blue-600 p-4 text-white shadow-xl shadow-blue-600/20 transition active:scale-[0.98]">
                        <span class="block text-base font-black">{{ $action['label'] }}</span>
                        <span class="mt-2 block text-xs leading-5 opacity-80">{{ $action['note'] }}</span>
                    </a>
                @endforeach
            </div>
        </section>

        @if ($owner)
            <section class="mt-5 erp-card p-5">
                <h2 class="text-lg font-black text-[#071a3b]">My units status</h2>
                <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    @forelse($ownerUnits as $unit)
                        <a href="{{ route('units.show', $unit) }}" class="rounded-3xl border border-slate-200 bg-white p-4 hover:bg-slate-50">
                            <p class="text-xs font-bold text-slate-500">{{ $unit->building->name }}</p>
                            <h3 class="mt-1 text-lg font-black text-[#071a3b]">Unit {{ $unit->unit_no }}</h3>
                            <p class="mt-1 text-xs text-slate-500">{{ $unit->unit_type }} / {{ str($unit->availability_status)->headline() }}</p>
                        </a>
                    @empty
                        <p class="rounded-2xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-500">No units assigned yet.</p>
                    @endforelse
                </div>
            </section>
        @endif
    @endif
</x-app-layout>
