<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div><p class="text-[11px] font-black uppercase tracking-[0.22em] text-blue-600">Communication workspace</p><h1 class="text-3xl font-black tracking-[-0.04em] text-[#071a3b]">Support Center</h1><p class="mt-2 text-sm text-slate-500">Live chat, tickets, internal notes, and customer context in one inbox.</p></div>
            <div class="flex flex-wrap gap-2"><button type="button" data-enable-support-alerts class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-black text-emerald-700">Enable alerts</button><a href="{{ route('support.create') }}" class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-black text-white">New request</a><a href="{{ route('support.public.create') }}" target="_blank" class="rounded-xl border border-blue-200 px-4 py-2.5 text-sm font-black text-blue-700">Public support link</a>@if($manage && auth()->user()->can('support.reports'))<a href="{{ route('support.reports') }}" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-black text-white">Reports</a>@endif</div>
        </div>
    </x-slot>

    @php
        $statusTone = ['open'=>'bg-blue-50 text-blue-700','waiting_for_customer'=>'bg-amber-50 text-amber-700','in_progress'=>'bg-violet-50 text-violet-700','resolved'=>'bg-emerald-50 text-emerald-700','closed'=>'bg-slate-100 text-slate-600'];
        $priorityTone = ['low'=>'text-slate-500','medium'=>'text-blue-600','high'=>'text-amber-600','urgent'=>'text-rose-600'];
        $assigneeOnline = $selected?->assignee?->onlineStatus?->is_online && $selected->assignee->onlineStatus->last_seen_at?->greaterThan(now()->subMinutes(3));
        $supportTopbar = (auth()->user()?->can('portal.tenant') && ! auth()->user()?->can('bookings.manage')) ? '4rem' : '5rem';
    @endphp

    <div class="space-y-4" style="--support-topbar-height: {{ $supportTopbar }}">
        @if(session('status'))<div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700">{{ $errors->first() }}</div>@endif

        <div data-support-shell class="support-mobile-shell grid min-h-[calc(100dvh-190px)] overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-xl shadow-slate-200/50 lg:relative lg:z-auto lg:min-h-[680px] lg:grid-cols-[320px_minmax(420px,1fr)_330px]">
            <aside class="support-mobile-pane {{ $selected ? 'hidden lg:block' : 'block' }} border-b border-slate-200 bg-[#f8faff] lg:border-b-0 lg:border-r">
                <div class="border-b border-slate-200 p-4"><form method="GET" class="space-y-2"><input name="search" value="{{ request('search') }}" class="erp-focus h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm" placeholder="Search conversations..."><div class="grid grid-cols-2 gap-2"><select name="status" class="erp-focus h-9 rounded-xl border border-slate-200 bg-white px-2 text-xs"><option value="">All status</option>@foreach(\App\Models\SupportTicket::STATUSES as $status)<option value="{{ $status }}" @selected(request('status')===$status)>{{ str($status)->replace('_',' ')->headline() }}</option>@endforeach</select><button class="rounded-xl bg-slate-900 text-xs font-black text-white">Filter</button></div></form></div>
                <div class="h-[calc(100%-93px)] overflow-y-auto p-2 lg:h-auto lg:max-h-[620px]">
                    @forelse($tickets as $ticket)
                        @php($last = $ticket->messages->first())
                        @php($requesterOnline = $ticket->requester?->onlineStatus?->is_online && $ticket->requester->onlineStatus->last_seen_at?->greaterThan(now()->subMinutes(3)))
                        <a href="{{ route('support.index', ['ticket'=>$ticket->id]) }}" class="mb-2 block rounded-2xl border p-3 transition {{ $selected?->id === $ticket->id ? 'border-blue-200 bg-white shadow-sm' : 'border-transparent hover:border-slate-200 hover:bg-white' }}">
                            <div class="flex items-start gap-3"><span class="relative grid h-10 w-10 shrink-0 place-items-center rounded-full bg-blue-100 text-xs font-black text-blue-700">{{ str($ticket->requester_name)->substr(0,2)->upper() }}<span class="absolute bottom-0 right-0 h-2.5 w-2.5 rounded-full border-2 border-white {{ $requesterOnline ? 'bg-emerald-400' : 'bg-slate-300' }}"></span></span><div class="min-w-0 flex-1"><div class="flex justify-between gap-2"><p class="truncate text-sm font-black text-[#071a3b]">{{ $ticket->requester_name }}</p><span class="text-[10px] text-slate-400">{{ $ticket->updated_at->diffForHumans(null,true) }}</span></div><div class="mt-1 flex items-center gap-2"><span class="rounded-full bg-slate-100 px-2 py-0.5 text-[9px] font-black uppercase text-slate-500">{{ $ticket->requester_role ?: 'Guest' }}</span><span class="text-[10px] font-black {{ $priorityTone[$ticket->priority] ?? '' }}">{{ str($ticket->priority)->upper() }}</span></div><p class="mt-2 truncate text-xs font-bold text-slate-600">{{ $ticket->subject }}</p><p class="mt-1 truncate text-xs text-slate-400">{{ $last?->body ?: $ticket->description }}</p></div></div>
                        </a>
                    @empty<p class="px-4 py-12 text-center text-sm text-slate-500">No conversations found.</p>@endforelse
                </div>
            </aside>

            <main class="support-mobile-pane {{ $selected ? 'flex' : 'hidden lg:flex' }} min-h-[560px] flex-col bg-[#eef3f9] lg:h-auto lg:min-h-[680px]">
                @if($selected)
                    <header class="flex items-center justify-between gap-3 border-b border-slate-200 bg-white p-3 md:p-4"><div class="flex min-w-0 items-center gap-3"><a href="{{ route('support.index') }}" class="grid h-10 w-10 shrink-0 place-items-center rounded-xl border border-slate-200 text-lg font-black text-[#071a3b] lg:hidden">&lsaquo;</a><span class="relative grid h-11 w-11 shrink-0 place-items-center rounded-full bg-blue-100 text-xs font-black text-blue-700">{{ str($selected->requester_name)->substr(0,2)->upper() }}<span class="absolute bottom-0 right-0 h-3 w-3 rounded-full border-2 border-white {{ $assigneeOnline ? 'bg-emerald-400' : 'bg-slate-300' }}"></span></span><div class="min-w-0"><h2 class="truncate font-black text-[#071a3b]">{{ $selected->requester_name }}</h2><p class="truncate text-xs text-slate-500">{{ $selected->ticket_no }} / {{ str($selected->mode)->headline() }} / {{ $selected->category?->name ?: 'General' }}</p></div></div><span class="shrink-0 rounded-full px-3 py-1 text-[10px] font-black md:text-xs {{ $statusTone[$selected->status] ?? 'bg-slate-100' }}">{{ str($selected->status)->replace('_',' ')->headline() }}</span></header>

                    <div class="min-h-0 flex-1 space-y-3 overflow-y-auto p-3 pb-4 md:p-6" data-support-messages data-message-count="{{ $selected->messages->count() }}">
                        @foreach($selected->messages as $message)
                            @php($mine = $message->sender_type === 'staff')
                            <div class="flex {{ $mine ? 'justify-end' : 'justify-start' }}">
                                <div class="max-w-[86%] rounded-[1.4rem] px-4 py-3 shadow-sm {{ $message->is_internal_note ? 'border border-amber-200 bg-amber-50' : ($mine ? 'bg-blue-600 text-white' : ($message->sender_type === 'bot' ? 'border border-violet-100 bg-violet-50 text-slate-700' : 'bg-white text-slate-700')) }}">
                                    <div class="flex items-center gap-2 text-[10px] font-black uppercase tracking-[0.12em] {{ $mine && !$message->is_internal_note ? 'text-blue-100' : 'text-slate-400' }}"><span>{{ $message->is_internal_note ? 'Private note' : ($message->sender_name ?: str($message->sender_type)->headline()) }}</span>@if($message->is_auto_reply)<span>Help Bot</span>@endif</div>
                                    <p class="mt-1 whitespace-pre-line text-sm leading-6">{{ $message->body }}</p>
                                    @foreach($message->attachments as $attachment)<a href="{{ route('support.attachments', $attachment) }}" class="mt-2 block rounded-xl bg-white/80 px-3 py-2 text-xs font-bold text-blue-700">Attachment: {{ $attachment->original_name }}</a>@endforeach
                                    <p class="mt-1 text-right text-[9px] opacity-60">{{ $message->created_at->format('M d, H:i') }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="sticky bottom-0 border-t border-slate-200 bg-white p-3 md:p-4">
                        <div class="mb-3 flex gap-2 overflow-x-auto pb-1">@foreach($quickReplies->take(8) as $reply)<button type="button" data-quick-reply="{{ $reply->body }}" class="shrink-0 rounded-full border border-blue-100 bg-blue-50 px-3 py-1.5 text-[11px] font-black text-blue-700">{{ $reply->title }}</button>@endforeach</div>
                        <form method="POST" action="{{ route('support.reply', $selected) }}" enctype="multipart/form-data" class="flex items-end gap-2">@csrf<label class="grid h-11 w-11 shrink-0 cursor-pointer place-items-center rounded-full border border-slate-200 text-lg text-slate-500">+<input type="file" name="attachment" class="sr-only"></label><div class="min-w-0 flex-1"><textarea name="body" rows="1" data-message-input class="erp-focus max-h-32 w-full resize-none rounded-2xl border border-slate-200 px-4 py-3 text-sm" placeholder="Type a message..." required></textarea>@if($manage)<label class="mt-1 inline-flex items-center gap-2 text-[11px] font-bold text-amber-700"><input type="checkbox" name="is_internal_note" value="1" class="rounded border-slate-300"> Private internal note</label>@endif</div><button class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-blue-600 text-sm font-black text-white md:w-auto md:px-5">Send</button></form>
                    </div>
                @else<div class="grid flex-1 place-items-center p-8 text-center"><div><div class="mx-auto grid h-16 w-16 place-items-center rounded-3xl bg-blue-100 text-2xl text-blue-700">?</div><h2 class="mt-4 text-xl font-black text-[#071a3b]">Select a conversation</h2><p class="mt-2 text-sm text-slate-500">Choose a chat from the inbox or start a new support request.</p></div></div>@endif
            </main>

            <aside class="hidden border-t border-slate-200 bg-white p-4 lg:block lg:border-l lg:border-t-0">
                @if($selected)
                    <div class="space-y-4">
                        <div><p class="text-xs font-black uppercase tracking-[0.16em] text-blue-600">Customer details</p><h3 class="mt-2 text-lg font-black text-[#071a3b]">{{ $selected->requester_name }}</h3><p class="mt-1 text-xs text-slate-500">{{ $selected->requester_email ?: 'No email' }}<br>{{ $selected->requester_mobile ?: 'No mobile' }}</p><span class="mt-3 inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-black text-blue-700">{{ $selected->requester_role ?: 'Guest' }}</span></div>
                        @if($manage)
                            <form method="POST" action="{{ route('support.update', $selected) }}" class="space-y-3 border-t border-slate-100 pt-4">@csrf @method('PATCH')
                                <input name="subject" value="{{ $selected->subject }}" class="erp-focus h-10 w-full rounded-xl border border-slate-200 px-3 text-xs font-bold" placeholder="Ticket subject">
                                <select name="assigned_to" class="erp-focus h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-xs"><option value="">Unassigned</option>@foreach($staff as $person)<option value="{{ $person->id }}" @selected($selected->assigned_to===$person->id)>{{ $person->name }}</option>@endforeach</select>
                                <div class="grid grid-cols-2 gap-2"><select name="priority" class="erp-focus h-10 rounded-xl border border-slate-200 bg-white px-2 text-xs">@foreach(\App\Models\SupportTicket::PRIORITIES as $priority)<option value="{{ $priority }}" @selected($selected->priority===$priority)>{{ str($priority)->headline() }}</option>@endforeach</select><select name="status" class="erp-focus h-10 rounded-xl border border-slate-200 bg-white px-2 text-xs">@foreach(\App\Models\SupportTicket::STATUSES as $status)<option value="{{ $status }}" @selected($selected->status===$status)>{{ str($status)->replace('_',' ')->headline() }}</option>@endforeach</select></div>
                                <select name="support_category_id" class="erp-focus h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-xs"><option value="">Category</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected($selected->support_category_id===$category->id)>{{ $category->name }}</option>@endforeach</select>
                                <select name="booking_id" class="erp-focus h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-xs"><option value="">Link booking</option>@foreach($bookings as $booking)<option value="{{ $booking->id }}" @selected($selected->booking_id===$booking->id)>{{ $booking->booking_no }}</option>@endforeach</select>
                                <select name="unit_id" class="erp-focus h-10 w-full rounded-xl border border-slate-200 bg-white px-3 text-xs"><option value="">Link property</option>@foreach($units as $unit)<option value="{{ $unit->id }}" @selected($selected->unit_id===$unit->id)>{{ $unit->building?->name }} / {{ $unit->unit_no }}</option>@endforeach</select>
                                <details class="rounded-xl border border-slate-200 p-3"><summary class="cursor-pointer text-xs font-black text-slate-600">More linked records</summary><div class="mt-3 space-y-2"><select name="tenant_id" class="erp-focus h-9 w-full rounded-xl border border-slate-200 bg-white px-2 text-xs"><option value="">Link tenant</option>@foreach($tenants as $tenant)<option value="{{ $tenant->id }}" @selected($selected->tenant_id===$tenant->id)>{{ $tenant->full_name }}</option>@endforeach</select><select name="owner_id" class="erp-focus h-9 w-full rounded-xl border border-slate-200 bg-white px-2 text-xs"><option value="">Link owner</option>@foreach($owners as $owner)<option value="{{ $owner->id }}" @selected($selected->owner_id===$owner->id)>{{ $owner->full_name }}</option>@endforeach</select><select name="agent_id" class="erp-focus h-9 w-full rounded-xl border border-slate-200 bg-white px-2 text-xs"><option value="">Link agent</option>@foreach($agents as $agent)<option value="{{ $agent->id }}" @selected($selected->agent_id===$agent->id)>{{ $agent->full_name }}</option>@endforeach</select><select name="operations_team_member_id" class="erp-focus h-9 w-full rounded-xl border border-slate-200 bg-white px-2 text-xs"><option value="">Link maintainer</option>@foreach($maintainers as $maintainer)<option value="{{ $maintainer->id }}" @selected($selected->operations_team_member_id===$maintainer->id)>{{ $maintainer->full_name }}</option>@endforeach</select><select name="payment_id" class="erp-focus h-9 w-full rounded-xl border border-slate-200 bg-white px-2 text-xs"><option value="">Link payment</option>@foreach($payments as $payment)<option value="{{ $payment->id }}" @selected($selected->payment_id===$payment->id)>{{ $payment->payment_no }} / AED {{ number_format((float)$payment->amount,2) }}</option>@endforeach</select></div></details>
                                <button class="w-full rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-black text-white">Save assignment & links</button>
                            </form>
                            @if($selected->mode==='chat')<form method="POST" action="{{ route('support.convert', $selected) }}">@csrf<button class="w-full rounded-xl border border-blue-200 px-4 py-2.5 text-xs font-black text-blue-700">Convert chat to ticket</button></form>@endif
                            <form method="POST" action="{{ route('support.destroy', $selected) }}" onsubmit="return confirm('Delete this support conversation?')">@csrf @method('DELETE')<button class="w-full rounded-xl border border-rose-200 px-4 py-2.5 text-xs font-black text-rose-600">Delete conversation</button></form>
                        @endif
                        <div class="border-t border-slate-100 pt-4"><p class="text-xs font-black uppercase tracking-[0.14em] text-slate-400">Linked context</p><div class="mt-3 space-y-2 text-xs text-slate-600">@if($selected->booking)<div class="rounded-xl bg-slate-50 p-3"><b>Booking:</b> {{ $selected->booking->booking_no }}<br>{{ $selected->booking->unit?->building?->name }} / {{ $selected->booking->unit?->unit_no }}</div>@endif @if($selected->unit)<div class="rounded-xl bg-slate-50 p-3"><b>Property:</b> {{ $selected->unit->building?->name }} / {{ $selected->unit->unit_no }}</div>@endif @if($selected->payment)<div class="rounded-xl bg-slate-50 p-3"><b>Payment:</b> {{ $selected->payment->payment_no }} / AED {{ number_format((float)$selected->payment->amount,2) }}</div>@endif @if(!$selected->booking && !$selected->unit && !$selected->payment)<p>No booking, property, or payment linked.</p>@endif</div></div>
                        <div class="rounded-2xl bg-slate-50 p-4 text-xs text-slate-500"><p><b>Created:</b> {{ $selected->created_at->format('M d, Y H:i') }}</p><p class="mt-2"><b>First response:</b> {{ $selected->first_response_at ? $selected->created_at->diffInMinutes($selected->first_response_at).' min' : 'Waiting' }}</p></div>
                    </div>
                @else<p class="text-center text-sm text-slate-500">Customer and linked record details appear here.</p>@endif
            </aside>
        </div>
        <div data-support-toast class="fixed left-3 right-3 top-24 z-[80] hidden rounded-2xl border border-blue-100 bg-white p-4 shadow-2xl shadow-slate-950/20 sm:left-auto sm:w-[390px]">
            <p data-support-toast-title class="text-sm font-black text-[#071a3b]">Support alert</p>
            <p data-support-toast-body class="mt-1 text-xs leading-5 text-slate-500">New support update received.</p>
        </div>
    </div>

    <script>
        if (window.matchMedia('(max-width: 1023px)').matches) document.body.classList.add('support-mobile-active');
        window.addEventListener('beforeunload', () => document.body.classList.remove('support-mobile-active'));
        const supportMessageBox = document.querySelector('[data-support-messages]');
        if (supportMessageBox) supportMessageBox.scrollTop = supportMessageBox.scrollHeight;
        document.querySelectorAll('[data-quick-reply]').forEach(button => button.addEventListener('click', () => { const input = document.querySelector('[data-message-input]'); if (input) { input.value = button.dataset.quickReply; input.focus(); input.dispatchEvent(new Event('input')); } }));
        document.querySelectorAll('[data-message-input]').forEach(input => input.addEventListener('input', () => { input.style.height = 'auto'; input.style.height = Math.min(input.scrollHeight, 128) + 'px'; }));
        const vapidPublicKey = @js(config('services.webpush.public_key'));
        const csrfToken = '{{ csrf_token() }}';
        const showSupportToast = (title, body) => {
            const toast = document.querySelector('[data-support-toast]');
            if (!toast) return;
            toast.querySelector('[data-support-toast-title]').textContent = title;
            toast.querySelector('[data-support-toast-body]').textContent = body;
            toast.classList.remove('hidden');
            clearTimeout(window.supportToastTimer);
            window.supportToastTimer = setTimeout(() => toast.classList.add('hidden'), 4500);
        };
        const notifyUser = (title, body) => {
            showSupportToast(title, body);
            if (!('Notification' in window) || Notification.permission !== 'granted') return;
            try { new Notification(title, { body, icon: '/icons/erp-icon.svg', badge: '/icons/erp-icon.svg' }); } catch (error) {}
        };
        const urlBase64ToUint8Array = (base64String) => {
            const padding = '='.repeat((4 - base64String.length % 4) % 4);
            const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
            const rawData = window.atob(base64);
            return Uint8Array.from([...rawData].map((char) => char.charCodeAt(0)));
        };
        document.querySelector('[data-enable-support-alerts]')?.addEventListener('click', async (event) => {
            if (!('Notification' in window)) { event.currentTarget.textContent = 'Alerts not supported'; return; }
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') { event.currentTarget.textContent = 'Alerts blocked'; return; }
            event.currentTarget.textContent = 'Alerts enabled';
            showSupportToast('Support alerts enabled', 'You will see support popups on this screen.');
            if ('serviceWorker' in navigator) {
                try {
                    const registration = await navigator.serviceWorker.ready;
                    await registration.showNotification('Support alerts enabled', { body: 'Pattern RMS support notifications are ready.', icon: '/icons/erp-icon.svg', badge: '/icons/erp-icon.svg', data: { url: '{{ route('support.index') }}' } });
                } catch (error) {}
            }
            if ('serviceWorker' in navigator && 'PushManager' in window && vapidPublicKey) {
                const registration = await navigator.serviceWorker.ready;
                const subscription = await registration.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: urlBase64ToUint8Array(vapidPublicKey) });
                await fetch('{{ route('push-subscriptions.store') }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }, body: JSON.stringify(subscription.toJSON()) });
            }
        });
        @auth
        fetch('{{ route('support.presence.ping') }}', { method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'} });
        setInterval(() => fetch('{{ route('support.presence.ping') }}', { method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'} }), 60000);
        @endauth
        @if($selected)
        setInterval(async () => { const box=document.querySelector('[data-support-messages]'); const input=document.querySelector('[data-message-input]'); if(!box || (input && input.value.trim())) return; const response=await fetch('{{ route('support.messages',$selected) }}',{headers:{'Accept':'application/json'}}); if(response.ok){const messages=await response.json(); const oldCount=Number(box.dataset.messageCount||0); if(messages.length>oldCount){const latest=messages[messages.length-1]; notifyUser('New support message', latest.body || '{{ $selected->ticket_no }}'); setTimeout(() => window.location.reload(), 1200);}}}, 6000);
        @endif
    </script>
</x-app-layout>
