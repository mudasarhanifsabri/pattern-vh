<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-[11px] font-black uppercase tracking-[0.22em] text-blue-600">Support Center</p>
                <h1 class="text-3xl font-black tracking-[-0.04em] text-[#071a3b]">New support request</h1>
                <p class="mt-2 text-sm text-slate-500">Open a live chat or formal ticket and link it to booking, property, payment, tenant, owner, or team records.</p>
            </div>
            <a href="{{ route('support.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-black text-slate-700">Back to inbox</a>
        </div>
    </x-slot>

    <div class="grid gap-5 xl:grid-cols-[1fr_360px]">
        <form method="POST" action="{{ route('support.store') }}" enctype="multipart/form-data" class="erp-card p-6">
            @csrf
            @if($errors->any())<div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700">{{ $errors->first() }}</div>@endif

            <div class="grid gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="text-xs font-black text-slate-500">Request mode</span>
                    <select name="mode" class="erp-focus mt-1 h-12 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold">
                        <option value="chat">Live chat</option>
                        <option value="ticket">Ticket</option>
                    </select>
                </label>
                <label class="block">
                    <span class="text-xs font-black text-slate-500">Category</span>
                    <select name="support_category_id" class="erp-focus mt-1 h-12 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold">
                        <option value="">General</option>
                        @foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="text-xs font-black text-slate-500">Priority</span>
                    <select name="priority" class="erp-focus mt-1 h-12 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold">
                        @foreach(\App\Models\SupportTicket::PRIORITIES as $priority)<option value="{{ $priority }}">{{ str($priority)->headline() }}</option>@endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="text-xs font-black text-slate-500">Subject</span>
                    <input name="subject" value="{{ old('subject') }}" class="erp-focus mt-1 h-12 w-full rounded-2xl border border-slate-200 px-4 text-sm font-bold" placeholder="Short request title" required>
                </label>
                <label class="block md:col-span-2">
                    <span class="text-xs font-black text-slate-500">Message</span>
                    <textarea name="description" rows="5" class="erp-focus mt-1 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm" placeholder="Write the request details..." required>{{ old('description') }}</textarea>
                </label>
                <label class="flex min-h-28 cursor-pointer flex-col items-center justify-center rounded-2xl border border-dashed border-blue-200 bg-blue-50 px-4 text-center text-sm font-black text-blue-700 md:col-span-2">
                    Attach file or image
                    <span class="mt-1 text-xs font-bold text-blue-400">PDF, JPG, PNG, DOC, XLS up to 10 MB</span>
                    <input type="file" name="attachment" class="sr-only">
                </label>
            </div>

            @if($manage)
                <div class="mt-6 rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
                    <p class="text-sm font-black text-[#071a3b]">Link records</p>
                    <p class="mt-1 text-xs text-slate-500">Optional, but useful for faster support handling.</p>
                    <div class="mt-4 grid gap-3 md:grid-cols-2">
                        <select name="booking_id" class="erp-focus h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm"><option value="">Link booking</option>@foreach($bookings as $booking)<option value="{{ $booking->id }}">{{ $booking->booking_no }} / {{ $booking->tenant?->full_name }}</option>@endforeach</select>
                        <select name="unit_id" class="erp-focus h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm"><option value="">Link property</option>@foreach($units as $unit)<option value="{{ $unit->id }}">{{ $unit->building?->name }} / {{ $unit->unit_no }}</option>@endforeach</select>
                        <select name="tenant_id" class="erp-focus h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm"><option value="">Link tenant</option>@foreach($tenants as $tenant)<option value="{{ $tenant->id }}">{{ $tenant->full_name }}</option>@endforeach</select>
                        <select name="owner_id" class="erp-focus h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm"><option value="">Link owner</option>@foreach($owners as $owner)<option value="{{ $owner->id }}">{{ $owner->full_name }}</option>@endforeach</select>
                        <select name="agent_id" class="erp-focus h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm"><option value="">Link agent</option>@foreach($agents as $agent)<option value="{{ $agent->id }}">{{ $agent->full_name }}</option>@endforeach</select>
                        <select name="operations_team_member_id" class="erp-focus h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm"><option value="">Link maintainer</option>@foreach($maintainers as $maintainer)<option value="{{ $maintainer->id }}">{{ $maintainer->full_name }}</option>@endforeach</select>
                        <select name="payment_id" class="erp-focus h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm md:col-span-2"><option value="">Link payment</option>@foreach($payments as $payment)<option value="{{ $payment->id }}">{{ $payment->payment_no }} / AED {{ number_format((float) $payment->amount, 2) }}</option>@endforeach</select>
                    </div>
                </div>
            @endif

            <div class="mt-6 flex justify-end gap-3">
                <a href="{{ route('support.index') }}" class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-black text-slate-700">Cancel</a>
                <button class="rounded-2xl bg-blue-600 px-6 py-3 text-sm font-black text-white shadow-lg shadow-blue-600/20">Open conversation</button>
            </div>
        </form>

        <aside class="space-y-4">
            <div class="erp-card p-5">
                <p class="text-lg font-black text-[#071a3b]">Support guide</p>
                <div class="mt-4 space-y-3 text-sm leading-6 text-slate-600">
                    <p class="rounded-2xl bg-blue-50 p-4 text-blue-700">Use live chat for quick questions. Use ticket mode for items that need tracking or approval.</p>
                    <p class="rounded-2xl bg-emerald-50 p-4 text-emerald-700">Attach payment proof, ID files, inspection images, or owner documents directly to the request.</p>
                    <p class="rounded-2xl bg-amber-50 p-4 text-amber-700">Public support link is available for guests who do not have a portal login yet.</p>
                </div>
            </div>
        </aside>
    </div>
</x-app-layout>
